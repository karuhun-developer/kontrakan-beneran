<?php

namespace App\Models;

use App\Enums\CashflowType;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\CashflowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cashflow extends Model
{
    /** @use HasFactory<CashflowFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'cashflow_category_id',
        'recorded_by',
        'type',
        'amount',
        'description',
        'occurred_on',
        'source_type',
        'source_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashflowType::class,
            'amount' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<CashflowCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CashflowCategory::class, 'cashflow_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * The originating record (e.g. a Payment) for auto-generated entries.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Cashflows of a given direction.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, CashflowType $type): Builder
    {
        return $query->where($this->qualifyColumn('type'), $type->value);
    }
}
