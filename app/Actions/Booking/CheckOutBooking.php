<?php

namespace App\Actions\Booking;

use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOutBooking
{
    /**
     * Move a checked-in booking to checked-out, free the room, and log the event.
     *
     * @throws ValidationException when the booking cannot be checked out
     */
    public function handle(Booking $booking, ?User $actor = null): Booking
    {
        if (! $booking->status->canCheckOut()) {
            throw ValidationException::withMessages([
                'booking' => 'Booking ini tidak bisa di-check-out.',
            ]);
        }

        return DB::transaction(function () use ($booking, $actor): Booking {
            $booking->update([
                'status' => BookingStatus::CheckedOut,
                'checked_out_at' => now(),
            ]);

            $booking->room?->update(['room_status' => RoomStatus::Available]);

            $booking->logs()->create([
                'user_id' => $actor?->id,
                'event' => BookingEvent::CheckedOut,
                'description' => 'Penyewa check-out.',
            ]);

            return $booking->refresh();
        });
    }
}
