<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
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
                ->has('calendar.rooms', 1)
                ->where('calendar.rooms.0.bars.0.start_index', 4)
                ->where('calendar.rooms.0.bars.0.span', 15)
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
