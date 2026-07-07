<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\ProductLabel;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<ProductLabel>
 */
class ProductLabelsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, ProductLabel>  $productLabels
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $productLabels, ?array $columns = null)
    {
        parent::__construct($productLabels, $columns);
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
            'color',
            'background_color',
            'icon',
            'is_active',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(ProductLabel): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (ProductLabel $label) => (string) $label->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (ProductLabel $label) => $label->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (ProductLabel $label) => $label->slug,
            ],
            'color' => [
                'heading' => 'Color',
                'map' => fn (ProductLabel $label) => $label->color,
            ],
            'background_color' => [
                'heading' => 'Background Color',
                'map' => fn (ProductLabel $label) => $label->background_color,
            ],
            'icon' => [
                'heading' => 'Icon',
                'map' => fn (ProductLabel $label) => $label->icon,
            ],
            'is_active' => [
                'heading' => 'Active',
                'map' => fn (ProductLabel $label) => $label->is_active ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (ProductLabel $label) => (string) $label->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (ProductLabel $label) => $label->created_at?->toDateTimeString(),
            ],
        ];
    }
}
