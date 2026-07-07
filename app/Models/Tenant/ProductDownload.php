<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Downloadable file for a digital product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $media_id
 * @property string $file_name
 * @property string|null $display_name
 * @property string|null $description
 * @property int|null $download_limit
 * @property int|null $download_expiry_days
 * @property int $download_count
 * @property int $sort_order
 * @property bool $is_preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Media $media
 * @property-read bool $is_expired
 * @property-read int|null $remaining_downloads
 *
 * @method static Builder<ProductDownload>|ProductDownload query()
 */
class ProductDownload extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'media_id',
        'file_name',
        'display_name',
        'description',
        'download_limit',
        'download_expiry_days',
        'download_count',
        'sort_order',
        'is_preview',
    ];

    /**
     * Product this download belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Media file for this download.
     *
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Determine if the download link has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->download_expiry_days) {
            return false;
        }

        return $this->created_at->addDays($this->download_expiry_days)->isPast();
    }

    /**
     * Remaining allowed downloads, or null when unlimited.
     */
    public function getRemainingDownloadsAttribute(): ?int
    {
        if (!$this->download_limit) {
            return null;
        }

        return max(0, $this->download_limit - $this->download_count);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'download_limit' => 'integer',
            'download_expiry_days' => 'integer',
            'download_count' => 'integer',
            'sort_order' => 'integer',
            'is_preview' => 'boolean',
        ];
    }
}
