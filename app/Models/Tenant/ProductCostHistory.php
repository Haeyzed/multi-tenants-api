<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit record for product variant cost price changes.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property string $old_cost
 * @property string $new_cost
 * @property int|null $supplier_id
 * @property int|null $changed_by
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductVariant $variant
 * @property-read Supplier|null $supplier
 * @property-read TenantUser|null $changedBy
 *
 * @method static Builder<ProductCostHistory>|ProductCostHistory query()
 */
class ProductCostHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'old_cost',
        'new_cost',
        'supplier_id',
        'changed_by',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_cost' => 'decimal:4',
            'new_cost' => 'decimal:4',
        ];
    }

    /**
     * Variant whose cost changed.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Supplier associated with the cost change.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * User who changed the cost.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'changed_by');
    }
}
