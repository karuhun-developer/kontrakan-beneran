<?php

namespace App\Models;

use App\Enums\CommonStatus;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'address',
        'phone',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommonStatus::class,
        ];
    }

    /**
     * Whether this branch has manual-transfer instructions to show penyewa.
     */
    public function hasTransferInstructions(): bool
    {
        return filled($this->bank_account_number) && filled($this->bank_name);
    }

    /**
     * The bank-transfer instructions penyewa follow to pay offline.
     *
     * @return array{bank_name: string, account_number: string, account_holder: string}|null
     */
    public function transferInstructions(): ?array
    {
        if (! $this->hasTransferInstructions()) {
            return null;
        }

        return [
            'bank_name' => $this->bank_name,
            'account_number' => $this->bank_account_number,
            'account_holder' => $this->bank_account_holder ?? $this->name,
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Staff/admin users assigned to this branch.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<Penyewa, $this>
     */
    public function penyewas(): HasMany
    {
        return $this->hasMany(Penyewa::class);
    }

    /**
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * @return HasMany<Facility, $this>
     */
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
