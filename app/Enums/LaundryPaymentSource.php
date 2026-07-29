<?php

namespace App\Enums;

enum LaundryPaymentSource: string
{
    case Deposit = 'deposit';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Potong Deposit',
            self::Invoice => 'Masuk Tagihan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Deposit => 'blue',
            self::Invoice => 'yellow',
        };
    }
}
