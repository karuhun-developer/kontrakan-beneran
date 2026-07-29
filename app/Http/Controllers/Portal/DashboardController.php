<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Penyewa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $penyewa = $request->user()->penyewa()->with('branch')->first();

        return Inertia::render('portal/dashboard', [
            'penyewa' => $penyewa ? [
                'id' => $penyewa->id,
                'name' => $penyewa->name,
                'branch' => $penyewa->branch?->name,
            ] : null,
            'summary' => $penyewa ? $this->buildSummary($penyewa) : null,
        ]);
    }

    /**
     * Headline figures for the penyewa's dashboard cards.
     *
     * @return array{room: ?string, outstanding_count: int, outstanding_value: int, deposit_balance: int, next_due: ?array<string, mixed>, recent_invoices: list<array<string, mixed>>}
     */
    private function buildSummary(Penyewa $penyewa): array
    {
        $activeBooking = $penyewa->bookings()
            ->active()
            ->with('room:id,number,name')
            ->latest('start_date')
            ->first();

        $outstanding = $penyewa->invoices()->outstanding()->with('payments')->get();

        $nextDue = $outstanding
            ->sortBy(fn (Invoice $invoice) => $invoice->due_date)
            ->first();

        return [
            'room' => $activeBooking?->room
                ? trim("{$activeBooking->room->number} {$activeBooking->room->name}")
                : null,
            'outstanding_count' => $outstanding->count(),
            'outstanding_value' => (int) $outstanding->sum(fn (Invoice $invoice) => $invoice->outstandingAmount()),
            'deposit_balance' => (int) $penyewa->deposits()->active()->sum('balance'),
            'next_due' => $nextDue ? [
                'id' => $nextDue->id,
                'number' => $nextDue->number,
                'due_date' => $nextDue->due_date->toDateString(),
                'outstanding' => $nextDue->outstandingAmount(),
            ] : null,
            'recent_invoices' => $this->recentInvoices($penyewa),
        ];
    }

    /**
     * The penyewa's five most recent invoices for an at-a-glance history.
     *
     * @return list<array<string, mixed>>
     */
    private function recentInvoices(Penyewa $penyewa): array
    {
        return $penyewa->invoices()
            ->latest('period_start')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'total' => $invoice->total,
                'due_date' => $invoice->due_date->toDateString(),
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'status_color' => $invoice->status->color(),
            ])
            ->all();
    }
}
