<?php

declare(strict_types=1);

use App\Enums\Tenant\InventoryAdjustmentItemAction;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Warehouse;

it('creates an inventory adjustment and updates stock', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $warehouse = Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->create(['name' => 'Adjustable Mug']);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'MUG-001',
        'price' => 12.00,
        'cost_price' => 4.50,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    Inventory::query()->create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
    ]);
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventory-adjustments", [
            'warehouse_id' => $warehouse->id,
            'reference_number' => 'COUNT-001',
            'reason' => 'Stock count correction',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 5,
                    'action' => InventoryAdjustmentItemAction::Addition->value,
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'posted')
        ->assertJsonPath('data.total_products', 1)
        ->assertJsonPath('data.total_quantity_adjusted', 5)
        ->assertJsonPath('data.items.0.quantity_before', 20)
        ->assertJsonPath('data.items.0.quantity_after', 25);

    tenancy()->initialize($ctx->tenant);
    $inventory = Inventory::query()
        ->where('product_variant_id', $variant->id)
        ->where('warehouse_id', $warehouse->id)
        ->first();
    expect($inventory?->quantity)->toBe(25);
    expect(InventoryAdjustment::query()->count())->toBe(1);
    tenancy()->end();
});

it('lists inventory adjustments', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/inventory-adjustments")
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});

it('searches products for inventory adjustments', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $warehouse = Warehouse::query()->create([
        'name' => 'Search Warehouse',
        'code' => 'WH-SRCH',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->create(['name' => 'Searchable Lamp']);
    ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'LAMP-SEARCH',
        'price' => 49.99,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/inventory-adjustments/search-products?warehouse_id={$warehouse->id}&search=Lamp")
        ->assertSuccessful()
        ->assertJsonPath('data.0.product_name', 'Searchable Lamp');
});
