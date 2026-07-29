<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceForBooking
{
    /**
     * Generate a monthly rent invoice for a booking covering one rental month
     * starting at $periodStart. Snapshots the booking's monthly price into a
     * rent line item and computes totals. Idempotent per booking + period so
     * re-running the scheduler never duplicates a month's invoice.
     */
    public function handle(Booking $booking, string $periodStart, ?string $dueDate = null): Invoice
    {
        $start = CarbonImmutable::parse($periodStart)->startOfDay();
        $end = $start->addMonth();
        $due = $dueDate !== null
            ? CarbonImmutable::parse($dueDate)->startOfDay()
            : $start->addDays(7);

        return DB::transaction(function () use ($booking, $start, $end, $due): Invoice {
            $existing = Invoice::query()
                ->where('booking_id', $booking->id)
                ->whereDate('period_start', $start->toDateString())
                ->first();

            if ($existing !== null) {
                return $existing->load('items');
            }

            $booking->loadMissing('room:id,number,name');

            $invoice = Invoice::create([
                'tenant_id' => $booking->tenant_id,
                'branch_id' => $booking->branch_id,
                'penyewa_id' => $booking->penyewa_id,
                'booking_id' => $booking->id,
                'number' => $this->nextNumber($booking->tenant_id, $start),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'due_date' => $due->toDateString(),
                'status' => InvoiceStatus::Unpaid,
            ]);

            $roomNumber = $booking->room?->number;

            $invoice->items()->create([
                'type' => InvoiceItemType::Rent,
                'description' => $roomNumber !== null
                    ? "Sewa kamar {$roomNumber} ({$start->translatedFormat('d M Y')} - {$end->translatedFormat('d M Y')})"
                    : 'Sewa kamar',
                'quantity' => 1,
                'unit_price' => $booking->monthly_price,
                'amount' => $booking->monthly_price,
            ]);

            $invoice->recalculateTotals()->save();

            return $invoice->load('items');
        });
    }

    /**
     * Next per-tenant invoice number for the period's month: INV-YYYYMM-0001.
     */
    private function nextNumber(int $tenantId, CarbonImmutable $period): string
    {
        $prefix = 'INV-'.$period->format('Ym').'-';

        $count = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('number', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
