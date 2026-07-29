<?php

namespace Tests\Feature\Laundry;

use App\Actions\Deposit\OpenDeposit;
use App\Actions\Laundry\CreateLaundryOrder;
use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\LaundryPaymentSource;
use App\Enums\LaundryStatus;
use App\Models\Branch;
use App\Models\LaundryOrder;
use App\Models\Penyewa;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LaundryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function penyewa(): Penyewa
    {
        return Penyewa::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_deposit_paid_order_deducts_the_balance_and_links_the_ledger()
    {
        $penyewa = $this->penyewa();
        $deposit = app(OpenDeposit::class)->handle($penyewa, 500_000);

        $order = app(CreateLaundryOrder::class)->handle(
            penyewa: $penyewa,
            price: 35_000,
            source: LaundryPaymentSource::Deposit,
            weight: 5.0,
        );

        $this->assertSame(LaundryStatus::Received, $order->status);
        $this->assertNotNull($order->deposit_transaction_id);
        $this->assertNull($order->invoice_id);

        $deposit->refresh();
        $this->assertSame(465_000, $deposit->balance);
        $this->assertSame(DepositStatus::Partial, $deposit->status);

        $this->assertDatabaseHas('deposit_transactions', [
            'id' => $order->deposit_transaction_id,
            'type' => DepositTransactionType::Deduction->value,
            'amount' => 35_000,
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
        ]);
    }

    public function test_deposit_paid_order_is_rejected_when_the_balance_is_insufficient()
    {
        $penyewa = $this->penyewa();
        app(OpenDeposit::class)->handle($penyewa, 20_000);

        $this->expectException(ValidationException::class);

        app(CreateLaundryOrder::class)->handle(
            penyewa: $penyewa,
            price: 35_000,
            source: LaundryPaymentSource::Deposit,
        );
    }

    public function test_deposit_paid_order_is_rejected_when_there_is_no_active_deposit()
    {
        $this->expectException(ValidationException::class);

        app(CreateLaundryOrder::class)->handle(
            penyewa: $this->penyewa(),
            price: 35_000,
            source: LaundryPaymentSource::Deposit,
        );
    }

    public function test_deposit_rejection_does_not_persist_the_order()
    {
        $penyewa = $this->penyewa();

        try {
            app(CreateLaundryOrder::class)->handle(
                penyewa: $penyewa,
                price: 35_000,
                source: LaundryPaymentSource::Deposit,
            );
        } catch (ValidationException) {
            // expected
        }

        $this->assertDatabaseCount('laundry_orders', 0);
    }

    public function test_invoice_paid_order_raises_an_invoice_with_a_laundry_item()
    {
        $penyewa = $this->penyewa();

        $order = app(CreateLaundryOrder::class)->handle(
            penyewa: $penyewa,
            price: 40_000,
            source: LaundryPaymentSource::Invoice,
            weight: 6.0,
        );

        $this->assertNull($order->deposit_transaction_id);
        $this->assertNotNull($order->invoice_id);

        $this->assertDatabaseHas('invoices', [
            'id' => $order->invoice_id,
            'penyewa_id' => $penyewa->id,
            'branch_id' => $this->branch->id,
            'total' => 40_000,
            'status' => InvoiceStatus::Unpaid->value,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $order->invoice_id,
            'type' => InvoiceItemType::Laundry->value,
            'amount' => 40_000,
        ]);
    }

    public function test_a_non_positive_price_is_rejected()
    {
        $this->expectException(ValidationException::class);

        app(CreateLaundryOrder::class)->handle(
            penyewa: $this->penyewa(),
            price: 0,
            source: LaundryPaymentSource::Invoice,
        );
    }

    public function test_order_numbers_are_sequential_per_tenant()
    {
        $penyewa = $this->penyewa();

        $first = app(CreateLaundryOrder::class)->handle(
            penyewa: $penyewa,
            price: 10_000,
            source: LaundryPaymentSource::Invoice,
        );
        $second = app(CreateLaundryOrder::class)->handle(
            penyewa: $penyewa,
            price: 10_000,
            source: LaundryPaymentSource::Invoice,
        );

        $this->assertNotSame($first->number, $second->number);
        $this->assertStringStartsWith('LDR-', $first->number);
        $this->assertStringEndsWith('-0001', $first->number);
        $this->assertStringEndsWith('-0002', $second->number);
    }

    public function test_open_scope_excludes_delivered_and_cancelled_orders()
    {
        LaundryOrder::factory()->forBranch($this->branch)->create();
        LaundryOrder::factory()->forBranch($this->branch)->status(LaundryStatus::Delivered)->create();
        LaundryOrder::factory()->forBranch($this->branch)->status(LaundryStatus::Cancelled)->create();

        $this->assertCount(1, LaundryOrder::query()->open()->get());
    }
}
