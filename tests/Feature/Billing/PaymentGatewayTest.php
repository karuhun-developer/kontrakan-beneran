<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\InitiateGatewayPayment;
use App\Actions\Billing\SettleGatewayPayment;
use App\Contracts\PaymentGateway;
use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidGatewayNotificationException;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\MidtransGateway;
use App\Services\Payments\NullGateway;
use App\Services\Payments\PaymentGatewayManager;
use App\Support\Payments\GatewayNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

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
    }

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
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

    public function test_the_container_resolves_the_configured_driver()
    {
        config()->set('payment.default', 'null');
        $this->assertInstanceOf(NullGateway::class, app(PaymentGateway::class));

        app()->forgetInstance(PaymentGateway::class);
        app()->forgetInstance(PaymentGatewayManager::class);

        config()->set('payment.default', 'midtrans');
        $this->assertInstanceOf(MidtransGateway::class, app(PaymentGateway::class));
    }

    public function test_initiating_a_charge_opens_a_pending_payment()
    {
        config()->set('payment.default', 'null');

        $invoice = $this->invoice(1_000_000);

        $charge = app(InitiateGatewayPayment::class)->handle($invoice);

        $this->assertNotNull($charge->redirectUrl);

        $payment = Payment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(PaymentMethod::Midtrans, $payment->method);
        $this->assertSame($charge->reference, $payment->reference);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->refresh()->status);
    }

    public function test_settling_a_notification_completes_payment_and_records_cash()
    {
        $invoice = $this->invoice(1_000_000);
        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'penyewa_id' => $invoice->penyewa_id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Midtrans,
            'status' => PaymentStatus::Pending,
            'reference' => 'KOSTPRO-1',
        ]);

        $settled = app(SettleGatewayPayment::class)->handle(new GatewayNotification(
            reference: 'KOSTPRO-1',
            status: PaymentStatus::Completed,
            amount: 1_000_000,
        ));

        $this->assertNotNull($settled);
        $this->assertSame(PaymentStatus::Completed, $payment->refresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);

        $this->assertDatabaseHas('cashflows', [
            'source_type' => (new Payment)->getMorphClass(),
            'source_id' => $payment->id,
            'type' => CashflowType::Income->value,
            'amount' => 1_000_000,
        ]);
    }

    public function test_settling_is_idempotent()
    {
        $invoice = $this->invoice(1_000_000);
        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Midtrans,
            'status' => PaymentStatus::Pending,
            'reference' => 'KOSTPRO-1',
        ]);

        $notification = new GatewayNotification('KOSTPRO-1', PaymentStatus::Completed, 1_000_000);

        app(SettleGatewayPayment::class)->handle($notification);
        app(SettleGatewayPayment::class)->handle($notification);

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(1, Cashflow::where('source_id', $payment->id)->count());
    }

    public function test_unknown_reference_settles_nothing()
    {
        $settled = app(SettleGatewayPayment::class)->handle(
            new GatewayNotification('KOSTPRO-999', PaymentStatus::Completed, 1_000_000),
        );

        $this->assertNull($settled);
    }

    public function test_webhook_endpoint_settles_a_pending_payment()
    {
        config()->set('payment.default', 'null');

        $invoice = $this->invoice(1_000_000);
        $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'amount' => 1_000_000,
            'method' => PaymentMethod::Midtrans,
            'status' => PaymentStatus::Pending,
            'reference' => 'KOSTPRO-1',
        ]);

        $this->postJson(route('webhooks.payment'), [
            'order_id' => 'KOSTPRO-1',
            'transaction_status' => 'settlement',
            'gross_amount' => '1000000.00',
        ])->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_midtrans_rejects_a_forged_signature()
    {
        $gateway = new MidtransGateway(['server_key' => 'secret-key']);

        $this->expectException(InvalidGatewayNotificationException::class);

        $gateway->parseNotification([
            'order_id' => 'KOSTPRO-1',
            'status_code' => '200',
            'gross_amount' => '1000000.00',
            'signature_key' => 'forged',
            'transaction_status' => 'settlement',
        ]);
    }

    public function test_midtrans_accepts_a_valid_signature()
    {
        $serverKey = 'secret-key';
        $gateway = new MidtransGateway(['server_key' => $serverKey]);

        $signature = hash('sha512', 'KOSTPRO-1'.'200'.'1000000.00'.$serverKey);

        $notification = $gateway->parseNotification([
            'order_id' => 'KOSTPRO-1',
            'status_code' => '200',
            'gross_amount' => '1000000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ]);

        $this->assertSame('KOSTPRO-1', $notification->reference);
        $this->assertSame(PaymentStatus::Completed, $notification->status);
        $this->assertSame(1_000_000, $notification->amount);
    }

    public function test_staff_can_checkout_an_invoice_online()
    {
        config()->set('payment.default', 'null');
        $this->actingAsRole(UserRole::Staff);

        $invoice = $this->invoice(1_000_000);

        $this->post(route('invoices.checkout', $invoice))->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'method' => PaymentMethod::Midtrans->value,
            'status' => PaymentStatus::Pending->value,
        ]);
    }
}
