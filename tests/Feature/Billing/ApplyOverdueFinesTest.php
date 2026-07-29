<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\ApplyOverdueFines;
use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyOverdueFinesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('billing.late_fee', ['enabled' => true, 'type' => 'percent', 'value' => 5]);

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function action(): ApplyOverdueFines
    {
        return app(ApplyOverdueFines::class);
    }

    public function test_it_marks_a_past_due_invoice_overdue_and_charges_a_fee()
    {
        $invoice = Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'subtotal' => 1_000_000,
            'discount' => 0,
            'fine' => 0,
            'total' => 1_000_000,
            'due_date' => '2026-07-01',
        ]);

        $marked = $this->action()->handle('2026-07-15');

        $this->assertSame(1, $marked);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Overdue, $invoice->status);
        $this->assertSame(50_000, $invoice->fine);
        $this->assertSame(1_050_000, $invoice->total);
    }

    public function test_it_does_not_double_charge_on_repeated_runs()
    {
        $invoice = Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'subtotal' => 1_000_000,
            'discount' => 0,
            'fine' => 0,
            'total' => 1_000_000,
            'due_date' => '2026-07-01',
        ]);

        $this->action()->handle('2026-07-15');
        $this->action()->handle('2026-07-16');

        $invoice->refresh();
        $this->assertSame(50_000, $invoice->fine);
        $this->assertSame(1_050_000, $invoice->total);
    }

    public function test_it_leaves_invoices_that_are_not_yet_due()
    {
        $invoice = Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Unpaid,
            'subtotal' => 1_000_000,
            'total' => 1_000_000,
            'due_date' => '2026-07-20',
        ]);

        $marked = $this->action()->handle('2026-07-15');

        $this->assertSame(0, $marked);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->refresh()->status);
        $this->assertSame(0, $invoice->fine);
    }

    public function test_it_ignores_paid_and_void_invoices()
    {
        Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Paid,
            'due_date' => '2026-07-01',
        ]);
        Invoice::factory()->forBranch($this->branch)->create([
            'status' => InvoiceStatus::Void,
            'due_date' => '2026-07-01',
        ]);

        $marked = $this->action()->handle('2026-07-15');

        $this->assertSame(0, $marked);
    }
}
