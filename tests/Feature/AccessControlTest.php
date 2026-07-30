<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function staffUser(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole(UserRole::Staff->value);

        return $user;
    }

    private function penyewaUser(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        Penyewa::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => Branch::factory()->create(['tenant_id' => $tenant->id]),
            'user_id' => $user->id,
        ]);

        return $user;
    }

    public function test_staff_is_redirected_to_dashboard_after_login()
    {
        $user = $this->staffUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_penyewa_is_redirected_to_portal_after_login()
    {
        $user = $this->penyewaUser();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_penyewa_cannot_access_admin_dashboard()
    {
        $this->actingAs($this->penyewaUser());

        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_staff_cannot_access_penyewa_portal()
    {
        $this->actingAs($this->staffUser());

        $this->get(route('portal.dashboard'))->assertForbidden();
    }

    public function test_staff_can_switch_to_an_assigned_branch()
    {
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

        $user = $this->staffUser($tenant);
        $user->branches()->sync([$branch->id]);

        $this->actingAs($user)
            ->put(route('branch.switch'), ['branch_id' => $branch->id])
            ->assertSessionHasNoErrors();

        $this->assertEquals($branch->id, session('current_branch_id'));
    }

    public function test_staff_cannot_switch_to_an_unassigned_branch()
    {
        $tenant = Tenant::factory()->create();
        $assigned = Branch::factory()->create(['tenant_id' => $tenant->id]);
        $other = Branch::factory()->create(['tenant_id' => $tenant->id]);

        $user = $this->staffUser($tenant);
        $user->branches()->sync([$assigned->id]);

        $this->actingAs($user)
            ->put(route('branch.switch'), ['branch_id' => $other->id])
            ->assertSessionHasErrors('branch_id');

        $this->assertNotEquals($other->id, session('current_branch_id'));
    }

    public function test_switching_branches_requires_the_branch_switch_permission()
    {
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

        Role::findByName(UserRole::Staff->value, 'web')->revokePermissionTo('branch.switch');

        $user = $this->staffUser($tenant);
        $user->branches()->sync([$branch->id]);

        $this->actingAs($user)
            ->put(route('branch.switch'), ['branch_id' => $branch->id])
            ->assertForbidden();
    }

    public function test_owner_can_switch_to_any_branch_within_their_tenant()
    {
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole(UserRole::Owner->value);

        $this->actingAs($owner)
            ->put(route('branch.switch'), ['branch_id' => $branch->id])
            ->assertSessionHasNoErrors();

        $this->assertEquals($branch->id, session('current_branch_id'));
    }

    public function test_super_admin_can_switch_to_all_branches()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superadmin)
            ->withSession(['current_branch_id' => 123])
            ->put(route('branch.switch'), ['branch_id' => 0])
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) session('current_branch_all'));
        $this->assertNull(session('current_branch_id'));
    }

    public function test_non_super_admin_cannot_switch_to_all_branches()
    {
        $tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole(UserRole::Owner->value);
        $owner->branches()->sync([$branch->id]);

        $this->actingAs($owner)
            ->put(route('branch.switch'), ['branch_id' => 0])
            ->assertForbidden();
    }
}
