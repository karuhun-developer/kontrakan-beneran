<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class VoidInvoice
{
    /**
     * Cancel an invoice so it no longer expects money. Only invoices without
     * any settled payment may be voided; paid or already-void invoices are
     * rejected.
     *
     * @throws ValidationException
     */
    public function handle(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan sudah dibatalkan.',
            ]);
        }

        if ($invoice->status === InvoiceStatus::Paid || $invoice->paidAmount() > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan yang sudah ada pembayaran tidak bisa dibatalkan.',
            ]);
        }

        $invoice->update([
            'status' => InvoiceStatus::Void,
            'paid_at' => null,
        ]);

        return $invoice;
    }
}
