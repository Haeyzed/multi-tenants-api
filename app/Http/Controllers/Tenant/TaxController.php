<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\CalculateTaxRequest;
use App\Http\Requests\Tenant\StoreTaxRegionRequest;
use App\Http\Resources\Tenant\TaxRegionResource;
use App\Models\Tenant\TaxClass;
use App\Services\Tenant\TaxRuleService;
use App\Services\Tenant\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Manages legacy tax regions and region-based calculation.
 */
class TaxController extends ApiController
{
    public function __construct(
        private readonly TaxService $taxService,
        private readonly TaxRuleService $taxRuleService,
    ) {}

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
