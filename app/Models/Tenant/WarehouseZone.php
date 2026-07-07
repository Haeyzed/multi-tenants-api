<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Logical zone within a warehouse.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $zone_type
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Warehouse $warehouse
 * @property-read EloquentCollection<int, WarehouseLocation> $locations
 *
 * @method static Builder<WarehouseZone>|WarehouseZone query()
 */
class WarehouseZone extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'description',
        'zone_type',
        'is_active',
        'sort_order',
    ];

    /**
     * Warehouse that owns this zone.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Bin or shelf locations in this zone.
     *
     * @return HasMany<WarehouseLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class, 'zone_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
