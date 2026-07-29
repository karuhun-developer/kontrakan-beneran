<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    private CreateBooking $action;

    private Branch $branch;

    private Room $room;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(CreateBooking::class);

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
        $type = RoomType::factory()->forBranch($this->branch)->create(['monthly_price' => 1_200_000]);
        $this->room = Room::factory()->forBranch($this->branch)->create([
            'room_type_id' => $type->id,
            'monthly_price' => null,
        ]);
        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create();
    }

    public function test_it_creates_a_booking_with_price_snapshot_and_log()
    {
        $booking = $this->action->handle($this->penyewa, $this->room, '2026-08-01', '2026-09-01');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'room_id' => $this->room->id,
            'penyewa_id' => $this->penyewa->id,
            'monthly_price' => 1_200_000,
            'status' => BookingStatus::Confirmed->value,
        ]);

        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'event' => BookingEvent::Created->value,
        ]);
    }

    public function test_confirmed_booking_marks_room_reserved()
    {
        $this->action->handle($this->penyewa, $this->room, '2026-08-01', '2026-09-01');

        $this->assertEquals(RoomStatus::Reserved, $this->room->refresh()->room_status);
    }

    public function test_checked_in_booking_marks_room_occupied_and_stamps_time()
    {
        $booking = $this->action->handle(
            $this->penyewa,
            $this->room,
            '2026-08-01',
            '2026-09-01',
            BookingStatus::CheckedIn,
        );

        $this->assertEquals(RoomStatus::Occupied, $this->room->refresh()->room_status);
        $this->assertNotNull($booking->checked_in_at);
    }

    public function test_it_rejects_a_conflicting_range()
    {
        Booking::factory()->forRoom($this->room, '2026-08-01', '2026-09-01')->create();

        $this->expectException(ValidationException::class);

        $this->action->handle($this->penyewa, $this->room, '2026-08-15', '2026-09-15');
    }

    public function test_it_rejects_an_inverted_range()
    {
        $this->expectException(ValidationException::class);

        $this->action->handle($this->penyewa, $this->room, '2026-09-01', '2026-08-01');
    }

    public function test_it_allows_booking_after_a_previous_stay_ends()
    {
        Booking::factory()
            ->forRoom($this->room, '2026-08-01', '2026-09-01')
            ->status(BookingStatus::CheckedOut)
            ->create();

        $booking = $this->action->handle($this->penyewa, $this->room, '2026-09-01', '2026-10-01');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }
}
