<?php

namespace App\Actions\Cashflow;

use App\Enums\CashflowType;
use App\Models\Branch;
use App\Models\Cashflow;
use App\Models\User;

class RecordManualCashflow
{
    /**
     * Record a manually-entered cash movement (income or expense) for a branch.
     * Manual entries carry no polymorphic source, which is what distinguishes
     * them from auto-recorded rows (e.g. payment income) that must not be
     * edited or deleted through the cash book UI.
     */
    public function handle(
        Branch $branch,
        CashflowType $type,
        int $amount,
        string $description,
        string $occurredOn,
        ?int $categoryId = null,
        ?string $notes = null,
        ?User $actor = null,
    ): Cashflow {
        return Cashflow::create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'cashflow_category_id' => $categoryId,
            'recorded_by' => $actor?->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'occurred_on' => $occurredOn,
            'notes' => $notes,
        ]);
    }
}
