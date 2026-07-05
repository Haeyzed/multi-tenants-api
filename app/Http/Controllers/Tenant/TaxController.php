<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\CalculateTaxRequest;
use App\Http\Requests\Tenant\StoreTaxRateRequest;
use App\Http\Requests\Tenant\StoreTaxRegionRequest;
use App\Http\Requests\Tenant\StoreTaxRuleRequest;
use App\Http\Resources\Tenant\TaxRateResource;
use App\Http\Resources\Tenant\TaxRegionResource;
use App\Http\Resources\Tenant\TaxRuleResource;
use App\Models\Tenant\TaxClass;
use App\Models\Tenant\TaxRate;
use App\Services\Tenant\TaxRateService;
use App\Services\Tenant\TaxRuleService;
use App\Services\Tenant\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Manages tax rates, rules, regions, and calculation.
 */
class TaxController extends ApiController
{
    public function __construct(
        private readonly TaxService $taxService,
        private readonly TaxRateService $taxRateService,
        private readonly TaxRuleService $taxRuleService,
    ) {}

    /**
     * Create a new tax rate.
     *
     * @throws Throwable
     */
    public function storeRate(StoreTaxRateRequest $request, TaxClass $taxClass): JsonResponse
    {
        $this->authorize('update', $taxClass);

        $rate = $this->taxRateService->create($taxClass, $request->validated());

        return $this->created(
            new TaxRateResource($rate),
            'Tax rate created successfully.',
        );
    }

    /**
     * Update an existing tax rate.
     *
     * @throws Throwable
     */
    public function updateRate(StoreTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $rate = $this->taxRateService->update($taxRate, $request->validated());

        return $this->updated(
            new TaxRateResource($rate),
            'Tax rate updated successfully.',
        );
    }

    /**
     * Delete a tax rate.
     *
     * @throws Throwable
     */
    public function destroyRate(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $this->taxRateService->delete($taxRate);

        return $this->deleted('Tax rate deleted successfully.');
    }

    /**
     * Create a new tax rule.
     *
     * @throws Throwable
     */
    public function storeRule(StoreTaxRuleRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate->taxClass);

        $rule = $this->taxRuleService->create($taxRate, $request->validated());

        return $this->created(
            new TaxRuleResource($rule->load('taxRegion')),
            'Tax rule created successfully.',
        );
    }

    /**
     * Create a new tax region.
     *
     * @throws Throwable
     */
    public function storeRegion(StoreTaxRegionRequest $request): JsonResponse
    {
        $this->authorize('create', TaxClass::class);

        $region = $this->taxRuleService->createRegion($request->validated());

        return $this->created(
            new TaxRegionResource($region),
            'Tax region created successfully.',
        );
    }

    /**
     * Calculate tax for a given amount and region.
     */
    public function calculate(CalculateTaxRequest $request): JsonResponse
    {
        Gate::authorize('tax.calculate');

        $result = $this->taxService->calculate(
            (float) $request->validated('amount'),
            $request->safe()->only(['country_code', 'state_code', 'postal_code']),
            $request->integer('tax_class_id') ?: null,
        );

        return $this->success($result, 'Tax calculated successfully.');
    }
}
