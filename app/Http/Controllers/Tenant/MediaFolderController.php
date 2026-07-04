<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\BulkDeleteMediaFoldersRequest;
use App\Http\Requests\Tenant\StoreMediaFolderRequest;
use App\Http\Requests\Tenant\UpdateMediaFolderRequest;
use App\Http\Resources\Tenant\MediaFolderResource;
use App\Models\Tenant\MediaFolder;
use App\Services\Tenant\MediaFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Media library folders for organizing tenant files.
 */
class MediaFolderController extends ApiController
{
    public function __construct(
        private readonly MediaFolderService $service,
    ) {}

    /**
     * List all media library folders.
     *
     * @param  Request  $request  Incoming HTTP request.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:media_folders,id'],
        ]);

        if ($request->has('parent_id')) {
            $filters['parent_id'] = $request->input('parent_id');
        }

        $items = $this->service->list($filters);

        return $this->success(
            MediaFolderResource::collection($items),
            'Media folders retrieved successfully.',
        );
    }

    /**
     * Folder tree for the media library sidebar.
     */
    public function tree(): JsonResponse
    {
        return $this->success(
            ['tree' => $this->service->getTree()],
            'Media folder tree retrieved successfully.',
        );
    }

    /**
     * Create a media library folder.
     *
     * @param  StoreMediaFolderRequest  $request  Validated request payload.
     */
    public function store(StoreMediaFolderRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return $this->created(new MediaFolderResource($item), 'Media folder created successfully.');
    }

    /**
     * Find folder by route binding.
     *
     * @param  MediaFolder  $folder  Folder instance.
     */
    public function show(MediaFolder $folder): JsonResponse
    {
        $item = $this->service->findOrFail($folder->id);

        return $this->success(new MediaFolderResource($item), 'Media folder retrieved successfully.');
    }

    /**
     * Update media library folder.
     *
     * @param  UpdateMediaFolderRequest  $request  Validated request payload.
     * @param  MediaFolder  $folder  Folder instance.
     */
    public function update(UpdateMediaFolderRequest $request, MediaFolder $folder): JsonResponse
    {
        $item = $this->service->update($folder, $request->validated());

        return $this->updated(new MediaFolderResource($item), 'Media folder updated successfully.');
    }

    /**
     * Delete media library folder.
     *
     * @param  MediaFolder  $folder  Folder instance.
     */
    public function destroy(MediaFolder $folder): JsonResponse
    {
        $this->service->delete($folder);

        return $this->deleted('Media folder deleted successfully.');
    }

    /**
     * Delete multiple empty folders in one request.
     */
    public function bulkDestroy(BulkDeleteMediaFoldersRequest $request): JsonResponse
    {
        $deleted = $this->service->deleteMany($request->validated('ids'));

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} media folder(s) deleted successfully.",
        );
    }
}
