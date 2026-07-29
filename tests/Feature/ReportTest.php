<?php

namespace Tests\Feature;

use App\Enums\CashflowType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function actingAsReportUser(string $role = UserRole::Owner->value): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role);
        $user->branches()->sync([$this->branch->id]);
        $this->withSession(['current_branch_id' => $this->branch->id]);
        $this->actingAs($user);

        return $user;
    }

    public function test_reports_branch_scoped_income_expense_and_net_for_the_range()
    {
        $this->actingAsReportUser();

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create([
            'amount' => 900_000,
            'occurred_on' => now()->startOfMonth()->addDays(2)->toDateString(),
        ]);
        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Expense)->create([
            'amount' => 250_000,
            'occurred_on' => now()->startOfMonth()->addDays(3)->toDateString(),
        ]);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Cashflow::factory()->forBranch($otherBranch)->type(CashflowType::Income)->create(['amount' => 500_000]);

        $this->get(route('reports.index'))
            ->assertInertia(fn ($page) => $page
                ->component('reports/index')
                ->where('summary.income', 900_000)
                ->where('summary.expense', 250_000)
                ->where('summary.net', 650_000)
                ->has('trend', 6)
            );
    }

    public function test_excludes_cashflow_outside_the_selected_range()
    {
        $this->actingAsReportUser();

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create([
            'amount' => 400_000,
            'occurred_on' => '2026-03-10',
        ]);
        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create([
            'amount' => 999_000,
            'occurred_on' => '2026-05-10',
        ]);

        $this->get(route('reports.index', ['start' => '2026-03-01', 'end' => '2026-03-31']))
            ->assertInertia(fn ($page) => $page
                ->where('summary.income', 400_000)
                ->where('filters.start', '2026-03-01')
                ->where('filters.end', '2026-03-31')
            );
    }

    public function test_exports_the_period_cashflow_as_a_csv_download()
    {
        $this->actingAsReportUser();

        Cashflow::factory()->forBranch($this->branch)->type(CashflowType::Income)->create([
            'amount' => 750_000,
            'description' => 'Sewa Kamar A1',
            'occurred_on' => now()->toDateString(),
        ]);

        $response = $this->get(route('reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $body = $response->streamedContent();
        $this->assertStringContainsString('Tanggal', $body);
        $this->assertStringContainsString('Sewa Kamar A1', $body);
        $this->assertStringContainsString('750000', $body);
    }

    public function test_forbids_users_without_the_report_view_permission()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Staff->value);
        $user->revokePermissionTo('report.view');
        $user->branches()->sync([$this->branch->id]);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_redirects_guests_to_the_login_page()
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
