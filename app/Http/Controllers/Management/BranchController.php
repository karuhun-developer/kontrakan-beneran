<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class BranchController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $tenantId = $this->currentBranch()->tenant_id;

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->withCount(['rooms', 'users'])
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'bank_name' => $branch->bank_name,
                'status' => $branch->status->value,
                'status_label' => $branch->status->label(),
                'rooms_count' => $branch->rooms_count,
                'users_count' => $branch->users_count,
            ]);

        return Inertia::render('management/branches/index', [
            'branches' => $branches,
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('management/branches/form')
            ->baseRoute('management.branches.index');
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $tenantId = $this->currentBranch()->tenant_id;

        Branch::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
            'slug' => $this->uniqueSlug($request->validated('name')),
        ]);

        return to_route('management.branches.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Branch $branch): Modal
    {
        $this->authorizeTenant($branch);

        return Inertia::modal('management/branches/form', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'bank_name' => $branch->bank_name,
                'bank_account_number' => $branch->bank_account_number,
                'bank_account_holder' => $branch->bank_account_holder,
                'status' => $branch->status->value,
            ],
        ])->baseRoute('management.branches.index');
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorizeTenant($branch);

        $branch->update($request->validated());

        return to_route('management.branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorizeTenant($branch);

        $branch->delete();

        return to_route('management.branches.index')->with('success', 'Cabang berhasil dihapus.');
    }

    /**
     * Ensure the branch belongs to the active tenant, else 404.
     */
    private function authorizeTenant(Branch $branch): void
    {
        abort_unless($branch->tenant_id === $this->currentBranch()->tenant_id, 404);
    }

    /**
     * Build a slug that is unique across branches.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cabang';
        $slug = $base;
        $suffix = 2;

        while (Branch::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
