<?php

namespace Tests\Feature\Management;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
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

    public function test_owner_sees_only_their_tenant_branches()
    {
        Branch::factory()->create(); // another tenant's branch
        $this->actingAsRole(UserRole::Owner);

        $this->get(route('management.branches.index'))
            ->assertInertia(fn ($page) => $page
                ->component('management/branches/index')
                ->has('branches.data', 1)
                ->where('branches.data.0.id', $this->branch->id)
            );
    }

    public function test_owner_can_create_a_branch()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->post(route('management.branches.store'), [
            'name' => 'Cabang Selatan',
            'bank_name' => 'BCA',
            'bank_account_number' => '999888777',
            'bank_account_holder' => 'Kost Selatan',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Cabang Selatan',
            'slug' => 'cabang-selatan',
            'bank_name' => 'BCA',
        ]);
    }

    public function test_owner_can_update_a_branch()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->put(route('management.branches.update', $this->branch), [
            'name' => 'Cabang Diperbarui',
            'status' => 'inactive',
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'id' => $this->branch->id,
            'name' => 'Cabang Diperbarui',
            'status' => 'inactive',
        ]);
    }

    public function test_owner_can_delete_a_branch()
    {
        $this->actingAsRole(UserRole::Owner);
        $other = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->delete(route('management.branches.destroy', $other))->assertRedirect();

        $this->assertDatabaseMissing('branches', ['id' => $other->id]);
    }

    public function test_admin_can_view_but_cannot_create_branches()
    {
        $this->actingAsRole(UserRole::Admin);

        $this->get(route('management.branches.index'))->assertOk();

        $this->post(route('management.branches.store'), [
            'name' => 'Cabang Nakal',
            'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('branches', ['name' => 'Cabang Nakal']);
    }

    public function test_owner_cannot_edit_a_branch_from_another_tenant()
    {
        $otherBranch = Branch::factory()->create();
        $this->actingAsRole(UserRole::Owner);

        $this->get(route('management.branches.edit', $otherBranch))->assertNotFound();
    }

    public function test_owner_without_a_branch_is_redirected_to_branch_setup()
    {
        $owner = $this->ownerWithoutBranch();
        $this->actingAs($owner);

        $this->get(route('dashboard'))->assertRedirect(route('management.branches.index'));
    }

    public function test_owner_without_a_branch_can_still_open_and_create_branches()
    {
        $owner = $this->ownerWithoutBranch();
        $this->actingAs($owner);

        // The setup page itself stays reachable (no redirect loop)...
        $this->get(route('management.branches.index'))->assertOk();

        // ...and the first branch can be created without an active branch.
        $this->post(route('management.branches.store'), [
            'name' => 'Cabang Pertama',
            'status' => 'active',
        ])->assertRedirect(route('management.branches.index'));

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $owner->tenant_id,
            'name' => 'Cabang Pertama',
        ]);
    }

    private function ownerWithoutBranch(): User
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole(UserRole::Owner->value);

        return $owner;
    }
}
