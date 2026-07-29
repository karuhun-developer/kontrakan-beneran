<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Anti double-booking engine. A room is unavailable for a requested date range
 * when it already has a status-blocking booking whose range overlaps it.
 * Ranges are treated as half-open [start, end), so a booking ending the same
 * day another starts does not conflict.
 */
class RoomAvailability
{
    /**
     * Whether the room can take a new booking for the given range.
     * Pass $ignoreBookingId when editing/extending to exclude the booking itself.
     */
    public function isAvailable(
        int $roomId,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $ignoreBookingId = null,
    ): bool {
        return ! $this->hasConflict($roomId, $start, $end, $ignoreBookingId);
    }

    /**
     * Whether the room has any conflicting booking for the given range.
     */
    public function hasConflict(
        int $roomId,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $ignoreBookingId = null,
    ): bool {
        return $this->conflictsQuery($roomId, $start, $end, $ignoreBookingId)->exists();
    }

    /**
     * The conflicting bookings for the given range, if any.
     *
     * @return Collection<int, Booking>
     */
    public function conflicts(
        int $roomId,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $ignoreBookingId = null,
    ): Collection {
        return $this->conflictsQuery($roomId, $start, $end, $ignoreBookingId)->get();
    }

    /**
     * @return Builder<Booking>
     */
    private function conflictsQuery(
        int $roomId,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $ignoreBookingId,
    ): Builder {
        return Booking::query()
            ->forRoom($roomId)
            ->active()
            ->overlapping($this->toDateString($start), $this->toDateString($end))
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId));
    }

    private function toDateString(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : $date;
    }
}
