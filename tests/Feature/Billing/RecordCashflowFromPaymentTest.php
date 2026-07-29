<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\RecordCashflowFromPayment;
use App\Actions\Billing\RecordPayment;
use App\Enums\CashflowType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\CashflowCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordCashflowFromPaymentTest extends TestCase
{
    use RefreshDatabase;

    private RecordCashflowFromPayment $action;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(RecordCashflowFromPayment::class);

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

    public function test_recording_a_payment_creates_an_income_cashflow()
    {
        $invoice = $this->invoice(1_000_000);

        $payment = app(RecordPayment::class)->handle($invoice, 1_000_000, PaymentMethod::Cash);

        $this->assertDatabaseHas('cashflows', [
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->id,
            'type' => CashflowType::Income->value,
            'amount' => 1_000_000,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_the_income_lands_in_a_system_rent_category()
    {
        $invoice = $this->invoice(500_000);

        app(RecordPayment::class)->handle($invoice, 500_000, PaymentMethod::Cash);

        $category = CashflowCategory::query()
            ->forBranch($this->branch->id)
            ->ofType(CashflowType::Income)
            ->first();

        $this->assertNotNull($category);
        $this->assertSame('Pendapatan Sewa', $category->name);
        $this->assertTrue($category->is_system);
        $this->assertSame(1, CashflowCategory::query()->forBranch($this->branch->id)->count());
    }

    public function test_it_is_idempotent_per_payment()
    {
        $invoice = $this->invoice(1_000_000);
        $payment = app(RecordPayment::class)->handle($invoice, 1_000_000, PaymentMethod::Cash);

        $this->action->handle($payment);
        $this->action->handle($payment);

        $this->assertSame(1, Cashflow::query()
            ->where('source_type', $payment->getMorphClass())
            ->where('source_id', $payment->id)
            ->count());
    }

    public function test_a_pending_payment_is_not_mirrored()
    {
        $invoice = $this->invoice(1_000_000);
        $payment = Payment::factory()->create([
            'tenant_id' => $this->branch->tenant_id,
            'branch_id' => $this->branch->id,
            'invoice_id' => $invoice->id,
            'penyewa_id' => $invoice->penyewa_id,
            'amount' => 1_000_000,
            'status' => PaymentStatus::Pending,
        ]);

        $this->assertNull($this->action->handle($payment));
        $this->assertDatabaseCount('cashflows', 0);
    }
}
