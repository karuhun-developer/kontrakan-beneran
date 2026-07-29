<?php

namespace App\Models;

use App\Enums\LaundryPaymentSource;
use App\Enums\LaundryStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\LaundryOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaundryOrder extends Model
{
    /** @use HasFactory<LaundryOrderFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'penyewa_id',
        'recorded_by',
        'deposit_transaction_id',
        'invoice_id',
        'number',
        'status',
        'payment_source',
        'weight',
        'price',
        'ordered_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LaundryStatus::class,
            'payment_source' => LaundryPaymentSource::class,
            'weight' => 'decimal:2',
            'price' => 'integer',
            'ordered_on' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<DepositTransaction, $this>
     */
    public function depositTransaction(): BelongsTo
    {
        return $this->belongsTo(DepositTransaction::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Orders that are still in progress (not delivered or cancelled).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            LaundryStatus::Received->value,
            LaundryStatus::Processing->value,
            LaundryStatus::Ready->value,
        ]);
    }
}
