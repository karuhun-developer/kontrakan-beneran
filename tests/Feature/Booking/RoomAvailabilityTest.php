<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Support\RoomAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private RoomAvailability $availability;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = app(RoomAvailability::class);
        $this->room = Room::factory()->create();
    }

    private function book(string $start, string $end, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        return Booking::factory()
            ->forRoom($this->room, $start, $end)
            ->status($status)
            ->create();
    }

    public function test_room_is_available_when_it_has_no_bookings()
    {
        $this->assertTrue(
            $this->availability->isAvailable($this->room->id, '2026-08-01', '2026-09-01'),
        );
    }

    public function test_overlapping_active_booking_makes_room_unavailable()
    {
        $this->book('2026-08-01', '2026-09-01');

        $this->assertFalse(
            $this->availability->isAvailable($this->room->id, '2026-08-15', '2026-09-15'),
        );
    }

    public function test_adjacent_ranges_do_not_conflict()
    {
        $this->book('2026-08-01', '2026-09-01');

        $this->assertTrue(
            $this->availability->isAvailable($this->room->id, '2026-09-01', '2026-10-01'),
        );
    }

    public function test_cancelled_booking_does_not_block()
    {
        $this->book('2026-08-01', '2026-09-01', BookingStatus::Cancelled);

        $this->assertTrue(
            $this->availability->isAvailable($this->room->id, '2026-08-10', '2026-08-20'),
        );
    }

    public function test_checked_out_booking_does_not_block()
    {
        $this->book('2026-08-01', '2026-09-01', BookingStatus::CheckedOut);

        $this->assertTrue(
            $this->availability->isAvailable($this->room->id, '2026-08-10', '2026-08-20'),
        );
    }

    public function test_pending_booking_blocks_availability()
    {
        $this->book('2026-08-01', '2026-09-01', BookingStatus::Pending);

        $this->assertFalse(
            $this->availability->isAvailable($this->room->id, '2026-08-10', '2026-08-20'),
        );
    }

    public function test_booking_on_another_room_does_not_conflict()
    {
        $other = Room::factory()->create();
        Booking::factory()->forRoom($other, '2026-08-01', '2026-09-01')->create();

        $this->assertTrue(
            $this->availability->isAvailable($this->room->id, '2026-08-10', '2026-08-20'),
        );
    }

    public function test_ignored_booking_is_excluded_so_it_can_be_extended()
    {
        $booking = $this->book('2026-08-01', '2026-09-01');

        // Extending its own range must not conflict with itself.
        $this->assertTrue(
            $this->availability->isAvailable(
                $this->room->id,
                '2026-08-01',
                '2026-10-01',
                ignoreBookingId: $booking->id,
            ),
        );
    }
}
