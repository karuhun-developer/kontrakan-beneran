<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
        'capacity',
        'monthly_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'monthly_price' => 'integer',
            'status' => CommonStatus::class,
        ];
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
