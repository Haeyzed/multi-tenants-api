<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\BrandsExport;
use App\Exports\Tenant\BrandsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreBrandRequest;
use App\Http\Requests\Tenant\UpdateBrandRequest;
use App\Http\Resources\Tenant\BrandResource;
use App\Http\Resources\Tenant\ProductResource;
use App\Imports\Tenant\BrandsImport;
use App\Models\Tenant\Brand;
use App\Services\Tenant\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing product brands within a tenant store.
 *
 * Exposes CRUD, bulk operations, import/export, statistics, and extension
 * endpoints such as slug lookup, product listing, toggles, and reordering.
 */
class BrandController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly BrandService $brandService,
    )
    {
    }

    /**
     * Get a paginated list of brands.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'array'],
            'is_visible.*' => ['string', 'in:visible,hidden'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $brands = $this->brandService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($brands, BrandResource::collection($brands), 'Brands retrieved successfully.');
    }

    /**
     * Create a new brand.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->authorize('create', Brand::class);

        $brand = $this->brandService->create($request->validated());

        return $this->created(
            new BrandResource($brand),
            'Brand created successfully.',
        );
    }

    /**
     * Get a single brand.
     */
    public function show(Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);

        return $this->success(new BrandResource($this->brandService->find($brand->id)), 'Brand retrieved successfully.');
    }

    /**
     * Update an existing brand.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $brand = $this->brandService->update($brand, $request->validated());

        return $this->updated(
            new BrandResource($brand),
            'Brand updated successfully.',
        );
    }

    /**
     * Soft delete a brand.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);

        $this->brandService->delete($brand);

        return $this->deleted('Brand deleted successfully.');
    }

    /**
     * Get brand options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        return $this->success($this->brandService->getOptions(), 'Brand options retrieved successfully.');
    }

    /**
     * Get brand statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        return $this->success($this->brandService->statistics(), 'Brand statistics retrieved successfully.');
    }

    /**
     * Delete multiple brands.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Brand::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:brands,id'],
        ]);

        $count = $this->brandService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} brands deleted successfully.");
    }

    /**
     * Export brands to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Brand::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            BrandsExport::availableColumns(),
            ['integer', 'exists:brands,id'],
        ));

        $brands = $this->brandService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new BrandsExport($brands, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'brands-export',
            'Brands Export',
            'Your brands export is attached.',
        );
    }

    /**
     * Download a sample import template for brands.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Brand::class);

        return $this->importSampleDownload($request, new BrandsImportSample, 'brands');
    }

    /**
     * Import brands from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Brand::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new BrandsImport, $request->file('file'));

        return $this->success(null, 'Brands imported successfully.');
    }

    /**
     * Force delete a brand permanently.
     */
    public function forceDestroy(Brand $brand): JsonResponse
    {
        $this->authorize('forceDelete', $brand);

        $this->brandService->forceDelete($brand);

        return $this->deleted('Brand permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted brand.
     */
    public function restore(Brand $brand): JsonResponse
    {
        $this->authorize('restore', $brand);

        $brand = $this->brandService->restore($brand);

        return $this->success(
            new BrandResource($brand),
            'Brand restored successfully.'
        );
    }

    /**
     * Restore multiple soft-deleted brands.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', Brand::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->brandService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} brands restored successfully.");
    }

    /**
     * Get a brand by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $brand = $this->brandService->findBySlug($slug);

        $this->authorize('view', $brand);

        return $this->success(new BrandResource($brand), 'Brand retrieved successfully.');
    }

    /**
     * Get products for a brand.
     */
    public function products(Request $request, Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);

        $filters = $request->validate([
            'is_visible' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->brandService->getProducts($brand, $filters);

        return $this->paginated(
            $products,
            ProductResource::collection($products),
            'Brand products retrieved successfully.',
        );
    }

    /**
     * Toggle brand visibility.
     */
    public function toggleVisibility(Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $brand = $this->brandService->toggleVisibility($brand);

        return $this->updated(new BrandResource($brand), 'Brand visibility toggled successfully.');
    }

    /**
     * Toggle brand featured status.
     */
    public function toggleFeatured(Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $brand = $this->brandService->toggleFeatured($brand);

        return $this->updated(new BrandResource($brand), 'Brand featured status toggled successfully.');
    }

    /**
     * Recalculate and update the brand products count.
     */
    public function updateProductsCount(Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $this->brandService->updateProductsCount($brand);

        return $this->success(
            new BrandResource($brand->fresh()),
            'Brand products count updated successfully.',
        );
    }

    /**
     * Reorder brands by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Brand::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:brands,id'],
        ]);

        $this->brandService->reorder($validated['ids']);

        return $this->success(null, 'Brands reordered successfully.');
    }
}
