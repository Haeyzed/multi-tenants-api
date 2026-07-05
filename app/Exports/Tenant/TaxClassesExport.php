<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\TaxClass;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<TaxClass>
 */
class TaxClassesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, TaxClass>  $taxClasses
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $taxClasses, ?array $columns = null)
    {
        parent::__construct($taxClasses, $columns);
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
            'is_default',
            'is_active',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(TaxClass): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (TaxClass $taxClass) => (string) $taxClass->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (TaxClass $taxClass) => $taxClass->name,
            ],
            'code' => [
                'heading' => 'Code',
                'map' => fn (TaxClass $taxClass) => $taxClass->code,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (TaxClass $taxClass) => $taxClass->description,
            ],
            'is_default' => [
                'heading' => 'Default',
                'map' => fn (TaxClass $taxClass) => $taxClass->is_default ? 'Yes' : 'No',
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (TaxClass $taxClass) => $taxClass->is_active ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (TaxClass $taxClass) => (string) $taxClass->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (TaxClass $taxClass) => $taxClass->created_at?->toDateTimeString(),
            ],
        ];
    }
}
