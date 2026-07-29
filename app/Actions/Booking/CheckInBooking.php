<?php

namespace App\Actions\Booking;

use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInBooking
{
    /**
     * Move a booking to checked-in, occupy the room, and log the event.
     *
     * @throws ValidationException when the booking cannot be checked in
     */
    public function handle(Booking $booking, ?User $actor = null): Booking
    {
        if (! $booking->status->canCheckIn()) {
            throw ValidationException::withMessages([
                'booking' => 'Booking ini tidak bisa di-check-in.',
            ]);
        }

        return DB::transaction(function () use ($booking, $actor): Booking {
            $booking->update([
                'status' => BookingStatus::CheckedIn,
                'checked_in_at' => now(),
            ]);

            $booking->room?->update(['room_status' => RoomStatus::Occupied]);

            $booking->logs()->create([
                'user_id' => $actor?->id,
                'event' => BookingEvent::CheckedIn,
                'description' => 'Penyewa check-in.',
            ]);

            return $booking->refresh();
        });
    }
}
