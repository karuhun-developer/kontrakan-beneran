<?php

namespace App\Support;

use App\Http\Middleware\SetCurrentBranch;
use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * Request-scoped holder for the currently selected branch and the set of
 * branches the authenticated user may operate in. Populated by the
 * {@see SetCurrentBranch} middleware and consumed by
 * Inertia shared props, controllers, and (later) query scopes.
 */
class BranchContext
{
    private ?Branch $current = null;

    /** @var Collection<int, Branch> */
    private Collection $accessible;

    public function __construct()
    {
        $this->accessible = collect();
    }

    /**
     * @param  Collection<int, Branch>  $accessible
     */
    public function set(?Branch $current, Collection $accessible): void
    {
        $this->current = $current;
        $this->accessible = $accessible;
    }

    public function current(): ?Branch
    {
        return $this->current;
    }

    public function currentId(): ?int
    {
        return $this->current?->id;
    }

    /**
     * @return Collection<int, Branch>
     */
    public function accessible(): Collection
    {
        return $this->accessible;
    }
}
