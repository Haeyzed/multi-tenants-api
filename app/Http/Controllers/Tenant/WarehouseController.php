<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\WarehousesExport;
use App\Exports\Tenant\WarehousesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Controllers\Tenant\Concerns\ImportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreWarehouseLocationRequest;
use App\Http\Requests\Tenant\StoreWarehouseRequest;
use App\Http\Requests\Tenant\StoreWarehouseZoneRequest;
use App\Http\Requests\Tenant\UpdateWarehouseLocationRequest;
use App\Http\Requests\Tenant\UpdateWarehouseRequest;
use App\Http\Requests\Tenant\UpdateWarehouseZoneRequest;
use App\Http\Resources\Tenant\WarehouseLocationResource;
use App\Http\Resources\Tenant\WarehouseResource;
use App\Http\Resources\Tenant\WarehouseZoneResource;
use App\Imports\Tenant\WarehousesImport;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseLocation;
use App\Models\Tenant\WarehouseZone;
use App\Services\Tenant\WarehouseService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * HTTP API for managing warehouses within a tenant store.
 */
class WarehouseController extends ApiController
{
    use ExportsSpreadsheets, ImportsSpreadsheets;

    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    /**
     * Get a paginated list of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $warehouses = $this->warehouseService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $warehouses,
            WarehouseResource::collection($warehouses),
            'Warehouses retrieved successfully.',
        );
    }

    /**
     * Create a new warehouse.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = $this->warehouseService->create($request->validated());

        return $this->created(
            new WarehouseResource($warehouse),
            'Warehouse created successfully.',
        );
    }

    /**
     * Get a single warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return $this->success(
            new WarehouseResource($this->warehouseService->find($warehouse->id)),
            'Warehouse retrieved successfully.',
        );
    }

    /**
     * Update an existing warehouse.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse = $this->warehouseService->update($warehouse, $request->validated());

        return $this->updated(
            new WarehouseResource($warehouse),
            'Warehouse updated successfully.',
        );
    }

    /**
     * Soft delete a warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        try {
            $this->warehouseService->delete($warehouse);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Warehouse deleted successfully.');
    }

    /**
     * Get warehouse statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        return $this->success(
            $this->warehouseService->statistics(),
            'Warehouse statistics retrieved successfully.',
        );
    }

    /**
     * Get warehouse options for select inputs.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        return $this->success(
            $this->warehouseService->getOptions(),
            'Warehouse options retrieved successfully.',
        );
    }

    /**
     * Get a warehouse by code.
     */
    public function showByCode(string $code): JsonResponse
    {
        $warehouse = $this->warehouseService->findByCode($code);
        $this->authorize('view', $warehouse);

        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse retrieved successfully.',
        );
    }

    /**
     * Get the primary warehouse.
     */
    public function primary(): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouse = $this->warehouseService->getPrimary();

        return $this->success(
            $warehouse ? new WarehouseResource($warehouse) : null,
            'Primary warehouse retrieved successfully.',
        );
    }

    /**
     * Delete multiple warehouses.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Warehouse::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:warehouses,id'],
        ]);

        $deleted = $this->warehouseService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} warehouse(s) deleted successfully.",
        );
    }

    /**
     * Export warehouses to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            WarehousesExport::availableColumns(),
            ['integer', 'exists:warehouses,id'],
        ));

        $warehouses = $this->warehouseService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new WarehousesExport($warehouses, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'warehouses-export',
            'Warehouses Export',
            'Your warehouses export is attached.',
        );
    }

    /**
     * Download a sample import template for warehouses.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Warehouse::class);

        return $this->importSampleDownload($request, new WarehousesImportSample, 'warehouses');
    }

    /**
     * Import warehouses from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        return $this->runSpreadsheetImport(
            new WarehousesImport,
            $request->file('file'),
            'Warehouses imported successfully.',
        );
    }

    /**
     * Permanently delete a warehouse.
     */
    public function forceDestroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('forceDelete', $warehouse);

        try {
            $this->warehouseService->forceDelete($warehouse);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Warehouse permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted warehouse.
     */
    public function restore(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('restore', $warehouse);

        $warehouse = $this->warehouseService->restore($warehouse);

        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse restored successfully.',
        );
    }

    /**
     * Toggle warehouse active status.
     */
    public function toggleActive(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse = $this->warehouseService->toggleActive($warehouse);

        return $this->updated(
            new WarehouseResource($warehouse),
            'Warehouse status updated successfully.',
        );
    }

    /**
     * Set a warehouse as primary.
     */
    public function setPrimary(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse = $this->warehouseService->setPrimary($warehouse);

        return $this->updated(
            new WarehouseResource($warehouse),
            'Primary warehouse updated successfully.',
        );
    }

    // ── Zones ──

    /**
     * List zones for a warehouse.
     */
    public function zones(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return $this->success(
            WarehouseZoneResource::collection($this->warehouseService->getZones($warehouse)),
            'Warehouse zones retrieved successfully.',
        );
    }

    /**
     * Create a zone for a warehouse.
     */
    public function storeZone(StoreWarehouseZoneRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $zone = $this->warehouseService->createZone($warehouse->id, $request->validated());

        return $this->created(
            new WarehouseZoneResource($zone),
            'Warehouse zone created successfully.',
        );
    }

    /**
     * Update a warehouse zone.
     */
    public function updateZone(
        UpdateWarehouseZoneRequest $request,
        Warehouse $warehouse,
        WarehouseZone $zone,
    ): JsonResponse {
        $this->authorize('update', $warehouse);
        $this->ensureZoneBelongsToWarehouse($warehouse, $zone);

        $zone = $this->warehouseService->updateZone($zone, $request->validated());

        return $this->updated(
            new WarehouseZoneResource($zone),
            'Warehouse zone updated successfully.',
        );
    }

    /**
     * Delete a warehouse zone.
     */
    public function destroyZone(Warehouse $warehouse, WarehouseZone $zone): JsonResponse
    {
        $this->authorize('update', $warehouse);
        $this->ensureZoneBelongsToWarehouse($warehouse, $zone);

        $this->warehouseService->deleteZone($zone);

        return $this->deleted('Warehouse zone deleted successfully.');
    }

    // ── Locations ──

    /**
     * List locations for a warehouse.
     */
    public function locations(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return $this->success(
            WarehouseLocationResource::collection($this->warehouseService->getLocations($warehouse)),
            'Warehouse locations retrieved successfully.',
        );
    }

    /**
     * Create a location for a warehouse.
     */
    public function storeLocation(StoreWarehouseLocationRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $location = $this->warehouseService->createLocation($warehouse->id, $request->validated());

        return $this->created(
            new WarehouseLocationResource($location),
            'Warehouse location created successfully.',
        );
    }

    /**
     * Update a warehouse location.
     */
    public function updateLocation(
        UpdateWarehouseLocationRequest $request,
        Warehouse $warehouse,
        WarehouseLocation $location,
    ): JsonResponse {
        $this->authorize('update', $warehouse);
        $this->ensureLocationBelongsToWarehouse($warehouse, $location);

        $location = $this->warehouseService->updateLocation($location, $request->validated());

        return $this->updated(
            new WarehouseLocationResource($location),
            'Warehouse location updated successfully.',
        );
    }

    /**
     * Delete a warehouse location.
     */
    public function destroyLocation(Warehouse $warehouse, WarehouseLocation $location): JsonResponse
    {
        $this->authorize('update', $warehouse);
        $this->ensureLocationBelongsToWarehouse($warehouse, $location);

        $this->warehouseService->deleteLocation($location);

        return $this->deleted('Warehouse location deleted successfully.');
    }

    private function ensureZoneBelongsToWarehouse(Warehouse $warehouse, WarehouseZone $zone): void
    {
        if ($zone->warehouse_id !== $warehouse->id) {
            throw new NotFoundHttpException('Warehouse zone not found.');
        }
    }

    private function ensureLocationBelongsToWarehouse(Warehouse $warehouse, WarehouseLocation $location): void
    {
        if ($location->warehouse_id !== $warehouse->id) {
            throw new NotFoundHttpException('Warehouse location not found.');
        }
    }
}
