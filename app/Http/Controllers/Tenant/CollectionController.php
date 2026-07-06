<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\CollectionsExport;
use App\Exports\Tenant\CollectionsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreCollectionRequest;
use App\Http\Requests\Tenant\UpdateCollectionRequest;
use App\Http\Resources\Tenant\CollectionResource;
use App\Http\Resources\Tenant\ProductResource;
use App\Imports\Tenant\CollectionsImport;
use App\Models\Tenant\Collection;
use App\Services\Tenant\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing product collections within a tenant store.
 *
 * Exposes CRUD, bulk operations, import/export, statistics, and extension
 * endpoints such as slug lookup, product sync, toggles, and reordering.
 */
class CollectionController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly CollectionService $collectionService,
    ) {}

    /**
     * Get a paginated list of collections.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Collection::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'array'],
            'is_visible.*' => ['string', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string'],
        ]);

        $collections = $this->collectionService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($collections, CollectionResource::collection($collections), 'Collections retrieved successfully.');
    }

    /**
     * Create a new collection.
     */
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $this->authorize('create', Collection::class);

        $collection = $this->collectionService->create($request->validated());

        return $this->created(
            new CollectionResource($collection),
            'Collection created successfully.',
        );
    }

    /**
     * Get a single collection.
     */
    public function show(Collection $collection): JsonResponse
    {
        $this->authorize('view', $collection);

        return $this->success(new CollectionResource($this->collectionService->find($collection->id)), 'Collection retrieved successfully.');
    }

    /**
     * Update an existing collection.
     */
    public function update(UpdateCollectionRequest $request, Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        $collection = $this->collectionService->update($collection, $request->validated());

        return $this->updated(
            new CollectionResource($collection),
            'Collection updated successfully.',
        );
    }

    /**
     * Soft delete a collection.
     */
    public function destroy(Collection $collection): JsonResponse
    {
        $this->authorize('delete', $collection);

        $this->collectionService->delete($collection);

        return $this->deleted('Collection deleted successfully.');
    }

    /**
     * Get collection options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Collection::class);

        return $this->success($this->collectionService->getOptions(), 'Collection options retrieved successfully.');
    }

    /**
     * Get collection statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Collection::class);

        return $this->success($this->collectionService->statistics(), 'Collection statistics retrieved successfully.');
    }

    /**
     * Delete multiple collections.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Collection::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:collections,id'],
        ]);

        $count = $this->collectionService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} collections deleted successfully.");
    }

    /**
     * Export collections to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Collection::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            CollectionsExport::availableColumns(),
            ['integer', 'exists:collections,id'],
        ));

        $collections = $this->collectionService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new CollectionsExport($collections, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'collections-export',
            'Collections Export',
            'Your collections export is attached.',
        );
    }

    /**
     * Download a sample import template for collections.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Collection::class);

        return $this->importSampleDownload($request, new CollectionsImportSample, 'collections');
    }

    /**
     * Import collections from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Collection::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new CollectionsImport, $request->file('file'));

        return $this->success(null, 'Collections imported successfully.');
    }

    /**
     * Force delete a collection permanently.
     */
    public function forceDestroy(Collection $collection): JsonResponse
    {
        $this->authorize('forceDelete', $collection);

        $this->collectionService->forceDelete($collection);

        return $this->deleted('Collection permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted collection.
     */
    public function restore(Collection $collection): JsonResponse
    {
        $this->authorize('restore', $collection);

        $collection = $this->collectionService->restore($collection);

        return $this->success(
            new CollectionResource($collection),
            'Collection restored successfully.',
        );
    }

    /**
     * Restore multiple soft-deleted collections.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Collection::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->collectionService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} collections restored successfully.");
    }

    /**
     * Get a collection by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $collection = $this->collectionService->findBySlug($slug);

        $this->authorize('view', $collection);

        return $this->success(new CollectionResource($collection), 'Collection retrieved successfully.');
    }

    /**
     * Get products for a collection.
     */
    public function products(Collection $collection): JsonResponse
    {
        $this->authorize('view', $collection);

        $products = $this->collectionService->getProducts($collection);

        return $this->success(ProductResource::collection($products), 'Collection products retrieved successfully.');
    }

    /**
     * Sync products for a collection.
     */
    public function syncProducts(Request $request, Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        $validated = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $this->collectionService->syncProducts($collection, $validated['product_ids']);

        return $this->success(
            new CollectionResource($this->collectionService->find($collection->id)),
            'Collection products synced successfully.',
        );
    }

    /**
     * Toggle collection visibility.
     */
    public function toggleVisibility(Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        $collection = $this->collectionService->toggleVisibility($collection);

        return $this->updated(new CollectionResource($collection), 'Collection visibility toggled successfully.');
    }

    /**
     * Toggle collection featured status.
     */
    public function toggleFeatured(Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        $collection = $this->collectionService->toggleFeatured($collection);

        return $this->updated(new CollectionResource($collection), 'Collection featured status toggled successfully.');
    }

    /**
     * Refresh products for an automated collection.
     */
    public function refreshAutomated(Collection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        $this->collectionService->refreshAutomated($collection);

        return $this->success(
            new CollectionResource($this->collectionService->find($collection->id)),
            'Automated collection refreshed successfully.',
        );
    }

    /**
     * Reorder collections by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Collection::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:collections,id'],
        ]);

        $this->collectionService->reorder($validated['ids']);

        return $this->success(null, 'Collections reordered successfully.');
    }
}
