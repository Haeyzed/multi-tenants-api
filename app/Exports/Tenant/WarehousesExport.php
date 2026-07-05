<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Warehouse>
 */
class WarehousesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Warehouse>  $warehouses
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $warehouses, ?array $columns = null)
    {
        parent::__construct($warehouses, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
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
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Warehouse): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Warehouse $warehouse) => (string) $warehouse->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Warehouse $warehouse) => $warehouse->name,
            ],
            'code' => [
                'heading' => 'Code',
                'map' => fn (Warehouse $warehouse) => $warehouse->code,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (Warehouse $warehouse) => $warehouse->description,
            ],
            'address_line_1' => [
                'heading' => 'Address Line 1',
                'map' => fn (Warehouse $warehouse) => $warehouse->address_line_1,
            ],
            'address_line_2' => [
                'heading' => 'Address Line 2',
                'map' => fn (Warehouse $warehouse) => $warehouse->address_line_2,
            ],
            'city' => [
                'heading' => 'City',
                'map' => fn (Warehouse $warehouse) => $warehouse->city,
            ],
            'state' => [
                'heading' => 'State',
                'map' => fn (Warehouse $warehouse) => $warehouse->state,
            ],
            'postal_code' => [
                'heading' => 'Postal Code',
                'map' => fn (Warehouse $warehouse) => $warehouse->postal_code,
            ],
            'country' => [
                'heading' => 'Country',
                'map' => fn (Warehouse $warehouse) => $warehouse->country,
            ],
            'phone' => [
                'heading' => 'Phone',
                'map' => fn (Warehouse $warehouse) => $warehouse->phone,
            ],
            'email' => [
                'heading' => 'Email',
                'map' => fn (Warehouse $warehouse) => $warehouse->email,
            ],
            'manager_name' => [
                'heading' => 'Manager Name',
                'map' => fn (Warehouse $warehouse) => $warehouse->manager_name,
            ],
            'latitude' => [
                'heading' => 'Latitude',
                'map' => fn (Warehouse $warehouse) => $warehouse->latitude !== null ? (string) $warehouse->latitude : null,
            ],
            'longitude' => [
                'heading' => 'Longitude',
                'map' => fn (Warehouse $warehouse) => $warehouse->longitude !== null ? (string) $warehouse->longitude : null,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (Warehouse $warehouse) => $warehouse->is_active ? 'Yes' : 'No',
            ],
            'is_primary' => [
                'heading' => 'Primary',
                'map' => fn (Warehouse $warehouse) => $warehouse->is_primary ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Warehouse $warehouse) => (string) $warehouse->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Warehouse $warehouse) => $warehouse->created_at?->toDateTimeString(),
            ],
        ];
    }
}
