<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\TagsExport;
use App\Exports\Tenant\TagsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreTagRequest;
use App\Http\Requests\Tenant\UpdateTagRequest;
use App\Http\Resources\Tenant\ProductResource;
use App\Http\Resources\Tenant\TagResource;
use App\Imports\Tenant\TagsImport;
use App\Models\Tenant\Tag;
use App\Services\Tenant\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing product tags within a tenant store.
 *
 * Exposes CRUD, bulk operations, import/export, statistics, and extension
 * endpoints such as slug lookup, product listing, toggles, and reordering.
 */
class TagController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly TagService $tagService,
    ) {}

    /**
     * Get a paginated list of tags.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'array'],
            'is_visible.*' => ['string', 'in:visible,hidden'],
        ]);

        $tags = $this->tagService->paginate(
            $filters,
            $request->integer('per_page', config('app.per_page')),
        );

        return $this->paginated($tags, TagResource::collection($tags), 'Tags retrieved successfully.');
    }

    /**
     * Create a new tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $tag = $this->tagService->create($request->validated());

        return $this->created(
            new TagResource($tag),
            'Tag created successfully.',
        );
    }

    /**
     * Get a single tag.
     */
    public function show(Tag $tag): JsonResponse
    {
        $this->authorize('view', $tag);

        return $this->success(new TagResource($this->tagService->find($tag->id)), 'Tag retrieved successfully.');
    }

    /**
     * Update an existing tag.
     */
    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $tag = $this->tagService->update($tag, $request->validated());

        return $this->updated(
            new TagResource($tag),
            'Tag updated successfully.',
        );
    }

    /**
     * Delete a tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $this->tagService->delete($tag);

        return $this->deleted('Tag deleted successfully.');
    }

    /**
     * Get tag options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        return $this->success($this->tagService->getOptions(), 'Tag options retrieved successfully.');
    }

    /**
     * Get tag statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        return $this->success($this->tagService->statistics(), 'Tag statistics retrieved successfully.');
    }

    /**
     * Delete multiple tags.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Tag::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $count = $this->tagService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} tags deleted successfully.");
    }

    /**
     * Export tags to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Tag::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            TagsExport::availableColumns(),
            ['integer', 'exists:tags,id'],
        ));

        $tags = $this->tagService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TagsExport($tags, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'tags-export',
            'Tags Export',
            'Your tags export is attached.',
        );
    }

    /**
     * Download a sample import template for tags.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Tag::class);

        return $this->importSampleDownload($request, new TagsImportSample, 'tags');
    }

    /**
     * Import tags from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TagsImport, $request->file('file'));

        return $this->success(null, 'Tags imported successfully.');
    }

    /**
     * Get a tag by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $tag = $this->tagService->findBySlug($slug);

        $this->authorize('view', $tag);

        return $this->success(new TagResource($tag), 'Tag retrieved successfully.');
    }

    /**
     * Get products for a tag.
     */
    public function products(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('view', $tag);

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->tagService->getProducts($tag, $filters);

        return $this->paginated(
            $products,
            ProductResource::collection($products),
            'Tag products retrieved successfully.',
        );
    }

    /**
     * Toggle tag visibility.
     */
    public function toggleVisibility(Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $tag = $this->tagService->toggleVisibility($tag);

        return $this->updated(new TagResource($tag), 'Tag visibility toggled successfully.');
    }

    /**
     * Recalculate and update the tag products count.
     */
    public function updateProductsCount(Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $this->tagService->updateProductsCount($tag);

        return $this->success(
            new TagResource($tag->fresh()),
            'Tag products count updated successfully.',
        );
    }

    /**
     * Reorder tags by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Tag::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $this->tagService->reorder($validated['ids']);

        return $this->success(null, 'Tags reordered successfully.');
    }
}
