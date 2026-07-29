<?php

namespace App\Actions\Billing;

use App\Enums\CashflowType;
use App\Enums\CommonStatus;
use App\Models\Cashflow;
use App\Models\CashflowCategory;
use App\Models\Payment;

class RecordCashflowFromPayment
{
    /**
     * Mirror a settled payment into the cash book as income. Idempotent per
     * payment so re-processing never double-counts. Returns null when the
     * payment has not settled.
     */
    public function handle(Payment $payment): ?Cashflow
    {
        if (! $payment->status->isSettled()) {
            return null;
        }

        $existing = Cashflow::query()
            ->where('source_type', $payment->getMorphClass())
            ->where('source_id', $payment->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $payment->loadMissing('invoice:id,number');
        $category = $this->incomeCategory($payment);
        $occurredOn = ($payment->paid_at ?? $payment->created_at ?? now())->toDateString();

        return Cashflow::create([
            'tenant_id' => $payment->tenant_id,
            'branch_id' => $payment->branch_id,
            'cashflow_category_id' => $category->id,
            'recorded_by' => $payment->recorded_by,
            'type' => CashflowType::Income,
            'amount' => $payment->amount,
            'description' => $payment->invoice?->number !== null
                ? "Pembayaran {$payment->invoice->number}"
                : 'Pembayaran tagihan',
            'occurred_on' => $occurredOn,
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->getKey(),
        ]);
    }

    /**
     * The per-branch system income category for rent payments.
     */
    private function incomeCategory(Payment $payment): CashflowCategory
    {
        return CashflowCategory::firstOrCreate(
            [
                'branch_id' => $payment->branch_id,
                'type' => CashflowType::Income->value,
                'name' => 'Pendapatan Sewa',
            ],
            [
                'tenant_id' => $payment->tenant_id,
                'is_system' => true,
                'status' => CommonStatus::Active->value,
            ],
        );
    }
}
