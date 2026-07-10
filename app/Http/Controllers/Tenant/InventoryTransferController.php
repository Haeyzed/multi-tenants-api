<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreInventoryTransferRequest;
use App\Http\Resources\Tenant\InventoryTransferResource;
use App\Models\Tenant\InventoryTransfer;
use App\Services\Tenant\InventoryTransferService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP API for inventory transfer documents.
 */
class InventoryTransferController extends ApiController
{
    public function __construct(
        private readonly InventoryTransferService $inventoryTransferService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryTransfer::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'from_warehouse_id' => ['nullable', 'integer'],
            'to_warehouse_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $transfers = $this->inventoryTransferService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $transfers,
            InventoryTransferResource::collection($transfers),
            'Inventory transfers retrieved successfully.',
        );
    }

    public function store(StoreInventoryTransferRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryTransfer::class);

        try {
            $transfer = $this->inventoryTransferService->create($request->validated());
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->created(
            new InventoryTransferResource($transfer),
            'Inventory transfer created successfully.',
        );
    }

    public function show(InventoryTransfer $inventoryTransfer): JsonResponse
    {
        $this->authorize('view', $inventoryTransfer);

        return $this->success(
            new InventoryTransferResource(
                $this->inventoryTransferService->find($inventoryTransfer->id)
            ),
            'Inventory transfer retrieved successfully.',
        );
    }

    public function destroy(InventoryTransfer $inventoryTransfer): JsonResponse
    {
        $this->authorize('delete', $inventoryTransfer);

        try {
            $this->inventoryTransferService->delete($inventoryTransfer);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(null, 'Inventory transfer deleted successfully.');
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', InventoryTransfer::class);

        return $this->success(
            $this->inventoryTransferService->statistics(),
            'Inventory transfer statistics retrieved successfully.',
        );
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('create', InventoryTransfer::class);

        $validated = $request->validate([
            'from_warehouse_id' => ['required', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return $this->success(
            $this->inventoryTransferService->searchProducts(
                (int) $validated['from_warehouse_id'],
                $validated['search'] ?? null,
                (int) ($validated['limit'] ?? 20),
            ),
            'Products retrieved successfully.',
        );
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', InventoryTransfer::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = $this->inventoryTransferService->deleteMany($validated['ids']);

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} inventory transfer(s) deleted successfully.",
        );
    }
}
