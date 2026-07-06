<?php

declare(strict_types=1);

use App\Events\Tenant\StockLow;
use App\Events\Tenant\VariantBackInStock;
use App\Models\Tenant\Attribute;
use App\Models\Tenant\AttributeSet;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Tag;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Facades\Event;

it('authenticates tenant store users', function (): void {
    $ctx = initializeTenantForTest();

    $response = $this->postJson("http://{$ctx->domain}/api/v1/tenant/auth/login", [
        'email' => $ctx->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'user']]);
});

it('returns authenticated tenant profile', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/auth/me")
        ->assertSuccessful()
        ->assertJsonPath('data.email', $ctx->user->email);
});

it('denies tenant routes without authentication', function (): void {
    $ctx = initializeTenantForTest();

    $this->getJson("http://{$ctx->domain}/api/v1/tenant/products")
        ->assertUnauthorized();
});

it('denies product management without permission', function (): void {
    $ctx = initializeTenantForTest(role: 'customer');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
        ])
        ->assertForbidden();
});

it('creates and lists products', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    tenancy()->end();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products", [
            'name' => 'Flash Sneakers',
            'default_variant' => [
                'sku' => 'SNK-001',
                'price' => 149.99,
                'inventory' => ['quantity' => 50],
            ],
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Flash Sneakers')
        ->assertJsonPath('data.sku', 'SNK-001')
        ->assertJsonPath('data.default_variant.sku', 'SNK-001')
        ->assertJsonPath('data.default_variant.inventories.0.quantity', 50);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

it('creates product variants with inventory', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->variable()->create();
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/variants", [
            'title' => 'Size 42',
            'sku' => 'SNK-001-42',
            'price' => 149.99,
            'is_default' => true,
            'inventory' => ['quantity' => 10],
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Size 42')
        ->assertJsonPath('data.inventories.0.quantity', 10);
});

it('syncs product options and generates variants', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->variable()->create(['name' => 'Hoodie']);
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/options", [
            'options' => [
                [
                    'name' => 'Color',
                    'code' => 'color',
                    'values' => [
                        ['value' => 'Red', 'code' => 'red'],
                        ['value' => 'Blue', 'code' => 'blue'],
                    ],
                ],
                [
                    'name' => 'Size',
                    'code' => 'size',
                    'values' => [
                        ['value' => 'Small', 'code' => 's'],
                        ['value' => 'Large', 'code' => 'l'],
                    ],
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/variants/generate", [
            'price' => 59.99,
            'inventory' => ['quantity' => 5],
        ])
        ->assertCreated()
        ->assertJsonCount(4, 'data');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}")
        ->assertSuccessful()
        ->assertJsonPath('data.variants_count', 4)
        ->assertJsonCount(4, 'data.variants');
});

it('adjusts and transfers inventory with movement history', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $mainWarehouse = Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $secondaryWarehouse = Warehouse::query()->create([
        'name' => 'Retail Warehouse',
        'code' => 'WH-RETAIL',
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['name' => 'Desk Lamp']);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'LAMP-001',
        'price' => 49.99,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    $inventory = Inventory::query()->create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $mainWarehouse->id,
        'quantity' => 20,
        'reorder_level' => 5,
    ]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventories/{$inventory->id}/adjust", [
            'quantity_change' => -3,
            'reason' => 'Damaged units',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.quantity', 17);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventories/{$inventory->id}/transfer", [
            'destination_warehouse_id' => $secondaryWarehouse->id,
            'quantity' => 5,
            'reason' => 'Retail replenishment',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.source.quantity', 12)
        ->assertJsonPath('data.destination.quantity', 5);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/inventories/movements?inventory_id={$inventory->id}")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2);
});

it('syncs product suppliers and updates tags on product update', function (): void {
    $ctx = initializeTenantForTest();

    $supplierId = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers", [
            'name' => 'Acme Supply',
            'code' => 'ACME-SUP',
            'is_active' => true,
        ])
        ->assertCreated()
        ->json('data.id');

    tenancy()->initialize($ctx->tenant);
    $tag = Tag::factory()->create(['name' => 'Featured']);
    $product = Product::factory()->create();
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/suppliers", [
            'suppliers' => [
                [
                    'supplier_id' => $supplierId,
                    'supplier_sku' => 'ACM-100',
                    'supplier_cost' => 12.50,
                    'lead_time_days' => 5,
                    'minimum_quantity' => 10,
                    'is_primary' => true,
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.0.supplier_sku', 'ACM-100')
        ->assertJsonPath('data.0.is_primary', true);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}", [
            'tag_ids' => [$tag->id],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.tags.0.id', $tag->id);
});

