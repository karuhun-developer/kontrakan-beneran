<?php

namespace App\Http\Controllers\Billing;

use App\Enums\CashflowType;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CashflowCategoryRequest;
use App\Models\CashflowCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class CashflowCategoryController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $categories = CashflowCategory::forBranch($branch->id)
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (CashflowCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'type_label' => $category->type->label(),
                'type_color' => $category->type->color(),
                'is_system' => $category->is_system,
                'status' => $category->status->value,
                'status_label' => $category->status->label(),
            ]);

        return Inertia::render('billing/cashflow-categories/index', [
            'categories' => $categories,
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('billing/cashflow-categories/form', [
            'typeOptions' => $this->typeOptions(),
        ])->baseRoute('cashflow-categories.index');
    }

    public function store(CashflowCategoryRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        CashflowCategory::create([
            ...$request->validated(),
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);

        return to_route('cashflow-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(CashflowCategory $cashflowCategory): Modal
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $cashflowCategory->branch_id);
        $this->guardSystem($cashflowCategory);

        return Inertia::modal('billing/cashflow-categories/form', [
            'category' => [
                'id' => $cashflowCategory->id,
                'name' => $cashflowCategory->name,
                'type' => $cashflowCategory->type->value,
                'status' => $cashflowCategory->status->value,
            ],
            'typeOptions' => $this->typeOptions(),
        ])->baseRoute('cashflow-categories.index');
    }

    public function update(CashflowCategoryRequest $request, CashflowCategory $cashflowCategory): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $cashflowCategory->branch_id);
        $this->guardSystem($cashflowCategory);

        $cashflowCategory->update($request->validated());

        return to_route('cashflow-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(CashflowCategory $cashflowCategory): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $cashflowCategory->branch_id);
        $this->guardSystem($cashflowCategory);

        $cashflowCategory->delete();

        return to_route('cashflow-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * System categories are created and maintained automatically (e.g. the rent
     * income bucket) and must not be edited or deleted by hand.
     */
    private function guardSystem(CashflowCategory $category): void
    {
        abort_if($category->is_system, 403, 'Kategori sistem tidak dapat diubah.');
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
}
