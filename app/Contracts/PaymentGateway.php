<?php

namespace App\Contracts;

use App\Exceptions\InvalidGatewayNotificationException;
use App\Models\Invoice;
use App\Support\Payments\GatewayCharge;
use App\Support\Payments\GatewayNotification;
use Illuminate\Http\Request;

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
     * Payment methods the payer can pick in-app before checkout. An empty list
     * means the driver has no in-app picker (the payer chooses on the gateway's
     * hosted page), so the UI shows a single "pay online" button.
     *
     * @return list<array{code: string, label: string, type: string}>
     */
    public function availableMethods(): array;

    /**
     * Create a charge for the invoice and return the hand-off details (redirect
     * URL and/or client token) the payer needs to complete payment. The optional
     * method code is one of availableMethods()'s codes when the payer picked one.
     */
    public function createCharge(Invoice $invoice, string $reference, int $amount, ?string $methodCode = null): GatewayCharge;

    /**
     * Decode and verify a webhook request into a normalized notification. The
     * whole request is passed so a driver can read the parsed body, raw body, or
     * headers as its signature scheme requires.
     *
     * @throws InvalidGatewayNotificationException when the payload is untrusted
     */
    public function parseNotification(Request $request): GatewayNotification;
}
