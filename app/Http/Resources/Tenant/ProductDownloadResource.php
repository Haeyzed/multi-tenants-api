<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductDownload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductDownload
 */
class ProductDownloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'media_id' => $this->media_id,
            'file_name' => $this->file_name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'download_limit' => $this->download_limit,
            'download_expiry_days' => $this->download_expiry_days,
            'download_count' => $this->download_count,
            'sort_order' => $this->sort_order,
            'is_preview' => $this->is_preview,
            'media' => $this->when($this->relationLoaded('media') && $this->media, fn () => [
                'id' => $this->media->id,
                'url' => $this->media->getUrl(),
                'file_name' => $this->media->file_name,
                'name' => $this->media->name,
                'mime_type' => $this->media->mime_type,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
