<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'room_type_id',
        'number',
        'name',
        'floor',
        'description',
        'monthly_price',
        'status',
        'room_status',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'monthly_price' => 'integer',
            'status' => CommonStatus::class,
            'room_status' => RoomStatus::class,
        ];
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsToMany<Facility, $this>
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Effective monthly rent: the room override, or the room type base price.
     *
     * @return Attribute<int, never>
     */
    protected function effectiveMonthlyPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->monthly_price
                ?? $this->roomType?->monthly_price
                ?? 0,
        );
    }
}
