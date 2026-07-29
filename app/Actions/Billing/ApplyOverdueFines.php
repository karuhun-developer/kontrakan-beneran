<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

class ApplyOverdueFines
{
    /**
     * Flag outstanding invoices past their due date as overdue and, once, add a
     * late fee. The fee is written to the invoice's dedicated `fine` column (so
     * it stays a separate line from the rent subtotal) and is applied only when
     * no fee has been charged yet, keeping repeated daily runs idempotent.
     *
     * @return int Number of invoices moved to overdue.
     */
    public function handle(?string $asOf = null): int
    {
        $today = $asOf !== null
            ? CarbonImmutable::parse($asOf)->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        $lateFee = config('billing.late_fee');

        $overdue = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
            ->whereDate('due_date', '<', $today->toDateString())
            ->cursor()
            ->each(function (Invoice $invoice) use ($lateFee, &$overdue): void {
                if (($lateFee['enabled'] ?? false) && $invoice->fine === 0) {
                    $invoice->fine = $this->computeFee($invoice, $lateFee);
                    $invoice->total = max(0, $invoice->subtotal - $invoice->discount + $invoice->fine);
                }

                $invoice->status = InvoiceStatus::Overdue;
                $invoice->save();

                $overdue++;
            });

        return $overdue;
    }

    /**
     * @param  array{type?: string, value?: int}  $lateFee
     */
    private function computeFee(Invoice $invoice, array $lateFee): int
    {
        $value = (int) ($lateFee['value'] ?? 0);

        if (($lateFee['type'] ?? 'flat') === 'percent') {
            return (int) round($invoice->subtotal * $value / 100);
        }

        return $value;
    }
}
