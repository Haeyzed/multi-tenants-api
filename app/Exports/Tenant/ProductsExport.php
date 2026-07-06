<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Product;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Product>
 */
class ProductsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $products, ?array $columns = null)
    {
        parent::__construct($products, $columns);
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
            'subtitle',
            'summary',
            'type',
            'condition',
            'status',
            'visibility',
            'is_featured',
            'default_variant_sku',
            'default_variant_price',
            'default_variant_compare_at_price',
            'default_variant_cost_price',
            'category',
            'brand',
            'quantity',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Product): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Product $product) => (string) $product->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Product $product) => $product->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Product $product) => $product->slug,
            ],
            'subtitle' => [
                'heading' => 'Subtitle',
                'map' => fn (Product $product) => $product->subtitle,
            ],
            'summary' => [
                'heading' => 'Summary',
                'map' => fn (Product $product) => $product->summary,
            ],
            'type' => [
                'heading' => 'Type',
                'map' => fn (Product $product) => $product->type?->value ?? (string) $product->type,
            ],
            'condition' => [
                'heading' => 'Condition',
                'map' => fn (Product $product) => $product->condition?->value ?? (string) $product->condition,
            ],
            'status' => [
                'heading' => 'Status',
                'map' => fn (Product $product) => $product->status?->value ?? (string) $product->status,
            ],
            'visibility' => [
                'heading' => 'Visibility',
                'map' => fn (Product $product) => $product->visibility?->value ?? (string) $product->visibility,
            ],
            'is_featured' => [
                'heading' => 'Featured',
                'map' => fn (Product $product) => $product->is_featured ? 'yes' : 'no',
            ],
            'default_variant_sku' => [
                'heading' => 'SKU',
                'map' => fn (Product $product) => $product->defaultVariant?->sku,
            ],
            'default_variant_price' => [
                'heading' => 'Price',
                'map' => fn (Product $product) => $product->defaultVariant?->price !== null
                    ? (string) $product->defaultVariant->price
                    : null,
            ],
            'default_variant_compare_at_price' => [
                'heading' => 'Compare At Price',
                'map' => fn (Product $product) => $product->defaultVariant?->compare_at_price !== null
                    ? (string) $product->defaultVariant->compare_at_price
                    : null,
            ],
            'default_variant_cost_price' => [
                'heading' => 'Cost Price',
                'map' => fn (Product $product) => $product->defaultVariant?->cost_price !== null
                    ? (string) $product->defaultVariant->cost_price
                    : null,
            ],
            'category' => [
                'heading' => 'Category',
                'map' => fn (Product $product) => $product->categories
                    ->firstWhere('pivot.is_primary', true)?->name
                    ?? $product->categories->first()?->name,
            ],
            'brand' => [
                'heading' => 'Brand',
                'map' => fn (Product $product) => $product->brand?->name,
            ],
            'quantity' => [
                'heading' => 'Quantity',
                'map' => fn (Product $product) => (string) ($product->defaultVariant?->inventories->sum('quantity') ?? 0),
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Product $product) => $product->created_at?->toDateTimeString(),
            ],
        ];
    }
}
