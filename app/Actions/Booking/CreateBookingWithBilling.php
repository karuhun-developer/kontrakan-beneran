<?php

namespace App\Actions\Booking;

use App\Actions\Billing\GenerateInvoiceForBooking;
use App\Actions\Billing\RecordPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBookingWithBilling
{
    public function __construct(
        private CreateBooking $createBooking,
        private GenerateInvoiceForBooking $generateInvoice,
        private RecordPayment $recordPayment,
    ) {}

    /**
     * Create a booking and, in the same transaction, optionally generate the
     * first-month invoice and settle an up-front payment (walk-in flow). The
     * whole chain is atomic: a payment failure rolls back the booking too.
     *
     * @throws ValidationException
     */
    public function handle(
        Penyewa $penyewa,
        Room $room,
        string $startDate,
        string $endDate,
        BookingStatus $status,
        ?string $notes,
        ?User $actor,
        bool $generateInvoice = false,
        ?int $paymentAmount = null,
        ?PaymentMethod $paymentMethod = null,
    ): Booking {
        $wantsPayment = $paymentAmount !== null && $paymentAmount > 0;

        return DB::transaction(function () use (
            $penyewa, $room, $startDate, $endDate, $status, $notes, $actor,
            $generateInvoice, $wantsPayment, $paymentAmount, $paymentMethod,
        ): Booking {
            $booking = $this->createBooking->handle(
                penyewa: $penyewa,
                room: $room,
                startDate: $startDate,
                endDate: $endDate,
                status: $status,
                notes: $notes,
                actor: $actor,
            );

            if (! $generateInvoice && ! $wantsPayment) {
                return $booking;
            }

            // First invoice covers the rental month starting at the booking's
            // start date.
            $invoice = $this->generateInvoice->handle(
                $booking,
                CarbonImmutable::parse($startDate)->toDateString(),
            );

            if ($wantsPayment) {
                if ($paymentMethod === null) {
                    throw ValidationException::withMessages([
                        'pay_method' => 'Metode pembayaran wajib dipilih.',
                    ]);
                }

                try {
                    $this->recordPayment->handle(
                        invoice: $invoice,
                        amount: $paymentAmount,
                        method: $paymentMethod,
                        actor: $actor,
                    );
                } catch (ValidationException $e) {
                    // Surface RecordPayment guards on the booking form's field.
                    throw ValidationException::withMessages([
                        'pay_amount' => $e->validator->errors()->all(),
                    ]);
                }
            }

            return $booking;
        });
    }
}
