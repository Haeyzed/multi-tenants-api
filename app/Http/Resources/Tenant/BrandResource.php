<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brand
 */
class BrandResource extends JsonResource
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
            'is_visible' => $this->is_visible,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'website_url' => $this->website_url,
            'sort_order' => $this->sort_order,
            'logo' => $this->whenLoaded('logoMedia', fn () => $this->logoMedia ? new MediaResource($this->logoMedia) : null),
            'banner' => $this->whenLoaded('bannerMedia', fn () => $this->bannerMedia ? new MediaResource($this->bannerMedia) : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
