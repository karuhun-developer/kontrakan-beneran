<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    private Room $room;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $type = RoomType::factory()->forBranch($this->branch)->create();
        $this->room = Room::factory()->forBranch($this->branch)->create(['room_type_id' => $type->id]);
        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create();
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

    public function test_staff_can_create_a_booking()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->post(route('bookings.store'), [
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'status' => BookingStatus::Confirmed->value,
        ])->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'branch_id' => $this->branch->id,
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_conflicting_booking_is_rejected_with_errors()
    {
        $this->actingAsRole(UserRole::Staff);

        Booking::factory()->forRoom($this->room, '2026-08-01', '2026-09-01')->create();

        $this->post(route('bookings.store'), [
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'start_date' => '2026-08-15',
            'end_date' => '2026-09-15',
            'status' => BookingStatus::Confirmed->value,
        ])->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_calendar_positions_booking_within_month_window()
    {
        $this->actingAsRole(UserRole::Staff);

        Booking::factory()->forRoom($this->room, '2026-08-05', '2026-08-20')->create();

        $this->get(route('bookings.index', ['month' => '2026-08']))
            ->assertInertia(fn ($page) => $page
                ->component('bookings/index')
                ->where('calendar.month', '2026-08')
                ->where('calendar.days_in_month', 31)
                ->has('calendar.groups', 1)
                ->has('calendar.groups.0.rooms', 1)
                ->where('calendar.groups.0.rooms.0.bars.0.start_index', 4)
                ->where('calendar.groups.0.rooms.0.bars.0.span', 15)
            );
    }

    public function test_staff_can_check_in_a_booking_via_endpoint()
    {
        $this->actingAsRole(UserRole::Staff);

        $booking = Booking::factory()
            ->forRoom($this->room, '2026-08-01', '2026-09-01')
            ->status(BookingStatus::Confirmed)
            ->create();

        $this->post(route('bookings.check-in', $booking))->assertRedirect();

        $this->assertSame(
            BookingStatus::CheckedIn,
            $booking->fresh()->status,
        );
    }

    public function test_cannot_act_on_a_booking_from_another_branch()
    {
        $this->actingAsRole(UserRole::Staff);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $booking = Booking::factory()->forBranch($otherBranch)->create();

        $this->post(route('bookings.check-in', $booking))->assertNotFound();
    }

    public function test_staff_can_quick_create_a_penyewa_from_booking_form()
    {
        $this->actingAsRole(UserRole::Staff);

        $response = $this->postJson(route('penyewa.quick-store'), [
            'name' => 'Walk-in Guest',
            'phone' => '081234567890',
            'gender' => 'male',
        ]);

        $response->assertOk()->assertJsonStructure(['id', 'name']);

        $this->assertDatabaseHas('penyewas', [
            'name' => 'Walk-in Guest',
            'branch_id' => $this->branch->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_quick_create_penyewa_requires_a_name()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->postJson(route('penyewa.quick-store'), [
            'phone' => '081234567890',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_walkin_with_pay_now_creates_booking_invoice_payment_and_cashflow()
    {
        $this->actingAsRole(UserRole::Staff);
        $this->room->update(['monthly_price' => 1_000_000]);

        $this->post(route('bookings.store'), [
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'status' => BookingStatus::CheckedIn->value,
            'generate_invoice' => true,
            'pay_now' => true,
            'pay_amount' => 1_000_000,
            'pay_method' => PaymentMethod::Cash->value,
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::first();
        $this->assertNotNull($booking);

        $this->assertDatabaseHas('invoices', [
            'booking_id' => $booking->id,
            'branch_id' => $this->branch->id,
            'total' => 1_000_000,
            'status' => InvoiceStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('payments', [
            'branch_id' => $this->branch->id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Cash->value,
        ]);
        $this->assertDatabaseHas('cashflows', [
            'branch_id' => $this->branch->id,
            'type' => CashflowType::Income->value,
            'amount' => 1_000_000,
        ]);
    }

    public function test_reservation_can_auto_generate_invoice_without_payment()
    {
        $this->actingAsRole(UserRole::Staff);
        $this->room->update(['monthly_price' => 1_000_000]);

        $this->post(route('bookings.store'), [
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'status' => BookingStatus::Confirmed->value,
            'generate_invoice' => true,
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::first();
        $this->assertDatabaseHas('invoices', [
            'booking_id' => $booking->id,
            'status' => InvoiceStatus::Unpaid->value,
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_walkin_overpayment_rolls_back_the_whole_booking()
    {
        $this->actingAsRole(UserRole::Staff);
        $this->room->update(['monthly_price' => 1_000_000]);

        $this->post(route('bookings.store'), [
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'status' => BookingStatus::CheckedIn->value,
            'pay_now' => true,
            'pay_amount' => 999_999_999,
            'pay_method' => PaymentMethod::Cash->value,
        ])->assertSessionHasErrors('pay_amount');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_index_only_shows_current_branch_bookings()
    {
        $this->actingAsRole(UserRole::Staff);

        Booking::factory()->forBranch($this->branch)->create();

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Booking::factory()->forBranch($otherBranch)->create();

        $this->get(route('bookings.index'))
            ->assertInertia(fn ($page) => $page
                ->component('bookings/index')
                ->has('bookings.data', 1)
            );
    }
}
