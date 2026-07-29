<?php

namespace App\Enums;

enum CashflowType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Income => 'green',
            self::Expense => 'red',
        };
    }

    /**
     * Signed multiplier for balance math: income adds, expense subtracts.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Income => 1,
            self::Expense => -1,
        };
    }
}
