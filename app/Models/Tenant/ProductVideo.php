<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Video attached to a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $provider
 * @property string $video_id
 * @property string $video_url
 * @property string|null $title
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 */
class ProductVideo extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'provider',
        'video_id',
        'video_url',
        'title',
        'description',
        'sort_order',
        'is_primary',
    ];

    /**
     * Get the product this video belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the embed URL for the video.
     */
    public function embedUrl(): string
    {
        return match ($this->provider) {
            'youtube' => "https://www.youtube.com/embed/{$this->video_id}",
            'vimeo' => "https://player.vimeo.com/video/{$this->video_id}",
            default => $this->video_url,
        };
    }

    /**
     * Get the thumbnail URL.
     */
    public function thumbnailUrl(): ?string
    {
        return match ($this->provider) {
            'youtube' => "https://img.youtube.com/vi/{$this->video_id}/hqdefault.jpg",
            'vimeo' => null,
            default => null,
        };
    }

    /**
     * Get the watch URL.
     */
    public function watchUrl(): string
    {
        return match ($this->provider) {
            'youtube' => "https://www.youtube.com/watch?v={$this->video_id}",
            default => $this->video_url,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }
}
