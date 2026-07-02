<?php

declare(strict_types=1);

namespace App\Jobs\Tenant;

use App\Models\Tenant\Media;
use App\Services\Tenant\MediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Removes the background from a tenant media library image.
 */
class RemoveMediaBackgroundJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(
        public int $mediaId,
        public int|string|null $uploadedBy = null,
    ) {}

    public function handle(MediaService $service): void
    {
        $media = Media::query()->findOrFail($this->mediaId);

        $service->removeBackground($media, $this->uploadedBy);
    }
}
