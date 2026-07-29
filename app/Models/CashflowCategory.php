<?php

namespace App\Models;

use App\Enums\CashflowType;
use App\Enums\CommonStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\CashflowCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashflowCategory extends Model
{
    /** @use HasFactory<CashflowCategoryFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'type',
        'is_system',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashflowType::class,
            'is_system' => 'boolean',
            'status' => CommonStatus::class,
        ];
    }

    /**
     * @return HasMany<Cashflow, $this>
     */
    public function cashflows(): HasMany
    {
        return $this->hasMany(Cashflow::class);
    }

    /**
     * Categories of a given cashflow direction.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, CashflowType $type): Builder
    {
        return $query->where($this->qualifyColumn('type'), $type->value);
    }
}
