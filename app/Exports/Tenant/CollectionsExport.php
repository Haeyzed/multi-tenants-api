<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Collection;
use Illuminate\Support\Collection as EloquentCollection;

/**
 * @extends BaseTenantExport<Collection>
 */
class CollectionsExport extends BaseTenantExport
{
    /**
     * @param  EloquentCollection<int, Collection>  $collections
     * @param  list<string>|null  $columns
     */
    public function __construct(EloquentCollection $collections, ?array $columns = null)
    {
        parent::__construct($collections, $columns);
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
            'is_featured',
            'type',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Collection): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Collection $collection) => (string) $collection->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Collection $collection) => $collection->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Collection $collection) => $collection->slug,
            ],
            'description' => [
                'heading' => 'Description',
                'map' => fn (Collection $collection) => $collection->description,
            ],
            'is_visible' => [
                'heading' => 'Visible',
                'map' => fn (Collection $collection) => $collection->is_visible ? 'Yes' : 'No',
            ],
            'is_featured' => [
                'heading' => 'Featured',
                'map' => fn (Collection $collection) => $collection->is_featured ? 'Yes' : 'No',
            ],
            'type' => [
                'heading' => 'Type',
                'map' => fn (Collection $collection) => $collection->type,
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Collection $collection) => (string) $collection->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Collection $collection) => $collection->created_at?->toDateTimeString(),
            ],
        ];
    }
}
