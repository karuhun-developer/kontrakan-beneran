<?php

namespace App\Models;

use App\Enums\DepositTransactionType;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\DepositTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DepositTransaction extends Model
{
    /** @use HasFactory<DepositTransactionFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'deposit_id',
        'recorded_by',
        'type',
        'amount',
        'reason',
        'occurred_on',
        'source_type',
        'source_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => DepositTransactionType::class,
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Deposit, $this>
     */
    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
