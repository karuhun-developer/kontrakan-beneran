<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidGatewayNotificationException;
use App\Exceptions\PaywuzException;
use App\Models\Invoice;
use App\Support\Payments\GatewayCharge;
use App\Support\Payments\GatewayNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Paywuz hosted-checkout driver. Creates a transaction, hands the payer off to
 * Paywuz's redirect URL, and settles later via a signed webhook. QRIS and each
 * Virtual Account bank are just distinct method codes fetched from Paywuz.
 */
class PaywuzGateway implements PaymentGateway
{
    /**
     * @param  array{base_url?: string, key?: string, webhook_secret?: string, expiry_minutes?: int, methods_cache_ttl?: int}  $config
     */
    public function __construct(private PaywuzClient $client, private array $config) {}

    public function name(): string
    {
        return 'paywuz';
    }

    public function availableMethods(): array
    {
        $ttl = (int) ($this->config['methods_cache_ttl'] ?? 3600);

        try {
            $methods = Cache::remember(
                'payment.paywuz.methods',
                $ttl,
                fn (): array => $this->client->paymentMethods(),
            );
        } catch (PaywuzException) {
            // Degrade gracefully: no in-app picker, payer chooses on hosted page.
            return [];
        }

        return array_values(array_map(
            fn (array $method): array => [
                'code' => (string) ($method['code'] ?? ''),
                'label' => (string) ($method['name'] ?? $method['code'] ?? ''),
                'type' => strtolower((string) ($method['type'] ?? '')),
            ],
            $methods,
        ));
    }

    public function createCharge(Invoice $invoice, string $reference, int $amount, ?string $methodCode = null): GatewayCharge
    {
        $data = $this->client->createTransaction(array_filter([
            'orderId' => $reference,
            'amount' => $amount,
            'paymentMethod' => $methodCode,
            'expiryMinutes' => (int) ($this->config['expiry_minutes'] ?? 60),
            'redirectUrl' => route('portal.invoices.index'),
            'metadata' => [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
            ],
        ], fn ($value): bool => $value !== null));

        return new GatewayCharge(
            reference: $reference,
            redirectUrl: $data['paymentUrl'] ?? null,
            token: $data['id'] ?? null,
            raw: $data,
        );
    }

    public function parseNotification(Request $request): GatewayNotification
    {
        $raw = (string) $request->getContent();
        $secret = (string) ($this->config['webhook_secret'] ?? '');
        $expected = 'sha256='.hash_hmac('sha256', $raw, $secret);
        $signature = (string) $request->header('X-Paywuz-Signature', '');

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            throw new InvalidGatewayNotificationException('Invalid Paywuz signature.');
        }

        /** @var array<string, mixed> $data */
        $data = $request->json('data', []);

        return new GatewayNotification(
            reference: (string) ($data['orderId'] ?? ''),
            status: $this->mapStatus((string) $request->header('X-Paywuz-Event', '')),
            amount: (int) round((float) ($data['amount'] ?? 0)),
            raw: $request->all(),
            method: isset($data['paymentMethod']) ? (string) $data['paymentMethod'] : null,
        );
    }

    private function mapStatus(string $event): PaymentStatus
    {
        return match ($event) {
            'transaction.paid', 'transaction.settlement' => PaymentStatus::Completed,
            'transaction.expired', 'transaction.cancelled' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }
}
