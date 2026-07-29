<?php

namespace Tests\Feature\Laundry;

use App\Actions\Deposit\OpenDeposit;
use App\Enums\CommonStatus;
use App\Enums\LaundryPaymentSource;
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

class LaundryControllerTest extends TestCase
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

        LaundryOrder::factory()->forBranch($this->branch)->status(LaundryStatus::Ready)->create();

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        LaundryOrder::factory()->forBranch($otherBranch)->create();

        $this->get(route('laundry.index'))
            ->assertInertia(fn ($page) => $page
                ->component('laundry/index')
                ->has('orders.data', 1)
                ->where('stats.open_orders', 1)
                ->where('stats.ready_orders', 1)
            );
    }

    public function test_index_filters_by_status()
    {
        $this->actingAsRole(UserRole::Staff);

        LaundryOrder::factory()->forBranch($this->branch)->status(LaundryStatus::Received)->create();
        LaundryOrder::factory()->forBranch($this->branch)->status(LaundryStatus::Delivered)->create();

        $this->get(route('laundry.index', ['status' => 'delivered']))
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.status', 'delivered')
                ->where('filters.status', 'delivered')
            );
    }

    public function test_staff_can_create_an_invoice_paid_order()
    {
        $this->actingAsRole(UserRole::Staff);
        $penyewa = $this->penyewa();

        $this->post(route('laundry.store'), [
            'penyewa_id' => $penyewa->id,
            'payment_source' => LaundryPaymentSource::Invoice->value,
            'weight' => 5,
            'price' => 35_000,
        ])->assertRedirect(route('laundry.index'));

        $this->assertDatabaseHas('laundry_orders', [
            'penyewa_id' => $penyewa->id,
            'branch_id' => $this->branch->id,
            'price' => 35_000,
            'payment_source' => LaundryPaymentSource::Invoice->value,
        ]);

        $this->assertDatabaseHas('invoices', [
            'penyewa_id' => $penyewa->id,
            'total' => 35_000,
        ]);
    }

    public function test_staff_can_create_a_deposit_paid_order()
    {
        $this->actingAsRole(UserRole::Staff);
        $penyewa = $this->penyewa();
        $deposit = app(OpenDeposit::class)->handle($penyewa, 500_000);

        $this->post(route('laundry.store'), [
            'penyewa_id' => $penyewa->id,
            'payment_source' => LaundryPaymentSource::Deposit->value,
            'price' => 40_000,
        ])->assertRedirect(route('laundry.index'));

        $this->assertSame(460_000, $deposit->refresh()->balance);
    }

    public function test_deposit_paid_order_without_enough_balance_is_rejected()
    {
        $this->actingAsRole(UserRole::Staff);
        $penyewa = $this->penyewa();
        app(OpenDeposit::class)->handle($penyewa, 20_000);

        $this->post(route('laundry.store'), [
            'penyewa_id' => $penyewa->id,
            'payment_source' => LaundryPaymentSource::Deposit->value,
            'price' => 40_000,
        ])->assertSessionHasErrors('payment_source');

        $this->assertDatabaseCount('laundry_orders', 0);
    }

    public function test_cannot_create_an_order_for_a_penyewa_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $penyewa = Penyewa::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $otherBranch->id,
        ]);

        $this->post(route('laundry.store'), [
            'penyewa_id' => $penyewa->id,
            'payment_source' => LaundryPaymentSource::Invoice->value,
            'price' => 35_000,
        ])->assertSessionHasErrors('penyewa_id');
    }

    public function test_staff_can_advance_the_order_status()
    {
        $this->actingAsRole(UserRole::Staff);

        $order = LaundryOrder::factory()->forBranch($this->branch)
            ->status(LaundryStatus::Received)
            ->create();

        $this->post(route('laundry.advance', $order))->assertRedirect();

        $this->assertSame(LaundryStatus::Processing, $order->refresh()->status);
    }

    public function test_cannot_advance_an_order_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $order = LaundryOrder::factory()->forBranch($otherBranch)->create();

        $this->post(route('laundry.advance', $order))->assertNotFound();
    }
}
