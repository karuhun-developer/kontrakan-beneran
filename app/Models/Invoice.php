<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'penyewa_id',
        'booking_id',
        'number',
        'period_start',
        'period_end',
        'due_date',
        'subtotal',
        'discount',
        'fine',
        'total',
        'status',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'fine' => 'integer',
            'total' => 'integer',
            'status' => InvoiceStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Penyewa, $this>
     */
    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(Penyewa::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total amount settled against this invoice.
     */
    public function paidAmount(): int
    {
        return (int) $this->payments()->settled()->sum('amount');
    }

    /**
     * Remaining balance after settled payments (never below zero).
     */
    public function outstandingAmount(): int
    {
        return max(0, $this->total - $this->paidAmount());
    }

    /**
     * Move the invoice status to match its settled payments: paid when fully
     * covered, partial when some money is in, otherwise unpaid (keeping an
     * existing overdue flag). Void invoices are left untouched.
     */
    public function refreshPaymentStatus(): static
    {
        if ($this->status === InvoiceStatus::Void) {
            return $this;
        }

        $paid = $this->paidAmount();

        if ($paid >= $this->total && $this->total > 0) {
            $this->status = InvoiceStatus::Paid;
            $this->paid_at = $this->paid_at ?? now();
        } elseif ($paid > 0) {
            $this->status = InvoiceStatus::Partial;
            $this->paid_at = null;
        } else {
            if ($this->status !== InvoiceStatus::Overdue) {
                $this->status = InvoiceStatus::Unpaid;
            }
            $this->paid_at = null;
        }

        return $this;
    }

    /**
     * Recompute subtotal and total from the line items and current adjustments.
     * total = subtotal - discount + fine (never below zero).
     */
    public function recalculateTotals(): static
    {
        $subtotal = (int) $this->items()->sum('amount');
        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal - $this->discount + $this->fine);

        return $this;
    }

    /**
     * Invoices that still expect money.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::outstanding());
    }
}
