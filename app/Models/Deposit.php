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
     * Recompute the running balance and status from the ledger, treating the
     * stored held amount as authoritative and subtracting deductions/refunds.
     * Does not persist.
     */
    public function refreshBalance(): static
    {
        $removed = $this->transactions
            ->whereIn('type', [DepositTransactionType::Deduction, DepositTransactionType::Refund])
            ->sum('amount');

        $this->balance = max(0, $this->amount - $removed);

        if ($this->balance <= 0) {
            $this->status = DepositStatus::Refunded;
            $this->settled_at ??= now()->toDateString();
        } elseif ($this->balance < $this->amount) {
            $this->status = DepositStatus::Partial;
            $this->settled_at = null;
        } else {
            $this->status = DepositStatus::Held;
            $this->settled_at = null;
        }

        return $this;
    }
}
