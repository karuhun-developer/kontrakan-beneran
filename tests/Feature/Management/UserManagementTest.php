<?php

namespace Tests\Feature\Management;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
    }

    public function test_owner_can_view_the_users_page()
    {
        $this->actingAsRole(UserRole::Owner);

        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->get(route('management.users.index'))
            ->assertInertia(fn ($page) => $page
                ->component('management/users/index')
                ->has('users.data', 1) // owner itself is excluded (only admin/staff listed)
                ->where('users.data.0.id', $staff->id)
            );
    }

    public function test_owner_can_create_a_staff_user_assigned_to_branches()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->post(route('management.users.store'), [
            'name' => 'Front Desk',
            'email' => 'frontdesk@kost.test',
            'password' => 'password',
            'role' => UserRole::Staff->value,
            'branches' => [$this->branch->id],
        ])->assertRedirect();

        $user = User::where('email', 'frontdesk@kost.test')->firstOrFail();
        $this->assertSame($this->tenant->id, $user->tenant_id);
        $this->assertTrue($user->hasRole(UserRole::Staff->value));
        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_owner_can_update_a_user_role_and_branches()
    {
        $this->actingAsRole(UserRole::Owner);

        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);
        $staff->branches()->sync([$this->branch->id]);

        $this->put(route('management.users.update', $staff), [
            'name' => 'Naik Jabatan',
            'email' => $staff->email,
            'role' => UserRole::Admin->value,
            'branches' => [],
        ])->assertRedirect();

        $staff->refresh();
        $this->assertSame('Naik Jabatan', $staff->name);
        $this->assertTrue($staff->hasRole(UserRole::Admin->value));
        $this->assertFalse($staff->hasRole(UserRole::Staff->value));
        $this->assertDatabaseMissing('branch_user', ['user_id' => $staff->id]);
    }

    public function test_creating_a_user_requires_a_password()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->post(route('management.users.store'), [
            'name' => 'Tanpa Password',
            'email' => 'nopass@kost.test',
            'role' => UserRole::Staff->value,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'nopass@kost.test']);
    }

    public function test_owner_cannot_grant_privileged_roles()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->post(route('management.users.store'), [
            'name' => 'Mau Jadi Owner',
            'email' => 'wannabe@kost.test',
            'password' => 'password',
            'role' => UserRole::Owner->value,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'wannabe@kost.test']);
    }

    public function test_admin_cannot_access_user_management()
    {
        $this->actingAsRole(UserRole::Admin);

        $this->get(route('management.users.index'))->assertForbidden();
    }

    public function test_owner_cannot_edit_a_user_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $stranger = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $stranger->assignRole(UserRole::Staff->value);

        $this->actingAsRole(UserRole::Owner);

        $this->get(route('management.users.edit', $stranger))->assertNotFound();
    }
}
