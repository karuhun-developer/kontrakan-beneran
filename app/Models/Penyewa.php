<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Models\Concerns\BelongsToBranch;
use Database\Factories\PenyewaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Penyewa extends Model implements HasMedia
{
    /** @use HasFactory<PenyewaFactory> */
    use BelongsToBranch, HasFactory, InteractsWithMedia;

    public const MEDIA_ID_CARD = 'id_card';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'name',
        'email',
        'phone',
        'id_card_number',
        'gender',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommonStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_ID_CARD)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 400, 400)
            ->nonQueued();
    }
}
