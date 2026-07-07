<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\TaxClassesExport;
use App\Exports\Tenant\TaxClassesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreTaxClassRequest;
use App\Http\Requests\Tenant\UpdateTaxClassRequest;
use App\Http\Resources\Tenant\TaxClassResource;
use App\Http\Resources\Tenant\TaxRateResource;
use App\Imports\Tenant\TaxClassesImport;
use App\Models\Tenant\TaxClass;
use App\Services\Tenant\TaxClassService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing tax classes within a tenant store.
 */
class TaxClassController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly TaxClassService $taxClassService,
    )
    {
    }

    /**
     * Get a paginated list of tax classes.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxClass::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $taxClasses = $this->taxClassService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $taxClasses,
            TaxClassResource::collection($taxClasses),
            'Tax classes retrieved successfully.',
        );
    }

    /**
     * Create a new tax class.
     */
    public function store(StoreTaxClassRequest $request): JsonResponse
    {
        $this->authorize('create', TaxClass::class);

        $taxClass = $this->taxClassService->create($request->validated());

        return $this->created(
            new TaxClassResource($taxClass),
            'Tax class created successfully.',
        );
    }

    /**
     * Get a single tax class.
     */
    public function show(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('view', $taxClass);

        return $this->success(
            new TaxClassResource($this->taxClassService->find($taxClass->id)),
            'Tax class retrieved successfully.',
        );
    }

    /**
     * Update an existing tax class.
     */
    public function update(UpdateTaxClassRequest $request, TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $taxClass = $this->taxClassService->update($taxClass, $request->validated());

        return $this->updated(
            new TaxClassResource($taxClass),
            'Tax class updated successfully.',
        );
    }

    /**
     * Soft delete a tax class.
     */
    public function destroy(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('delete', $taxClass);

        $this->taxClassService->delete($taxClass);

        return $this->deleted('Tax class deleted successfully.');
    }

    /**
     * Get tax class statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', TaxClass::class);

        return $this->success(
            $this->taxClassService->statistics(),
            'Tax class statistics retrieved successfully.',
        );
    }

    /**
     * Get tax class options for select inputs.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', TaxClass::class);

        return $this->success(
            $this->taxClassService->getOptions(),
            'Tax class options retrieved successfully.',
        );
    }

    /**
     * Get a tax class by code.
     */
    public function showByCode(string $code): JsonResponse
    {
        $taxClass = $this->taxClassService->findByCode($code);
        $this->authorize('view', $taxClass);

        return $this->success(
            new TaxClassResource($taxClass),
            'Tax class retrieved successfully.',
        );
    }

    /**
     * Delete multiple tax classes.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', TaxClass::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_classes,id'],
        ]);

        $deleted = $this->taxClassService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} tax class(es) deleted successfully.",
        );
    }

    /**
     * Export tax classes to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', TaxClass::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            TaxClassesExport::availableColumns(),
            ['integer', 'exists:tax_classes,id'],
        ));

        $taxClasses = $this->taxClassService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TaxClassesExport($taxClasses, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'tax-classes-export',
            'Tax Classes Export',
            'Your tax classes export is attached.',
        );
    }

    /**
     * Download a sample import template for tax classes.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', TaxClass::class);

        return $this->importSampleDownload($request, new TaxClassesImportSample, 'tax-classes');
    }

    /**
     * Import tax classes from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', TaxClass::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TaxClassesImport, $request->file('file'));

        return $this->success(null, 'Tax classes imported successfully.');
    }

    /**
     * Permanently delete a tax class.
     */
    public function forceDestroy(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('forceDelete', $taxClass);

        try {
            $this->taxClassService->forceDelete($taxClass);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Tax class permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted tax class.
     */
    public function restore(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('restore', $taxClass);

        $taxClass = $this->taxClassService->restore($taxClass);

        return $this->success(
            new TaxClassResource($taxClass),
            'Tax class restored successfully.',
        );
    }

    /**
     * Restore multiple soft-deleted tax classes.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', TaxClass::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->taxClassService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} tax class(es) restored successfully.");
    }

    /**
     * Reorder tax classes.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', TaxClass::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_classes,id'],
        ]);

        $this->taxClassService->reorder($validated['ids']);

        return $this->success(null, 'Tax classes reordered successfully.');
    }

    /**
     * Set a tax class as the default.
     */
    public function setDefault(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $taxClass = $this->taxClassService->setDefault($taxClass);

        return $this->updated(
            new TaxClassResource($taxClass),
            'Default tax class updated successfully.',
        );
    }

    /**
     * Toggle the active status of a tax class.
     */
    public function toggleActive(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $taxClass = $this->taxClassService->toggleActive($taxClass);

        return $this->updated(
            new TaxClassResource($taxClass),
            'Tax class status updated successfully.',
        );
    }

    /**
     * Get rates for a tax class.
     */
    public function rates(TaxClass $taxClass): JsonResponse
    {
        $this->authorize('view', $taxClass);

        $rates = $this->taxClassService->getRates($taxClass);

        return $this->success(
            TaxRateResource::collection($rates),
            'Tax rates retrieved successfully.',
        );
    }
}
