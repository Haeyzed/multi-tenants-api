<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductDocument
 */
class ProductDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'media_id' => $this->media_id,
            'title' => $this->title,
            'description' => $this->description,
            'document_type' => $this->document_type,
            'language' => $this->language,
            'sort_order' => $this->sort_order,
            'is_public' => $this->is_public,
            'media_url' => $this->when(
                $this->relationLoaded('media') && $this->media,
                fn () => $this->media->getUrl(),
            ),
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
