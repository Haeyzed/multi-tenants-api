<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Unit;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Unit>
 */
class UnitsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Unit>  $units
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $units, ?array $columns = null)
    {
        parent::__construct($units, $columns);
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
            'symbol',
            'type',
            'conversion_factor',
            'is_base',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Unit): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Unit $unit) => (string) $unit->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Unit $unit) => $unit->name,
            ],
            'code' => [
                'heading' => 'Code',
                'map' => fn (Unit $unit) => $unit->code,
            ],
            'symbol' => [
                'heading' => 'Symbol',
                'map' => fn (Unit $unit) => $unit->symbol,
            ],
            'type' => [
                'heading' => 'Type',
                'map' => fn (Unit $unit) => $unit->type,
            ],
            'conversion_factor' => [
                'heading' => 'Conversion Factor',
                'map' => fn (Unit $unit) => (string) $unit->conversion_factor,
            ],
            'is_base' => [
                'heading' => 'Base Unit',
                'map' => fn (Unit $unit) => $unit->is_base ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Unit $unit) => (string) $unit->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Unit $unit) => $unit->created_at?->toDateTimeString(),
            ],
        ];
    }
}
