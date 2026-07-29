<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Billing\InitiateGatewayPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Concerns\ResolvesPenyewa;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    use ResolvesPenyewa;

    public function index(Request $request): Response
    {
        $penyewa = $this->currentPenyewa();
        $status = $request->query('status');

        $query = $penyewa->invoices()
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->where('status', PaymentStatus::Completed->value)], 'amount');

        if (is_string($status) && InvoiceStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $invoices = $query
            ->latest('period_start')
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(function (Invoice $invoice): array {
                $paid = (int) ($invoice->paid_amount ?? 0);

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'period_start' => $invoice->period_start->toDateString(),
                    'period_end' => $invoice->period_end->toDateString(),
                    'due_date' => $invoice->due_date->toDateString(),
                    'total' => $invoice->total,
                    'outstanding' => max(0, $invoice->total - $paid),
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'status_color' => $invoice->status->color(),
                ];
            });

        return Inertia::render('portal/invoices/index', [
            'invoices' => $invoices,
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
            'booking.room:id,number,name',
            'items',
            'payments' => fn ($q) => $q->latest('paid_at')->latest('id'),
        ]);

        $paid = $invoice->paidAmount();

        return Inertia::modal('portal/invoices/detail', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
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
                'can_pay' => $invoice->status->isOutstanding(),
                'items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                    'id' => $item->id,
                    'type_label' => $item->type->label(),
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                ]),
                'payments' => $invoice->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'method_label' => $payment->method->label(),
                    'status_label' => $payment->status->label(),
                    'status_color' => $payment->status->color(),
                    'paid_at' => $payment->paid_at?->toDateTimeString(),
                ]),
            ],
        ])->baseRoute('portal.invoices.index');
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

    /**
     * Resolve an invoice owned by the current penyewa or abort with 404.
     */
    private function findInvoice(int $invoiceId): Invoice
    {
        return $this->currentPenyewa()->invoices()->findOrFail($invoiceId);
    }
}
