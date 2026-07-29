<?php

namespace Tests\Feature\Portal;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Deposit;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDepositTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
    }

    private function deposit(): Deposit
    {
        return Deposit::factory()->forBranch($this->branch)->create([
            'penyewa_id' => $this->penyewa->id,
        ]);
    }

    public function test_index_lists_only_the_penyewas_deposits()
    {
        $this->deposit();

        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        Deposit::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.deposits.index'))
            ->assertInertia(fn ($page) => $page
                ->component('portal/deposits/index')
                ->has('deposits.data', 1)
                ->where('summary.active_balance', fn ($value) => $value > 0)
            );
    }

    public function test_can_view_own_deposit_detail()
    {
        $deposit = $this->deposit();

        $this->get(route('portal.deposits.show', $deposit))
            ->assertOk();
    }

    public function test_cannot_view_another_penyewas_deposit()
    {
        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        $deposit = Deposit::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.deposits.show', $deposit))
            ->assertNotFound();
    }

    public function test_staff_cannot_reach_the_portal_deposits()
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->actingAs($staff)
            ->get(route('portal.deposits.index'))
            ->assertForbidden();
    }
}
