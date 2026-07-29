<?php

namespace Tests\Feature;

use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function actingAsStaff(bool $withBranch = true): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Staff->value);

        if ($withBranch) {
            $user->branches()->sync([$this->branch->id]);
            $this->withSession(['current_branch_id' => $this->branch->id]);
        }

        $this->actingAs($user);

        return $user;
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_staff_users_can_visit_the_dashboard()
    {
        $this->actingAsStaff();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_analytics_is_null_without_an_active_branch()
    {
        $this->actingAsStaff(withBranch: false);

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('analytics', null)
            );
    }

    public function test_analytics_reports_branch_scoped_occupancy_and_billing()
    {
        $this->actingAsStaff();

        Room::factory()->forBranch($this->branch)->occupied()->count(3)->create();
        Room::factory()->forBranch($this->branch)->create();

        Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'total' => 750_000,
        ]);

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create([
            'amount' => 900_000,
            'occurred_on' => now()->toDateString(),
        ]);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Room::factory()->forBranch($otherBranch)->occupied()->create();
        Invoice::factory()->forBranch($otherBranch)->create(['status' => InvoiceStatus::Unpaid]);

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('analytics.occupancy.total', 4)
                ->where('analytics.occupancy.occupancy_rate', 75)
                ->where('analytics.billing.outstanding_count', 1)
                ->where('analytics.billing.outstanding_value', 750_000)
                ->where('analytics.cashflow.income', 900_000)
                ->where('analytics.cashflow.net', 900_000)
                ->has('analytics.trend', 6)
            );
    }

    public function test_due_soon_lists_only_invoices_due_within_a_week()
    {
        $this->actingAsStaff();

        Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'due_date' => now()->addDays(20)->toDateString(),
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->has('analytics.due_soon', 1)
            );
    }
}
