<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Reserved => 'Dipesan',
            self::Occupied => 'Terisi',
            self::Maintenance => 'Perbaikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Reserved => 'yellow',
            self::Occupied => 'red',
            self::Maintenance => 'gray',
        };
    }

    /**
     * Whether a room in this status can accept a new booking.
     */
    public function isBookable(): bool
    {
        return $this === self::Available;
    }
}
