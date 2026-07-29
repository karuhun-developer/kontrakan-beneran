<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class SubmitManualPayment
{
    /**
     * Record a penyewa-submitted offline transfer as a pending payment awaiting
     * staff confirmation. The amount does not reduce the invoice balance until
     * confirmed. Proof media is attached by the caller.
     *
     * @throws ValidationException when the invoice cannot accept payment, the amount is invalid, or one is already pending
     */
    public function handle(
        Invoice $invoice,
        int $amount,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan sudah dibatalkan.',
            ]);
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan sudah lunas.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pembayaran harus lebih dari nol.',
            ]);
        }

        if ($amount > $invoice->outstandingAmount()) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pembayaran melebihi sisa tagihan.',
            ]);
        }

        $hasPending = $invoice->payments()
            ->where('status', PaymentStatus::Pending)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'invoice' => 'Masih ada konfirmasi pembayaran yang menunggu ditinjau.',
            ]);
        }

        return $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'penyewa_id' => $invoice->penyewa_id,
            'recorded_by' => null,
            'amount' => $amount,
            'method' => PaymentMethod::Transfer,
            'status' => PaymentStatus::Pending,
            'reference' => $reference,
            'paid_at' => null,
            'notes' => $notes,
        ]);
    }
}
