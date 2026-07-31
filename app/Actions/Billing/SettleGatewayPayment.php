<?php

namespace App\Actions\Billing;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\GatewayNotification;
use Illuminate\Support\Facades\DB;

class SettleGatewayPayment
{
    public function __construct(
        private RecordCashflowFromPayment $recordCashflow,
        private PaymentGateway $gateway,
    ) {}

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
            return DB::transaction(function () use ($payment, $notification): Payment {
                $payment->update([
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                    'method' => $this->refineMethod($payment->method, $notification->method),
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

    /**
     * Refine the stored method from the method the payer actually chose at the
     * gateway (reported in the webhook). Keeps the current method when the
     * notification carries no code or it is not in the gateway's catalog.
     */
    private function refineMethod(PaymentMethod $current, ?string $methodCode): PaymentMethod
    {
        if ($methodCode === null) {
            return $current;
        }

        foreach ($this->gateway->availableMethods() as $method) {
            if (($method['code'] ?? null) === $methodCode) {
                return PaymentMethod::fromGatewayType($method['type'] ?? null);
            }
        }

        return $current;
    }
}
