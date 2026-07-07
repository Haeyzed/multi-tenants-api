<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Customer-submitted product question with optional admin answer.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $customer_id
 * @property string|null $author_name
 * @property string|null $author_email
 * @property string $question
 * @property string|null $answer
 * @property bool $is_visible
 * @property bool $is_answered
 * @property int $helpful_count
 * @property int|null $answered_by
 * @property Carbon|null $answered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Customer|null $customer
 * @property-read TenantUser|null $answeredBy
 *
 * @method static Builder<ProductQuestion>|ProductQuestion query()
 */
class ProductQuestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'author_name',
        'author_email',
        'question',
        'answer',
        'is_visible',
        'is_answered',
        'helpful_count',
        'answered_by',
        'answered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_answered' => 'boolean',
            'helpful_count' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * Product this question belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Customer who submitted this question.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Admin user who answered this question.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'answered_by');
    }
}
