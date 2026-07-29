<?php

namespace Tests\Feature\Deposit;

use App\Enums\CommonStatus;
use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Deposit;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositControllerTest extends TestCase
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

    private function penyewa(): Penyewa
    {
        return Penyewa::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'status' => CommonStatus::Active,
        ]);
    }

    public function test_index_lists_current_branch_only_with_stats()
    {
        $this->actingAsRole(UserRole::Staff);

        Deposit::factory()->forBranch($this->branch)->create(['balance' => 500_000]);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Deposit::factory()->forBranch($otherBranch)->create();

        $this->get(route('deposits.index'))
            ->assertInertia(fn ($page) => $page
                ->component('deposits/index')
                ->has('deposits.data', 1)
                ->where('stats.held_balance', 500_000)
                ->where('stats.active_count', 1)
            );
    }

    public function test_index_filters_by_status()
    {
        $this->actingAsRole(UserRole::Staff);

        Deposit::factory()->forBranch($this->branch)->create();
        Deposit::factory()->forBranch($this->branch)->create([
            'status' => DepositStatus::Refunded,
            'balance' => 0,
        ]);

        $this->get(route('deposits.index', ['status' => 'refunded']))
            ->assertInertia(fn ($page) => $page
                ->has('deposits.data', 1)
                ->where('deposits.data.0.status', 'refunded')
                ->where('filters.status', 'refunded')
            );
    }

    public function test_staff_can_open_a_deposit()
    {
        $this->actingAsRole(UserRole::Staff);
        $penyewa = $this->penyewa();

        $this->post(route('deposits.store'), [
            'penyewa_id' => $penyewa->id,
            'amount' => 1_000_000,
        ])->assertRedirect(route('deposits.index'));

        $this->assertDatabaseHas('deposits', [
            'penyewa_id' => $penyewa->id,
            'branch_id' => $this->branch->id,
            'amount' => 1_000_000,
            'balance' => 1_000_000,
            'status' => DepositStatus::Held->value,
        ]);

        $this->assertDatabaseHas('deposit_transactions', [
            'type' => DepositTransactionType::Hold->value,
            'amount' => 1_000_000,
        ]);
    }

    public function test_cannot_open_a_deposit_for_a_penyewa_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $penyewa = Penyewa::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $otherBranch->id,
        ]);

        $this->post(route('deposits.store'), [
            'penyewa_id' => $penyewa->id,
            'amount' => 1_000_000,
        ])->assertSessionHasErrors('penyewa_id');
    }

    public function test_staff_can_record_a_deduction()
    {
        $this->actingAsRole(UserRole::Staff);

        $deposit = Deposit::factory()->forBranch($this->branch)->create([
            'amount' => 1_000_000,
            'balance' => 1_000_000,
        ]);

        $this->post(route('deposits.transaction', $deposit), [
            'type' => DepositTransactionType::Deduction->value,
            'amount' => 300_000,
            'reason' => 'Ganti rugi',
        ])->assertRedirect();

        $deposit->refresh();

        $this->assertSame(700_000, $deposit->balance);
        $this->assertSame(DepositStatus::Partial, $deposit->status);
    }

    public function test_a_deduction_over_the_balance_is_rejected()
    {
        $this->actingAsRole(UserRole::Staff);

        $deposit = Deposit::factory()->forBranch($this->branch)->create([
            'amount' => 500_000,
            'balance' => 500_000,
        ]);

        $this->post(route('deposits.transaction', $deposit), [
            'type' => DepositTransactionType::Deduction->value,
            'amount' => 600_000,
        ])->assertSessionHasErrors('amount');

        $this->assertSame(500_000, $deposit->refresh()->balance);
    }

    public function test_cannot_touch_a_deposit_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $deposit = Deposit::factory()->forBranch($otherBranch)->create();

        $this->post(route('deposits.transaction', $deposit), [
            'type' => DepositTransactionType::Deduction->value,
            'amount' => 100_000,
        ])->assertNotFound();
    }
}
