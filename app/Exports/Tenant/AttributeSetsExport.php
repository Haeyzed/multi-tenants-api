<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\AttributeSet;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<AttributeSet>
 */
class AttributeSetsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, AttributeSet>  $attributeSets
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $attributeSets, ?array $columns = null)
    {
        parent::__construct($attributeSets, $columns);
    }

    /**
     * @return list<string>
     */
    public static function availableColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'description',
            'is_active',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(AttributeSet): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (AttributeSet $attributeSet) => (string) $attributeSet->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (AttributeSet $attributeSet) => $attributeSet->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (AttributeSet $attributeSet) => $attributeSet->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (AttributeSet $attributeSet) => $attributeSet->description,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (AttributeSet $attributeSet) => $attributeSet->is_active ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (AttributeSet $attributeSet) => (string) $attributeSet->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (AttributeSet $attributeSet) => $attributeSet->created_at?->toDateTimeString(),
            ],
        ];
    }
}
