<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit record for an inventory quantity change.
 *
 * @property int $id
 * @property int $inventory_id
 * @property int $quantity_change
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $reason
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Inventory $inventory
 * @property-read TenantUser|null $creator
 *
 * @method static Builder<InventoryMovement>|InventoryMovement query()
 */
class InventoryMovement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_id',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'type',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    /**
     * Inventory record this movement belongs to.
     *
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Staff user who created this movement.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }
}
