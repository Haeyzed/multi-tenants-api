<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Contracts\BackgroundRemover;
use App\Models\Tenant\Media;
use App\Models\Tenant\MediaFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Tenant media library files and queries.
 */
class MediaService
{
    private const string LIBRARY_COLLECTION = 'library';

    private const string LIBRARY_MODEL_TYPE = MediaFolder::class;

    /**
     * Base query for library media items.
     *
     * @return Builder<Media>
     */
    private function query(): Builder
    {
        return Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->with(['folder', 'uploader']);
    }

    /**
     * Get paginated media library items.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Media>
     */
    public function paginate(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        return Media::query()
            ->with(['folder', 'uploader'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find media by ID or fail.
     *
     * @param int $id
     * @return Media
     */
    public function findOrFail(int $id): Media
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Upload a file into the media library.
     *
     * @param UploadedFile $file
     * @param  array<string, mixed>  $meta
     * @return Media
     */
    public function upload(UploadedFile $file, array $meta = []): Media
    {
        $folderId = isset($meta['folder_id']) ? (int) $meta['folder_id'] : null;
        $disk = (string) config('media-library.disk_name', 'public');
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->putFileAs(
            $this->libraryDirectory($folderId),
            $file,
            $storedName,
        );

        $media = Media::query()->create([
            'folder_id' => $folderId,
            'model_type' => self::LIBRARY_MODEL_TYPE,
            'model_id' => $folderId ?? 0,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::LIBRARY_COLLECTION,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'title' => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'alt_text' => $meta['alt_text'] ?? null,
            'uploaded_by' => Auth::id(),
            'file_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'conversions_disk' => config('media-library.conversions_disk_name'),
            'size' => $file->getSize(),
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        return $media->fresh(['folder', 'uploader']);
    }

    /**
     * Upload multiple files into the media library.
     *
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $meta
     * @return list<Media>
     */
    public function uploadMany(array $files, array $meta = []): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            $uploaded[] = $this->upload($file, $meta);
        }

        return $uploaded;
    }

    /**
     * Download a remote file and store it in the media library.
     *
     * @param string $url
     * @param  array<string, mixed>  $meta
     * @return Media
     */
    public function importFromUrl(string $url, array $meta = []): Media
    {
        $response = Http::timeout(30)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download the file from the provided URL.');
        }

        $content = $response->body();
        $maxSize = (int) config('media-library.max_file_size');

        if (strlen($content) > $maxSize) {
            throw new RuntimeException('The file exceeds the maximum allowed size.');
        }

        if ($content === '') {
            throw new RuntimeException('The remote file is empty.');
        }

        $contentType = $response->header('Content-Type');
        $mimeType = trim(explode(';', (string) $contentType)[0]) ?: 'application/octet-stream';
        $originalName = $this->resolveImportFilename($url, $response->header('Content-Disposition'));
        $extension = pathinfo($originalName, PATHINFO_EXTENSION)
            ?: $this->extensionFromMimeType($mimeType)
            ?: 'bin';
        $storedName = Str::uuid()->toString().'.'.strtolower($extension);
        $folderId = isset($meta['folder_id']) ? (int) $meta['folder_id'] : null;
        $disk = (string) config('media-library.disk_name', 'public');

        Storage::disk($disk)->put($this->libraryPath($folderId, $storedName), $content);

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        $media = Media::query()->create([
            'folder_id' => $folderId,
            'model_type' => self::LIBRARY_MODEL_TYPE,
            'model_id' => $folderId ?? 0,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::LIBRARY_COLLECTION,
            'name' => $baseName !== '' ? $baseName : 'imported-file',
            'title' => $meta['title'] ?? ($baseName !== '' ? $baseName : 'Imported file'),
            'alt_text' => $meta['alt_text'] ?? null,
            'uploaded_by' => Auth::id(),
            'file_name' => $storedName,
            'mime_type' => $mimeType,
            'disk' => $disk,
            'conversions_disk' => config('media-library.conversions_disk_name'),
            'size' => strlen($content),
            'manipulations' => [],
            'custom_properties' => ['source_url' => $url],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        return $media->fresh(['folder', 'uploader']);
    }

    /**
     * Update media metadata and relocate the file when the folder changes.
     *
     * @param Media $media
     * @param  array<string, mixed>  $data
     * @return Media
     *
     * @throws Throwable
     */
    public function update(Media $media, array $data): Media
    {
        $this->assertLibraryMedia($media);

        return DB::transaction(function () use ($media, $data): Media {
            if (array_key_exists('folder_id', $data)) {
                $folderId = $data['folder_id'] !== null ? (int) $data['folder_id'] : null;
                $this->relocateLibraryFile($media, $folderId);
                $media->folder_id = $folderId;
                $media->model_id = $folderId ?? 0;
                unset($data['folder_id']);
            }

            if ($data !== []) {
                $media->fill($data);
            }

            $media->save();

            return $media->fresh(['folder', 'uploader']);
        });
    }

    /**
     * Move multiple library files into a folder.
     *
     * @param  list<int>  $ids
     * @param int|null $folderId
     * @return list<Media>
     */
    public function moveMany(array $ids, ?int $folderId): array
    {
        $moved = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($folderId, &$moved): void {
            $moved[] = $this->update($media, ['folder_id' => $folderId]);
        });

        return $moved;
    }

    /**
     * Copy multiple library files into a folder.
     *
     * @param  list<int>  $ids
     * @param int|null $folderId
     * @return list<Media>
     */
    public function copyMany(array $ids, ?int $folderId): array
    {
        $copied = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($folderId, &$copied): void {
            $copied[] = $this->copy($media, $folderId);
        });

        return $copied;
    }

    /**
     * Apply shared metadata updates to multiple library files.
     *
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $data
     * @return list<Media>
     */
    public function updateMany(array $ids, array $data): array
    {
        $payload = array_intersect_key($data, array_flip(['title', 'alt_text']));

        if ($payload === []) {
            return [];
        }

        $updated = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($payload, &$updated): void {
            $media->update($payload);
            $updated[] = $media->fresh(['folder', 'uploader']);
        });

        return $updated;
    }

    /**
     * Delete media and remove the underlying file from storage.
     *
     * @param Media $media
     * @return bool
     */
    public function delete(Media $media): bool
    {
        $this->assertLibraryMedia($media);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        return $media->delete();
    }

    /**
     * Delete multiple media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        $this->libraryMediaByIds($ids)->each(function (Media $media) use (&$deleted): void {
            if ($this->delete($media)) {
                $deleted++;
            }
        });

        return $deleted;
    }

    /**
     * Force delete a media item permanently.
     *
     * @param Media $media
     * @return bool
     */
    public function forceDelete(Media $media): bool
    {
        $this->assertLibraryMedia($media);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        return $media->forceDelete();
    }

    /**
     * Force delete multiple media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function forceDeleteMany(array $ids): int
    {
        $deleted = 0;

        $this->libraryMediaByIds($ids)->each(function (Media $media) use (&$deleted): void {
            if ($this->forceDelete($media)) {
                $deleted++;
            }
        });

        return $deleted;
    }

    /**
     * Restore a soft-deleted media item.
     *
     * @param Media $media
     * @return Media
     */
    public function restore(Media $media): Media
    {
        $media->restore();

        return $media->fresh(['folder', 'uploader']);
    }

    /**
     * Restore multiple soft-deleted media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Media::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * @return array{total: int, images: int, storage_mb: float}
     */
    public function statistics(): array
    {
        $total = Media::query()->where('collection_name', self::LIBRARY_COLLECTION)->count();
        $images = Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->where('mime_type', 'like', 'image/%')
            ->count();
        $totalSize = (int) Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->sum('size');

        return [
            'total' => $total,
            'images' => $images,
            'storage_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    /**
     * Move a single library file into a folder.
     *
     * @param Media $media
     * @param int|null $folderId
     * @return Media
     */
    public function moveOne(Media $media, ?int $folderId): Media
    {
        return $this->moveMany([$media->id], $folderId)[0];
    }

    /**
     * Copy a single library file into a folder.
     *
     * @param Media $media
     * @param int|null $folderId
     * @return Media
     */
    public function copyOne(Media $media, ?int $folderId): Media
    {
        return $this->copyMany([$media->id], $folderId)[0];
    }

    /**
     * Remove the background from an image and store a new PNG in the same folder.
     *
     * @param Media $media
     * @param int|string|null $uploadedBy
     * @return Media
     */
    public function removeBackground(Media $media, int|string|null $uploadedBy = null): Media
    {
        $this->assertLibraryMedia($media);

        $mimeType = (string) $media->mime_type;

        if (! str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('Only image files support background removal.');
        }

        if (str_contains($mimeType, 'svg')) {
            throw new RuntimeException('SVG images are not supported for background removal.');
        }

        $maxSize = (int) config('background-removal.max_file_size');

        if ((int) $media->size > $maxSize) {
            throw new RuntimeException('The image exceeds the maximum allowed size for background removal.');
        }

        $disk = Storage::disk($media->disk);
        $sourcePath = $media->getPathRelativeToRoot();

        if (! $sourcePath || ! $disk->exists($sourcePath)) {
            throw new RuntimeException("Source file not found for media [{$media->id}].");
        }

        $tempDirectory = storage_path('app/temp/background-removal');

        if (! is_dir($tempDirectory) && ! mkdir($tempDirectory, 0755, true) && ! is_dir($tempDirectory)) {
            throw new RuntimeException('Could not create a temporary directory for background removal.');
        }

        $inputExtension = pathinfo($media->file_name, PATHINFO_EXTENSION) ?: 'jpg';
        $inputTemp = $tempDirectory.'/'.Str::uuid()->toString().'.'.$inputExtension;
        $outputTemp = $tempDirectory.'/'.Str::uuid()->toString().'.png';

        file_put_contents($inputTemp, $disk->get($sourcePath));
        $processInput = $this->optimizeImageForBackgroundRemoval($inputTemp, $tempDirectory);

        try {
            app(BackgroundRemover::class)->remove($processInput, $outputTemp);

            $outputContents = file_get_contents($outputTemp);

            if ($outputContents === false || $outputContents === '') {
                throw new RuntimeException('Background removal produced an empty file.');
            }

            $folderId = $media->folder_id;
            $storedName = Str::uuid()->toString().'.png';
            $destinationPath = $this->libraryPath($folderId, $storedName);

            $disk->makeDirectory($this->libraryDirectory($folderId));
            $disk->put($destinationPath, $outputContents);

            $baseName = preg_replace('/-no-bg$/i', '', (string) $media->name) ?: (string) $media->name;
            $titleBase = preg_replace('/-no-bg$/i', '', (string) ($media->title ?? $media->name)) ?: ($media->title ?? $media->name);

            $newMedia = Media::query()->create([
                'folder_id' => $folderId,
                'model_type' => self::LIBRARY_MODEL_TYPE,
                'model_id' => $folderId ?? 0,
                'uuid' => (string) Str::uuid(),
                'collection_name' => self::LIBRARY_COLLECTION,
                'name' => $baseName.'-no-bg',
                'title' => $titleBase.' (no background)',
                'alt_text' => $media->alt_text,
                'uploaded_by' => $uploadedBy ?? Auth::id(),
                'file_name' => $storedName,
                'mime_type' => 'image/png',
                'disk' => $media->disk,
                'conversions_disk' => $media->conversions_disk,
                'size' => strlen($outputContents),
                'manipulations' => [],
                'custom_properties' => [
                    'source_media_id' => $media->id,
                    'ai_action' => 'remove_background',
                ],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);

            return $newMedia->fresh(['folder', 'uploader']);
        } finally {
            if ($processInput !== $inputTemp) {
                $this->deleteTempFileIfExists($processInput);
            }

            $this->deleteTempFileIfExists($inputTemp);
            $this->deleteTempFileIfExists($outputTemp);
        }
    }

    /**
     * Delete a temp file without masking the original exception on Windows file locks.
     *
     * @param string $path
     * @return void
     */
    private function deleteTempFileIfExists(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            if (@unlink($path)) {
                return;
            }

            usleep(200_000);
        }
    }

    /**
     * Downscale very large images before rembg to reduce processing time on CPU.
     *
     * @param string $inputPath
     * @param string $tempDirectory
     * @return string
     */
    private function optimizeImageForBackgroundRemoval(string $inputPath, string $tempDirectory): string
    {
        $info = @getimagesize($inputPath);

        if ($info === false) {
            return $inputPath;
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $maxDimension = (int) config('background-removal.max_dimension', 1920);

        if ($width <= 0 || $height <= 0 || ($width <= $maxDimension && $height <= $maxDimension)) {
            return $inputPath;
        }

        $mime = (string) ($info['mime'] ?? '');
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($inputPath),
            'image/png' => @imagecreatefrompng($inputPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($inputPath) : false,
            default => false,
        };

        if ($source === false) {
            return $inputPath;
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $resized = imagescale($source, $targetWidth, $targetHeight, IMG_BILINEAR_FIXED);
        imagedestroy($source);

        if ($resized === false) {
            return $inputPath;
        }

        $optimizedPath = $tempDirectory.'/'.Str::uuid()->toString().'.jpg';
        imagejpeg($resized, $optimizedPath, 90);
        imagedestroy($resized);

        return is_file($optimizedPath) ? $optimizedPath : $inputPath;
    }

    /**
     * Copy a single library file into a folder.
     *
     * @param Media $media
     * @param int|null $folderId
     * @return Media
     */
    private function copy(Media $media, ?int $folderId): Media
    {
        $disk = Storage::disk($media->disk);
        $sourcePath = $media->getPathRelativeToRoot();

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException("Source file not found for media [{$media->id}].");
        }

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $storedName = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
        $destinationPath = $this->libraryPath($folderId, $storedName);

        $disk->makeDirectory($this->libraryDirectory($folderId));
        $disk->copy($sourcePath, $destinationPath);

        $copy = Media::query()->create([
            'folder_id' => $folderId,
            'model_type' => self::LIBRARY_MODEL_TYPE,
            'model_id' => $folderId ?? 0,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::LIBRARY_COLLECTION,
            'name' => $media->name,
            'title' => $media->title,
            'alt_text' => $media->alt_text,
            'uploaded_by' => Auth::id(),
            'file_name' => $storedName,
            'mime_type' => $media->mime_type,
            'disk' => $media->disk,
            'conversions_disk' => $media->conversions_disk,
            'size' => $media->size,
            'manipulations' => $media->manipulations,
            'custom_properties' => $media->custom_properties,
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        return $copy->fresh(['folder', 'uploader']);
    }

    /**
     * Relocate a library file to a new folder on the disk.
     *
     * @param Media $media
     * @param int|null $folderId
     * @return void
     */
    private function relocateLibraryFile(Media $media, ?int $folderId): void
    {
        if ((int) ($media->folder_id ?? 0) === (int) ($folderId ?? 0)) {
            return;
        }

        $disk = Storage::disk($media->disk);
        $currentPath = $media->getPathRelativeToRoot();
        $newPath = $this->libraryPath($folderId, $media->file_name);

        if ($currentPath === $newPath) {
            return;
        }

        if ($disk->exists($currentPath)) {
            $disk->makeDirectory($this->libraryDirectory($folderId));
            $disk->move($currentPath, $newPath);
        }
    }

    /**
     * Get the directory path for a library folder.
     *
     * @param int|null $folderId
     * @return string
     */
    private function libraryDirectory(?int $folderId): string
    {
        return 'media/library/'.($folderId ?? 'root');
    }

    /**
     * Get the full path for a file within a library folder.
     *
     * @param int|null $folderId
     * @param string $fileName
     * @return string
     */
    private function libraryPath(?int $folderId, string $fileName): string
    {
        return $this->libraryDirectory($folderId).'/'.$fileName;
    }

    /**
     * Retrieve a collection of media items by their IDs.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Media>
     */
    private function libraryMediaByIds(array $ids): Collection
    {
        $items = $this->query()->whereIn('id', $ids)->get();

        if ($items->count() !== count(array_unique($ids))) {
            throw new RuntimeException('One or more media items could not be found in the library.');
        }

        return $items;
    }

    /**
     * Ensure the given media belongs to the library collection.
     *
     * @param Media $media
     * @return void
     */
    private function assertLibraryMedia(Media $media): void
    {
        if ($media->collection_name !== self::LIBRARY_COLLECTION) {
            throw new RuntimeException('Only library media items can be managed through the media library.');
        }
    }

    /**
     * @param string $url
     * @param string|null $contentDisposition
     * @return string
     */
    private function resolveImportFilename(string $url, ?string $contentDisposition): string
    {
        if ($contentDisposition && preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $contentDisposition, $matches)) {
            return urldecode(trim($matches[1]));
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            $basename = basename($path);

            if ($basename !== '' && $basename !== '/') {
                return urldecode($basename);
            }
        }

        return 'imported-file';
    }

    /**
     * @param string $mimeType
     * @return string|null
     */
    private function extensionFromMimeType(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            default => null,
        };
    }
}
