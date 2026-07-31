<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Support\Payments\GatewayCharge;
use App\Support\Payments\GatewayNotification;
use Illuminate\Http\Request;

/**
 * A driver that performs no real network calls. Used for local development and
 * tests: it echoes back a deterministic charge and trusts webhook payloads.
 */
class NullGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'null';
    }

    public function availableMethods(): array
    {
        return [];
    }

    public function createCharge(Invoice $invoice, string $reference, int $amount, ?string $methodCode = null): GatewayCharge
    {
        return new GatewayCharge(
            reference: $reference,
            redirectUrl: url("/payments/simulate/{$reference}"),
            token: 'null-'.$reference,
            raw: ['order_id' => $reference, 'gross_amount' => $amount],
        );
    }

    public function parseNotification(Request $request): GatewayNotification
    {
        $payload = $request->all();

        return new GatewayNotification(
            reference: (string) ($payload['order_id'] ?? ''),
            status: $this->mapStatus((string) ($payload['transaction_status'] ?? 'settlement')),
            amount: (int) round((float) ($payload['gross_amount'] ?? 0)),
            raw: $payload,
        );
    }

    private function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'settlement', 'capture' => PaymentStatus::Completed,
            'pending' => PaymentStatus::Pending,
            default => PaymentStatus::Failed,
        };
    }
}
