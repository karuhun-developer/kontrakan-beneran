<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Completed => 'Berhasil',
            self::Failed => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Completed => 'green',
            self::Failed => 'red',
        };
    }

    /**
     * Whether the payment has settled and counts toward the invoice balance.
     */
    public function isSettled(): bool
    {
        return $this === self::Completed;
    }
}
