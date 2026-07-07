<?php

declare(strict_types=1);

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductCostHistory;
use App\Models\Tenant\ProductFaq;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Warehouse;

it('duplicates a product as draft with copied nested data', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Warehouse::query()->create([
        'name' => 'Main Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_primary' => true,
    ]);
    $product = Product::factory()->create(['name' => 'Original Widget']);
    ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'WIDGET-001',
        'price' => 29.99,
        'cost_price' => 12.50,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    ProductFaq::query()->create([
        'product_id' => $product->id,
        'question' => 'Is it waterproof?',
        'answer' => 'Yes, IP67 rated.',
        'is_visible' => true,
        'sort_order' => 0,
    ]);
    $productId = $product->id;
    tenancy()->end();

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/duplicate");

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Original Widget (Copy)')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.default_variant.sku', 'WIDGET-001-COPY');

    $copyId = $response->json('data.id');

    tenancy()->initialize($ctx->tenant);
    expect(Product::query()->find($copyId)?->faqs)->toHaveCount(1);
    expect(Product::query()->find($productId)?->faqs)->toHaveCount(1);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products/{$copyId}/faqs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.question', 'Is it waterproof?');
});

it('manages product faq crud', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $productId = $product->id;
    tenancy()->end();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/faqs", [
            'question' => 'What is the warranty?',
            'answer' => 'Two years manufacturer warranty.',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.question', 'What is the warranty?')
        ->assertJsonPath('data.is_visible', true);

    $faqId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/faqs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/faqs/{$faqId}", [
            'answer' => 'Three years manufacturer warranty.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.answer', 'Three years manufacturer warranty.');

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/faqs/{$faqId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/faqs")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('records cost history when variant cost price changes', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $product = Product::factory()->create();
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'title' => 'Default',
        'sku' => 'COST-001',
        'price' => 49.99,
        'cost_price' => 10.00,
        'is_default' => true,
        'status' => 'active',
        'visibility' => 'visible',
    ]);
    $productId = $product->id;
    $variantId = $variant->id;
    tenancy()->end();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/products/{$productId}/variants/{$variantId}", [
            'cost_price' => 14.50,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.cost_price', '14.5000');

    tenancy()->initialize($ctx->tenant);
    $history = ProductCostHistory::query()->where('product_variant_id', $variantId)->first();
    expect($history)->not->toBeNull();
    expect((string) $history->old_cost)->toBe('10.0000');
    expect((string) $history->new_cost)->toBe('14.5000');
    expect($history->changed_by)->not->toBeNull();
    tenancy()->end();
});
