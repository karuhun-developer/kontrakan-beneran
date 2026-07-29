<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Penyewa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $penyewa = $request->user()->penyewa()->with('branch')->first();

        return Inertia::render('portal/dashboard', [
            'penyewa' => $penyewa ? [
                'id' => $penyewa->id,
                'name' => $penyewa->name,
                'branch' => $penyewa->branch?->name,
            ] : null,
            'summary' => $penyewa ? $this->buildSummary($penyewa) : null,
        ]);
    }

    /**
     * Headline figures for the penyewa's dashboard cards.
     *
     * @return array{room: ?string, outstanding_count: int, outstanding_value: int, deposit_balance: int}
     */
    private function buildSummary(Penyewa $penyewa): array
    {
        $activeBooking = $penyewa->bookings()
            ->active()
            ->with('room:id,number,name')
            ->latest('start_date')
            ->first();

        $outstanding = $penyewa->invoices()->outstanding()->with('payments')->get();

        return [
            'room' => $activeBooking?->room
                ? trim("{$activeBooking->room->number} {$activeBooking->room->name}")
                : null,
            'outstanding_count' => $outstanding->count(),
            'outstanding_value' => (int) $outstanding->sum(fn ($invoice) => $invoice->outstandingAmount()),
            'deposit_balance' => (int) $penyewa->deposits()->active()->sum('balance'),
        ];
    }
}
