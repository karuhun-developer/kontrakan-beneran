<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\RecordPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordPaymentTest extends TestCase
{
    use RefreshDatabase;

    private RecordPayment $action;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(RecordPayment::class);

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function invoice(int $total): Invoice
    {
        return Invoice::factory()->forBranch($this->branch)->create([
            'subtotal' => $total,
            'total' => $total,
            'status' => InvoiceStatus::Unpaid,
        ]);
    }

    public function test_a_full_payment_marks_the_invoice_paid()
    {
        $invoice = $this->invoice(1_000_000);

        $payment = $this->action->handle($invoice, 1_000_000, PaymentMethod::Cash);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame(0, $invoice->outstandingAmount());
    }

    public function test_a_partial_payment_marks_the_invoice_partial()
    {
        $invoice = $this->invoice(1_000_000);

        $this->action->handle($invoice, 400_000, PaymentMethod::Transfer);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Partial, $invoice->status);
        $this->assertSame(600_000, $invoice->outstandingAmount());
    }

    public function test_two_partials_settle_the_invoice()
    {
        $invoice = $this->invoice(1_000_000);

        $this->action->handle($invoice, 600_000, PaymentMethod::Cash);
        $this->action->handle($invoice->fresh(), 400_000, PaymentMethod::Cash);

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_it_rejects_an_amount_over_the_outstanding_balance()
    {
        $invoice = $this->invoice(1_000_000);

        $this->expectException(ValidationException::class);

        $this->action->handle($invoice, 1_200_000, PaymentMethod::Cash);
    }

    public function test_it_rejects_payment_on_a_void_invoice()
    {
        $invoice = $this->invoice(1_000_000);
        $invoice->update(['status' => InvoiceStatus::Void]);

        $this->expectException(ValidationException::class);

        $this->action->handle($invoice, 500_000, PaymentMethod::Cash);
    }
}
