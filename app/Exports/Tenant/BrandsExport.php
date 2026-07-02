<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Brand;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Brand>
 */
class BrandsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Brand>  $brands
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $brands, ?array $columns = null)
    {
        parent::__construct($brands, $columns);
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
            'is_visible',
            'logo_media_id',
            'banner_media_id',
            'meta_title',
            'meta_description',
            'website_url',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Brand): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Brand $brand) => (string) $brand->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Brand $brand) => $brand->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Brand $brand) => $brand->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (Brand $brand) => $brand->description,
            ],
            'is_visible' => [
                'heading' => 'Visible',
                'map' => fn (Brand $brand) => $brand->is_visible ? 'Yes' : 'No',
            ],
            'logo_media_id' => [
                'heading' => 'Logo Media ID',
                'map' => fn (Brand $brand) => $brand->logo_media_id !== null ? (string) $brand->logo_media_id : null,
            ],
            'banner_media_id' => [
                'heading' => 'Banner Media ID',
                'map' => fn (Brand $brand) => $brand->banner_media_id !== null ? (string) $brand->banner_media_id : null,
            ],
            'meta_title' => [
                'heading' => 'Meta Title',
                'map' => fn (Brand $brand) => $brand->meta_title,
            ],
            'meta_description' => [
                'heading' => 'Meta Description',
                'map' => fn (Brand $brand) => $brand->meta_description,
            ],
            'website_url' => [
                'heading' => 'Website URL',
                'map' => fn (Brand $brand) => $brand->website_url,
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Brand $brand) => $brand->sort_order !== null ? (string) $brand->sort_order : null,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Brand $brand) => $brand->created_at?->toDateTimeString(),
            ],
        ];
    }
}
