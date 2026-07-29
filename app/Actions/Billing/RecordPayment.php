<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPayment
{
    public function __construct(private RecordCashflowFromPayment $recordCashflow) {}

    /**
     * Record a settled payment against an invoice and re-sync the invoice
     * status (unpaid → partial → paid). Manual methods settle immediately.
     *
     * @throws ValidationException when the invoice cannot accept payment or the amount is invalid
     */
    public function handle(
        Invoice $invoice,
        int $amount,
        PaymentMethod $method,
        ?User $actor = null,
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

        return DB::transaction(function () use ($invoice, $amount, $method, $actor, $reference, $notes): Payment {
            $payment = $invoice->payments()->create([
                'tenant_id' => $invoice->tenant_id,
                'branch_id' => $invoice->branch_id,
                'penyewa_id' => $invoice->penyewa_id,
                'recorded_by' => $actor?->id,
                'amount' => $amount,
                'method' => $method,
                'status' => PaymentStatus::Completed,
                'reference' => $reference,
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            $invoice->refreshPaymentStatus()->save();

            $this->recordCashflow->handle($payment);

            return $payment;
        });
    }
}
