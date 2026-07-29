<?php

namespace App\Http\Controllers\FrontDesk;

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontDesk\BookingRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class BookingController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $bookings = Booking::forBranch($branch->id)
            ->with(['room:id,number,name', 'penyewa:id,name'])
            ->latest('start_date')
            ->paginate(15)
            ->through(fn (Booking $booking): array => [
                'id' => $booking->id,
                'room' => $booking->room?->number,
                'penyewa' => $booking->penyewa?->name,
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
                'monthly_price' => $booking->monthly_price,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
            ]);

        return Inertia::render('bookings/index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(): Modal
    {
        $branch = $this->currentBranch();

        return Inertia::modal('bookings/form', $this->formOptions($branch))
            ->baseRoute('bookings.index');
    }

    public function store(BookingRequest $request, CreateBooking $createBooking): RedirectResponse
    {
        $branch = $this->currentBranch();

        $room = Room::forBranch($branch->id)->findOrFail($request->validated('room_id'));
        $penyewa = Penyewa::forBranch($branch->id)->findOrFail($request->validated('penyewa_id'));

        $createBooking->handle(
            penyewa: $penyewa,
            room: $room,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            status: BookingStatus::from($request->validated('status') ?? BookingStatus::Confirmed->value),
            notes: $request->validated('notes'),
            actor: $request->user(),
        );

        return to_route('bookings.index')->with('success', 'Booking berhasil dibuat.');
    }

    /**
     * Shared select options for the booking form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Branch $branch): array
    {
        return [
            'roomOptions' => Room::forBranch($branch->id)
                ->with('roomType:id,name,monthly_price')
                ->orderBy('number')
                ->get()
                ->map(fn (Room $room): array => [
                    'value' => $room->id,
                    'label' => $room->name ? "{$room->number} · {$room->name}" : $room->number,
                    'monthly_price' => $room->effective_monthly_price,
                ]),
            'penyewaOptions' => Penyewa::forBranch($branch->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Penyewa $penyewa): array => [
                    'value' => $penyewa->id,
                    'label' => $penyewa->name,
                ]),
            'statusOptions' => collect([
                BookingStatus::Confirmed,
                BookingStatus::CheckedIn,
                BookingStatus::Pending,
            ])->map(fn (BookingStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ];
    }
}
