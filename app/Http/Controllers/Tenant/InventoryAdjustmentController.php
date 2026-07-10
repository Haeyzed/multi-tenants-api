<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreInventoryAdjustmentRequest;
use App\Http\Resources\Tenant\InventoryAdjustmentResource;
use App\Models\Tenant\InventoryAdjustment;
use App\Services\Tenant\InventoryAdjustmentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP API for inventory adjustment documents.
 */
class InventoryAdjustmentController extends ApiController
{
    public function __construct(
        private readonly InventoryAdjustmentService $inventoryAdjustmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryAdjustment::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'warehouse_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $adjustments = $this->inventoryAdjustmentService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $adjustments,
            InventoryAdjustmentResource::collection($adjustments),
            'Inventory adjustments retrieved successfully.',
        );
    }

    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryAdjustment::class);

        try {
            $adjustment = $this->inventoryAdjustmentService->create($request->validated());
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->created(
            new InventoryAdjustmentResource($adjustment),
            'Inventory adjustment created successfully.',
        );
    }

    public function show(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('view', $inventoryAdjustment);

        return $this->success(
            new InventoryAdjustmentResource(
                $this->inventoryAdjustmentService->find($inventoryAdjustment->id)
            ),
            'Inventory adjustment retrieved successfully.',
        );
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): JsonResponse
    {
        $this->authorize('delete', $inventoryAdjustment);

        try {
            $this->inventoryAdjustmentService->delete($inventoryAdjustment);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(null, 'Inventory adjustment deleted successfully.');
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', InventoryAdjustment::class);

        return $this->success(
            $this->inventoryAdjustmentService->statistics(),
            'Inventory adjustment statistics retrieved successfully.',
        );
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('create', InventoryAdjustment::class);

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return $this->success(
            $this->inventoryAdjustmentService->searchProducts(
                (int) $validated['warehouse_id'],
                $validated['search'] ?? null,
                (int) ($validated['limit'] ?? 20),
            ),
            'Products retrieved successfully.',
        );
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', InventoryAdjustment::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = $this->inventoryAdjustmentService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} inventory adjustment(s) deleted successfully.",
        );
    }
}
