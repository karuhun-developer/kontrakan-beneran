<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\InitiateGatewayPayment;
use App\Actions\Billing\SettleGatewayPayment;
use App\Contracts\PaymentGateway;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaywuzClient;
use App\Services\Payments\PaywuzGateway;
use App\Support\Payments\GatewayNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaywuzGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const CONFIG = [
        'base_url' => 'https://api.paywuz.test/v1',
        'key' => 'pk_test_key',
        'webhook_secret' => 'whsec_test',
        'expiry_minutes' => 60,
        'methods_cache_ttl' => 3600,
    ];

    private Tenant $tenant;

    private Branch $branch;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create();

        Cache::flush();
    }

    private function usePaywuz(): void
    {
        config()->set('payment.default', 'paywuz');
        config()->set('payment.gateways.paywuz', self::CONFIG);

        app()->forgetInstance(PaymentGateway::class);
        app()->forgetInstance(PaymentGatewayManager::class);
    }

    private function gateway(): PaywuzGateway
    {
        return new PaywuzGateway(new PaywuzClient(self::CONFIG), self::CONFIG);
    }

    private function invoice(int $total = 1_000_000): Invoice
    {
        return Invoice::factory()->forBranch($this->branch)->create([
            'penyewa_id' => $this->penyewa->id,
            'subtotal' => $total,
            'total' => $total,
            'status' => InvoiceStatus::Unpaid,
        ]);
    }

    public function test_the_container_resolves_the_paywuz_driver()
    {
        $this->usePaywuz();

        $this->assertInstanceOf(PaywuzGateway::class, app(PaymentGateway::class));
    }

    public function test_available_methods_are_mapped_and_cached()
    {
        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'qris_id', 'name' => 'QRIS', 'type' => 'QRIS'],
                ['code' => 'va_bca', 'name' => 'BCA Virtual Account', 'type' => 'virtual_account'],
            ]]),
        ]);

        $gateway = $this->gateway();

        $methods = $gateway->availableMethods();
        $gateway->availableMethods();

        $this->assertSame([
            ['code' => 'qris_id', 'label' => 'QRIS', 'type' => 'qris'],
            ['code' => 'va_bca', 'label' => 'BCA Virtual Account', 'type' => 'virtual_account'],
        ], $methods);

        Http::assertSentCount(1);
    }

    public function test_available_methods_degrade_to_empty_on_failure()
    {
        Http::fake(['*/payment-methods' => Http::response('boom', 500)]);

        $this->assertSame([], $this->gateway()->availableMethods());
    }

    public function test_create_charge_forwards_the_selected_method_code()
    {
        Http::fake([
            '*/transactions' => Http::response(['data' => [
                'id' => 'PW-REF-1',
                'orderId' => 'KOSTPRO-1',
                'paymentUrl' => 'https://pay.paywuz.test/checkout/PW-REF-1',
                'status' => 'pending',
            ]]),
        ]);

        $invoice = $this->invoice();
        $charge = $this->gateway()->createCharge($invoice, 'KOSTPRO-1', 1_000_000, 'qris_id');

        $this->assertSame('https://pay.paywuz.test/checkout/PW-REF-1', $charge->redirectUrl);
        $this->assertSame('PW-REF-1', $charge->token);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/transactions')
            && $request['orderId'] === 'KOSTPRO-1'
            && $request['amount'] === 1_000_000
            && $request['paymentMethod'] === 'qris_id');
    }

    public function test_initiating_a_charge_stores_the_selected_method()
    {
        $this->usePaywuz();

        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'qris_id', 'name' => 'QRIS', 'type' => 'qris'],
            ]]),
            '*/transactions' => Http::response(['data' => [
                'id' => 'PW-REF-1',
                'paymentUrl' => 'https://pay.paywuz.test/checkout/PW-REF-1',
            ]]),
        ]);

        $invoice = $this->invoice();
        app(InitiateGatewayPayment::class)->handle($invoice, null, 'qris_id');

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(PaymentMethod::Qris, $payment->method);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNotNull($payment->reference);
    }

    public function test_webhook_settles_a_payment_with_a_valid_signature()
    {
        $this->usePaywuz();

        $invoice = $this->invoice();
        $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Qris,
            'status' => PaymentStatus::Pending,
            'reference' => 'KOSTPRO-1',
        ]);

        $raw = json_encode(['data' => ['orderId' => 'KOSTPRO-1', 'amount' => 1_000_000]]);
        $signature = 'sha256='.hash_hmac('sha256', $raw, self::CONFIG['webhook_secret']);

        $this->call('POST', route('webhooks.payment'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYWUZ_SIGNATURE' => $signature,
            'HTTP_X_PAYWUZ_EVENT' => 'transaction.paid',
        ], $raw)->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_webhook_rejects_a_forged_signature()
    {
        $this->usePaywuz();

        $raw = json_encode(['data' => ['orderId' => 'KOSTPRO-1']]);

        $this->call('POST', route('webhooks.payment'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256=forged',
            'HTTP_X_PAYWUZ_EVENT' => 'transaction.paid',
        ], $raw)->assertStatus(403);
    }

    public function test_notification_maps_terminal_events()
    {
        $this->usePaywuz();

        foreach ([
            'transaction.paid' => PaymentStatus::Completed,
            'transaction.settlement' => PaymentStatus::Completed,
            'transaction.expired' => PaymentStatus::Failed,
            'transaction.cancelled' => PaymentStatus::Failed,
        ] as $event => $expected) {
            $raw = json_encode(['data' => ['orderId' => 'KOSTPRO-1']]);
            $signature = 'sha256='.hash_hmac('sha256', $raw, self::CONFIG['webhook_secret']);

            $request = Request::create(
                '/webhooks/payment',
                'POST',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYWUZ_SIGNATURE' => $signature, 'HTTP_X_PAYWUZ_EVENT' => $event],
                $raw,
            );

            $this->assertSame($expected, $this->gateway()->parseNotification($request)->status);
        }
    }

    public function test_settle_refines_the_method_from_the_webhook()
    {
        $this->usePaywuz();

        Http::fake([
            '*/payment-methods' => Http::response(['data' => [
                ['code' => 'va_bca', 'name' => 'BCA Virtual Account', 'type' => 'virtual_account'],
            ]]),
        ]);

        $invoice = $this->invoice();
        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Midtrans,
            'status' => PaymentStatus::Pending,
            'reference' => 'KOSTPRO-1',
        ]);

        app(SettleGatewayPayment::class)->handle(new GatewayNotification(
            reference: 'KOSTPRO-1',
            status: PaymentStatus::Completed,
            amount: 1_000_000,
            method: 'va_bca',
        ));

        $this->assertSame(PaymentMethod::VirtualAccount, $payment->refresh()->method);
    }
}
