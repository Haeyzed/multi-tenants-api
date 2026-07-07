<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Exports\Central\PlansExport;
use App\Exports\Central\PlansImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Central\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Central\ExportResourceRequest;
use App\Http\Requests\Central\StorePlanRequest;
use App\Http\Requests\Central\UpdatePlanRequest;
use App\Http\Resources\Central\PlanResource;
use App\Imports\Central\PlansImport;
use App\Models\Central\Plan;
use App\Services\Central\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manages platform subscription plans.
 */
class PlanController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly PlanService $planService,
    )
    {
    }

    /**
     * Get a paginated list of plans.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $plans = $this->planService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($plans, PlanResource::collection($plans), 'Plans retrieved successfully.');
    }

    /**
     * Store a newly created plan in storage.
     *
     * @param StorePlanRequest $request
     * @return JsonResponse
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $this->authorize('create', Plan::class);

        $plan = $this->planService->create($request->validated());

        return $this->created(
            new PlanResource($plan),
            'Plan created successfully.',
        );
    }

    /**
     * Display the specified plan.
     *
     * @param Plan $plan
     * @return JsonResponse
     */
    public function show(Plan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        return $this->success(new PlanResource($this->planService->find($plan->id)), 'Plan retrieved successfully.');
    }

    /**
     * Update the specified plan in storage.
     *
     * @param UpdatePlanRequest $request
     * @param Plan $plan
     * @return JsonResponse
     */
    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        $plan = $this->planService->update($plan, $request->validated());

        return $this->updated(
            new PlanResource($plan),
            'Plan updated successfully.',
        );
    }

    /**
     * Remove the specified plan from storage.
     *
     * @param Plan $plan
     * @return JsonResponse
     */
    public function destroy(Plan $plan): JsonResponse
    {
        $this->authorize('delete', $plan);

        try {
            $this->planService->delete($plan);
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->deleted('Plan deleted successfully.');
    }

    /**
     * Get plan options.
     *
     * @return JsonResponse
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        return $this->success($this->planService->getOptions(), 'Plan options retrieved successfully.');
    }

    /**
     * Get plan statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        return $this->success($this->planService->statistics(), 'Plan statistics retrieved successfully.');
    }

    /**
     * Delete multiple plans.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Plan::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:plans,id'],
        ]);

        $count = $this->planService->deleteMany($validated['ids']);

        return $this->success(null, "{$count} plans deleted successfully.");
    }

    /**
     * Export plans to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Plan::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            PlansExport::availableColumns(),
            ['integer', 'exists:plans,id'],
        ));

        $plans = $this->planService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new PlansExport($plans, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'plans-export',
            'Plans Export',
            'Your plans export is attached.',
        );
    }

    /**
     * Download a sample import template for plans.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Plan::class);

        return $this->importSampleDownload($request, new PlansImportSample(), 'plans');
    }

    /**
     * Import plans from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Plan::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new PlansImport, $request->file('file'));

        return $this->success(null, 'Plans imported successfully.');
    }
}
