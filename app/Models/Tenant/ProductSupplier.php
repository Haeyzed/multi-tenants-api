<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Product supplier sourcing record with commercial terms.
 *
 * @property int $id
 * @property int $product_id
 * @property int $supplier_id
 * @property string|null $supplier_sku
 * @property string|null $supplier_cost
 * @property int|null $lead_time_days
 * @property int $minimum_quantity
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Supplier $supplier
 *
 * @method static Builder<ProductSupplier>|ProductSupplier query()
 */
class ProductSupplier extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'supplier_id',
        'supplier_sku',
        'supplier_cost',
        'lead_time_days',
        'minimum_quantity',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supplier_cost' => 'decimal:4',
            'lead_time_days' => 'integer',
            'minimum_quantity' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the product being sourced.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier providing the product.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
