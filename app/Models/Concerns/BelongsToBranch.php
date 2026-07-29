<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Tenant;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared tenant/branch ownership for operational models. Provides the
 * relations plus a query scope to constrain records to a given branch.
 */
trait BelongsToBranch
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Constrain the query to a specific branch id.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where($this->qualifyColumn('branch_id'), $branchId);
    }

    /**
     * Constrain the query to the currently active branch, if any.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentBranch(Builder $query): Builder
    {
        $branchId = app(BranchContext::class)->currentId();

        return $branchId === null
            ? $query->whereRaw('1 = 0')
            : $this->scopeForBranch($query, $branchId);
    }
}
