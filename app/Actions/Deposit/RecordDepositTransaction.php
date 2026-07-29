<?php

namespace App\Actions\Deposit;

use App\Enums\DepositTransactionType;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordDepositTransaction
{
    /**
     * Record a deduction or refund against a deposit, then re-sync its balance
     * and status. The amount may not exceed the remaining balance. An optional
     * source model (e.g. a laundry order) links the ledger entry to its origin.
     *
     * @throws ValidationException when the type, amount, or balance is invalid
     */
    public function handle(
        Deposit $deposit,
        DepositTransactionType $type,
        int $amount,
        ?string $reason = null,
        ?User $actor = null,
        ?Model $source = null,
        ?string $notes = null,
    ): DepositTransaction {
        if ($type === DepositTransactionType::Hold) {
            throw ValidationException::withMessages([
                'type' => 'Gunakan OpenDeposit untuk menahan dana baru.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal transaksi harus lebih dari nol.',
            ]);
        }

        if ($amount > $deposit->balance) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal melebihi saldo deposit.',
            ]);
        }

        return DB::transaction(function () use ($deposit, $type, $amount, $reason, $actor, $source, $notes): DepositTransaction {
            $transaction = $deposit->transactions()->create([
                'tenant_id' => $deposit->tenant_id,
                'branch_id' => $deposit->branch_id,
                'recorded_by' => $actor?->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'occurred_on' => now()->toDateString(),
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'notes' => $notes,
            ]);

            $deposit->load('transactions');
            $deposit->refreshBalance()->save();

            return $transaction;
        });
    }
}
