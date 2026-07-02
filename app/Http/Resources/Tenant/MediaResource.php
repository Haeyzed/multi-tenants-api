<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'name' => $this->name,
            'title' => $this->title,
            'alt_text' => $this->alt_text,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'disk' => $this->disk,
            'uploaded_by' => $this->uploaded_by,
            'collection' => $this->collection_name,
            'path' => $this->getPathRelativeToRoot(),
            'url' => $this->url,
            'folder' => $this->whenLoaded('folder', fn () => $this->folder ? new MediaLibraryFolderResource($this->folder) : null),
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader ? new TenantUserResource($this->uploader) : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
