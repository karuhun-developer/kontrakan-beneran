<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Baseline permissions grouped by domain. Fase 0 keeps a lean set that
     * later phases (rooms, booking, billing, …) will extend.
     *
     * @var array<string, list<string>>
     */
    private array $permissions = [
        'branch' => ['branch.view', 'branch.manage', 'branch.switch'],
        'tenant' => ['tenant.manage', 'tenant.member.manage'],
        'penyewa' => ['penyewa.view', 'penyewa.manage'],
        'roomtype' => ['roomtype.view', 'roomtype.manage'],
        'facility' => ['facility.view', 'facility.manage'],
        'room' => ['room.view', 'room.manage'],
        'portal' => ['portal.access'],
    ];

    /**
     * Permissions granted to each role. super-admin implicitly gets all via
     * a Gate::before check, so it is omitted here.
     *
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        UserRole::Owner->value => [
            'branch.view', 'branch.manage', 'branch.switch',
            'tenant.manage', 'tenant.member.manage',
            'penyewa.view', 'penyewa.manage',
            'roomtype.view', 'roomtype.manage',
            'facility.view', 'facility.manage',
            'room.view', 'room.manage',
        ],
        UserRole::Admin->value => [
            'branch.view', 'branch.switch',
            'penyewa.view', 'penyewa.manage',
            'roomtype.view', 'roomtype.manage',
            'facility.view', 'facility.manage',
            'room.view', 'room.manage',
        ],
        UserRole::Staff->value => [
            'branch.view', 'branch.switch',
            'penyewa.view',
            'roomtype.view',
            'facility.view',
            'room.view',
        ],
        UserRole::Penyewa->value => [
            'portal.access',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $group) {
            foreach ($group as $name) {
                Permission::findOrCreate($name, 'web');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::cases() as $role) {
            $model = Role::findOrCreate($role->value, 'web');

            if (isset($this->rolePermissions[$role->value])) {
                $model->syncPermissions($this->rolePermissions[$role->value]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
