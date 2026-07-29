<?php

namespace App\Enums;

enum BookingEvent: string
{
    case Created = 'created';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Extended = 'extended';
    case RoomChanged = 'room_changed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Booking dibuat',
            self::Confirmed => 'Booking dikonfirmasi',
            self::CheckedIn => 'Check-in',
            self::CheckedOut => 'Check-out',
            self::Cancelled => 'Booking dibatalkan',
            self::Extended => 'Sewa diperpanjang',
            self::RoomChanged => 'Pindah kamar',
        };
    }
}
