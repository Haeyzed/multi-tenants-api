<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Service provider assigned to a product.
 *
 * @property int $id
 * @property int $product_id
 * @property int $provider_id
 * @property bool $is_primary
 * @property string|null $commission_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read TenantUser $provider
 *
 * @method static Builder<ProductProvider>|ProductProvider query()
 */
class ProductProvider extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'provider_id',
        'is_primary',
        'commission_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'commission_rate' => 'decimal:2',
        ];
    }

    /**
     * Product this provider is assigned to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Staff user providing the service.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'provider_id');
    }
}
