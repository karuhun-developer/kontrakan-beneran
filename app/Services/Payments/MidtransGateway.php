<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidGatewayNotificationException;
use App\Models\Invoice;
use App\Support\Payments\GatewayCharge;
use App\Support\Payments\GatewayNotification;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransGateway implements PaymentGateway
{
    /**
     * @param  array{server_key?: string, client_key?: string, is_production?: bool}  $config
     */
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'midtrans';
    }

    public function availableMethods(): array
    {
        // Midtrans hosts its own method picker on the Snap page.
        return [];
    }

    public function createCharge(Invoice $invoice, string $reference, int $amount, ?string $methodCode = null): GatewayCharge
    {
        $this->configure();

        $invoice->loadMissing('penyewa:id,name,email,phone');

        $params = [
            'transaction_details' => [
                'order_id' => $reference,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $invoice->penyewa?->name ?? 'Penyewa',
                'email' => $invoice->penyewa?->email,
                'phone' => $invoice->penyewa?->phone,
            ],
            'item_details' => [[
                'id' => (string) $invoice->id,
                'price' => $amount,
                'quantity' => 1,
                'name' => "Tagihan {$invoice->number}",
            ]],
        ];

        $snap = Snap::createTransaction($params);

        return new GatewayCharge(
            reference: $reference,
            redirectUrl: $snap->redirect_url ?? null,
            token: $snap->token ?? null,
            raw: (array) $snap,
        );
    }

    public function parseNotification(Request $request): GatewayNotification
    {
        $payload = $request->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.($this->config['server_key'] ?? ''));

        if (! hash_equals($expected, (string) ($payload['signature_key'] ?? ''))) {
            throw new InvalidGatewayNotificationException('Invalid Midtrans signature.');
        }

        return new GatewayNotification(
            reference: $orderId,
            status: $this->mapStatus(
                (string) ($payload['transaction_status'] ?? ''),
                (string) ($payload['fraud_status'] ?? ''),
            ),
            amount: (int) round((float) $grossAmount),
            raw: $payload,
        );
    }

    private function configure(): void
    {
        Config::$serverKey = $this->config['server_key'] ?? '';
        Config::$clientKey = $this->config['client_key'] ?? '';
        Config::$isProduction = (bool) ($this->config['is_production'] ?? false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function mapStatus(string $status, string $fraud): PaymentStatus
    {
        return match ($status) {
            'capture' => $fraud === 'challenge' ? PaymentStatus::Pending : PaymentStatus::Completed,
            'settlement' => PaymentStatus::Completed,
            'pending' => PaymentStatus::Pending,
            default => PaymentStatus::Failed,
        };
    }
}
