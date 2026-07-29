<?php

namespace App\Actions\Deposit;

use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Models\Booking;
use App\Models\Deposit;
use App\Models\Penyewa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenDeposit
{
    /**
     * Hold an initial deposit for a penyewa and open its ledger with a matching
     * hold transaction. Optionally tied to the booking it secures.
     *
     * @throws ValidationException when the amount is invalid
     */
    public function handle(
        Penyewa $penyewa,
        int $amount,
        ?Booking $booking = null,
        ?User $actor = null,
        ?string $notes = null,
    ): Deposit {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal deposit harus lebih dari nol.',
            ]);
        }

        return DB::transaction(function () use ($penyewa, $amount, $booking, $actor, $notes): Deposit {
            $deposit = Deposit::create([
                'tenant_id' => $penyewa->tenant_id,
                'branch_id' => $penyewa->branch_id,
                'penyewa_id' => $penyewa->id,
                'booking_id' => $booking?->id,
                'amount' => $amount,
                'balance' => $amount,
                'status' => DepositStatus::Held,
                'held_at' => now()->toDateString(),
                'notes' => $notes,
            ]);

            $deposit->transactions()->create([
                'tenant_id' => $deposit->tenant_id,
                'branch_id' => $deposit->branch_id,
                'recorded_by' => $actor?->id,
                'type' => DepositTransactionType::Hold,
                'amount' => $amount,
                'reason' => 'Setoran deposit',
                'occurred_on' => now()->toDateString(),
            ]);

            return $deposit;
        });
    }
}
