<?php

namespace Tests\Feature\Portal;

use App\Enums\LaundryStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\LaundryOrder;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLaundryTest extends TestCase
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

    private function order(?LaundryStatus $status = null): LaundryOrder
    {
        return LaundryOrder::factory()
            ->forBranch($this->branch)
            ->status($status ?? LaundryStatus::Received)
            ->create(['penyewa_id' => $this->penyewa->id]);
    }

    public function test_index_lists_only_the_penyewas_orders_and_counts_open()
    {
        $this->order(LaundryStatus::Processing);
        $this->order(LaundryStatus::Delivered);

        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        LaundryOrder::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.laundry.index'))
            ->assertInertia(fn ($page) => $page
                ->component('portal/laundry/index')
                ->has('orders.data', 2)
                ->where('summary.open_count', 1)
            );
    }

    public function test_can_view_own_order_detail()
    {
        $order = $this->order();

        $this->get(route('portal.laundry.show', $order))
            ->assertOk();
    }

    public function test_cannot_view_another_penyewas_order()
    {
        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        $order = LaundryOrder::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.laundry.show', $order))
            ->assertNotFound();
    }

    public function test_staff_cannot_reach_the_portal_laundry()
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->actingAs($staff)
            ->get(route('portal.laundry.index'))
            ->assertForbidden();
    }
}
