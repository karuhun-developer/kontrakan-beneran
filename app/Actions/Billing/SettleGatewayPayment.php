<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\GatewayNotification;
use Illuminate\Support\Facades\DB;

class SettleGatewayPayment
{
    public function __construct(private RecordCashflowFromPayment $recordCashflow) {}

    /**
     * Apply a gateway notification to its pending payment. Idempotent: a payment
     * that has already settled is returned untouched. Returns null when no
     * payment matches the notification reference.
     */
    public function handle(GatewayNotification $notification): ?Payment
    {
        $payment = Payment::where('reference', $notification->reference)->first();

        if ($payment === null) {
            return null;
        }

        if ($payment->status === PaymentStatus::Completed) {
            return $payment;
        }

        if ($notification->status === PaymentStatus::Completed) {
            return DB::transaction(function () use ($payment): Payment {
                $payment->update([
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                ]);

                $payment->loadMissing('invoice');
                $payment->invoice->refreshPaymentStatus()->save();

                $this->recordCashflow->handle($payment);

                return $payment;
            });
        }

        if ($notification->status === PaymentStatus::Failed) {
            $payment->update(['status' => PaymentStatus::Failed]);
        }

        return $payment;
    }
}
