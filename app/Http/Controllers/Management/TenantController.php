<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\TenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::query()
            ->withCount(['branches', 'users'])
            ->latest()
            ->paginate(15)
            ->through(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'description' => $tenant->description,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'branches_count' => $tenant->branches_count,
                'users_count' => $tenant->users_count,
            ]);

        return Inertia::render('management/tenants/index', [
            'tenants' => $tenants,
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('management/tenants/form')
            ->baseRoute('management.tenants.index');
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $tenant = Tenant::create([
                'name' => $request->validated('name'),
                'slug' => $this->uniqueSlug($request->validated('name')),
                'description' => $request->validated('description'),
                'status' => $request->validated('status'),
            ]);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->validated('owner_name'),
                'email' => $request->validated('owner_email'),
                'password' => Hash::make($request->validated('owner_password')),
            ]);

            $owner->assignRole(UserRole::Owner->value);
        });

        return to_route('management.tenants.index')->with('success', 'Bisnis berhasil ditambahkan.');
    }

    public function edit(Tenant $tenant): Modal
    {
        return Inertia::modal('management/tenants/form', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'description' => $tenant->description,
                'status' => $tenant->status->value,
            ],
        ])->baseRoute('management.tenants.index');
    }

    public function update(TenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'status' => $request->validated('status'),
        ]);

        return to_route('management.tenants.index')->with('success', 'Bisnis berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return to_route('management.tenants.index')->with('success', 'Bisnis berhasil dihapus.');
    }

    /**
     * Build a slug that is unique across tenants.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'bisnis';
        $slug = $base;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
