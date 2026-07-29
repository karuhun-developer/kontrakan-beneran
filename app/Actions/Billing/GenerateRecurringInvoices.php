<?php

namespace App\Actions\Billing;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;

class GenerateRecurringInvoices
{
    public function __construct(private GenerateInvoiceForBooking $generateInvoice) {}

    /**
     * Generate any due monthly invoices for active bookings. For each booking
     * still running (confirmed or checked-in), this walks its rental months
     * from the start date and generates an invoice for every month that has
     * begun (plus the configured lead window) and has not been billed yet.
     * Idempotent per booking + period, so it is safe to run daily.
     *
     * @return int Number of invoices newly created.
     */
    public function handle(?string $asOf = null): int
    {
        $today = $asOf !== null
            ? CarbonImmutable::parse($asOf)->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        $leadDays = (int) config('billing.invoice_lead_days', 0);
        $threshold = $today->addDays($leadDays);

        $created = 0;

        Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::CheckedIn->value])
            ->with('room:id,number,name')
            ->cursor()
            ->each(function (Booking $booking) use ($threshold, &$created): void {
                $created += $this->generateDueMonths($booking, $threshold);
            });

        return $created;
    }

    /**
     * Generate the not-yet-billed rental months for one booking, up to the
     * lead threshold and bounded by the booking's end date.
     */
    private function generateDueMonths(Booking $booking, CarbonImmutable $threshold): int
    {
        $end = CarbonImmutable::parse($booking->end_date->toDateString());
        $period = CarbonImmutable::parse($booking->start_date->toDateString());
        $created = 0;

        while ($period->lte($threshold) && $period->lt($end)) {
            $invoice = $this->generateInvoice->handle($booking, $period->toDateString());

            if ($invoice->wasRecentlyCreated) {
                $created++;
            }

            $period = $period->addMonth();
        }

        return $created;
    }
}
