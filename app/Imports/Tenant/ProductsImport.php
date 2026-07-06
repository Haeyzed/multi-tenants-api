<?php

declare(strict_types=1);

namespace App\Imports\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Enums\Tenant\ProductType;
use App\Enums\Tenant\ProductVisibility;
use App\Imports\Concerns\NormalizesImportRows;
use App\Imports\Concerns\TracksImportResults;
use App\Models\Tenant\Brand;
use App\Models\Tenant\Category;
use App\Services\Tenant\ProductService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use NormalizesImportRows, SkipsFailures, TracksImportResults;

    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $brandId = null;

            if (filled($row['brand_slug'] ?? null)) {
                $brandId = Brand::query()
                    ->where('slug', (string) $row['brand_slug'])
                    ->value('id');
            }

            $categoryIds = [];

            if (filled($row['category_slug'] ?? null)) {
                $categoryId = Category::query()
                    ->where('slug', (string) $row['category_slug'])
                    ->value('id');

                if ($categoryId !== null) {
                    $categoryIds[] = $categoryId;
                }
            }

            $payload = [
                'name' => (string) $row['name'],
                'slug' => filled($row['slug'] ?? null) ? (string) $row['slug'] : null,
                'summary' => filled($row['summary'] ?? null) ? (string) $row['summary'] : null,
                'description' => filled($row['description'] ?? null) ? (string) $row['description'] : null,
                'type' => filled($row['type'] ?? null) ? (string) $row['type'] : ProductType::Simple->value,
                'status' => filled($row['status'] ?? null) ? (string) $row['status'] : ProductStatus::Draft->value,
                'visibility' => filled($row['visibility'] ?? null)
                    ? (string) $row['visibility']
                    : ProductVisibility::Visible->value,
                'brand_id' => $brandId,
                'category_ids' => $categoryIds,
                'primary_category_id' => $categoryIds[0] ?? null,
                'track_inventory' => $this->parseBoolean($row['track_inventory'] ?? true),
                'is_featured' => $this->parseBoolean($row['is_featured'] ?? false, false),
                'sku' => (string) $row['sku'],
                'price' => (float) $row['price'],
                'compare_at_price' => filled($row['compare_at_price'] ?? null)
                    ? (float) $row['compare_at_price']
                    : null,
            ];

            $this->productService->create($payload);

            $this->incrementImported();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index): array
    {
        return $this->nullifyEmpty($data, [
            'slug',
            'summary',
            'description',
            'brand_slug',
            'category_slug',
            'compare_at_price',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')],
            '*.sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            '*.price' => ['required', 'numeric', 'min:0'],
            '*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            '*.type' => ['nullable', Rule::enum(ProductType::class)],
            '*.status' => ['nullable', Rule::enum(ProductStatus::class)],
            '*.visibility' => ['nullable', Rule::enum(ProductVisibility::class)],
            '*.summary' => ['nullable', 'string', 'max:500'],
            '*.description' => ['nullable', 'string'],
            '*.brand_slug' => ['nullable', 'string', 'max:255', Rule::exists('brands', 'slug')],
            '*.category_slug' => ['nullable', 'string', 'max:255', Rule::exists('categories', 'slug')],
            '*.track_inventory' => ['nullable'],
            '*.is_featured' => ['nullable'],
        ];
    }
}
