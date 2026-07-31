<?php

namespace App\Services\Payments;

use App\Exceptions\PaywuzException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Paywuz payment gateway REST API. Uses Laravel's Http
 * client with the project API key as a bearer token. Config is injected so the
 * client stays testable and mirrors how MidtransGateway receives its array.
 */
class PaywuzClient
{
    /**
     * @param  array{base_url?: string, key?: string}  $config
     */
    public function __construct(private array $config) {}

    /**
     * Build a pre-configured pending request scoped to the Paywuz base URL.
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'] ?? '')
            ->withToken($this->config['key'] ?? '')
            ->acceptJson()
            ->asJson();
    }

    /**
     * Create a hosted-checkout transaction and return the transaction payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws PaywuzException
     */
    public function createTransaction(array $payload): array
    {
        $response = $this->http()->post('transactions', $payload);

        if ($response->failed()) {
            throw new PaywuzException('Paywuz createTransaction failed: '.$response->body());
        }

        return $response->json('data', []);
    }

    /**
     * List the payment methods enabled for the project. Each entry carries a
     * `code` (passed back as `paymentMethod` when creating a transaction), a
     * display `name`, `type`, `fee` and `limits`.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws PaywuzException
     */
    public function paymentMethods(): array
    {
        $response = $this->http()->get('payment-methods');

        if ($response->failed()) {
            throw new PaywuzException('Paywuz paymentMethods failed: '.$response->body());
        }

        return $response->json('data', []);
    }

    /**
     * Fetch a transaction by our order id (used to reconcile missed webhooks).
     *
     * @return array<string, mixed>
     *
     * @throws PaywuzException
     */
    public function getTransaction(string $orderId): array
    {
        $response = $this->http()->get('transactions/'.$orderId);

        if ($response->failed()) {
            throw new PaywuzException('Paywuz getTransaction failed: '.$response->body());
        }

        return $response->json('data', []);
    }
}
