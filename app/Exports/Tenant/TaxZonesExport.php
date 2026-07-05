<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\TaxZone;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<TaxZone>
 */
class TaxZonesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, TaxZone>  $taxZones
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $taxZones, ?array $columns = null)
    {
        parent::__construct($taxZones, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'name',
            'country_code',
            'state',
            'city',
            'postal_code',
            'postal_code_pattern',
            'latitude',
            'longitude',
            'radius_km',
            'is_default',
            'is_active',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(TaxZone): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (TaxZone $taxZone) => (string) $taxZone->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (TaxZone $taxZone) => $taxZone->name,
            ],
            'country_code' => [
                'heading' => 'Country Code',
                'map' => fn (TaxZone $taxZone) => $taxZone->country_code,
            ],
            'state' => [
                'heading' => 'State',
                'map' => fn (TaxZone $taxZone) => $taxZone->state,
            ],
            'city' => [
                'heading' => 'City',
                'map' => fn (TaxZone $taxZone) => $taxZone->city,
            ],
            'postal_code' => [
                'heading' => 'Postal Code',
                'map' => fn (TaxZone $taxZone) => $taxZone->postal_code,
            ],
            'postal_code_pattern' => [
                'heading' => 'Postal Code Pattern',
                'map' => fn (TaxZone $taxZone) => $taxZone->postal_code_pattern,
            ],
            'latitude' => [
                'heading' => 'Latitude',
                'map' => fn (TaxZone $taxZone) => $taxZone->latitude !== null ? (string) $taxZone->latitude : null,
            ],
            'longitude' => [
                'heading' => 'Longitude',
                'map' => fn (TaxZone $taxZone) => $taxZone->longitude !== null ? (string) $taxZone->longitude : null,
            ],
            'radius_km' => [
                'heading' => 'Radius (km)',
                'map' => fn (TaxZone $taxZone) => $taxZone->radius_km !== null ? (string) $taxZone->radius_km : null,
            ],
            'is_default' => [
                'heading' => 'Default',
                'map' => fn (TaxZone $taxZone) => $taxZone->is_default ? 'Yes' : 'No',
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (TaxZone $taxZone) => $taxZone->is_active ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (TaxZone $taxZone) => (string) $taxZone->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (TaxZone $taxZone) => $taxZone->created_at?->toDateTimeString(),
            ],
        ];
    }
}
