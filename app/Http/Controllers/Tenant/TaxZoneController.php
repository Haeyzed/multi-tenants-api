<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\TaxZonesExport;
use App\Exports\Tenant\TaxZonesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreTaxZoneRequest;
use App\Http\Requests\Tenant\UpdateTaxZoneRequest;
use App\Http\Resources\Tenant\TaxRateResource;
use App\Http\Resources\Tenant\TaxZoneResource;
use App\Imports\Tenant\TaxZonesImport;
use App\Models\Tenant\TaxZone;
use App\Services\Tenant\TaxZoneService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing tax zones within a tenant store.
 */
class TaxZoneController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly TaxZoneService $taxZoneService,
    ) {}

    /**
     * Get a paginated list of tax zones.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxZone::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $taxZones = $this->taxZoneService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $taxZones,
            TaxZoneResource::collection($taxZones),
            'Tax zones retrieved successfully.',
        );
    }

    /**
     * Create a new tax zone.
     */
    public function store(StoreTaxZoneRequest $request): JsonResponse
    {
        $this->authorize('create', TaxZone::class);

        $taxZone = $this->taxZoneService->create($request->validated());

        return $this->created(
            new TaxZoneResource($taxZone),
            'Tax zone created successfully.',
        );
    }

    /**
     * Get a single tax zone.
     */
    public function show(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('view', $taxZone);

        return $this->success(
            new TaxZoneResource($this->taxZoneService->find($taxZone->id)),
            'Tax zone retrieved successfully.',
        );
    }

    /**
     * Update an existing tax zone.
     */
    public function update(UpdateTaxZoneRequest $request, TaxZone $taxZone): JsonResponse
    {
        $this->authorize('update', $taxZone);

        $taxZone = $this->taxZoneService->update($taxZone, $request->validated());

        return $this->updated(
            new TaxZoneResource($taxZone),
            'Tax zone updated successfully.',
        );
    }

    /**
     * Soft delete a tax zone.
     */
    public function destroy(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('delete', $taxZone);

        $this->taxZoneService->delete($taxZone);

        return $this->deleted('Tax zone deleted successfully.');
    }

    /**
     * Get tax zone statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', TaxZone::class);

        return $this->success(
            $this->taxZoneService->statistics(),
            'Tax zone statistics retrieved successfully.',
        );
    }

    /**
     * Get tax zone options for select inputs.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', TaxZone::class);

        return $this->success(
            $this->taxZoneService->getOptions(),
            'Tax zone options retrieved successfully.',
        );
    }

    /**
     * Delete multiple tax zones.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', TaxZone::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_zones,id'],
        ]);

        $deleted = $this->taxZoneService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} tax zone(s) deleted successfully.",
        );
    }

    /**
     * Export tax zones to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', TaxZone::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            TaxZonesExport::availableColumns(),
            ['integer', 'exists:tax_zones,id'],
        ));

        $taxZones = $this->taxZoneService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TaxZonesExport($taxZones, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'tax-zones-export',
            'Tax Zones Export',
            'Your tax zones export is attached.',
        );
    }

    /**
     * Download a sample import template for tax zones.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', TaxZone::class);

        return $this->importSampleDownload($request, new TaxZonesImportSample, 'tax-zones');
    }

    /**
     * Import tax zones from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', TaxZone::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TaxZonesImport, $request->file('file'));

        return $this->success(null, 'Tax zones imported successfully.');
    }

    /**
     * Permanently delete a tax zone.
     */
    public function forceDestroy(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('forceDelete', $taxZone);

        try {
            $this->taxZoneService->forceDelete($taxZone);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Tax zone permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted tax zone.
     */
    public function restore(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('restore', $taxZone);

        $taxZone = $this->taxZoneService->restore($taxZone);

        return $this->success(
            new TaxZoneResource($taxZone),
            'Tax zone restored successfully.',
        );
    }

    /**
     * Restore multiple soft-deleted tax zones.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', TaxZone::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->taxZoneService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} tax zone(s) restored successfully.");
    }

    /**
     * Reorder tax zones.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', TaxZone::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_zones,id'],
        ]);

        $this->taxZoneService->reorder($validated['ids']);

        return $this->success(null, 'Tax zones reordered successfully.');
    }

    /**
     * Set a tax zone as the default.
     */
    public function setDefault(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('update', $taxZone);

        $taxZone = $this->taxZoneService->setDefault($taxZone);

        return $this->updated(
            new TaxZoneResource($taxZone),
            'Default tax zone updated successfully.',
        );
    }

    /**
     * Toggle the active status of a tax zone.
     */
    public function toggleActive(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('update', $taxZone);

        $taxZone = $this->taxZoneService->toggleActive($taxZone);

        return $this->updated(
            new TaxZoneResource($taxZone),
            'Tax zone status updated successfully.',
        );
    }

    /**
     * Get rates for a tax zone.
     */
    public function rates(TaxZone $taxZone): JsonResponse
    {
        $this->authorize('view', $taxZone);

        $rates = $this->taxZoneService->getRates($taxZone);

        return $this->success(
            TaxRateResource::collection($rates),
            'Tax rates retrieved successfully.',
        );
    }

    /**
     * Find a tax zone matching an address.
     */
    public function matchByAddress(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxZone::class);

        $validated = $request->validate([
            'country' => ['required', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal' => ['nullable', 'string', 'max:20'],
        ]);

        $taxZone = $this->taxZoneService->findByAddress(
            $validated['country'],
            $validated['state'] ?? null,
            $validated['city'] ?? null,
            $validated['postal'] ?? null,
        );

        if ($taxZone === null) {
            return $this->success(null, 'No matching tax zone found.');
        }

        return $this->success(
            new TaxZoneResource($taxZone),
            'Tax zone matched successfully.',
        );
    }

    /**
     * Find a tax zone matching geographic coordinates.
     */
    public function matchByCoordinates(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxZone::class);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $taxZone = $this->taxZoneService->findByCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        if ($taxZone === null) {
            return $this->success(null, 'No matching tax zone found.');
        }

        return $this->success(
            new TaxZoneResource($taxZone),
            'Tax zone matched successfully.',
        );
    }
}
