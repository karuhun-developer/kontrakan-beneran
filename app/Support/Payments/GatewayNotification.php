<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

/**
 * A normalized settlement notification decoded from a gateway webhook.
 */
final class GatewayNotification
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $reference,
        public readonly PaymentStatus $status,
        public readonly int $amount,
        public readonly array $raw = [],
        public readonly ?string $method = null,
    ) {}
}
