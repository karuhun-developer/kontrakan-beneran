<?php

namespace App\Actions\Billing;

use App\Contracts\PaymentGateway;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Payments\GatewayCharge;
use Illuminate\Validation\ValidationException;
use Throwable;

class InitiateGatewayPayment
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Open a pending payment for the invoice's outstanding balance and create a
     * charge at the gateway. The payment is settled later via webhook. The
     * optional method code is a payer-selected code from availableMethods().
     *
     * @throws ValidationException when the invoice cannot accept payment
     */
    public function handle(Invoice $invoice, ?User $actor = null, ?string $methodCode = null): GatewayCharge
    {
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

        $amount = $invoice->outstandingAmount();

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'invoice' => 'Tidak ada sisa tagihan yang harus dibayar.',
            ]);
        }

        $payment = $invoice->payments()->create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'penyewa_id' => $invoice->penyewa_id,
            'recorded_by' => $actor?->id,
            'amount' => $amount,
            'method' => $this->resolveMethod($methodCode),
            'status' => PaymentStatus::Pending,
        ]);

        $reference = 'KOSTPRO-'.$payment->getKey();
        $payment->update(['reference' => $reference]);

        try {
            return $this->gateway->createCharge($invoice, $reference, $amount, $methodCode);
        } catch (Throwable $e) {
            $payment->update(['status' => PaymentStatus::Failed]);

            throw $e;
        }
    }

    /**
     * Map a payer-selected method code to a stored PaymentMethod using the active
     * gateway's method catalog. Falls back to the gateway default (Midtrans) when
     * no code was picked or it is not recognised.
     */
    private function resolveMethod(?string $methodCode): PaymentMethod
    {
        if ($methodCode === null) {
            return PaymentMethod::Midtrans;
        }

        foreach ($this->gateway->availableMethods() as $method) {
            if (($method['code'] ?? null) === $methodCode) {
                return PaymentMethod::fromGatewayType($method['type'] ?? null);
            }
        }

        return PaymentMethod::Midtrans;
    }
}
