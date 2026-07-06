<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Attribute;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Attribute>
 */
class AttributesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Attribute>  $attributes
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $attributes, ?array $columns = null)
    {
        parent::__construct($attributes, $columns);
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
            'code',
            'type',
            'display_type',
            'is_filterable',
            'is_variant',
            'is_required',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Attribute): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Attribute $attribute) => (string) $attribute->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Attribute $attribute) => $attribute->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Attribute $attribute) => $attribute->slug,
            ],
            'code' => [
                'heading' => 'Code',
                'map' => fn (Attribute $attribute) => $attribute->code,
            ],
            'type' => [
                'heading' => 'Type',
                'map' => fn (Attribute $attribute) => $attribute->type,
            ],
            'display_type' => [
                'heading' => 'Display Type',
                'map' => fn (Attribute $attribute) => $attribute->display_type,
            ],
            'is_filterable' => [
                'heading' => 'Filterable',
                'map' => fn (Attribute $attribute) => $attribute->is_filterable ? 'Yes' : 'No',
            ],
            'is_variant' => [
                'heading' => 'Variant',
                'map' => fn (Attribute $attribute) => $attribute->is_variant ? 'Yes' : 'No',
            ],
            'is_required' => [
                'heading' => 'Required',
                'map' => fn (Attribute $attribute) => $attribute->is_required ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Attribute $attribute) => (string) $attribute->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Attribute $attribute) => $attribute->created_at?->toDateTimeString(),
            ],
        ];
    }
}
