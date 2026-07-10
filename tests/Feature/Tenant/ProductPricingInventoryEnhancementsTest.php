<?php

declare(strict_types=1);

use App\Enums\Tenant\UnitConversionOperator;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\VariantWarehousePrice;
use App\Models\Tenant\Warehouse;
use App\Services\Tenant\UnitService;

it('creates products with multi-warehouse initial stock', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $warehouseA = Warehouse::query()->create([
        'name' => 'Lagos DC',
        'code' => 'WH-LAG',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $warehouseB = Warehouse::query()->create([
        'name' => 'Abuja DC',
        'code' => 'WH-ABJ',
        'is_active' => true,
        'is_primary' => false,
    ]);
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Dual Warehouse Tee',
            'default_variant' => [
                'sku' => 'TEE-001',
                'price' => 25.00,
                'inventories' => [
                    [
                        'warehouse_id' => $warehouseA->id,
                        'quantity' => 40,
                        'reorder_level' => 5,
                    ],
                    [
                        'warehouse_id' => $warehouseB->id,
                        'quantity' => 12,
                        'reorder_level' => 3,
                    ],
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.default_variant.inventories.0.quantity', 40)
        ->assertJsonPath('data.default_variant.inventories.1.quantity', 12);
});

it('stores promotional pricing and warehouse-specific prices', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $warehouse = Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Promo Hoodie',
            'default_variant' => [
                'sku' => 'HD-001',
                'price' => 80.00,
                'compare_at_price' => 100.00,
                'sale_price' => 65.00,
                'sale_starts_at' => now()->subDay()->toIso8601String(),
                'sale_ends_at' => now()->addWeek()->toIso8601String(),
                'use_warehouse_pricing' => true,
                'warehouse_prices' => [
                    [
                        'warehouse_id' => $warehouse->id,
                        'price' => 72.50,
                    ],
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.default_variant.sale_price', '65.0000')
        ->assertJsonPath('data.default_variant.is_sale_active', true)
        ->assertJsonPath('data.default_variant.use_warehouse_pricing', true)
        ->assertJsonPath('data.default_variant.warehouse_prices.0.price', '72.5000');

    $variantId = $response->json('data.default_variant.id');

    tenancy()->initialize($ctx->tenant);
    $variant = ProductVariant::query()->findOrFail($variantId);
    expect($variant->resolveSellingPrice($warehouse->id))->toBe(72.5);
    expect($variant->resolveSellingPrice())->toBe(65.0);
    tenancy()->end();
});

it('resolves quantity tier price ahead of warehouse and sale pricing', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'TIER-001',
        'price' => 100.00,
        'sale_price' => 80.00,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addWeek(),
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    $variant->priceTiers()->create([
        'min_quantity' => 10,
        'max_quantity' => null,
        'price' => 55.00,
    ]);
    tenancy()->end();

    tenancy()->initialize($ctx->tenant);
    $variant = ProductVariant::query()->with('priceTiers')->findOrFail($variant->id);
    expect($variant->resolveSellingPrice(null, 10))->toBe(55.0);
    expect($variant->resolveSellingPrice(null, 1))->toBe(80.0);
    tenancy()->end();
});

it('derives unit conversion factor from operator and value', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $service = app(UnitService::class);

    $carton = $service->create([
        'name' => 'Carton',
        'code' => 'carton',
        'symbol' => 'ctn',
        'type' => 'count',
        'conversion_operator' => UnitConversionOperator::Multiply->value,
        'conversion_value' => 24,
        'is_base' => false,
    ]);

    $kilogram = $service->create([
        'name' => 'Kilogram',
        'code' => 'kg',
        'symbol' => 'kg',
        'type' => 'weight',
        'conversion_operator' => UnitConversionOperator::Divide->value,
        'conversion_value' => 1000,
        'is_base' => false,
    ]);
    tenancy()->end();

    expect((float) $carton->conversion_factor)->toBe(24.0);
    expect((float) $kilogram->conversion_factor)->toBe(1000.0);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/{$carton->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.conversion_operator', 'multiply')
        ->assertJsonPath('data.conversion_value', '24.00000000')
        ->assertJsonPath('data.conversion_example', '1 Carton = 24 base units');
});

it('clears warehouse prices when warehouse pricing is disabled', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $warehouse = Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->create();
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'WH-PRICE-001',
        'price' => 50.00,
        'use_warehouse_pricing' => true,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    VariantWarehousePrice::query()->create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'price' => 44.00,
    ]);
    $productId = $product->id;
    $variantId = $variant->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/variants/{$variantId}", [
            'use_warehouse_pricing' => false,
            'warehouse_prices' => [],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.use_warehouse_pricing', false)
        ->assertJsonCount(0, 'data.warehouse_prices');

    tenancy()->initialize($ctx->tenant);
    expect(VariantWarehousePrice::query()->where('product_variant_id', $variantId)->count())->toBe(0);
    tenancy()->end();
});
