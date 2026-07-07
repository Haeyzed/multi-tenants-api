<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Document attached to a product such as a manual or datasheet.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $media_id
 * @property string $title
 * @property string|null $description
 * @property string $document_type
 * @property string $language
 * @property int $sort_order
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Media|null $media
 *
 * @method static Builder<ProductDocument>|ProductDocument query()
 */
class ProductDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'media_id',
        'title',
        'description',
        'document_type',
        'language',
        'sort_order',
        'is_public',
    ];

    /**
     * Product this document belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Media file for this document.
     *
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }
}
