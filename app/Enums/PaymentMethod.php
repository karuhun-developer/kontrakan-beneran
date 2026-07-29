<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qris = 'qris';
    case VirtualAccount = 'virtual_account';
    case Midtrans = 'midtrans';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer Bank',
            self::Qris => 'QRIS',
            self::VirtualAccount => 'Virtual Account',
            self::Midtrans => 'Midtrans',
        };
    }

    /**
     * Whether this method is settled manually by staff (vs. an online gateway).
     */
    public function isManual(): bool
    {
        return match ($this) {
            self::Cash, self::Transfer, self::Qris, self::VirtualAccount => true,
            self::Midtrans => false,
        };
    }
}
