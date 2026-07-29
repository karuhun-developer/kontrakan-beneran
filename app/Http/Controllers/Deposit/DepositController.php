<?php

namespace App\Http\Controllers\Deposit;

use App\Actions\Deposit\OpenDeposit;
use App\Actions\Deposit\RecordDepositTransaction;
use App\Enums\CommonStatus;
use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deposit\DepositRequest;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use App\Models\Penyewa;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class DepositController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $status = $request->query('status');

        $query = Deposit::forBranch($branch->id)
            ->with('penyewa:id,name');

        if (is_string($status) && DepositStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $deposits = $query
            ->latest('held_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Deposit $deposit): array => [
                'id' => $deposit->id,
                'penyewa' => $deposit->penyewa?->name ?? '—',
                'amount' => $deposit->amount,
                'balance' => $deposit->balance,
                'held_at' => $deposit->held_at->toDateString(),
                'settled_at' => $deposit->settled_at?->toDateString(),
                'status' => $deposit->status->value,
                'status_label' => $deposit->status->label(),
                'status_color' => $deposit->status->color(),
            ]);

        return Inertia::render('deposits/index', [
            'deposits' => $deposits,
            'stats' => $this->buildStats($branch->id),
            'filters' => [
                'status' => is_string($status) ? $status : null,
            ],
            'statusOptions' => array_map(
                fn (DepositStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
                DepositStatus::cases(),
            ),
        ]);
    }

    public function create(): Modal
    {
        $branch = $this->currentBranch();

        return Inertia::modal('deposits/form', [
            'penyewaOptions' => $this->penyewaOptions($branch->id),
        ])->baseRoute('deposits.index');
    }

    public function store(DepositRequest $request, OpenDeposit $action): RedirectResponse
    {
        $branch = $this->currentBranch();
        $validated = $request->validated();

        $penyewa = Penyewa::forBranch($branch->id)->findOrFail((int) $validated['penyewa_id']);

        $action->handle(
            penyewa: $penyewa,
            amount: (int) $validated['amount'],
            actor: $request->user(),
            notes: $validated['notes'] ?? null,
        );

        return to_route('deposits.index')->with('success', 'Deposit berhasil dibuka.');
    }

    public function show(Deposit $deposit): Modal
    {
        $deposit = $this->findDeposit($deposit->getKey());
        $deposit->load([
            'penyewa:id,name,phone',
            'booking.room:id,number,name',
            'transactions' => fn ($q) => $q->latest('occurred_on')->latest('id'),
            'transactions.recordedBy:id,name',
        ]);

        return Inertia::modal('deposits/detail', [
            'deposit' => [
                'id' => $deposit->id,
                'penyewa' => $deposit->penyewa?->name ?? '—',
                'penyewa_phone' => $deposit->penyewa?->phone,
                'room' => $deposit->booking?->room
                    ? trim("{$deposit->booking->room->number} {$deposit->booking->room->name}")
                    : null,
                'amount' => $deposit->amount,
                'balance' => $deposit->balance,
                'held_at' => $deposit->held_at->toDateString(),
                'settled_at' => $deposit->settled_at?->toDateString(),
                'status' => $deposit->status->value,
                'status_label' => $deposit->status->label(),
                'status_color' => $deposit->status->color(),
                'notes' => $deposit->notes,
                'can_transact' => $deposit->status->isActive() && $deposit->balance > 0,
                'transactions' => $deposit->transactions->map(fn (DepositTransaction $transaction): array => [
                    'id' => $transaction->id,
                    'type' => $transaction->type->value,
                    'type_label' => $transaction->type->label(),
                    'type_color' => $transaction->type->color(),
                    'sign' => $transaction->type->sign(),
                    'amount' => $transaction->amount,
                    'reason' => $transaction->reason,
                    'occurred_on' => $transaction->occurred_on->toDateString(),
                    'recorded_by' => $transaction->recordedBy?->name,
                ]),
            ],
            'transactionTypes' => array_map(
                fn (DepositTransactionType $type): array => ['value' => $type->value, 'label' => $type->label()],
                [DepositTransactionType::Deduction, DepositTransactionType::Refund],
            ),
        ])->baseRoute('deposits.index');
    }

    public function transaction(Request $request, Deposit $deposit, RecordDepositTransaction $action): RedirectResponse
    {
        $deposit = $this->findDeposit($deposit->getKey());

        $validated = $request->validate([
            'type' => ['required', Rule::in([DepositTransactionType::Deduction->value, DepositTransactionType::Refund->value])],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['amount' => 'nominal', 'reason' => 'alasan']);

        $action->handle(
            deposit: $deposit,
            type: DepositTransactionType::from($validated['type']),
            amount: (int) $validated['amount'],
            reason: $validated['reason'] ?? null,
            actor: $request->user(),
            notes: $validated['notes'] ?? null,
        );

        return back()->with('success', 'Transaksi deposit berhasil dicatat.');
    }

    /**
     * Resolve a deposit within the active branch or abort with 404.
     */
    private function findDeposit(int $depositId): Deposit
    {
        return Deposit::forBranch($this->currentBranch()->id)->findOrFail($depositId);
    }

    /**
     * Active penyewa in the branch, as select options.
     *
     * @return list<array{value: int, label: string}>
     */
    private function penyewaOptions(int $branchId): array
    {
        return Penyewa::forBranch($branchId)
            ->where('status', CommonStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Penyewa $penyewa): array => [
                'value' => $penyewa->id,
                'label' => $penyewa->name,
            ])
            ->all();
    }

    /**
     * Headline deposit figures for the current branch.
     *
     * @return array<string, int>
     */
    private function buildStats(int $branchId): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();

        return [
            'held_balance' => (int) Deposit::forBranch($branchId)->active()->sum('balance'),
            'active_count' => Deposit::forBranch($branchId)->active()->count(),
            'refunded_this_month' => (int) DepositTransaction::forBranch($branchId)
                ->where('type', DepositTransactionType::Refund->value)
                ->where('occurred_on', '>=', $monthStart->toDateString())
                ->sum('amount'),
        ];
    }
}
