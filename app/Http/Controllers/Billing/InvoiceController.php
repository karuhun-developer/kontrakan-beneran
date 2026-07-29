<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\ConfirmManualPayment;
use App\Actions\Billing\GenerateInvoiceForBooking;
use App\Actions\Billing\InitiateGatewayPayment;
use App\Actions\Billing\RecordPayment;
use App\Actions\Billing\VoidInvoice;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();
        $status = $request->query('status');

        $query = Invoice::forBranch($branch->id)
            ->with('penyewa:id,name')
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->where('status', PaymentStatus::Completed->value)], 'amount');

        if (is_string($status) && InvoiceStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $invoices = $query
            ->latest('period_start')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Invoice $invoice): array {
                $paid = (int) ($invoice->paid_amount ?? 0);

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'penyewa' => $invoice->penyewa?->name ?? '—',
                    'period_start' => $invoice->period_start->toDateString(),
                    'period_end' => $invoice->period_end->toDateString(),
                    'due_date' => $invoice->due_date->toDateString(),
                    'total' => $invoice->total,
                    'paid' => $paid,
                    'outstanding' => max(0, $invoice->total - $paid),
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'status_color' => $invoice->status->color(),
                ];
            });

        return Inertia::render('billing/invoices/index', [
            'invoices' => $invoices,
            'stats' => $this->buildStats($branch->id),
            'filters' => [
                'status' => is_string($status) ? $status : null,
            ],
            'statusOptions' => array_map(
                fn (InvoiceStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
                InvoiceStatus::cases(),
            ),
        ]);
    }

    public function show(Invoice $invoice): Modal
    {
        $invoice = $this->findInvoice($invoice->getKey());
        $invoice->load([
            'penyewa:id,name,phone',
            'booking.room:id,number,name',
            'items',
            'payments' => fn ($q) => $q->latest('paid_at')->latest('id'),
            'payments.recordedBy:id,name',
            'payments.media',
        ]);

        $paid = $invoice->paidAmount();

        return Inertia::modal('billing/invoices/detail', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'penyewa' => $invoice->penyewa?->name ?? '—',
                'penyewa_phone' => $invoice->penyewa?->phone,
                'room' => $invoice->booking?->room
                    ? trim("{$invoice->booking->room->number} {$invoice->booking->room->name}")
                    : null,
                'period_start' => $invoice->period_start->toDateString(),
                'period_end' => $invoice->period_end->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'fine' => $invoice->fine,
                'total' => $invoice->total,
                'paid' => $paid,
                'outstanding' => max(0, $invoice->total - $paid),
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'status_color' => $invoice->status->color(),
                'notes' => $invoice->notes,
                'paid_at' => $invoice->paid_at?->toDateTimeString(),
                'can_pay' => $invoice->status->isOutstanding(),
                'can_void' => $invoice->status !== InvoiceStatus::Void
                    && $invoice->status !== InvoiceStatus::Paid
                    && $paid === 0,
                'items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'type_label' => $item->type->label(),
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                ]),
                'payments' => $invoice->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'method' => $payment->method->value,
                    'method_label' => $payment->method->label(),
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'status_color' => $payment->status->color(),
                    'reference' => $payment->reference,
                    'recorded_by' => $payment->recordedBy?->name,
                    'paid_at' => $payment->paid_at?->toDateTimeString(),
                    'proof_url' => $payment->getFirstMediaUrl(Payment::MEDIA_PROOF) ?: null,
                    'can_review' => $payment->status === PaymentStatus::Pending,
                ]),
            ],
            'paymentMethods' => array_map(
                fn (PaymentMethod $method): array => ['value' => $method->value, 'label' => $method->label()],
                PaymentMethod::manualCases(),
            ),
        ])->baseRoute('invoices.index');
    }

    public function pay(Request $request, Invoice $invoice, RecordPayment $action): RedirectResponse
    {
        $invoice = $this->findInvoice($invoice->getKey());

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'image', 'max:4096'],
        ], [], ['amount' => 'jumlah', 'method' => 'metode', 'proof' => 'bukti pembayaran']);

        $payment = $action->handle(
            invoice: $invoice,
            amount: (int) $validated['amount'],
            method: PaymentMethod::from($validated['method']),
            actor: $request->user(),
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        if ($request->hasFile('proof')) {
            $payment->addMediaFromRequest('proof')->toMediaCollection(Payment::MEDIA_PROOF);
        }

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function confirmPayment(Payment $payment, ConfirmManualPayment $action): RedirectResponse
    {
        $payment = $this->findPayment($payment->getKey());

        $action->handle($payment, request()->user());

        return back()->with('success', 'Pembayaran dikonfirmasi.');
    }

    public function rejectPayment(Payment $payment): RedirectResponse
    {
        $payment = $this->findPayment($payment->getKey());

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->with('error', 'Pembayaran ini sudah ditinjau.');
        }

        $payment->update(['status' => PaymentStatus::Failed]);

        return back()->with('success', 'Konfirmasi pembayaran ditolak.');
    }

    public function checkout(Request $request, Invoice $invoice, InitiateGatewayPayment $action): SymfonyResponse
    {
        $invoice = $this->findInvoice($invoice->getKey());

        $charge = $action->handle($invoice, $request->user());

        if ($charge->redirectUrl !== null) {
            return Inertia::location($charge->redirectUrl);
        }

        return back()->with('success', 'Pembayaran online telah dibuat, menunggu konfirmasi.');
    }

    public function void(Invoice $invoice, VoidInvoice $action): RedirectResponse
    {
        $action->handle($this->findInvoice($invoice->getKey()));

        return back()->with('success', 'Tagihan dibatalkan.');
    }

    public function storeFromBooking(Booking $booking, GenerateInvoiceForBooking $action): RedirectResponse
    {
        $branch = $this->currentBranch();
        $booking = Booking::forBranch($branch->id)->findOrFail($booking->getKey());

        $action->handle($booking, CarbonImmutable::now()->startOfMonth()->toDateString());

        return back()->with('success', 'Tagihan bulan ini berhasil dibuat.');
    }

    /**
     * Resolve an invoice within the active branch or abort with 404.
     */
    private function findInvoice(int $invoiceId): Invoice
    {
        return Invoice::forBranch($this->currentBranch()->id)->findOrFail($invoiceId);
    }

    /**
     * Resolve a payment within the active branch or abort with 404.
     */
    private function findPayment(int $paymentId): Payment
    {
        return Payment::forBranch($this->currentBranch()->id)->findOrFail($paymentId);
    }

    /**
     * Headline billing figures for the current branch.
     *
     * @return array<string, int>
     */
    private function buildStats(int $branchId): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();

        $outstandingTotal = (int) Invoice::forBranch($branchId)->outstanding()->sum('total');
        $paidOnOutstanding = (int) Payment::forBranch($branchId)->settled()
            ->whereHas('invoice', fn (Builder $q) => $q->outstanding())
            ->sum('amount');

        return [
            'outstanding_count' => Invoice::forBranch($branchId)->outstanding()->count(),
            'outstanding_value' => max(0, $outstandingTotal - $paidOnOutstanding),
            'overdue_count' => Invoice::forBranch($branchId)->where('status', InvoiceStatus::Overdue->value)->count(),
            'received_this_month' => (int) Payment::forBranch($branchId)->settled()
                ->where('paid_at', '>=', $monthStart)
                ->sum('amount'),
        ];
    }
}
