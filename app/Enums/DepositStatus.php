<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Held = 'held';
    case Partial = 'partial';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Ditahan',
            self::Partial => 'Sebagian',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Held => 'blue',
            self::Partial => 'yellow',
            self::Refunded => 'green',
        };
    }

    /**
     * Whether the deposit still holds funds that can be deducted or refunded.
     */
    public function isActive(): bool
    {
        return $this !== self::Refunded;
    }
}
