<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'summary' => $this->summary,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'parent_id' => $this->parent_id,
            'depth' => $this->depth,
            'path' => $this->path,
            'is_visible' => $this->is_visible,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'color' => $this->color,
            'icon_class' => $this->icon_class,
            'layout_template' => $this->layout_template,
            'products_count' => $this->products_count,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'image' => $this->whenLoaded('imageMedia', fn () => $this->imageMedia ? new MediaResource($this->imageMedia) : null),
            'banner' => $this->whenLoaded('bannerMedia', fn () => $this->bannerMedia ? new MediaResource($this->bannerMedia) : null),
            'icon' => $this->whenLoaded('iconMedia', fn () => $this->iconMedia ? new MediaResource($this->iconMedia) : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
