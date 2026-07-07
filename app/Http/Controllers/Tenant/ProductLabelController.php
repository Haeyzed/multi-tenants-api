<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\ProductLabelsExport;
use App\Exports\Tenant\ProductLabelsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreProductLabelRequest;
use App\Http\Requests\Tenant\UpdateProductLabelRequest;
use App\Http\Resources\Tenant\ProductLabelResource;
use App\Imports\Tenant\ProductLabelsImport;
use App\Models\Tenant\ProductLabel;
use App\Services\Tenant\ProductLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing product labels within a tenant store.
 */
class ProductLabelController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly ProductLabelService $productLabelService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductLabel::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $productLabels = $this->productLabelService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $productLabels,
            ProductLabelResource::collection($productLabels),
            'Product labels retrieved successfully.',
        );
    }

    public function store(StoreProductLabelRequest $request): JsonResponse
    {
        $this->authorize('create', ProductLabel::class);

        $productLabel = $this->productLabelService->create($request->validated());

        return $this->created(
            new ProductLabelResource($productLabel),
            'Product label created successfully.',
        );
    }

    public function show(ProductLabel $product_label): JsonResponse
    {
        $this->authorize('view', $product_label);

        return $this->success(
            new ProductLabelResource($this->productLabelService->find($product_label->id)),
            'Product label retrieved successfully.',
        );
    }

    public function update(UpdateProductLabelRequest $request, ProductLabel $product_label): JsonResponse
    {
        $this->authorize('update', $product_label);

        $productLabel = $this->productLabelService->update($product_label, $request->validated());

        return $this->updated(
            new ProductLabelResource($productLabel),
            'Product label updated successfully.',
        );
    }

    public function destroy(ProductLabel $product_label): JsonResponse
    {
        $this->authorize('delete', $product_label);

        $this->productLabelService->delete($product_label);

        return $this->deleted('Product label deleted successfully.');
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', ProductLabel::class);

        return $this->success(
            $this->productLabelService->getOptions(),
            'Product label options retrieved successfully.',
        );
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', ProductLabel::class);

        return $this->success(
            $this->productLabelService->statistics(),
            'Product label statistics retrieved successfully.',
        );
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', ProductLabel::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:product_labels,id'],
        ]);

        $count = $this->productLabelService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} product labels deleted successfully.");
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', ProductLabel::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            ProductLabelsExport::availableColumns(),
            ['integer', 'exists:product_labels,id'],
        ));

        $productLabels = $this->productLabelService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new ProductLabelsExport($productLabels, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'product-labels-export',
            'Product Labels Export',
            'Your product labels export is attached.',
        );
    }

    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', ProductLabel::class);

        return $this->importSampleDownload($request, new ProductLabelsImportSample, 'product-labels');
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', ProductLabel::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new ProductLabelsImport, $request->file('file'));

        return $this->success(null, 'Product labels imported successfully.');
    }

    public function toggleActive(ProductLabel $product_label): JsonResponse
    {
        $this->authorize('update', $product_label);

        $productLabel = $this->productLabelService->toggleActive($product_label);

        return $this->updated(
            new ProductLabelResource($productLabel),
            'Product label status toggled successfully.',
        );
    }
}
