<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Rent = 'rent';
    case Deposit = 'deposit';
    case Laundry = 'laundry';
    case Fine = 'fine';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Sewa',
            self::Deposit => 'Deposit',
            self::Laundry => 'Laundry',
            self::Fine => 'Denda',
            self::Other => 'Lainnya',
        };
    }
}
