<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    /**
     * Roles that can be assigned through this screen. Owners are provisioned via
     * the tenant flow, never here, and their role can never be changed.
     *
     * @var array<int, UserRole>
     */
    private const ASSIGNABLE_ROLES = [UserRole::Admin, UserRole::Staff];

    public function index(): Response
    {
        $tenantId = $this->resolveTenantId();

        $users = User::query()
            ->role($this->roleValues($this->visibleRoles()))
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->with(['roles:id,name', 'branches:id,name', 'tenant:id,name'])
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->primaryRole()?->value,
                'role_label' => $user->primaryRole()?->label(),
                'tenant' => $user->tenant?->name,
                'branches' => $user->branches->pluck('name')->values(),
                'is_owner' => $user->primaryRole() === UserRole::Owner,
            ]);

        return Inertia::render('management/users/index', [
            'users' => $users,
            'canViewTenants' => $this->actor()->isSuperAdmin(),
            'canCreate' => $tenantId !== null,
        ]);
    }

    public function create(): Modal
    {
        $tenantId = $this->requireTenantId();

        return Inertia::modal('management/users/form', [
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions($tenantId),
        ])->baseRoute('management.users.index');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $tenantId = $this->requireTenantId();

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
        $this->authorizeManage($user);

        return Inertia::modal('management/users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->primaryRole()?->value,
                'role_label' => $user->primaryRole()?->label(),
                'is_owner' => $user->primaryRole() === UserRole::Owner,
                'branches' => $user->branches()->pluck('branches.id')->values(),
            ],
            'roleOptions' => $this->roleOptions(),
            'branchOptions' => $this->branchOptions($user->tenant_id),
        ])->baseRoute('management.users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeManage($user);

        DB::transaction(function () use ($request, $user): void {
            $user->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
            ]);

            if (filled($request->validated('password'))) {
                $user->update(['password' => Hash::make($request->validated('password'))]);
            }

            // An owner's role and branch scope are fixed; only their profile changes.
            if ($user->primaryRole() !== UserRole::Owner) {
                $user->syncRoles([$request->validated('role')]);
                $user->branches()->sync($this->tenantBranchIds($request->validated('branches', []), $user->tenant_id));
            }
        });

        return to_route('management.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeManage($user);

        // Owners are removed by deleting their tenant, never from here.
        abort_if($user->primaryRole() === UserRole::Owner, HttpResponse::HTTP_FORBIDDEN);

        $user->delete();

        return to_route('management.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * The authenticated actor: an owner (manages their own staff) or a
     * super-admin (manages owners and staff across tenants).
     */
    private function actor(): User
    {
        return request()->user();
    }

    /**
     * Roles the actor is allowed to see and manage.
     *
     * @return array<int, UserRole>
     */
    private function visibleRoles(): array
    {
        return $this->actor()->isSuperAdmin()
            ? [UserRole::Owner, UserRole::Admin, UserRole::Staff]
            : self::ASSIGNABLE_ROLES;
    }

    /**
     * Ensure the target user is within the actor's tenant scope and role reach.
     */
    private function authorizeManage(User $user): void
    {
        abort_unless(in_array($user->primaryRole(), $this->visibleRoles(), true), 404);

        $tenantId = $this->resolveTenantId();

        if ($tenantId !== null) {
            abort_unless($user->tenant_id === $tenantId, 404);
        }
    }

    /**
     * The tenant being managed: the active branch's tenant, the actor's own
     * tenant, or null when a super-admin is viewing every branch at once.
     */
    private function resolveTenantId(): ?int
    {
        $current = app(BranchContext::class)->current();

        if ($current !== null) {
            return $current->tenant_id;
        }

        $user = $this->actor();

        if ($user->isSuperAdmin()) {
            return null;
        }

        abort_if($user->tenant_id === null, HttpResponse::HTTP_CONFLICT, 'Tidak ada tenant aktif.');

        return $user->tenant_id;
    }

    /**
     * Resolve a concrete tenant for write operations, which cannot target the
     * cross-branch overview.
     */
    private function requireTenantId(): int
    {
        $tenantId = $this->resolveTenantId();

        abort_if(
            $tenantId === null,
            HttpResponse::HTTP_CONFLICT,
            'Pilih cabang atau bisnis tertentu sebelum mengelola pengguna.',
        );

        return $tenantId;
    }

    /**
     * Keep only branch ids that belong to the given tenant.
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
     * @param  array<int, UserRole>  $roles
     * @return array<int, string>
     */
    private function roleValues(array $roles): array
    {
        return array_map(fn (UserRole $role): string => $role->value, $roles);
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
    private function branchOptions(int $tenantId): array
    {
        return Branch::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $branch): array => ['value' => $branch->id, 'label' => $branch->name])
            ->all();
    }
}
