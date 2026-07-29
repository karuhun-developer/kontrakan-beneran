<?php

namespace Database\Seeders;

use App\Enums\CommonStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed a demo tenant with two branches and one user per staff role plus a
     * penyewa wired to the portal. All accounts use the password "password".
     */
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@kostpro.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles(UserRole::SuperAdmin->value);

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'kost-demo'],
            [
                'name' => 'Kost Demo',
                'description' => 'Bisnis kost demo untuk pengembangan.',
                'status' => CommonStatus::Active,
            ],
        );

        $branchPusat = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'pusat'],
            [
                'name' => 'Cabang Pusat',
                'address' => 'Jl. Merdeka No. 1, Jakarta',
                'phone' => '021-1111111',
                'status' => CommonStatus::Active,
            ],
        );

        $branchSelatan = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'selatan'],
            [
                'name' => 'Cabang Selatan',
                'address' => 'Jl. Kebayoran No. 2, Jakarta',
                'phone' => '021-2222222',
                'status' => CommonStatus::Active,
            ],
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@kostpro.test'],
            [
                'name' => 'Owner Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $owner->syncRoles(UserRole::Owner->value);

        $admin = User::firstOrCreate(
            ['email' => 'admin@kostpro.test'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $admin->syncRoles(UserRole::Admin->value);
        $admin->branches()->sync([$branchPusat->id, $branchSelatan->id]);

        $staff = User::firstOrCreate(
            ['email' => 'staff@kostpro.test'],
            [
                'name' => 'Staff Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $staff->syncRoles(UserRole::Staff->value);
        $staff->branches()->sync([$branchPusat->id]);

        $penyewaUser = User::firstOrCreate(
            ['email' => 'penyewa@kostpro.test'],
            [
                'name' => 'Penyewa Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $penyewaUser->syncRoles(UserRole::Penyewa->value);

        Penyewa::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'penyewa@kostpro.test'],
            [
                'branch_id' => $branchPusat->id,
                'user_id' => $penyewaUser->id,
                'name' => 'Penyewa Demo',
                'phone' => '0812-3456-7890',
                'id_card_number' => '3171000000000001',
                'gender' => 'male',
                'address' => 'Jl. Kos No. 3, Jakarta',
                'status' => CommonStatus::Active,
            ],
        );
    }
}
