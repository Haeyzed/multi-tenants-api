<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Exports\Tenant\TaxRulesExport;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Tenant\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Tenant\ExportResourceRequest;
use App\Http\Requests\Tenant\StoreTaxRuleRequest;
use App\Http\Requests\Tenant\UpdateTaxRuleRequest;
use App\Http\Resources\Tenant\TaxRuleResource;
use App\Models\Tenant\TaxRule;
use App\Services\Tenant\TaxRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP API for managing tax rules within a tenant store.
 */
class TaxRuleController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly TaxRuleService $taxRuleService,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxRule::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'applicable_type' => ['nullable', 'string', 'in:product,customer_group'],
            'rule_type' => ['nullable', 'string', 'in:override,exempt,reduce,increase'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $taxRules = $this->taxRuleService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $taxRules,
            TaxRuleResource::collection($taxRules),
            'Tax rules retrieved successfully.',
        );
    }

    public function store(StoreTaxRuleRequest $request): JsonResponse
    {
        $this->authorize('create', TaxRule::class);

        $taxRule = $this->taxRuleService->create($request->validated());

        return $this->created(
            new TaxRuleResource($taxRule),
            'Tax rule created successfully.',
        );
    }

    public function show(TaxRule $taxRule): JsonResponse
    {
        $this->authorize('view', $taxRule);

        return $this->success(
            new TaxRuleResource($this->taxRuleService->find($taxRule->id)),
            'Tax rule retrieved successfully.',
        );
    }

    public function update(UpdateTaxRuleRequest $request, TaxRule $taxRule): JsonResponse
    {
        $this->authorize('update', $taxRule);

        $taxRule = $this->taxRuleService->update($taxRule, $request->validated());

        return $this->updated(
            new TaxRuleResource($taxRule),
            'Tax rule updated successfully.',
        );
    }

    public function destroy(TaxRule $taxRule): JsonResponse
    {
        $this->authorize('delete', $taxRule);

        $this->taxRuleService->delete($taxRule);

        return $this->deleted('Tax rule deleted successfully.');
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', TaxRule::class);

        return $this->success(
            $this->taxRuleService->statistics(),
            'Tax rule statistics retrieved successfully.',
        );
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', TaxRule::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:tax_rules,id'],
        ]);

        $deleted = $this->taxRuleService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} tax rule(s) deleted successfully.",
        );
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', TaxRule::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            TaxRulesExport::availableColumns(),
            ['integer', 'exists:tax_rules,id'],
        ));

        $taxRules = $this->taxRuleService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new TaxRulesExport($taxRules, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'tax-rules-export',
            'Tax Rules Export',
            'Your tax rules export is attached.',
        );
    }

    public function toggleActive(TaxRule $taxRule): JsonResponse
    {
        $this->authorize('update', $taxRule);

        $taxRule = $this->taxRuleService->toggleActive($taxRule);

        return $this->updated(
            new TaxRuleResource($taxRule),
            'Tax rule status updated successfully.',
        );
    }
}
