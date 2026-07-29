<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesPenyewa;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class StayController extends Controller
{
    use ResolvesPenyewa;

    public function index(): Response
    {
        $penyewa = $this->currentPenyewa();

        $stays = $penyewa->bookings()
            ->with('room:id,number,name')
            ->latest('start_date')
            ->latest('id')
            ->paginate(12)
            ->through(fn (Booking $booking): array => [
                'id' => $booking->id,
                'room' => $this->roomLabel($booking),
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
                'monthly_price' => $booking->monthly_price,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
            ]);

        $activeRoom = $penyewa->bookings()
            ->active()
            ->with('room:id,number,name')
            ->latest('start_date')
            ->first();

        return Inertia::render('portal/stays/index', [
            'stays' => $stays,
            'summary' => [
                'total' => $penyewa->bookings()->count(),
                'active_room' => $activeRoom ? $this->roomLabel($activeRoom) : null,
            ],
        ]);
    }

    public function show(Booking $stay): Modal
    {
        $stay = $this->findStay($stay->getKey());
        $stay->load([
            'room:id,number,name',
            'logs' => fn ($q) => $q->latest('created_at')->latest('id'),
        ]);

        return Inertia::modal('portal/stays/detail', [
            'stay' => [
                'id' => $stay->id,
                'room' => $this->roomLabel($stay),
                'start_date' => $stay->start_date->toDateString(),
                'end_date' => $stay->end_date->toDateString(),
                'monthly_price' => $stay->monthly_price,
                'status' => $stay->status->value,
                'status_label' => $stay->status->label(),
                'status_color' => $stay->status->color(),
                'checked_in_at' => $stay->checked_in_at?->toDateString(),
                'checked_out_at' => $stay->checked_out_at?->toDateString(),
                'notes' => $stay->notes,
                'logs' => $stay->logs->map(fn (BookingLog $log): array => [
                    'id' => $log->id,
                    'event_label' => $log->event->label(),
                    'description' => $log->description,
                    'occurred_on' => $log->created_at?->toDateString(),
                ]),
            ],
        ])->baseRoute('portal.stays.index');
    }

    /**
     * Resolve a booking owned by the current penyewa or abort with 404.
     */
    private function findStay(int $bookingId): Booking
    {
        return $this->currentPenyewa()->bookings()->findOrFail($bookingId);
    }

    private function roomLabel(Booking $booking): ?string
    {
        return $booking->room
            ? trim("{$booking->room->number} {$booking->room->name}")
            : null;
    }
}
