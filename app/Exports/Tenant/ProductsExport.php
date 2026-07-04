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
            'sku',
            'barcode',
            'price',
            'compare_at_price',
            'sale_price',
            'cost_price',
            'status',
            'is_featured',
            'product_type',
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
            'sku' => [
                'heading' => 'SKU',
                'map' => fn (Product $product) => $product->sku,
            ],
            'barcode' => [
                'heading' => 'Barcode',
                'map' => fn (Product $product) => $product->barcode,
            ],
            'price' => [
                'heading' => 'Price',
                'map' => fn (Product $product) => (string) $product->price,
            ],
            'compare_at_price' => [
                'heading' => 'Compare At Price',
                'map' => fn (Product $product) => $product->compare_at_price !== null
                    ? (string) $product->compare_at_price
                    : null,
            ],
            'sale_price' => [
                'heading' => 'Sale Price',
                'map' => fn (Product $product) => $product->sale_price !== null
                    ? (string) $product->sale_price
                    : null,
            ],
            'cost_price' => [
                'heading' => 'Cost Price',
                'map' => fn (Product $product) => $product->cost_price !== null
                    ? (string) $product->cost_price
                    : null,
            ],
            'status' => [
                'heading' => 'Status',
                'map' => fn (Product $product) => $product->status?->value ?? (string) $product->status,
            ],
            'is_featured' => [
                'heading' => 'Featured',
                'map' => fn (Product $product) => $product->is_featured ? 'yes' : 'no',
            ],
            'product_type' => [
                'heading' => 'Type',
                'map' => fn (Product $product) => $product->product_type?->value ?? (string) $product->product_type,
            ],
            'category' => [
                'heading' => 'Category',
                'map' => fn (Product $product) => $product->category?->name,
            ],
            'brand' => [
                'heading' => 'Brand',
                'map' => fn (Product $product) => $product->brand?->name,
            ],
            'quantity' => [
                'heading' => 'Quantity',
                'map' => fn (Product $product) => (string) ($product->inventory?->quantity ?? 0),
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Product $product) => $product->created_at?->toDateTimeString(),
            ],
        ];
    }
}
