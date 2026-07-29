<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Payment extends Model implements HasMedia
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToBranch, HasFactory, InteractsWithMedia;

    public const MEDIA_PROOF = 'proof';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'invoice_id',
        'penyewa_id',
        'recorded_by',
        'amount',
        'method',
        'status',
        'reference',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Penyewa, $this>
     */
    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(Penyewa::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Payments that have settled and count toward an invoice balance.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PaymentStatus::Completed);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_PROOF)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 600, 600)
            ->nonQueued();
    }
}
