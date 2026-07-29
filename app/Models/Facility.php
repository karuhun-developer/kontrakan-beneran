<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommonStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }
}
