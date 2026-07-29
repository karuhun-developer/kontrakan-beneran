<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesPenyewa;
use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class LaundryController extends Controller
{
    use ResolvesPenyewa;

    public function index(): Response
    {
        $penyewa = $this->currentPenyewa();

        $orders = $penyewa->laundryOrders()
            ->latest('ordered_on')
            ->latest('id')
            ->paginate(12)
            ->through(fn (LaundryOrder $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'weight' => $order->weight,
                'price' => $order->price,
                'payment_source_label' => $order->payment_source->label(),
                'payment_source_color' => $order->payment_source->color(),
                'ordered_on' => $order->ordered_on?->toDateString(),
            ]);

        return Inertia::render('portal/laundry/index', [
            'orders' => $orders,
            'summary' => [
                'open_count' => $penyewa->laundryOrders()->open()->count(),
            ],
        ]);
    }

    public function show(LaundryOrder $laundry): Modal
    {
        $order = $this->findOrder($laundry->getKey());
        $order->load('invoice:id,number');

        return Inertia::modal('portal/laundry/detail', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'weight' => $order->weight,
                'price' => $order->price,
                'payment_source_label' => $order->payment_source->label(),
                'payment_source_color' => $order->payment_source->color(),
                'invoice_number' => $order->invoice?->number,
                'ordered_on' => $order->ordered_on?->toDateString(),
                'notes' => $order->notes,
            ],
        ])->baseRoute('portal.laundry.index');
    }

    /**
     * Resolve a laundry order owned by the current penyewa or abort with 404.
     */
    private function findOrder(int $orderId): LaundryOrder
    {
        return $this->currentPenyewa()->laundryOrders()->findOrFail($orderId);
    }
}
