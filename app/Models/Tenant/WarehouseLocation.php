<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bin or shelf-level storage position in a warehouse.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $zone_id
 * @property string $code
 * @property string|null $name
 * @property string|null $description
 * @property string|null $max_weight
 * @property string|null $max_volume
 * @property bool $is_active
 * @property bool $is_picking_location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Warehouse $warehouse
 * @property-read WarehouseZone|null $zone
 *
 * @method static Builder<WarehouseLocation>|WarehouseLocation query()
 */
class WarehouseLocation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'code',
        'name',
        'description',
        'max_weight',
        'max_volume',
        'is_active',
        'is_picking_location',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_weight' => 'decimal:3',
            'max_volume' => 'decimal:3',
            'is_active' => 'boolean',
            'is_picking_location' => 'boolean',
        ];
    }

    /**
     * Warehouse that owns this location.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Zone that contains this location.
     *
     * @return BelongsTo<WarehouseZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }
}
