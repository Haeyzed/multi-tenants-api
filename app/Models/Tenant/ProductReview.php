<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Customer review for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property string|null $author_name
 * @property string|null $author_email
 * @property int $rating
 * @property string|null $title
 * @property string|null $content
 * @property array<int, mixed>|null $images
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property int $helpful_count
 * @property int $unhelpful_count
 * @property int|null $parent_id
 * @property string|null $admin_reply
 * @property Carbon|null $replied_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 * @property-read Customer|null $customer
 * @property-read Order|null $order
 * @property-read ProductReview|null $parent
 * @property-read EloquentCollection<int, ProductReview> $replies
 * @property-read bool $has_reply
 */
class ProductReview extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'customer_id',
        'order_id',
        'author_name',
        'author_email',
        'rating',
        'title',
        'content',
        'images',
        'is_verified_purchase',
        'is_approved',
        'helpful_count',
        'unhelpful_count',
        'parent_id',
        'admin_reply',
        'replied_at',
    ];

    /**
     * Get the product this review belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant this review belongs to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the customer who wrote this review.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order associated with this review.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the parent review (for replies).
     *
     * @return BelongsTo<ProductReview, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get replies to this review.
     *
     * @return HasMany<ProductReview, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope a query to only include approved reviews.
     *
     * @param Builder<ProductReview> $query
     * @return Builder<ProductReview>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include verified purchase reviews.
     *
     * @param Builder<ProductReview> $query
     * @return Builder<ProductReview>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Determine if an admin reply exists.
     */
    public function getHasReplyAttribute(): bool
    {
        return !empty($this->admin_reply);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'images' => 'array',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'helpful_count' => 'integer',
            'unhelpful_count' => 'integer',
            'replied_at' => 'datetime',
        ];
    }
}
