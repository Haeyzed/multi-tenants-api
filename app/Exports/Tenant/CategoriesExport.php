<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Category;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Category>
 */
class CategoriesExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Category>  $categories
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $categories, ?array $columns = null)
    {
        parent::__construct($categories, $columns);
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
            'meta_title',
            'meta_description',
            'parent_id',
            'is_visible',
            'sort_order',
            'image_media_id',
            'banner_media_id',
            'color',
            'icon',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Category): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Category $category) => (string) $category->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Category $category) => $category->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Category $category) => $category->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (Category $category) => $category->description,
            ],
            'meta_title' => [
                'heading' => 'Meta Title',
                'map' => fn (Category $category) => $category->meta_title,
            ],
            'meta_description' => [
                'heading' => 'Meta Description',
                'map' => fn (Category $category) => $category->meta_description,
            ],
            'parent_id' => [
                'heading' => 'Parent ID',
                'map' => fn (Category $category) => $category->parent_id !== null ? (string) $category->parent_id : null,
            ],
            'is_visible' => [
                'heading' => 'Visible',
                'map' => fn (Category $category) => $category->is_visible ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Category $category) => (string) $category->sort_order,
            ],
            'image_media_id' => [
                'heading' => 'Image Media ID',
                'map' => fn (Category $category) => $category->image_media_id !== null ? (string) $category->image_media_id : null,
            ],
            'banner_media_id' => [
                'heading' => 'Banner Media ID',
                'map' => fn (Category $category) => $category->banner_media_id !== null ? (string) $category->banner_media_id : null,
            ],
            'color' => [
                'heading' => 'Color',
                'map' => fn (Category $category) => $category->color,
            ],
            'icon' => [
                'heading' => 'Icon',
                'map' => fn (Category $category) => $category->icon,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Category $category) => $category->created_at?->toDateTimeString(),
            ],
        ];
    }
}
