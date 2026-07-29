<?php

namespace Tests\Feature\Billing;

use App\Actions\Billing\GenerateRecurringInvoices;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateRecurringInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function action(): GenerateRecurringInvoices
    {
        return app(GenerateRecurringInvoices::class);
    }

    public function test_it_generates_an_invoice_for_each_started_month()
    {
        $booking = Booking::factory()->forBranch($this->branch)->create([
            'status' => BookingStatus::CheckedIn,
            'start_date' => '2026-05-01',
            'end_date' => '2026-12-01',
            'monthly_price' => 1_000_000,
        ]);

        // May, Jun, Jul have begun by mid-July; Aug is beyond the lead window.
        $created = $this->action()->handle('2026-07-15');

        $this->assertSame(3, $created);
        $this->assertSame(3, Invoice::where('booking_id', $booking->id)->count());
    }

    public function test_it_is_idempotent_across_runs()
    {
        $booking = Booking::factory()->forBranch($this->branch)->create([
            'status' => BookingStatus::CheckedIn,
            'start_date' => '2026-05-01',
            'end_date' => '2026-12-01',
            'monthly_price' => 1_000_000,
        ]);

        $this->action()->handle('2026-07-15');
        $second = $this->action()->handle('2026-07-15');

        $this->assertSame(0, $second);
        $this->assertSame(3, Invoice::where('booking_id', $booking->id)->count());
    }

    public function test_it_skips_bookings_that_no_longer_hold_a_room()
    {
        $booking = Booking::factory()->forBranch($this->branch)->create([
            'status' => BookingStatus::Cancelled,
            'start_date' => '2026-05-01',
            'end_date' => '2026-12-01',
            'monthly_price' => 1_000_000,
        ]);

        $created = $this->action()->handle('2026-07-15');

        $this->assertSame(0, $created);
        $this->assertSame(0, Invoice::where('booking_id', $booking->id)->count());
    }

    public function test_it_does_not_bill_beyond_the_booking_end_date()
    {
        $booking = Booking::factory()->forBranch($this->branch)->create([
            'status' => BookingStatus::CheckedIn,
            'start_date' => '2026-05-01',
            'end_date' => '2026-07-01',
            'monthly_price' => 1_000_000,
        ]);

        // Term is May + June only; asOf far in the future must not over-bill.
        $created = $this->action()->handle('2026-12-31');

        $this->assertSame(2, $created);
    }
}
