<?php

namespace App\Actions\Tenancy;

use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionOwnerAccount
{
    /**
     * Turn a freshly self-registered user into a tenant owner and seed the
     * minimum data needed for a functional dashboard: a business, a default
     * branch, a default room type, and one room. Owners get tenant-wide branch
     * access automatically, so no branch_user pivot rows are needed.
     */
    public function provision(User $user): Tenant
    {
        return DB::transaction(function () use ($user): Tenant {
            $tenant = Tenant::create([
                'name' => $this->tenantName($user->name),
                'slug' => $this->uniqueTenantSlug($this->tenantName($user->name)),
                'description' => null,
                'status' => CommonStatus::Active,
            ]);

            $user->forceFill(['tenant_id' => $tenant->id])->save();
            $user->assignRole(UserRole::Owner->value);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Cabang Utama',
                'slug' => $this->uniqueBranchSlug('Cabang Utama'),
                'status' => CommonStatus::Active,
            ]);

            $roomType = RoomType::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => 'Standard',
                'capacity' => 1,
                'monthly_price' => 1_000_000,
                'status' => CommonStatus::Active,
            ]);

            Room::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'room_type_id' => $roomType->id,
                'number' => '001',
                'floor' => 1,
                'status' => CommonStatus::Active,
                'room_status' => RoomStatus::Available,
            ]);

            return $tenant;
        });
    }

    /**
     * Derive a friendly business name from the owner's name.
     */
    private function tenantName(string $ownerName): string
    {
        return 'Kos '.$ownerName;
    }

    /**
     * Build a slug that is unique across tenants.
     */
    private function uniqueTenantSlug(string $name): string
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

    /**
     * Build a slug that is unique across branches.
     */
    private function uniqueBranchSlug(string $name): string
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
