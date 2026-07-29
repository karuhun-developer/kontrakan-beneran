<?php

namespace App\Models;

use App\Enums\BookingEvent;
use Database\Factories\BookingLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingLog extends Model
{
    /** @use HasFactory<BookingLogFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'event',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'event' => BookingEvent::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
