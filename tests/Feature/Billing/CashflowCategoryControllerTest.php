<?php

namespace Tests\Feature\Billing;

use App\Enums\CashflowType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\CashflowCategory;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowCategoryControllerTest extends TestCase
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

    public function test_staff_can_create_a_category()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->post(route('cashflow-categories.store'), [
            'name' => 'Listrik',
            'type' => CashflowType::Expense->value,
            'status' => 'active',
        ])->assertRedirect(route('cashflow-categories.index'));

        $this->assertDatabaseHas('cashflow_categories', [
            'branch_id' => $this->branch->id,
            'name' => 'Listrik',
            'type' => CashflowType::Expense->value,
            'is_system' => false,
        ]);
    }

    public function test_duplicate_name_within_same_type_is_rejected()
    {
        $this->actingAsRole(UserRole::Staff);

        CashflowCategory::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create(['name' => 'Listrik']);

        $this->post(route('cashflow-categories.store'), [
            'name' => 'Listrik',
            'type' => CashflowType::Expense->value,
            'status' => 'active',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('cashflow_categories', 1);
    }

    public function test_system_category_cannot_be_deleted()
    {
        $this->actingAsRole(UserRole::Staff);

        $category = CashflowCategory::factory()->forBranch($this->branch)->create(['is_system' => true]);

        $this->delete(route('cashflow-categories.destroy', $category))->assertForbidden();

        $this->assertDatabaseHas('cashflow_categories', ['id' => $category->id]);
    }

    public function test_cannot_touch_a_category_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $category = CashflowCategory::factory()->forBranch($otherBranch)->create();

        $this->delete(route('cashflow-categories.destroy', $category))->assertNotFound();
    }
}
