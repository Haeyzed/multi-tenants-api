<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Physical warehouse storage location.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $manager_name
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_active
 * @property bool $is_primary
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EloquentCollection<int, WarehouseZone> $zones
 * @property-read EloquentCollection<int, WarehouseLocation> $locations
 * @property-read EloquentCollection<int, Inventory> $inventories
 *
 * @method static Builder<Warehouse>|Warehouse query()
 * @method static Builder<Warehouse>|Warehouse filter(array $filters)
 */
class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'manager_name',
        'latitude',
        'longitude',
        'is_active',
        'is_primary',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope a query to filter warehouses.
     *
     * @param  Builder<Warehouse>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Warehouse>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = $filters['search'];
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($filters['is_active']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_active'])
                    ? $filters['is_active']
                    : explode(',', (string) $filters['is_active']);

                $booleans = [];

                if (in_array('active', $statuses, true)) {
                    $booleans[] = true;
                }

                if (in_array('inactive', $statuses, true)) {
                    $booleans[] = false;
                }

                if (! empty($booleans)) {
                    $q->whereIn('is_active', $booleans);
                }
            })
            ->when(! empty($filters['country']), function (Builder $q) use ($filters): void {
                $q->where('country', $filters['country']);
            });
    }

    /**
     * Zones within this warehouse.
     *
     * @return HasMany<WarehouseZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class)->orderBy('sort_order');
    }

    /**
     * Storage locations within this warehouse.
     *
     * @return HasMany<WarehouseLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    /**
     * Inventory records stored in this warehouse.
     *
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
