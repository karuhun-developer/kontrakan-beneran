<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class UserController extends Controller
{
    use ResolvesCurrentBranch;

    /**
     * Roles an owner may assign to their staff.
     *
     * @var array<int, UserRole>
     */
    private const ASSIGNABLE_ROLES = [UserRole::Admin, UserRole::Staff];

    public function index(): Response
    {
        $tenantId = $this->currentBranch()->tenant_id;

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->role(array_map(fn (UserRole $role): string => $role->value, self::ASSIGNABLE_ROLES))
            ->with(['roles:id,name', 'branches:id,name'])
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->primaryRole()?->value,
                'role_label' => $user->primaryRole()?->label(),
                'branches' => $user->branches->pluck('name')->values(),
            ]);

        return Inertia::render('management/users/index', [
            'users' => $users,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('management/users/form', [
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions(),
        ])->baseRoute('management.users.index');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $tenantId = $this->currentBranch()->tenant_id;

        DB::transaction(function () use ($request, $tenantId): void {
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
            ]);

            $user->syncRoles([$request->validated('role')]);
            $user->branches()->sync($this->tenantBranchIds($request->validated('branches', []), $tenantId));
        });

        return to_route('management.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): Modal
    {
        $this->authorizeTenant($user);

        return Inertia::modal('management/users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->primaryRole()?->value,
                'branches' => $user->branches()->pluck('branches.id')->values(),
            ],
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions(),
        ])->baseRoute('management.users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeTenant($user);

        $tenantId = $this->currentBranch()->tenant_id;

        DB::transaction(function () use ($request, $user, $tenantId): void {
            $user->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
            ]);

            if (filled($request->validated('password'))) {
                $user->update(['password' => Hash::make($request->validated('password'))]);
            }

            $user->syncRoles([$request->validated('role')]);
            $user->branches()->sync($this->tenantBranchIds($request->validated('branches', []), $tenantId));
        });

        return to_route('management.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeTenant($user);

        $user->delete();

        return to_route('management.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Only manage branch-level staff inside the active tenant.
     */
    private function authorizeTenant(User $user): void
    {
        abort_unless($user->tenant_id === $this->currentBranch()->tenant_id, 404);
        abort_unless(in_array($user->primaryRole(), self::ASSIGNABLE_ROLES, true), 404);
    }

    /**
     * Keep only branch ids that belong to the active tenant.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function tenantBranchIds(array $ids, int $tenantId): array
    {
        return Branch::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::ASSIGNABLE_ROLES,
        );
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function branchOptions(): array
    {
        return Branch::query()
            ->where('tenant_id', $this->currentBranch()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $branch): array => ['value' => $branch->id, 'label' => $branch->name])
            ->all();
    }
}
