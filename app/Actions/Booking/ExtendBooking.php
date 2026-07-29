<?php

namespace App\Actions\Booking;

use App\Enums\BookingEvent;
use App\Models\Booking;
use App\Models\User;
use App\Support\RoomAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtendBooking
{
    public function __construct(private RoomAvailability $availability) {}

    /**
     * Extend a booking's end date after guarding the extension window against
     * conflicts, and log the event.
     *
     * @throws ValidationException when the new date is invalid or the room is taken
     */
    public function handle(Booking $booking, string $newEndDate, ?User $actor = null): Booking
    {
        if (! $booking->status->canExtend()) {
            throw ValidationException::withMessages([
                'booking' => 'Booking ini tidak bisa diperpanjang.',
            ]);
        }

        $currentEnd = $booking->end_date->toDateString();

        if ($newEndDate <= $currentEnd) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal perpanjangan harus setelah tanggal selesai saat ini.',
            ]);
        }

        if (! $this->availability->isAvailable($booking->room_id, $currentEnd, $newEndDate, $booking->id)) {
            throw ValidationException::withMessages([
                'end_date' => 'Kamar sudah dibooking pada rentang perpanjangan.',
            ]);
        }

        return DB::transaction(function () use ($booking, $newEndDate, $currentEnd, $actor): Booking {
            $booking->update(['end_date' => $newEndDate]);

            $booking->logs()->create([
                'user_id' => $actor?->id,
                'event' => BookingEvent::Extended,
                'description' => "Sewa diperpanjang hingga {$newEndDate}.",
                'meta' => [
                    'previous_end_date' => $currentEnd,
                    'new_end_date' => $newEndDate,
                ],
            ]);

            return $booking->refresh();
        });
    }
}
