<?php

namespace App\Actions\Booking;

use App\Enums\BookingEvent;
use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\User;
use App\Support\RoomAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBooking
{
    public function __construct(private RoomAvailability $availability) {}

    /**
     * Create a booking after guarding against date-range conflicts. Snapshots
     * the room's effective monthly price, syncs the room status, and records a
     * "created" log entry. Billing is handled later (Fase 3).
     *
     * @throws ValidationException when the range is invalid or the room is taken
     */
    public function handle(
        Penyewa $penyewa,
        Room $room,
        string $startDate,
        string $endDate,
        BookingStatus $status = BookingStatus::Confirmed,
        ?string $notes = null,
        ?User $actor = null,
    ): Booking {
        if ($endDate <= $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai harus setelah tanggal mulai.',
            ]);
        }

        if (! $this->availability->isAvailable($room->id, $startDate, $endDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Kamar sudah dibooking pada rentang tanggal tersebut.',
            ]);
        }

        return DB::transaction(function () use ($penyewa, $room, $startDate, $endDate, $status, $notes, $actor): Booking {
            $booking = Booking::create([
                'tenant_id' => $room->tenant_id,
                'branch_id' => $room->branch_id,
                'room_id' => $room->id,
                'penyewa_id' => $penyewa->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'monthly_price' => $room->effective_monthly_price,
                'status' => $status,
                'notes' => $notes,
                'checked_in_at' => $status === BookingStatus::CheckedIn ? now() : null,
            ]);

            $this->syncRoomStatus($room, $status);

            $booking->logs()->create([
                'user_id' => $actor?->id,
                'event' => BookingEvent::Created,
                'description' => 'Booking dibuat untuk '.$penyewa->name,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status->value,
                ],
            ]);

            return $booking;
        });
    }

    /**
     * Reflect the booking state onto the room's occupancy status.
     */
    private function syncRoomStatus(Room $room, BookingStatus $status): void
    {
        $roomStatus = match ($status) {
            BookingStatus::CheckedIn => RoomStatus::Occupied,
            BookingStatus::Pending, BookingStatus::Confirmed => RoomStatus::Reserved,
            default => null,
        };

        if ($roomStatus !== null && $room->room_status !== $roomStatus) {
            $room->update(['room_status' => $roomStatus]);
        }
    }
}
