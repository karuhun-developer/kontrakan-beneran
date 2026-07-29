<?php

namespace App\Http\Controllers\Laundry;

use App\Actions\Laundry\CreateLaundryOrder;
use App\Enums\CommonStatus;
use App\Enums\LaundryPaymentSource;
use App\Enums\LaundryStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Laundry\LaundryOrderRequest;
use App\Models\LaundryOrder;
use App\Models\Penyewa;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class LaundryController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $status = $request->query('status');

        $query = LaundryOrder::forBranch($branch->id)
            ->with('penyewa:id,name');

        if (is_string($status) && LaundryStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $orders = $query
            ->latest('ordered_on')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LaundryOrder $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'penyewa' => $order->penyewa?->name ?? '—',
                'weight' => $order->weight !== null ? (float) $order->weight : null,
                'price' => $order->price,
                'ordered_on' => $order->ordered_on->toDateString(),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_source_label' => $order->payment_source->label(),
                'payment_source_color' => $order->payment_source->color(),
            ]);

        return Inertia::render('laundry/index', [
            'orders' => $orders,
            'stats' => $this->buildStats($branch->id),
            'filters' => [
                'status' => is_string($status) ? $status : null,
            ],
            'statusOptions' => array_map(
                fn (LaundryStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
                LaundryStatus::cases(),
            ),
        ]);
    }

    public function create(): Modal
    {
        $branch = $this->currentBranch();

        return Inertia::modal('laundry/form', [
            'penyewaOptions' => $this->penyewaOptions($branch->id),
            'paymentSources' => array_map(
                fn (LaundryPaymentSource $case): array => ['value' => $case->value, 'label' => $case->label()],
                LaundryPaymentSource::cases(),
            ),
        ])->baseRoute('laundry.index');
    }

    public function store(LaundryOrderRequest $request, CreateLaundryOrder $action): RedirectResponse
    {
        $branch = $this->currentBranch();
        $validated = $request->validated();

        $penyewa = Penyewa::forBranch($branch->id)->findOrFail((int) $validated['penyewa_id']);

        $action->handle(
            penyewa: $penyewa,
            price: (int) $validated['price'],
            source: LaundryPaymentSource::from($validated['payment_source']),
            weight: isset($validated['weight']) ? (float) $validated['weight'] : null,
            actor: $request->user(),
            notes: $validated['notes'] ?? null,
        );

        return to_route('laundry.index')->with('success', 'Order laundry berhasil dibuat.');
    }

    public function show(LaundryOrder $laundry): Modal
    {
        $order = $this->findOrder($laundry->getKey());
        $order->load([
            'penyewa:id,name,phone',
            'recordedBy:id,name',
            'invoice:id,number,status',
        ]);

        return Inertia::modal('laundry/detail', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'penyewa' => $order->penyewa?->name ?? '—',
                'penyewa_phone' => $order->penyewa?->phone,
                'weight' => $order->weight !== null ? (float) $order->weight : null,
                'price' => $order->price,
                'ordered_on' => $order->ordered_on->toDateString(),
                'notes' => $order->notes,
                'recorded_by' => $order->recordedBy?->name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_source' => $order->payment_source->value,
                'payment_source_label' => $order->payment_source->label(),
                'payment_source_color' => $order->payment_source->color(),
                'invoice_number' => $order->invoice?->number,
                'next_status_label' => $order->status->next()?->label(),
                'can_advance' => $order->status->next() !== null,
            ],
        ])->baseRoute('laundry.index');
    }

    public function advance(LaundryOrder $laundry): RedirectResponse
    {
        $order = $this->findOrder($laundry->getKey());
        $next = $order->status->next();

        if ($next === null) {
            return back()->with('error', 'Status laundry sudah final.');
        }

        $order->update(['status' => $next]);

        return back()->with('success', "Status laundry diperbarui ke {$next->label()}.");
    }

    /**
     * Resolve a laundry order within the active branch or abort with 404.
     */
    private function findOrder(int $orderId): LaundryOrder
    {
        return LaundryOrder::forBranch($this->currentBranch()->id)->findOrFail($orderId);
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
     * Headline laundry figures for the current branch.
     *
     * @return array<string, int>
     */
    private function buildStats(int $branchId): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();

        return [
            'open_orders' => LaundryOrder::forBranch($branchId)->open()->count(),
            'ready_orders' => LaundryOrder::forBranch($branchId)
                ->where('status', LaundryStatus::Ready->value)
                ->count(),
            'billed_this_month' => (int) LaundryOrder::forBranch($branchId)
                ->where('ordered_on', '>=', $monthStart->toDateString())
                ->sum('price'),
        ];
    }
}
