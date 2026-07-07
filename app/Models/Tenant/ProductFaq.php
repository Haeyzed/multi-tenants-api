<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Frequently asked question for a product.
 *
 * @property int $id
 * @property int $product_id
 * @property string $question
 * @property string $answer
 * @property bool $is_visible
 * @property int $sort_order
 * @property int $helpful_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static Builder<ProductFaq>|ProductFaq query()
 */
class ProductFaq extends Model
{
    protected $table = 'product_faqs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'is_visible',
        'sort_order',
        'helpful_count',
    ];

    /**
     * Product this FAQ belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'helpful_count' => 'integer',
        ];
    }
}
