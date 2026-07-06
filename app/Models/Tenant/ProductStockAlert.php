<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Customer back-in-stock notification subscription.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int|null $customer_id
 * @property string $email
 * @property bool $is_notified
 * @property Carbon|null $notified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductVariant $variant
 * @property-read Customer|null $customer
 *
 * @method static Builder<ProductStockAlert>|ProductStockAlert query()
 */
class ProductStockAlert extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'customer_id',
        'email',
        'is_notified',
        'notified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_notified' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @param  Builder<ProductStockAlert>  $query
     * @return Builder<ProductStockAlert>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_notified', false);
    }

    /**
     * @param  Builder<ProductStockAlert>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<ProductStockAlert>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['product_variant_id']), fn (Builder $q) => $q->where(
                'product_variant_id',
                (int) $filters['product_variant_id'],
            ))
            ->when(isset($filters['is_notified']), function (Builder $q) use ($filters): void {
                $q->where('is_notified', filter_var($filters['is_notified'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where('email', 'like', "%{$search}%");
            });
    }
}
