<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Cashflow\RecordManualCashflow;
use App\Enums\CashflowType;
use App\Enums\CommonStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CashflowRequest;
use App\Models\Cashflow;
use App\Models\CashflowCategory;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class CashflowController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $typeFilter = $this->resolveType($request->query('type'));

        $query = Cashflow::forBranch($branch->id)
            ->with('category:id,name')
            ->latest('occurred_on')
            ->latest('id');

        if ($typeFilter !== null) {
            $query->ofType($typeFilter);
        }

        $cashflows = $query->paginate(15)
            ->withQueryString()
            ->through(fn (Cashflow $cashflow): array => [
                'id' => $cashflow->id,
                'type' => $cashflow->type->value,
                'type_label' => $cashflow->type->label(),
                'type_color' => $cashflow->type->color(),
                'amount' => $cashflow->amount,
                'description' => $cashflow->description,
                'category' => $cashflow->category?->name,
                'occurred_on' => $cashflow->occurred_on->toDateString(),
                'is_system' => $cashflow->source_type !== null,
            ]);

        return Inertia::render('billing/cashflows/index', [
            'cashflows' => $cashflows,
            'stats' => $this->buildStats($branch->id),
            'filters' => ['type' => $typeFilter?->value],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function create(): Modal
    {
        $branch = $this->currentBranch();

        return Inertia::modal('billing/cashflows/form', [
            'categories' => $this->categoryOptions($branch->id),
            'typeOptions' => $this->typeOptions(),
        ])->baseRoute('cashflows.index');
    }

    public function store(CashflowRequest $request, RecordManualCashflow $action): RedirectResponse
    {
        $branch = $this->currentBranch();
        $validated = $request->validated();

        $action->handle(
            branch: $branch,
            type: CashflowType::from($validated['type']),
            amount: (int) $validated['amount'],
            description: $validated['description'],
            occurredOn: $validated['occurred_on'],
            categoryId: isset($validated['cashflow_category_id']) ? (int) $validated['cashflow_category_id'] : null,
            notes: $validated['notes'] ?? null,
            actor: $request->user(),
        );

        return to_route('cashflows.index')->with('success', 'Transaksi kas berhasil dicatat.');
    }

    public function edit(Cashflow $cashflow): Modal
    {
        $branch = $this->currentBranch();
        $cashflow = $this->findManual($cashflow->getKey());

        return Inertia::modal('billing/cashflows/form', [
            'cashflow' => [
                'id' => $cashflow->id,
                'type' => $cashflow->type->value,
                'amount' => $cashflow->amount,
                'description' => $cashflow->description,
                'occurred_on' => $cashflow->occurred_on->toDateString(),
                'cashflow_category_id' => $cashflow->cashflow_category_id,
                'notes' => $cashflow->notes,
            ],
            'categories' => $this->categoryOptions($branch->id),
            'typeOptions' => $this->typeOptions(),
        ])->baseRoute('cashflows.index');
    }

    public function update(CashflowRequest $request, Cashflow $cashflow): RedirectResponse
    {
        $cashflow = $this->findManual($cashflow->getKey());
        $validated = $request->validated();

        $cashflow->update([
            'type' => $validated['type'],
            'amount' => (int) $validated['amount'],
            'description' => $validated['description'],
            'occurred_on' => $validated['occurred_on'],
            'cashflow_category_id' => isset($validated['cashflow_category_id']) ? (int) $validated['cashflow_category_id'] : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('cashflows.index')->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(Cashflow $cashflow): RedirectResponse
    {
        $cashflow = $this->findManual($cashflow->getKey());
        $cashflow->delete();

        return to_route('cashflows.index')->with('success', 'Transaksi kas berhasil dihapus.');
    }

    /**
     * Resolve a manual (non-system) cashflow within the active branch or abort.
     * Auto-recorded rows (payment income, etc.) carry a polymorphic source and
     * must stay read-only in the cash book.
     */
    private function findManual(int $cashflowId): Cashflow
    {
        $cashflow = Cashflow::forBranch($this->currentBranch()->id)->findOrFail($cashflowId);

        abort_if($cashflow->source_type !== null, 403, 'Transaksi otomatis tidak dapat diubah.');

        return $cashflow;
    }

    private function resolveType(?string $type): ?CashflowType
    {
        return $type !== null ? CashflowType::tryFrom($type) : null;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (CashflowType $case): array => ['value' => $case->value, 'label' => $case->label()],
            CashflowType::cases(),
        );
    }

    /**
     * @return array<int, array{value: int, label: string, type: string}>
     */
    private function categoryOptions(int $branchId): array
    {
        return CashflowCategory::forBranch($branchId)
            ->where('status', CommonStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (CashflowCategory $category): array => [
                'value' => $category->id,
                'label' => $category->name,
                'type' => $category->type->value,
            ])
            ->all();
    }

    /**
     * @return array{balance: int, income_this_month: int, expense_this_month: int}
     */
    private function buildStats(int $branchId): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();

        $incomeTotal = (int) Cashflow::forBranch($branchId)->ofType(CashflowType::Income)->sum('amount');
        $expenseTotal = (int) Cashflow::forBranch($branchId)->ofType(CashflowType::Expense)->sum('amount');

        $incomeMonth = (int) Cashflow::forBranch($branchId)
            ->ofType(CashflowType::Income)
            ->whereDate('occurred_on', '>=', $monthStart)
            ->sum('amount');

        $expenseMonth = (int) Cashflow::forBranch($branchId)
            ->ofType(CashflowType::Expense)
            ->whereDate('occurred_on', '>=', $monthStart)
            ->sum('amount');

        return [
            'balance' => $incomeTotal - $expenseTotal,
            'income_this_month' => $incomeMonth,
            'expense_this_month' => $expenseMonth,
        ];
    }
}
