<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\UnitsExport;
use App\Exports\Tenant\UnitsImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ConvertUnitRequest;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreUnitRequest;
use App\Http\Requests\Tenant\UpdateUnitRequest;
use App\Http\Resources\Tenant\UnitResource;
use App\Imports\Tenant\UnitsImport;
use App\Models\Tenant\Unit;
use App\Services\Tenant\UnitService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing measurement units within a tenant store.
 */
class UnitController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly UnitService $unitService,
    ) {}

    /**
     * Get a paginated list of units.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'type' => ['nullable', 'array'],
            'type.*' => ['string'],
            'is_base' => ['nullable', 'boolean'],
        ]);

        $units = $this->unitService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($units, UnitResource::collection($units), 'Units retrieved successfully.');
    }

    /**
     * Create a new unit.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $unit = $this->unitService->create($request->validated());

        return $this->created(
            new UnitResource($unit),
            'Unit created successfully.',
        );
    }

    /**
     * Get a single unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return $this->success(new UnitResource($this->unitService->find($unit->id)), 'Unit retrieved successfully.');
    }

    /**
     * Update an existing unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $unit = $this->unitService->update($unit, $request->validated());

        return $this->updated(
            new UnitResource($unit),
            'Unit updated successfully.',
        );
    }

    /**
     * Delete a unit.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $this->unitService->delete($unit);

        return $this->deleted('Unit deleted successfully.');
    }

    /**
     * Get unit statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        return $this->success($this->unitService->statistics(), 'Unit statistics retrieved successfully.');
    }

    /**
     * Get unit options.
     */
    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $validated = $request->validate([
            'type' => ['nullable', 'string'],
        ]);

        return $this->success(
            $this->unitService->getOptions($validated['type'] ?? null),
            'Unit options retrieved successfully.',
        );
    }

    /**
     * Get unit type options.
     */
    public function typeOptions(): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        return $this->success($this->unitService->getTypeOptions(), 'Unit type options retrieved successfully.');
    }

    /**
     * Get a unit by code.
     */
    public function showByCode(string $code): JsonResponse
    {
        $unit = $this->unitService->findByCode($code);

        $this->authorize('view', $unit);

        return $this->success(new UnitResource($unit), 'Unit retrieved successfully.');
    }

    /**
     * Get units by type.
     */
    public function byType(string $type): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        return $this->success(
            UnitResource::collection($this->unitService->getByType($type)),
            'Units retrieved successfully.',
        );
    }

    /**
     * Get the base unit for a type.
     */
    public function baseUnit(string $type): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $unit = $this->unitService->getBaseUnit($type);

        if ($unit === null) {
            return $this->success(null, 'No base unit found for this type.');
        }

        return $this->success(new UnitResource($unit), 'Base unit retrieved successfully.');
    }

    /**
     * Convert a value between unit codes.
     */
    public function convert(ConvertUnitRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $validated = $request->validated();

        try {
            $result = $this->unitService->convert(
                (float) $validated['value'],
                $validated['from_code'],
                $validated['to_code'],
            );
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success([
            'value' => $result,
            'from_code' => $validated['from_code'],
            'to_code' => $validated['to_code'],
        ], 'Unit conversion completed successfully.');
    }

    /**
     * Delete multiple units.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Unit::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:units,id'],
        ]);

        $count = $this->unitService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} units deleted successfully.");
    }

    /**
     * Export units to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Unit::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            UnitsExport::availableColumns(),
            ['integer', 'exists:units,id'],
        ));

        $units = $this->unitService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new UnitsExport($units, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'units-export',
            'Units Export',
            'Your units export is attached.',
        );
    }

    /**
     * Download a sample import template for units.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Unit::class);

        return $this->importSampleDownload($request, new UnitsImportSample, 'units');
    }

    /**
     * Import units from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new UnitsImport, $request->file('file'));

        return $this->success(null, 'Units imported successfully.');
    }

    /**
     * Set a unit as the base unit for its type.
     */
    public function setBase(Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        $unit = $this->unitService->setBase($unit);

        return $this->updated(new UnitResource($unit), 'Base unit updated successfully.');
    }

    /**
     * Reorder units by ID list.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('updateAny', Unit::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:units,id'],
        ]);

        $this->unitService->reorder($validated['ids']);

        return $this->success(null, 'Units reordered successfully.');
    }
}
