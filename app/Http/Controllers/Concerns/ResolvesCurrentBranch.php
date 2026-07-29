<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Support\BranchContext;
use Symfony\Component\HttpFoundation\Response;

trait ResolvesCurrentBranch
{
    /**
     * The active branch, or abort if none is selected.
     */
    protected function currentBranch(): Branch
    {
        $branch = app(BranchContext::class)->current();

        abort_if(
            $branch === null,
            Response::HTTP_CONFLICT,
            'Tidak ada cabang aktif. Pilih cabang terlebih dahulu.',
        );

        return $branch;
    }

    /**
     * Ensure the given model belongs to the active branch, else 404.
     */
    protected function authorizeBranch(Branch $branch, int $modelBranchId): void
    {
        abort_unless($modelBranchId === $branch->id, Response::HTTP_NOT_FOUND);
    }
}
