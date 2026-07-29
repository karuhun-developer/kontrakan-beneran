<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Partial => 'Sebagian',
            self::Paid => 'Lunas',
            self::Overdue => 'Jatuh Tempo',
            self::Void => 'Batal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'yellow',
            self::Partial => 'blue',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Void => 'gray',
        };
    }

    /**
     * Whether the invoice still expects money (can receive payments).
     */
    public function isOutstanding(): bool
    {
        return match ($this) {
            self::Unpaid, self::Partial, self::Overdue => true,
            self::Paid, self::Void => false,
        };
    }

    /**
     * Statuses that still expect money.
     *
     * @return list<self>
     */
    public static function outstanding(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $status->isOutstanding(),
        ));
    }
}
