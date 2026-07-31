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

    /**
     * Map an online gateway's method type (e.g. Paywuz "qris" / "virtual_account")
     * to a stored method. Unknown or absent types fall back to Midtrans so the
     * existing single-gateway behavior is preserved.
     */
    public static function fromGatewayType(?string $type): self
    {
        return match (strtolower((string) $type)) {
            'qris' => self::Qris,
            'va', 'virtual_account', 'virtualaccount' => self::VirtualAccount,
            default => self::Midtrans,
        };
    }

    /**
     * Methods a staff member can record by hand.
     *
     * @return list<self>
     */
    public static function manualCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $method): bool => $method->isManual(),
        ));
    }
}
