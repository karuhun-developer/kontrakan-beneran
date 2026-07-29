<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'room_id',
        'penyewa_id',
        'start_date',
        'end_date',
        'monthly_price',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_price' => 'integer',
            'status' => BookingStatus::class,
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<Penyewa, $this>
     */
    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(Penyewa::class);
    }

    /**
     * @return HasMany<BookingLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BookingLog::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Only bookings whose status holds the room (blocks new overlapping bookings).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::blocking());
    }

    /**
     * Bookings for a given room.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForRoom(Builder $query, int $roomId): Builder
    {
        return $query->where($this->qualifyColumn('room_id'), $roomId);
    }

    /**
     * Bookings whose date range overlaps the half-open interval [start, end).
     * Adjacent ranges (one ends the day another starts) do not overlap.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query
            ->whereDate($this->qualifyColumn('start_date'), '<', $end)
            ->whereDate($this->qualifyColumn('end_date'), '>', $start);
    }
}
