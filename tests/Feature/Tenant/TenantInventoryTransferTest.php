<?php

declare(strict_types=1);

use App\Enums\Tenant\InventoryTransferStatus;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryTransfer;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Warehouse;

it('creates a completed inventory transfer and moves stock', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $fromWarehouse = Warehouse::query()->create([
        'name' => 'Source Warehouse',
        'code' => 'WH-SRC',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $toWarehouse = Warehouse::query()->create([
        'name' => 'Destination Warehouse',
        'code' => 'WH-DST',
        'is_active' => true,
        'is_primary' => false,
    ]);
    $product = Product::factory()->create(['name' => 'Transferable Chair']);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'CHAIR-001',
        'price' => 99.00,
        'cost_price' => 35.00,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    Inventory::query()->create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $fromWarehouse->id,
        'quantity' => 30,
    ]);
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventory-transfers", [
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'status' => InventoryTransferStatus::Completed->value,
            'shipping_cost' => 12.50,
            'reason' => 'Replenish retail store',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 10,
                    'unit_cost' => 35.00,
                    'tax_rate' => 10,
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.total_products', 1)
        ->assertJsonPath('data.total_quantity_transferred', 10)
        ->assertJsonPath('data.items.0.quantity', 10);

    tenancy()->initialize($ctx->tenant);
    $sourceInventory = Inventory::query()
        ->where('product_variant_id', $variant->id)
        ->where('warehouse_id', $fromWarehouse->id)
        ->first();
    $destinationInventory = Inventory::query()
        ->where('product_variant_id', $variant->id)
        ->where('warehouse_id', $toWarehouse->id)
        ->first();
    expect($sourceInventory?->quantity)->toBe(20);
    expect($destinationInventory?->quantity)->toBe(10);
    expect(InventoryTransfer::query()->count())->toBe(1);
    tenancy()->end();
});

it('lists inventory transfers', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/inventory-transfers")
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});
