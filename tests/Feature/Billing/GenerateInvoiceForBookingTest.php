<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\GenerateInvoiceForBooking;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateInvoiceForBookingTest extends TestCase
{
    use RefreshDatabase;

    private GenerateInvoiceForBooking $action;

    private Branch $branch;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(GenerateInvoiceForBooking::class);

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
        $type = RoomType::factory()->forBranch($this->branch)->create();
        $room = Room::factory()->forBranch($this->branch)->create([
            'room_type_id' => $type->id,
        ]);
        $penyewa = Penyewa::factory()->forBranch($this->branch)->create();

        $this->booking = Booking::factory()
            ->forRoom($room, '2026-08-01', '2026-11-01')
            ->create([
                'penyewa_id' => $penyewa->id,
                'monthly_price' => 1_500_000,
            ]);
    }

    public function test_it_generates_a_rent_invoice_with_snapshot_and_totals()
    {
        $invoice = $this->action->handle($this->booking, '2026-08-01');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'booking_id' => $this->booking->id,
            'penyewa_id' => $this->booking->penyewa_id,
            'branch_id' => $this->branch->id,
            'subtotal' => 1_500_000,
            'total' => 1_500_000,
            'status' => InvoiceStatus::Unpaid->value,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'type' => InvoiceItemType::Rent->value,
            'amount' => 1_500_000,
        ]);

        $this->assertSame('2026-08-01', $invoice->period_start->toDateString());
        $this->assertSame('2026-09-01', $invoice->period_end->toDateString());
        $this->assertSame('2026-08-08', $invoice->due_date->toDateString());
    }

    public function test_it_numbers_invoices_sequentially_per_month()
    {
        $first = $this->action->handle($this->booking, '2026-08-01');
        $second = $this->action->handle($this->booking, '2026-09-01');

        $this->assertSame('INV-202608-0001', $first->number);
        $this->assertSame('INV-202609-0001', $second->number);
    }

    public function test_it_is_idempotent_for_the_same_booking_and_period()
    {
        $first = $this->action->handle($this->booking, '2026-08-01');
        $again = $this->action->handle($this->booking, '2026-08-01');

        $this->assertSame($first->id, $again->id);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
    }

    public function test_totals_apply_discount_and_fine()
    {
        $invoice = $this->action->handle($this->booking, '2026-08-01');

        $invoice->update(['discount' => 200_000, 'fine' => 50_000]);
        $invoice->recalculateTotals()->save();

        $this->assertSame(1_350_000, $invoice->fresh()->total);
    }
}
