<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Confirmed => 'Dikonfirmasi',
            self::CheckedIn => 'Check-in',
            self::CheckedOut => 'Check-out',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Confirmed => 'blue',
            self::CheckedIn => 'green',
            self::CheckedOut => 'gray',
            self::Cancelled => 'red',
        };
    }

    /**
     * Whether a booking in this status holds the room for its date range, i.e.
     * blocks another overlapping booking. Checked-out and cancelled free it.
     */
    public function blocksAvailability(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::CheckedIn => true,
            self::CheckedOut, self::Cancelled => false,
        };
    }

    /**
     * Statuses that hold a room for its date range.
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $status->blocksAvailability(),
        ));
    }
}
