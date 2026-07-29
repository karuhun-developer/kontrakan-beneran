<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\CheckInBooking;
use App\Actions\Booking\CheckOutBooking;
use App\Actions\Booking\ExtendBooking;
use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
        $type = RoomType::factory()->forBranch($this->branch)->create();
        $this->room = Room::factory()->forBranch($this->branch)->create(['room_type_id' => $type->id]);
    }

    private function makeBooking(BookingStatus $status): Booking
    {
        return Booking::factory()
            ->forRoom($this->room, '2026-08-01', '2026-09-01')
            ->status($status)
            ->create();
    }

    public function test_check_in_occupies_the_room_and_logs()
    {
        $booking = $this->makeBooking(BookingStatus::Confirmed);

        app(CheckInBooking::class)->handle($booking);

        $this->assertSame(BookingStatus::CheckedIn, $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->checked_in_at);
        $this->assertSame(RoomStatus::Occupied, $this->room->fresh()->room_status);
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'event' => BookingEvent::CheckedIn->value,
        ]);
    }

    public function test_cannot_check_in_a_checked_out_booking()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedOut);

        $this->expectException(ValidationException::class);

        app(CheckInBooking::class)->handle($booking);
    }

    public function test_check_out_frees_the_room_and_logs()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedIn);

        app(CheckOutBooking::class)->handle($booking);

        $this->assertSame(BookingStatus::CheckedOut, $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->checked_out_at);
        $this->assertSame(RoomStatus::Available, $this->room->fresh()->room_status);
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'event' => BookingEvent::CheckedOut->value,
        ]);
    }

    public function test_cancel_frees_the_room_and_logs()
    {
        $booking = $this->makeBooking(BookingStatus::Confirmed);

        app(CancelBooking::class)->handle($booking, 'Penyewa batal');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(RoomStatus::Available, $this->room->fresh()->room_status);
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'event' => BookingEvent::Cancelled->value,
        ]);
    }

    public function test_cannot_cancel_a_checked_in_booking()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedIn);

        $this->expectException(ValidationException::class);

        app(CancelBooking::class)->handle($booking);
    }

    public function test_extend_updates_end_date_and_logs()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedIn);

        app(ExtendBooking::class)->handle($booking, '2026-10-01');

        $this->assertSame('2026-10-01', $booking->fresh()->end_date->toDateString());
        $this->assertDatabaseHas('booking_logs', [
            'booking_id' => $booking->id,
            'event' => BookingEvent::Extended->value,
        ]);
    }

    public function test_extend_rejects_a_date_before_current_end()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedIn);

        $this->expectException(ValidationException::class);

        app(ExtendBooking::class)->handle($booking, '2026-08-15');
    }

    public function test_extend_rejects_a_conflicting_range()
    {
        $booking = $this->makeBooking(BookingStatus::CheckedIn);
        Booking::factory()
            ->forRoom($this->room, '2026-09-01', '2026-10-01')
            ->status(BookingStatus::Confirmed)
            ->create();

        $this->expectException(ValidationException::class);

        app(ExtendBooking::class)->handle($booking, '2026-09-15');
    }
}
