<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\Tenant\InventoryMovementType;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\AdjustInventoryRequest;
use App\Http\Requests\Tenant\TransferInventoryRequest;
use App\Http\Requests\Tenant\UpdateInventoryRequest;
use App\Http\Resources\Tenant\InventoryMovementResource;
use App\Http\Resources\Tenant\InventoryResource;
use App\Http\Resources\Tenant\ProductStockAlertResource;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages variant inventory levels, movements, and stock alerts.
 */
class InventoryController extends ApiController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'product_id' => ['nullable', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'low_stock' => ['nullable', 'boolean'],
        ]);

        $inventories = $this->inventoryService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $inventories,
            InventoryResource::collection($inventories),
            'Inventory records retrieved successfully.',
        );
    }

    public function show(Inventory $inventory): JsonResponse
    {
        $this->authorize('view', $inventory);

        return $this->success(
            new InventoryResource($this->inventoryService->find($inventory->id)),
            'Inventory record retrieved successfully.',
        );
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('update', $inventory);

        $inventory = $this->inventoryService->update($inventory, $request->validated());

        return $this->updated(
            new InventoryResource($inventory),
            'Inventory settings updated successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function adjust(AdjustInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('update', $inventory);

        $validated = $request->validated();
        $inventory = $this->inventoryService->adjust(
            $inventory,
            (int) $validated['quantity_change'],
            $validated['type'] ?? InventoryMovementType::Adjustment->value,
            reason: $validated['reason'] ?? null,
        );

        return $this->updated(
            new InventoryResource($inventory),
            'Inventory adjusted successfully.',
        );
    }

    /**
     * @throws Throwable
     */
    public function transfer(TransferInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('update', $inventory);

        $validated = $request->validated();
        $result = $this->inventoryService->transfer(
            $inventory,
            (int) $validated['destination_warehouse_id'],
            (int) $validated['quantity'],
            $validated['reason'] ?? null,
        );

        return $this->success([
            'source' => new InventoryResource($result['source']),
            'destination' => new InventoryResource($result['destination']),
        ], 'Inventory transferred successfully.');
    }

    public function movements(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $filters = $request->validate([
            'inventory_id' => ['nullable', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string'],
        ]);

        $movements = $this->inventoryService->paginateMovements(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $movements,
            InventoryMovementResource::collection($movements),
            'Inventory movements retrieved successfully.',
        );
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        return $this->success(
            $this->inventoryService->statistics(),
            'Inventory statistics retrieved successfully.',
        );
    }

    public function stockAlerts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'product_variant_id' => ['nullable', 'integer'],
            'is_notified' => ['nullable', 'boolean'],
        ]);

        $alerts = $this->inventoryService->paginateStockAlerts(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated(
            $alerts,
            ProductStockAlertResource::collection($alerts),
            'Stock alerts retrieved successfully.',
        );
    }

    public function variantInventories(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);
        abort_unless($variant->product_id === $product->id, 404);

        $inventories = $this->inventoryService->inventoriesForVariant($variant);

        return $this->success(
            InventoryResource::collection($inventories),
            'Variant inventory retrieved successfully.',
        );
    }
}
