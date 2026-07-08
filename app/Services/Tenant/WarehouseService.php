<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseLocation;
use App\Models\Tenant\WarehouseZone;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Manages warehouses, zones, and storage locations within a tenant store.
 */
class WarehouseService
{
    /**
     * @var list<string>
     */
    private const DETAIL_RELATIONS = ['zones.locations'];

    /**
     * Paginate warehouses.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Warehouse>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Warehouse::query()
            ->withCount(['zones', 'locations', 'inventories'])
            ->filter($filters)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find a warehouse by ID.
     *
     * @param int $id
     * @return Warehouse
     */
    public function find(int $id): Warehouse
    {
        return Warehouse::query()
            ->with(self::DETAIL_RELATIONS)
            ->withCount(['zones', 'locations', 'inventories'])
            ->findOrFail($id);
    }

    /**
     * Find a warehouse by code.
     *
     * @param string $code
     * @return Warehouse
     */
    public function findByCode(string $code): Warehouse
    {
        return Warehouse::query()
            ->with(self::DETAIL_RELATIONS)
            ->withCount(['zones', 'locations', 'inventories'])
            ->where('code', $code)
            ->firstOrFail();
    }

    /**
     * Create a warehouse.
     *
     * @param  array<string, mixed>  $data
     * @return Warehouse
     */
    public function create(array $data): Warehouse
    {
        if (! empty($data['is_primary'])) {
            Warehouse::query()->where('is_primary', true)->update(['is_primary' => false]);
        }

        $warehouse = Warehouse::query()->create($data);

        return $this->find($warehouse->id);
    }

    /**
     * Update a warehouse.
     *
     * @param Warehouse $warehouse
     * @param  array<string, mixed>  $data
     * @return Warehouse
     */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        if (! empty($data['is_primary']) && ! $warehouse->is_primary) {
            Warehouse::query()->where('is_primary', true)->update(['is_primary' => false]);
        }

        $warehouse->update($data);

        return $this->find($warehouse->id);
    }

    /**
     * Soft delete a warehouse.
     *
     * @param Warehouse $warehouse
     * @return void
     */
    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->inventories()->exists()) {
            throw new DomainException('Cannot delete warehouse with inventory.');
        }

        if ($warehouse->is_primary) {
            $warehouse->update(['is_primary' => false]);
        }

        $warehouse->delete();
    }

    /**
     * Soft delete multiple warehouses.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Warehouse::query()
            ->whereIn('id', $ids)
            ->whereDoesntHave('inventories')
            ->delete();
    }

    /**
     * Restore a soft-deleted warehouse.
     *
     * @param Warehouse $warehouse
     * @return Warehouse
     */
    public function restore(Warehouse $warehouse): Warehouse
    {
        $warehouse->restore();

        return $this->find($warehouse->id);
    }

    /**
     * Permanently delete a warehouse.
     *
     * @param Warehouse $warehouse
     * @return void
     */
    public function forceDelete(Warehouse $warehouse): void
    {
        if ($warehouse->inventories()->exists()) {
            throw new DomainException('Cannot permanently delete warehouse with inventory.');
        }

        $warehouse->forceDelete();
    }

    /**
     * Get warehouse statistics.
     *
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => Warehouse::query()->count(),
            'active' => Warehouse::query()->where('is_active', true)->count(),
            'inactive' => Warehouse::query()->where('is_active', false)->count(),
            'primary' => Warehouse::query()->where('is_primary', true)->count(),
            'with_inventory' => Warehouse::query()->whereHas('inventories')->count(),
        ];
    }

    /**
     * Get warehouse options for select inputs.
     *
     * @return SupportCollection<int, array{label: string, value: int, code: string}>
     */
    public function getOptions(): SupportCollection
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Warehouse $warehouse): array => [
                'label' => $warehouse->name,
                'value' => $warehouse->id,
                'code' => $warehouse->code,
            ]);
    }

    /**
     * Build the export query for spreadsheet downloads.
     *
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection<int, Warehouse>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Warehouse::query()->orderBy('sort_order')->latest();

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Toggle warehouse active status.
     *
     * @param Warehouse $warehouse
     * @return Warehouse
     */
    public function toggleActive(Warehouse $warehouse): Warehouse
    {
        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return $this->find($warehouse->id);
    }

    /**
     * Set a warehouse as primary.
     *
     * @param Warehouse $warehouse
     * @return Warehouse
     */
    public function setPrimary(Warehouse $warehouse): Warehouse
    {
        Warehouse::query()->where('is_primary', true)->update(['is_primary' => false]);
        $warehouse->update(['is_primary' => true]);

        return $this->find($warehouse->id);
    }

    /**
     * Get the primary warehouse.
     *
     * @return Warehouse|null
     */
    public function getPrimary(): ?Warehouse
    {
        $warehouse = Warehouse::query()->where('is_primary', true)->first();

        return $warehouse ? $this->find($warehouse->id) : null;
    }

    // ── Zones ──

    /**
     * @param Warehouse $warehouse
     * @return Collection<int, WarehouseZone>
     */
    public function getZones(Warehouse $warehouse): Collection
    {
        return $warehouse->zones()->withCount('locations')->orderBy('sort_order')->get();
    }

    /**
     * @param int $warehouseId
     * @param  array<string, mixed>  $data
     * @return WarehouseZone
     */
    public function createZone(int $warehouseId, array $data): WarehouseZone
    {
        $data['warehouse_id'] = $warehouseId;

        return WarehouseZone::query()->create($data);
    }

    /**
     * @param WarehouseZone $zone
     * @param  array<string, mixed>  $data
     * @return WarehouseZone
     */
    public function updateZone(WarehouseZone $zone, array $data): WarehouseZone
    {
        $zone->update($data);

        return $zone->fresh()->loadCount('locations');
    }

    /**
     * @param WarehouseZone $zone
     * @return void
     */
    public function deleteZone(WarehouseZone $zone): void
    {
        $zone->delete();
    }

    // ── Locations ──

    /**
     * @param Warehouse $warehouse
     * @return Collection<int, WarehouseLocation>
     */
    public function getLocations(Warehouse $warehouse): Collection
    {
        return $warehouse->locations()->with('zone')->orderBy('code')->get();
    }

    /**
     * @param WarehouseZone $zone
     * @return Collection<int, WarehouseLocation>
     */
    public function getLocationsByZone(WarehouseZone $zone): Collection
    {
        return $zone->locations()->orderBy('code')->get();
    }

    /**
     * @param int $warehouseId
     * @param  array<string, mixed>  $data
     * @return WarehouseLocation
     */
    public function createLocation(int $warehouseId, array $data): WarehouseLocation
    {
        $data['warehouse_id'] = $warehouseId;

        return WarehouseLocation::query()->create($data)->load('zone');
    }

    /**
     * @param WarehouseLocation $location
     * @param  array<string, mixed>  $data
     * @return WarehouseLocation
     */
    public function updateLocation(WarehouseLocation $location, array $data): WarehouseLocation
    {
        $location->update($data);

        return $location->fresh()->load('zone');
    }

    /**
     * @param WarehouseLocation $location
     * @return void
     */
    public function deleteLocation(WarehouseLocation $location): void
    {
        $location->delete();
    }
}
