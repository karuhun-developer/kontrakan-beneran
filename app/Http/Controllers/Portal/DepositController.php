<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesPenyewa;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class DepositController extends Controller
{
    use ResolvesPenyewa;

    public function index(): Response
    {
        $penyewa = $this->currentPenyewa();

        $deposits = $penyewa->deposits()
            ->latest('held_at')
            ->latest('id')
            ->paginate(12)
            ->through(fn (Deposit $deposit): array => [
                'id' => $deposit->id,
                'amount' => $deposit->amount,
                'balance' => $deposit->balance,
                'status' => $deposit->status->value,
                'status_label' => $deposit->status->label(),
                'status_color' => $deposit->status->color(),
                'held_at' => $deposit->held_at?->toDateString(),
            ]);

        return Inertia::render('portal/deposits/index', [
            'deposits' => $deposits,
            'summary' => [
                'active_balance' => (int) $penyewa->deposits()->active()->sum('balance'),
                'total_held' => (int) $penyewa->deposits()->sum('amount'),
            ],
        ]);
    }

    public function show(Deposit $deposit): Modal
    {
        $deposit = $this->findDeposit($deposit->getKey());
        $deposit->load(['transactions' => fn ($q) => $q->latest('occurred_on')->latest('id')]);

        return Inertia::modal('portal/deposits/detail', [
            'deposit' => [
                'id' => $deposit->id,
                'amount' => $deposit->amount,
                'balance' => $deposit->balance,
                'status' => $deposit->status->value,
                'status_label' => $deposit->status->label(),
                'status_color' => $deposit->status->color(),
                'held_at' => $deposit->held_at?->toDateString(),
                'settled_at' => $deposit->settled_at?->toDateString(),
                'notes' => $deposit->notes,
                'transactions' => $deposit->transactions->map(fn (DepositTransaction $transaction): array => [
                    'id' => $transaction->id,
                    'type_label' => $transaction->type->label(),
                    'type_color' => $transaction->type->color(),
                    'sign' => $transaction->type->sign(),
                    'amount' => $transaction->amount,
                    'reason' => $transaction->reason,
                    'occurred_on' => $transaction->occurred_on?->toDateString(),
                ]),
            ],
        ])->baseRoute('portal.deposits.index');
    }

    /**
     * Resolve a deposit owned by the current penyewa or abort with 404.
     */
    private function findDeposit(int $depositId): Deposit
    {
        return $this->currentPenyewa()->deposits()->findOrFail($depositId);
    }
}
