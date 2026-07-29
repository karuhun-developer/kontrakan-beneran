<?php

namespace App\Contracts;

use App\Exceptions\InvalidGatewayNotificationException;
use App\Models\Invoice;
use App\Support\Payments\GatewayCharge;
use App\Support\Payments\GatewayNotification;

/**
 * Contract every online payment provider implements. The billing domain talks
 * only to this interface, so a provider can be swapped via config alone.
 */
interface PaymentGateway
{
    /**
     * The driver's identifier (e.g. "midtrans").
     */
    public function name(): string;

    /**
     * Create a charge for the invoice and return the hand-off details (redirect
     * URL and/or client token) the payer needs to complete payment.
     */
    public function createCharge(Invoice $invoice, string $reference, int $amount): GatewayCharge;

    /**
     * Decode and verify a webhook payload into a normalized notification.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidGatewayNotificationException when the payload is untrusted
     */
    public function parseNotification(array $payload): GatewayNotification;
}
