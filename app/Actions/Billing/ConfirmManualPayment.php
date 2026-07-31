<?php

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmManualPayment
{
    public function __construct(private RecordCashflowFromPayment $recordCashflow) {}

    /**
     * Confirm a pending offline payment: settle it, re-sync the invoice status,
     * and mirror it into the cash book. Re-checks the balance at confirm time
     * since other payments may have landed while it was pending.
     *
     * @throws ValidationException when the payment is not pending or no longer fits the balance
     */
    public function handle(Payment $payment, ?User $actor = null): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran ini sudah ditinjau.',
            ]);
        }

        if ($payment->reference !== null) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran ini diselesaikan otomatis oleh gateway.',
            ]);
        }

        $invoice = $payment->invoice;

        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan sudah dibatalkan.',
            ]);
        }

        if ($payment->amount > $invoice->outstandingAmount()) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pembayaran melebihi sisa tagihan.',
            ]);
        }

        return DB::transaction(function () use ($payment, $invoice, $actor): Payment {
            $payment->update([
                'status' => PaymentStatus::Completed,
                'recorded_by' => $actor?->id,
                'paid_at' => now(),
            ]);

            $invoice->refreshPaymentStatus()->save();

            $this->recordCashflow->handle($payment);

            return $payment;
        });
    }
}
