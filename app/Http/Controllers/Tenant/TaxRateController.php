<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\TaxRatesExport;
use App\Exports\Tenant\TaxRatesImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreTaxRateRequest;
use App\Http\Requests\Tenant\UpdateTaxRateRequest;
use App\Http\Resources\Tenant\TaxRateResource;
use App\Http\Resources\Tenant\TaxRuleResource;
use App\Imports\Tenant\TaxRatesImport;
use App\Models\Tenant\TaxRate;
use App\Services\Tenant\TaxRateService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP API for managing tax rates within a tenant store.
 */
class TaxRateController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly TaxRateService $taxRateService,
    ) {}

    /**
     * Get a paginated list of tax rates.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'tax_class_id' => ['nullable', 'integer', 'exists:tax_classes,id'],
            'tax_zone_id' => ['nullable', 'integer', 'exists:tax_zones,id'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $taxRates = $this->taxRateService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $taxRates,
            TaxRateResource::collection($taxRates),
            'Tax rates retrieved successfully.',
        );
    }

    /**
     * Create a new tax rate.
     */
    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $this->authorize('create', TaxRate::class);

        $taxRate = $this->taxRateService->create($request->validated());

        return $this->created(
            new TaxRateResource($taxRate),
            'Tax rate created successfully.',
        );
    }

    /**
     * Get a single tax rate.
     */
    public function show(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('view', $taxRate);

        return $this->success(
            new TaxRateResource($this->taxRateService->find($taxRate->id)),
            'Tax rate retrieved successfully.',
        );
    }

    /**
     * Update an existing tax rate.
     */
    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate);

        $taxRate = $this->taxRateService->update($taxRate, $request->validated());

        return $this->updated(
            new TaxRateResource($taxRate),
            'Tax rate updated successfully.',
        );
    }

    /**
     * Soft delete a tax rate.
     */
    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('delete', $taxRate);

        $this->taxRateService->delete($taxRate);

        return $this->deleted('Tax rate deleted successfully.');
    }

    /**
     * Get tax rate statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);

        return $this->success(
            $this->taxRateService->statistics(),
            'Tax rate statistics retrieved successfully.',
        );
    }

    /**
     * Get tax rate options for select inputs.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);

        return $this->success(
            $this->taxRateService->getOptions(),
            'Tax rate options retrieved successfully.',
        );
    }

    /**
     * Delete multiple tax rates.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', TaxRate::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_rates,id'],
        ]);

        $deleted = $this->taxRateService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} tax rate(s) deleted successfully.",
        );
    }

    /**
     * Export tax rates to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', TaxRate::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            TaxRatesExport::availableColumns(),
            ['integer', 'exists:tax_rates,id'],
        ));

        $taxRates = $this->taxRateService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TaxRatesExport($taxRates, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'tax-rates-export',
            'Tax Rates Export',
            'Your tax rates export is attached.',
        );
    }

    /**
     * Download a sample import template for tax rates.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', TaxRate::class);

        return $this->importSampleDownload($request, new TaxRatesImportSample, 'tax-rates');
    }

    /**
     * Import tax rates from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', TaxRate::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new TaxRatesImport, $request->file('file'));

        return $this->success(null, 'Tax rates imported successfully.');
    }

    /**
     * Permanently delete a tax rate.
     */
    public function forceDestroy(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('forceDelete', $taxRate);

        try {
            $this->taxRateService->forceDelete($taxRate);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->deleted('Tax rate permanently deleted successfully.');
    }

    /**
     * Restore a soft-deleted tax rate.
     */
    public function restore(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('restore', $taxRate);

        $taxRate = $this->taxRateService->restore($taxRate);

        return $this->success(
            new TaxRateResource($taxRate),
            'Tax rate restored successfully.',
        );
    }

    /**
     * Restore multiple soft-deleted tax rates.
     */
    public function restoreMany(Request $request): JsonResponse
    {
        $this->authorize('restoreAny', TaxRate::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->taxRateService->restoreMany($validated['ids']);

        return $this->success(null, "{$count} tax rate(s) restored successfully.");
    }

    /**
     * Toggle the active status of a tax rate.
     */
    public function toggleActive(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate);

        $taxRate = $this->taxRateService->toggleActive($taxRate);

        return $this->updated(
            new TaxRateResource($taxRate),
            'Tax rate status updated successfully.',
        );
    }

    /**
     * Get rules for a tax rate.
     */
    public function rules(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('view', $taxRate);

        $rules = $this->taxRateService->getRules($taxRate);

        return $this->success(
            TaxRuleResource::collection($rules),
            'Tax rules retrieved successfully.',
        );
    }

    /**
     * Calculate tax for an amount, class, and zone.
     */
    public function calculate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_class_id' => ['required', 'integer', 'exists:tax_classes,id'],
            'tax_zone_id' => ['required', 'integer', 'exists:tax_zones,id'],
        ]);

        $amount = (float) $validated['amount'];
        $taxTotal = $this->taxRateService->calculateTax(
            $amount,
            (int) $validated['tax_class_id'],
            (int) $validated['tax_zone_id'],
        );

        return $this->success([
            'subtotal' => $amount,
            'tax_total' => $taxTotal,
            'total' => round($amount + $taxTotal, 2),
        ], 'Tax calculated successfully.');
    }
}
