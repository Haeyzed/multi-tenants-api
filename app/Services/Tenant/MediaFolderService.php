<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\MediaFolder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant media library folder records and queries.
 */
class MediaFolderService
{
    /**
     * Get folders filtered for the media browser.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, MediaFolder>
     */
    public function list(array $filters = []): Collection
    {
        return MediaFolder::query()
            ->filter($filters)
            ->withCount('media')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get folder tree for the media library sidebar.
     *
     * @return list<array<string, mixed>>
     */
    public function getTree(): array
    {
        $folders = MediaFolder::query()
            ->withCount('media')
            ->orderBy('name')
            ->get();

        return $this->buildTree($folders);
    }

    /**
     * Find folder by ID or fail.
     */
    public function findOrFail(int $id): MediaFolder
    {
        return MediaFolder::query()
            ->withCount('media')
            ->findOrFail($id);
    }

    /**
     * Create a folder.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MediaFolder
    {
        $data['path'] = $this->buildPath($data['name'], $data['parent_id'] ?? null);

        return MediaFolder::query()->create($data);
    }

    /**
     * Update folder.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MediaFolder $folder, array $data): MediaFolder
    {
        if (isset($data['name']) || array_key_exists('parent_id', $data)) {
            $data['path'] = $this->buildPath(
                $data['name'] ?? $folder->name,
                $data['parent_id'] ?? $folder->parent_id,
            );
        }

        $folder->update($data);

        return $folder->fresh()->loadCount('media');
    }

    /**
     * Delete folder when empty.
     */
    public function delete(MediaFolder $folder): bool
    {
        if ($folder->media()->exists() || $folder->children()->exists()) {
            return false;
        }

        return $folder->delete();
    }

    /**
     * Delete multiple empty folders by ID.
     *
     * @param  list<int>  $ids
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        MediaFolder::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (MediaFolder $folder) use (&$deleted): void {
                if ($this->delete($folder)) {
                    $deleted++;
                }
            });

        return $deleted;
    }

    /**
     * Force delete a folder permanently.
     */
    public function forceDelete(MediaFolder $folder): bool
    {
        return $folder->forceDelete();
    }

    /**
     * Force delete multiple folders by ID.
     *
     * @param  list<int>  $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        return MediaFolder::query()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Restore a soft-deleted folder.
     */
    public function restore(MediaFolder $folder): MediaFolder
    {
        $folder->restore();

        return $folder->fresh()->loadCount('media');
    }

    /**
     * Restore multiple soft-deleted folders by ID.
     *
     * @param  list<int>  $ids
     */
    public function restoreMany(array $ids): int
    {
        return MediaFolder::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Build a nested tree structure of folders.
     *
     * @param  Collection<int, MediaFolder>  $folders
     * @return list<array<string, mixed>>
     */
    private function buildTree(Collection $folders, ?int $parentId = null): array
    {
        return $folders
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (MediaFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'parent_id' => $folder->parent_id,
                'media_count' => $folder->media_count ?? $folder->media()->count(),
                'children' => $this->buildTree($folders, $folder->id),
            ])
            ->all();
    }

    /**
     * Build the path for a folder based on its parent.
     */
    private function buildPath(string $name, ?int $parentId): string
    {
        if ($parentId === null) {
            return $name;
        }

        $parent = MediaFolder::query()->find($parentId);

        return $parent ? trim($parent->path.'/'.$name, '/') : $name;
    }
}
