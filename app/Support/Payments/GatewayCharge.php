<?php

namespace App\Support\Payments;

/**
 * A charge created at the gateway, ready to hand off to the payer.
 */
final class GatewayCharge
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $reference,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $token = null,
        public readonly array $raw = [],
    ) {}
}
