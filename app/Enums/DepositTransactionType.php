<?php

namespace App\Enums;

enum DepositTransactionType: string
{
    case Hold = 'hold';
    case Deduction = 'deduction';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Hold => 'Setoran',
            self::Deduction => 'Potongan',
            self::Refund => 'Pengembalian',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Hold => 'blue',
            self::Deduction => 'red',
            self::Refund => 'green',
        };
    }

    /**
     * Signed multiplier for balance math: a hold adds funds, a deduction or
     * refund removes them.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Hold => 1,
            self::Deduction, self::Refund => -1,
        };
    }
}
