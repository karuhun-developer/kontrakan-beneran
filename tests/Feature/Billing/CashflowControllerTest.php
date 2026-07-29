<?php

namespace Tests\Feature\Billing;

use App\Enums\CashflowType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\CashflowCategory;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowControllerTest extends TestCase
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

    public function test_index_lists_current_branch_only_with_stats()
    {
        $this->actingAsRole(UserRole::Staff);

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create(['amount' => 500_000]);
        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create(['amount' => 200_000]);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Cashflow::factory()->forBranch($otherBranch)->create();

        $this->get(route('cashflows.index'))
            ->assertInertia(fn ($page) => $page
                ->component('billing/cashflows/index')
                ->has('cashflows.data', 2)
                ->where('stats.balance', 300_000)
                ->where('stats.income_this_month', 500_000)
                ->where('stats.expense_this_month', 200_000)
            );
    }

    public function test_index_includes_a_calendar_summary_for_the_month()
    {
        $this->actingAsRole(UserRole::Staff);

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)
            ->create(['amount' => 500_000, 'occurred_on' => now()->startOfMonth()->toDateString()]);
        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Expense)
            ->create(['amount' => 200_000, 'occurred_on' => now()->startOfMonth()->toDateString()]);

        $this->get(route('cashflows.index'))
            ->assertInertia(fn ($page) => $page
                ->where('calendar.summary.income', 500_000)
                ->where('calendar.summary.expense', 200_000)
                ->where('calendar.summary.net', 300_000)
                ->has('calendar.weeks')
                ->has('calendar.categories', 2)
            );
    }

    public function test_calendar_respects_the_month_query()
    {
        $this->actingAsRole(UserRole::Staff);

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)
            ->create(['amount' => 500_000, 'occurred_on' => now()->toDateString()]);

        $this->get(route('cashflows.index', ['month' => '2020-01']))
            ->assertInertia(fn ($page) => $page
                ->where('calendar.month', '2020-01')
                ->where('calendar.summary.income', 0)
            );
    }

    public function test_index_filters_by_type()
    {
        $this->actingAsRole(UserRole::Staff);

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create();
        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create();

        $this->get(route('cashflows.index', ['type' => 'expense']))
            ->assertInertia(fn ($page) => $page
                ->has('cashflows.data', 1)
                ->where('cashflows.data.0.type', 'expense')
                ->where('filters.type', 'expense')
            );
    }

    public function test_staff_can_record_a_manual_expense()
    {
        $this->actingAsRole(UserRole::Staff);

        $category = CashflowCategory::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create();

        $this->post(route('cashflows.store'), [
            'type' => CashflowType::Expense->value,
            'amount' => 150_000,
            'description' => 'Bayar listrik',
            'occurred_on' => '2026-07-29',
            'cashflow_category_id' => $category->id,
        ])->assertRedirect(route('cashflows.index'));

        $this->assertDatabaseHas('cashflows', [
            'branch_id' => $this->branch->id,
            'type' => CashflowType::Expense->value,
            'amount' => 150_000,
            'description' => 'Bayar listrik',
            'cashflow_category_id' => $category->id,
        ]);
    }

    public function test_category_from_mismatched_type_is_rejected()
    {
        $this->actingAsRole(UserRole::Staff);

        $incomeCategory = CashflowCategory::factory()->forBranch($this->branch)->type(CashflowType::Income)->create();

        $this->post(route('cashflows.store'), [
            'type' => CashflowType::Expense->value,
            'amount' => 150_000,
            'description' => 'Salah kategori',
            'occurred_on' => '2026-07-29',
            'cashflow_category_id' => $incomeCategory->id,
        ])->assertSessionHasErrors('cashflow_category_id');

        $this->assertDatabaseCount('cashflows', 0);
    }

    public function test_manual_cashflow_can_be_updated_and_deleted()
    {
        $this->actingAsRole(UserRole::Staff);

        $cashflow = Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create([
            'amount' => 100_000,
            'description' => 'Awal',
        ]);

        $this->put(route('cashflows.update', $cashflow), [
            'type' => CashflowType::Expense->value,
            'amount' => 250_000,
            'description' => 'Diperbarui',
            'occurred_on' => '2026-07-29',
        ])->assertRedirect(route('cashflows.index'));

        $this->assertDatabaseHas('cashflows', [
            'id' => $cashflow->id,
            'amount' => 250_000,
            'description' => 'Diperbarui',
        ]);

        $this->delete(route('cashflows.destroy', $cashflow))
            ->assertRedirect(route('cashflows.index'));

        $this->assertDatabaseMissing('cashflows', ['id' => $cashflow->id]);
    }

    public function test_system_cashflow_cannot_be_edited_or_deleted()
    {
        $this->actingAsRole(UserRole::Staff);

        $cashflow = Cashflow::factory()->forBranch($this->branch)->create([
            'source_type' => Payment::class,
            'source_id' => 999,
        ]);

        $this->get(route('cashflows.edit', $cashflow))->assertForbidden();
        $this->delete(route('cashflows.destroy', $cashflow))->assertForbidden();

        $this->assertDatabaseHas('cashflows', ['id' => $cashflow->id]);
    }

    public function test_cannot_touch_a_cashflow_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $cashflow = Cashflow::factory()->forBranch($otherBranch)->create();

        $this->delete(route('cashflows.destroy', $cashflow))->assertNotFound();
    }
}
