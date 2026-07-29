<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\DepositFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    /** @use HasFactory<DepositFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'penyewa_id',
        'booking_id',
        'amount',
        'balance',
        'status',
        'held_at',
        'settled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance' => 'integer',
            'status' => DepositStatus::class,
            'held_at' => 'date',
            'settled_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Penyewa, $this>
     */
    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(Penyewa::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasMany<DepositTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    /**
     * Only deposits that still hold funds.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [DepositStatus::Held->value, DepositStatus::Partial->value]);
    }

    /**
     * Recompute the running balance and status from the ledger. Does not persist.
     */
    public function refreshBalance(): static
    {
        $held = 0;
        $removed = 0;

        foreach ($this->transactions as $transaction) {
            if ($transaction->type === DepositTransactionType::Hold) {
                $held += $transaction->amount;
            } else {
                $removed += $transaction->amount;
            }
        }

        $this->amount = $held;
        $this->balance = max(0, $held - $removed);

        if ($this->balance <= 0) {
            $this->status = DepositStatus::Refunded;
            $this->settled_at ??= now()->toDateString();
        } elseif ($this->balance < $held) {
            $this->status = DepositStatus::Partial;
            $this->settled_at = null;
        } else {
            $this->status = DepositStatus::Held;
            $this->settled_at = null;
        }

        return $this;
    }
}
