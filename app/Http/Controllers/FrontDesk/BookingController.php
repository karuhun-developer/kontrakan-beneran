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
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class BookingController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
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
            'calendar' => $this->buildCalendar($branch, $request->query('month')),
        ]);
    }

    /**
     * Build the room-timeline (Gantt) payload for a given month window.
     *
     * @return array<string, mixed>
     */
    private function buildCalendar(Branch $branch, ?string $month): array
    {
        $monthStart = $this->resolveMonth($month);
        $windowEndExclusive = $monthStart->addMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $days = [];
        for ($offset = 0; $offset < $daysInMonth; $offset++) {
            $date = $monthStart->addDays($offset);
            $days[] = [
                'day' => $date->day,
                'weekday' => $date->locale('id')->isoFormat('dd'),
                'is_weekend' => $date->isWeekend(),
                'is_today' => $date->isToday(),
            ];
        }

        $bookingsByRoom = Booking::forBranch($branch->id)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->whereDate('start_date', '<', $windowEndExclusive->toDateString())
            ->whereDate('end_date', '>', $monthStart->toDateString())
            ->with('penyewa:id,name')
            ->get()
            ->groupBy('room_id');

        $rooms = Room::forBranch($branch->id)
            ->orderBy('number')
            ->get(['id', 'number', 'name'])
            ->map(function (Room $room) use ($bookingsByRoom, $monthStart, $windowEndExclusive, $daysInMonth): array {
                $bars = ($bookingsByRoom->get($room->id) ?? collect())
                    ->map(function (Booking $booking) use ($monthStart, $windowEndExclusive, $daysInMonth): array {
                        $start = $booking->start_date->max($monthStart);
                        $endExclusive = $booking->end_date->min($windowEndExclusive);
                        $startIndex = $start->day - 1;
                        $span = max(1, min((int) $start->diffInDays($endExclusive), $daysInMonth - $startIndex));

                        return [
                            'id' => $booking->id,
                            'penyewa' => $booking->penyewa?->name ?? '—',
                            'start_index' => $startIndex,
                            'span' => $span,
                            'status' => $booking->status->value,
                            'status_label' => $booking->status->label(),
                            'status_color' => $booking->status->color(),
                            'start_date' => $booking->start_date->toDateString(),
                            'end_date' => $booking->end_date->toDateString(),
                            'continues_before' => $booking->start_date->lt($monthStart),
                            'continues_after' => $booking->end_date->gt($windowEndExclusive),
                        ];
                    })
                    ->sortBy('start_index')
                    ->values();

                return [
                    'id' => $room->id,
                    'number' => $room->number,
                    'name' => $room->name,
                    'bars' => $bars,
                ];
            });

        return [
            'month' => $monthStart->format('Y-m'),
            'month_label' => $monthStart->locale('id')->isoFormat('MMMM YYYY'),
            'days_in_month' => $daysInMonth,
            'prev_month' => $monthStart->subMonth()->format('Y-m'),
            'next_month' => $windowEndExclusive->format('Y-m'),
            'days' => $days,
            'rooms' => $rooms,
        ];
    }

    private function resolveMonth(?string $month): CarbonImmutable
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return CarbonImmutable::createFromFormat('Y-m-d', "{$month}-01")->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
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
