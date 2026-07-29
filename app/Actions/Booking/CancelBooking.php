<?php

namespace App\Actions\Booking;

use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    /**
     * Cancel a booking before check-in, free the room, and log the event.
     *
     * @throws ValidationException when the booking cannot be cancelled
     */
    public function handle(Booking $booking, ?string $reason = null, ?User $actor = null): Booking
    {
        if (! $booking->status->canCancel()) {
            throw ValidationException::withMessages([
                'booking' => 'Booking ini tidak bisa dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($booking, $reason, $actor): Booking {
            $booking->update(['status' => BookingStatus::Cancelled]);

            $booking->room?->update(['room_status' => RoomStatus::Available]);

            $booking->logs()->create([
                'user_id' => $actor?->id,
                'event' => BookingEvent::Cancelled,
                'description' => $reason ? "Dibatalkan: {$reason}" : 'Booking dibatalkan.',
                'meta' => $reason ? ['reason' => $reason] : null,
            ]);

            return $booking->refresh();
        });
    }
}
