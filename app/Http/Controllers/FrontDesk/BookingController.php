<?php

namespace App\Http\Controllers\FrontDesk;

use App\Actions\Booking\CancelBooking;
use App\Actions\Booking\CheckInBooking;
use App\Actions\Booking\CheckOutBooking;
use App\Actions\Booking\CreateBookingWithBilling;
use App\Actions\Booking\ExtendBooking;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontDesk\BookingRequest;
use App\Models\Booking;
use App\Models\BookingLog;
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

        $mapRoom = function (Room $room) use ($bookingsByRoom, $monthStart, $windowEndExclusive, $daysInMonth): array {
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
        };

        $groups = Room::forBranch($branch->id)
            ->with('roomType:id,name')
            ->orderBy('number')
            ->get()
            ->groupBy(fn (Room $room): string => $room->roomType?->name ?? 'Tanpa Tipe')
            ->map(fn ($rooms, string $typeName): array => [
                'name' => $typeName,
                'rooms' => $rooms->map($mapRoom)->values(),
            ])
            ->sortKeys()
            ->values();

        return [
            'month' => $monthStart->format('Y-m'),
            'month_label' => $monthStart->locale('id')->isoFormat('MMMM YYYY'),
            'days_in_month' => $daysInMonth,
            'prev_month' => $monthStart->subMonth()->format('Y-m'),
            'next_month' => $windowEndExclusive->format('Y-m'),
            'days' => $days,
            'groups' => $groups,
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

    public function store(BookingRequest $request, CreateBookingWithBilling $createBooking): RedirectResponse
    {
        $branch = $this->currentBranch();

        $room = Room::forBranch($branch->id)->findOrFail($request->validated('room_id'));
        $penyewa = Penyewa::forBranch($branch->id)->findOrFail($request->validated('penyewa_id'));

        $payNow = $request->boolean('pay_now');
        $payMethod = $request->validated('pay_method');

        $createBooking->handle(
            penyewa: $penyewa,
            room: $room,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            status: BookingStatus::from($request->validated('status') ?? BookingStatus::Confirmed->value),
            notes: $request->validated('notes'),
            actor: $request->user(),
            generateInvoice: $request->boolean('generate_invoice') || $payNow,
            paymentAmount: $payNow ? (int) $request->validated('pay_amount') : null,
            paymentMethod: $payNow && $payMethod !== null ? PaymentMethod::from($payMethod) : null,
        );

        return to_route('bookings.index')->with('success', 'Booking berhasil dibuat.');
    }

    public function show(Booking $booking): Modal
    {
        $booking = $this->findInBranch($booking->getKey());
        $booking->load(['room:id,number,name', 'penyewa:id,name,phone', 'logs.user:id,name']);

        return Inertia::modal('bookings/detail', [
            'booking' => [
                'id' => $booking->id,
                'room' => $booking->room ? trim("{$booking->room->number} {$booking->room->name}") : '—',
                'penyewa' => $booking->penyewa?->name ?? '—',
                'penyewa_phone' => $booking->penyewa?->phone,
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
                'monthly_price' => $booking->monthly_price,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
                'notes' => $booking->notes,
                'checked_in_at' => $booking->checked_in_at?->toDateTimeString(),
                'checked_out_at' => $booking->checked_out_at?->toDateTimeString(),
                'can_check_in' => $booking->status->canCheckIn(),
                'can_check_out' => $booking->status->canCheckOut(),
                'can_cancel' => $booking->status->canCancel(),
                'can_extend' => $booking->status->canExtend(),
                'logs' => $booking->logs
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn (BookingLog $log): array => [
                        'id' => $log->id,
                        'event' => $log->event->value,
                        'event_label' => $log->event->label(),
                        'description' => $log->description,
                        'user' => $log->user?->name,
                        'created_at' => $log->created_at?->toDateTimeString(),
                    ]),
            ],
        ])->baseRoute('bookings.index');
    }

    public function checkIn(Request $request, Booking $booking, CheckInBooking $action): RedirectResponse
    {
        $action->handle($this->findInBranch($booking->getKey()), $request->user());

        return back()->with('success', 'Penyewa berhasil check-in.');
    }

    public function checkOut(Request $request, Booking $booking, CheckOutBooking $action): RedirectResponse
    {
        $action->handle($this->findInBranch($booking->getKey()), $request->user());

        return back()->with('success', 'Penyewa berhasil check-out.');
    }

    public function cancel(Request $request, Booking $booking, CancelBooking $action): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action->handle($this->findInBranch($booking->getKey()), $validated['reason'] ?? null, $request->user());

        return back()->with('success', 'Booking dibatalkan.');
    }

    public function extend(Request $request, Booking $booking, ExtendBooking $action): RedirectResponse
    {
        $validated = $request->validate([
            'end_date' => ['required', 'date'],
        ], [], ['end_date' => 'tanggal perpanjangan']);

        $action->handle($this->findInBranch($booking->getKey()), $validated['end_date'], $request->user());

        return back()->with('success', 'Sewa berhasil diperpanjang.');
    }

    /**
     * Resolve a booking within the active branch or abort with 404.
     */
    private function findInBranch(int $bookingId): Booking
    {
        return Booking::forBranch($this->currentBranch()->id)->findOrFail($bookingId);
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
            'paymentMethods' => collect(PaymentMethod::manualCases())
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ]),
        ];
    }
}
