<?php

namespace Tests\Feature\Management;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_super_admin_can_view_the_tenants_page()
    {
        $this->actingAsSuperAdmin();
        Tenant::factory()->create(['name' => 'Kost Melati']);

        $this->get(route('management.tenants.index'))
            ->assertInertia(fn ($page) => $page
                ->component('management/tenants/index')
                ->has('tenants.data', 1)
            );
    }

    public function test_super_admin_can_create_a_tenant_with_its_owner_account()
    {
        $this->actingAsSuperAdmin();

        $this->post(route('management.tenants.store'), [
            'name' => 'Kost Mawar',
            'description' => 'Grup kost mawar',
            'status' => 'active',
            'owner_name' => 'Bu Mawar',
            'owner_email' => 'owner@mawar.test',
            'owner_password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', ['name' => 'Kost Mawar', 'slug' => 'kost-mawar']);

        $owner = User::where('email', 'owner@mawar.test')->firstOrFail();
        $this->assertTrue($owner->hasRole(UserRole::Owner->value));
        $this->assertSame(Tenant::where('name', 'Kost Mawar')->value('id'), $owner->tenant_id);
    }

    public function test_creating_a_tenant_requires_owner_credentials()
    {
        $this->actingAsSuperAdmin();

        $this->post(route('management.tenants.store'), [
            'name' => 'Kost Tanpa Owner',
            'status' => 'active',
        ])->assertSessionHasErrors(['owner_name', 'owner_email', 'owner_password']);

        $this->assertDatabaseMissing('tenants', ['name' => 'Kost Tanpa Owner']);
    }

    public function test_super_admin_can_update_a_tenant()
    {
        $this->actingAsSuperAdmin();
        $tenant = Tenant::factory()->create(['name' => 'Lama']);

        $this->put(route('management.tenants.update', $tenant), [
            'name' => 'Baru',
            'status' => 'inactive',
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Baru', 'status' => 'inactive']);
    }

    public function test_super_admin_can_delete_a_tenant()
    {
        $this->actingAsSuperAdmin();
        $tenant = Tenant::factory()->create();

        $this->delete(route('management.tenants.destroy', $tenant))->assertRedirect();

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_owner_cannot_access_tenant_management()
    {
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole(UserRole::Owner->value);
        $owner->branches()->sync([$branch->id]);
        $this->actingAs($owner);

        $this->get(route('management.tenants.index'))->assertForbidden();
    }
}