it('updates product seo metadata and attribute values', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $attribute = Attribute::factory()->create([
        'name' => 'Material',
        'type' => 'text',
    ]);
    $attributeSet = AttributeSet::factory()->create(['name' => 'Apparel']);
    $attributeSet->attributes()->attach($attribute->id, ['sort_order' => 0, 'is_required' => false]);
    $product = Product::factory()->create(['attribute_set_id' => $attributeSet->id]);
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}", [
            'meta_title' => 'Premium Cotton Tee',
            'meta_description' => 'Soft cotton t-shirt for everyday wear.',
            'seo' => [
                'og_title' => 'Cotton Tee',
                'robots_meta' => 'index, follow',
            ],
            'attribute_values' => [
                [
                    'attribute_id' => $attribute->id,
                    'custom_value' => 'Cotton',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.meta_title', 'Premium Cotton Tee')
        ->assertJsonPath('data.seo.og_title', 'Cotton Tee')
        ->assertJsonPath('data.attributes.0.value.value', 'Cotton');
});

it('syncs product relations', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $related = Product::factory()->create(['name' => 'Related Tee']);
    $crossSell = Product::factory()->create(['name' => 'Cross Sell Hat']);
    $productId = $product->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/relations", [
            'related_product_ids' => [$related->id],
            'cross_sell_product_ids' => [$crossSell->id],
            'up_sell_product_ids' => [],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.related_product_ids.0', $related->id)
        ->assertJsonPath('data.cross_sell_product_ids.0', $crossSell->id);
});

it('syncs type-specific product configuration', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $bundleProduct = Product::factory()->create(['type' => 'bundle']);
    $includedProduct = Product::factory()->create(['name' => 'Included Item']);
    $serviceProduct = Product::factory()->create(['type' => 'service']);
    $subscriptionProduct = Product::factory()->create(['type' => 'subscription']);
    $providerId = $ctx->user->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$bundleProduct->id}/bundle-items", [
            'bundle_items' => [
                [
                    'included_product_id' => $includedProduct->id,
                    'quantity' => 2,
                    'is_optional' => false,
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.0.included_product_id', $includedProduct->id)
        ->assertJsonPath('data.0.quantity', 2);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$serviceProduct->id}/service", [
            'service' => [
                'duration_minutes' => 60,
                'location_type' => 'online',
                'meeting_url' => 'https://meet.example.com/room',
            ],
            'providers' => [
                [
                    'provider_id' => $providerId,
                    'is_primary' => true,
                    'commission_rate' => 10,
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.service.duration_minutes', 60)
        ->assertJsonPath('data.providers.0.provider_id', $providerId);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$subscriptionProduct->id}/subscription", [
            'subscription' => [
                'interval' => 'month',
                'interval_count' => 1,
                'trial_days' => 14,
                'prorate' => true,
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.interval', 'month')
        ->assertJsonPath('data.trial_days', 14);
});

it('dispatches inventory threshold events on adjustment', function (): void {
    Event::fake([StockLow::class, VariantBackInStock::class]);

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
        'sku' => 'SKU-LOW',
        'price' => 19.99,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    $inventory = Inventory::query()->create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
        'reorder_level' => 5,
    ]);
    $inventoryId = $inventory->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventories/{$inventoryId}/adjust", [
            'quantity_change' => -6,
        ])
        ->assertSuccessful();

    Event::assertDispatched(StockLow::class);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventories/{$inventoryId}/adjust", [
            'quantity_change' => -4,
        ])
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/inventories/{$inventoryId}/adjust", [
            'quantity_change' => 3,
        ])
        ->assertSuccessful();

    Event::assertDispatched(VariantBackInStock::class);
});

it('manages categories and brands', function (): void {
    $ctx = initializeTenantForTest();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories", ['name' => 'Footwear'])
        ->assertCreated();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands", ['name' => 'Nike'])
        ->assertCreated();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});
