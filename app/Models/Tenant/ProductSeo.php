<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended SEO data for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $canonical_url
 * @property string|null $og_title
 * @property string|null $og_description
 * @property int|null $og_image_media_id
 * @property string|null $twitter_card
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property int|null $twitter_image_media_id
 * @property string|null $schema_markup
 * @property string|null $robots_meta
 * @property array<string, mixed>|null $custom_meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Media|null $ogImageMedia
 * @property-read Media|null $twitterImageMedia
 */
class ProductSeo extends Model
{
    protected $table = 'product_seo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_media_id',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image_media_id',
        'schema_markup',
        'robots_meta',
        'custom_meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_meta' => 'array',
        ];
    }

    /**
     * Get the product this SEO data belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the OG image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function ogImageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->ogImageMedia();
    }

    /**
     * Get the Twitter image media.
     *
     * @return BelongsTo<Media, $this>
     */
    public function twitterImageMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'twitter_image_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function twitterImage(): BelongsTo
    {
        return $this->twitterImageMedia();
    }
}
