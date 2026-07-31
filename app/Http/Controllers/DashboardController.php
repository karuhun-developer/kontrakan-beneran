<?php

namespace App\Http\Controllers;

use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext): Response
    {
        $branch = $branchContext->current();

        return Inertia::render('dashboard', [
            'currentBranch' => $branch
                ? ['id' => $branch->id, 'name' => $branch->name]
                : null,
            'analytics' => $branch ? $this->buildAnalytics($branch->id) : null,
        ]);
    }

    /**
     * Branch-scoped headline metrics for the dashboard.
     *
     * @return array<string, mixed>
     */
    private function buildAnalytics(int $branchId): array
    {
        $today = CarbonImmutable::now()->startOfDay();

        return [
            'occupancy' => $this->buildOccupancy($branchId),
            'active_penyewa' => Booking::forBranch($branchId)->active()
                ->distinct()
                ->count('penyewa_id'),
            'billing' => $this->buildBilling($branchId, $today),
            'cashflow' => $this->buildCashflow($branchId, $today),
            'trend' => $this->buildTrend($branchId, $today),
            'due_soon' => $this->buildDueSoon($branchId, $today),
        ];
    }

    /**
     * Room counts per status plus the occupancy rate.
     *
     * @return array{total: int, occupancy_rate: int, statuses: list<array{status: string, label: string, color: string, count: int}>}
     */
    private function buildOccupancy(int $branchId): array
    {
        $counts = Room::forBranch($branchId)
            ->selectRaw('room_status, COUNT(*) as aggregate')
            ->groupBy('room_status')
            ->pluck('aggregate', 'room_status');

        $total = (int) $counts->sum();
        $occupied = (int) ($counts[RoomStatus::Occupied->value] ?? 0);

        return [
            'total' => $total,
            'occupancy_rate' => $total > 0 ? (int) round($occupied / $total * 100) : 0,
            'statuses' => array_map(fn (RoomStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], RoomStatus::cases()),
        ];
    }

    /**
     * Outstanding totals plus revenue collected in the current month.
     *
     * @return array{outstanding_count: int, outstanding_value: int, collected_this_month: int}
     */
    private function buildBilling(int $branchId, CarbonImmutable $today): array
    {
        $outstanding = Invoice::forBranch($branchId)
            ->whereIn('status', InvoiceStatus::outstanding())
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->where('status', PaymentStatus::Completed->value)], 'amount')
            ->get(['id', 'total']);

        $collected = Payment::query()
            ->where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$today->startOfMonth(), $today->endOfMonth()])
            ->whereHas('invoice', fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        return [
            'outstanding_count' => $outstanding->count(),
            'outstanding_value' => (int) $outstanding->sum(fn (Invoice $invoice): int => max(0, $invoice->total - (int) ($invoice->paid_amount ?? 0))),
            'collected_this_month' => (int) $collected,
        ];
    }

    /**
     * Current-month cash income, expense, and net.
     *
     * @return array{income: int, expense: int, net: int}
     */
    private function buildCashflow(int $branchId, CarbonImmutable $today): array
    {
        $rows = Cashflow::forBranch($branchId)
            ->whereBetween('occurred_on', [$today->startOfMonth(), $today->endOfMonth()])
            ->selectRaw('type, SUM(amount) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $income = (int) ($rows[CashflowType::Income->value] ?? 0);
        $expense = (int) ($rows[CashflowType::Expense->value] ?? 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    /**
     * Income vs expense over the trailing six months (oldest first).
     *
     * @return list<array{month: string, label: string, income: int, expense: int}>
     */
    private function buildTrend(int $branchId, CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->subMonths(5);

        $rows = Cashflow::forBranch($branchId)
            ->whereBetween('occurred_on', [$start, $today->endOfMonth()])
            ->get(['type', 'amount', 'occurred_on']);

        $trend = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->addMonths($i);
            $trend[$month->format('Y-m')] = [
                'month' => $month->format('Y-m'),
                'label' => $month->translatedFormat('M Y'),
                'income' => 0,
                'expense' => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = $row->occurred_on->format('Y-m');

            if (! isset($trend[$key])) {
                continue;
            }

            $bucket = $row->type === CashflowType::Income ? 'income' : 'expense';
            $trend[$key][$bucket] += $row->amount;
        }

        return array_values($trend);
    }

    /**
     * Outstanding invoices due within the next seven days (soonest first).
     *
     * @return list<array<string, mixed>>
     */
    private function buildDueSoon(int $branchId, CarbonImmutable $today): array
    {
        return Invoice::forBranch($branchId)
            ->whereIn('status', InvoiceStatus::outstanding())
            ->whereBetween('due_date', [$today, $today->addDays(7)->endOfDay()])
            ->with('penyewa:id,name')
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->where('status', PaymentStatus::Completed->value)], 'amount')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'penyewa' => $invoice->penyewa?->name,
                'due_date' => $invoice->due_date->toDateString(),
                'outstanding' => max(0, $invoice->total - (int) ($invoice->paid_amount ?? 0)),
            ])
            ->all();
    }
}
