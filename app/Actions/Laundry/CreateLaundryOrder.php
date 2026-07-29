<?php

namespace App\Actions\Laundry;

use App\Actions\Deposit\RecordDepositTransaction;
use App\Enums\DepositTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\LaundryPaymentSource;
use App\Enums\LaundryStatus;
use App\Models\Deposit;
use App\Models\Invoice;
use App\Models\LaundryOrder;
use App\Models\Penyewa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLaundryOrder
{
    public function __construct(private RecordDepositTransaction $recordDepositTransaction) {}

    /**
     * Record a laundry order and route its charge: either deduct the penyewa's
     * active deposit, or raise a standalone invoice they settle themselves.
     *
     * @throws ValidationException when the price, deposit, or balance is invalid
     */
    public function handle(
        Penyewa $penyewa,
        int $price,
        LaundryPaymentSource $source,
        ?float $weight = null,
        ?User $actor = null,
        ?string $notes = null,
    ): LaundryOrder {
        if ($price <= 0) {
            throw ValidationException::withMessages([
                'price' => 'Harga laundry harus lebih dari nol.',
            ]);
        }

        return DB::transaction(function () use ($penyewa, $price, $source, $weight, $actor, $notes): LaundryOrder {
            $order = LaundryOrder::create([
                'tenant_id' => $penyewa->tenant_id,
                'branch_id' => $penyewa->branch_id,
                'penyewa_id' => $penyewa->id,
                'recorded_by' => $actor?->id,
                'number' => $this->nextNumber($penyewa->tenant_id),
                'status' => LaundryStatus::Received,
                'payment_source' => $source,
                'weight' => $weight,
                'price' => $price,
                'ordered_on' => now()->toDateString(),
                'notes' => $notes,
            ]);

            if ($source === LaundryPaymentSource::Deposit) {
                $this->chargeDeposit($order, $penyewa, $price, $actor);
            } else {
                $this->raiseInvoice($order, $penyewa, $price);
            }

            $order->save();

            return $order;
        });
    }

    /**
     * Deduct the order's price from the penyewa's active deposit and link the
     * resulting ledger entry back to the order.
     *
     * @throws ValidationException when no funded deposit covers the price
     */
    private function chargeDeposit(LaundryOrder $order, Penyewa $penyewa, int $price, ?User $actor): void
    {
        $deposit = Deposit::query()
            ->where('penyewa_id', $penyewa->id)
            ->active()
            ->orderByDesc('balance')
            ->first();

        if ($deposit === null || $deposit->balance < $price) {
            throw ValidationException::withMessages([
                'payment_source' => 'Saldo deposit penyewa tidak mencukupi untuk laundry ini.',
            ]);
        }

        $transaction = $this->recordDepositTransaction->handle(
            deposit: $deposit,
            type: DepositTransactionType::Deduction,
            amount: $price,
            reason: "Laundry {$order->number}",
            actor: $actor,
            source: $order,
        );

        $order->deposit_transaction_id = $transaction->id;
    }

    /**
     * Raise a standalone invoice with a single laundry line item and link it to
     * the order.
     */
    private function raiseInvoice(LaundryOrder $order, Penyewa $penyewa, int $price): void
    {
        $today = CarbonImmutable::parse($order->ordered_on)->startOfDay();

        $invoice = Invoice::create([
            'tenant_id' => $penyewa->tenant_id,
            'branch_id' => $penyewa->branch_id,
            'penyewa_id' => $penyewa->id,
            'number' => $this->nextInvoiceNumber($penyewa->tenant_id, $today),
            'period_start' => $today->toDateString(),
            'period_end' => $today->toDateString(),
            'due_date' => $today->addDays(7)->toDateString(),
            'status' => InvoiceStatus::Unpaid,
        ]);

        $invoice->items()->create([
            'type' => InvoiceItemType::Laundry,
            'description' => "Laundry {$order->number}",
            'quantity' => 1,
            'unit_price' => $price,
            'amount' => $price,
        ]);

        $invoice->recalculateTotals()->save();

        $order->invoice_id = $invoice->id;
    }

    /**
     * Next per-tenant laundry order number for the current month: LDR-YYYYMM-0001.
     */
    private function nextNumber(int $tenantId): string
    {
        $prefix = 'LDR-'.now()->format('Ym').'-';

        $count = LaundryOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('number', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Next per-tenant invoice number for the period's month: INV-YYYYMM-0001.
     */
    private function nextInvoiceNumber(int $tenantId, CarbonImmutable $period): string
    {
        $prefix = 'INV-'.$period->format('Ym').'-';

        $count = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('number', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
