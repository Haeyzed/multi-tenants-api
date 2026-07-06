<?php

declare(strict_types=1);

namespace App\Exports\Tenant;

use App\Exports\Tenant\Concerns\BaseTenantExport;
use App\Models\Tenant\Tag;
use Illuminate\Support\Collection;

/**
 * @extends BaseTenantExport<Tag>
 */
class TagsExport extends BaseTenantExport
{
    /**
     * @param  Collection<int, Tag>  $tags
     * @param  list<string>|null  $columns
     */
    public function __construct(Collection $tags, ?array $columns = null)
    {
        parent::__construct($tags, $columns);
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
            'icon',
            'is_visible',
            'sort_order',
            'created_at',
        ];
    }

    /**
     * @return array<string, array{heading: string, map: callable(Tag): (string|null)}>
     */
    protected function columnDefinitions(): array
    {
        return [
            'id' => [
                'heading' => 'ID',
                'map' => fn (Tag $tag) => (string) $tag->id,
            ],
            'name' => [
                'heading' => 'Name',
                'map' => fn (Tag $tag) => $tag->name,
            ],
            'slug' => [
                'heading' => 'Slug',
                'map' => fn (Tag $tag) => $tag->slug,
            ],
            'color' => [
                'heading' => 'Color',
                'map' => fn (Tag $tag) => $tag->color,
            ],
            'icon' => [
                'heading' => 'Icon',
                'map' => fn (Tag $tag) => $tag->icon,
            ],
            'is_visible' => [
                'heading' => 'Visible',
                'map' => fn (Tag $tag) => $tag->is_visible ? 'Yes' : 'No',
            ],
            'sort_order' => [
                'heading' => 'Sort Order',
                'map' => fn (Tag $tag) => (string) $tag->sort_order,
            ],
            'created_at' => [
                'heading' => 'Created At',
                'map' => fn (Tag $tag) => $tag->created_at?->toDateTimeString(),
            ],
        ];
    }
}
