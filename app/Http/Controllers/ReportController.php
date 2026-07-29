<?php

namespace App\Http\Controllers;

use App\Enums\CashflowType;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Models\Cashflow;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        [$start, $end] = $this->resolveRange($request);

        return Inertia::render('reports/index', [
            'filters' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'summary' => $this->buildSummary($branch->id, $start, $end),
            'categories' => $this->buildCategoryBreakdown($branch->id, $start, $end),
            'trend' => $this->buildTrend($branch->id, $end),
        ]);
    }

    /**
     * Stream the period's cash transactions as a CSV download.
     */
    public function exportCashflow(Request $request): StreamedResponse
    {
        $branch = $this->currentBranch();
        [$start, $end] = $this->resolveRange($request);

        $filename = "laporan-kas-{$start->toDateString()}-{$end->toDateString()}.csv";

        return response()->streamDownload(function () use ($branch, $start, $end): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Jumlah', 'Sumber']);

            Cashflow::forBranch($branch->id)
                ->with('category:id,name')
                ->whereDate('occurred_on', '>=', $start->toDateString())
                ->whereDate('occurred_on', '<=', $end->toDateString())
                ->orderBy('occurred_on')
                ->orderBy('id')
                ->chunk(200, function ($cashflows) use ($handle): void {
                    foreach ($cashflows as $cashflow) {
                        fputcsv($handle, [
                            $cashflow->occurred_on->toDateString(),
                            $cashflow->type->label(),
                            $cashflow->category?->name ?? 'Tanpa Kategori',
                            $cashflow->description,
                            $cashflow->amount,
                            $cashflow->source_type !== null ? 'Otomatis' : 'Manual',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Resolve the inclusive report range, defaulting to the current month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(Request $request): array
    {
        $start = $this->parseDate($request->query('start')) ?? CarbonImmutable::now()->startOfMonth();
        $end = $this->parseDate($request->query('end')) ?? CarbonImmutable::now()->endOfMonth();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    /**
     * Income, expense, and net totals for the range.
     *
     * @return array{income: int, expense: int, net: int}
     */
    private function buildSummary(int $branchId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $this->rangeQuery($branchId, $start, $end)
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
     * Per-category totals within the range, ordered by amount.
     *
     * @return list<array{name: string, type: string, type_label: string, type_color: string, total: int}>
     */
    private function buildCategoryBreakdown(int $branchId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->rangeQuery($branchId, $start, $end)
            ->with('category:id,name')
            ->get(['id', 'type', 'amount', 'cashflow_category_id'])
            ->groupBy(fn (Cashflow $cashflow): string => $cashflow->type->value.'|'.($cashflow->category?->name ?? 'Tanpa Kategori'))
            ->map(function ($group): array {
                /** @var Cashflow $first */
                $first = $group->first();

                return [
                    'name' => $first->category?->name ?? 'Tanpa Kategori',
                    'type' => $first->type->value,
                    'type_label' => $first->type->label(),
                    'type_color' => $first->type->color(),
                    'total' => (int) $group->sum('amount'),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Income vs expense over the trailing six months (oldest first).
     *
     * @return list<array{month: string, label: string, income: int, expense: int}>
     */
    private function buildTrend(int $branchId, CarbonImmutable $end): array
    {
        $anchor = $end->startOfMonth();
        $start = $anchor->subMonths(5);

        $rows = Cashflow::forBranch($branchId)
            ->whereDate('occurred_on', '>=', $start->toDateString())
            ->whereDate('occurred_on', '<=', $anchor->endOfMonth()->toDateString())
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
     * Base branch-scoped query bounded to the inclusive report range.
     *
     * @return Builder<Cashflow>
     */
    private function rangeQuery(int $branchId, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Cashflow::forBranch($branchId)
            ->whereDate('occurred_on', '>=', $start->toDateString())
            ->whereDate('occurred_on', '<=', $end->toDateString());
    }
}
