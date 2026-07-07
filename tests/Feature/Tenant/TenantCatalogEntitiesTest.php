<?php

declare(strict_types=1);

it('manages tenant tags with statistics options import and export', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tags", [
            'name' => 'Summer Sale',
            'color' => '#ff0000',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Summer Sale')
        ->assertJsonPath('data.is_visible', true);

    $tagId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tags/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tags/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.value', $tagId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tags/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tags/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/tags/{$tagId}", [
            'name' => 'Summer Sale Updated',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Summer Sale Updated');

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/tags/{$tagId}")
        ->assertSuccessful();
});

it('manages tenant product labels with statistics options import and export', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/product-labels", [
            'name' => 'New',
            'color' => '#ffffff',
            'background_color' => '#3b82f6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.is_active', true);

    $labelId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/product-labels/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/product-labels/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.value', $labelId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/product-labels/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/product-labels/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/product-labels/{$labelId}", [
            'name' => 'New Updated',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'New Updated');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/product-labels/{$labelId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/product-labels/{$labelId}")
        ->assertSuccessful();
});

it('manages tenant attributes with values', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/attributes", [
            'name' => 'Color',
            'code' => 'color',
            'type' => 'select',
            'display_type' => 'swatch',
            'is_filterable' => true,
            'is_variant' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Color')
        ->assertJsonPath('data.code', 'color');

    $attributeId = $createResponse->json('data.id');

    $valueResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/attributes/{$attributeId}/values", [
            'value' => 'Red',
            'color_hex' => '#ff0000',
            'sort_order' => 1,
        ]);

    $valueResponse->assertCreated()
        ->assertJsonPath('data.value', 'Red');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/attributes/{$attributeId}/values")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/attributes/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);
});

it('manages tenant attribute sets', function (): void {
    $ctx = initializeTenantForTest();

    $attribute = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/attributes", [
            'name' => 'Size',
            'code' => 'size',
            'type' => 'select',
        ])
        ->assertCreated()
        ->json('data');

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/attribute-sets", [
            'name' => 'Apparel',
            'is_active' => true,
            'attribute_ids' => [$attribute['id']],
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Apparel');

    $setId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/attribute-sets/{$setId}/attributes")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('manages tenant collections', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/collections", [
            'name' => 'Featured Products',
            'type' => 'manual',
            'is_visible' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Featured Products')
        ->assertJsonPath('data.type', 'manual');

    $collectionId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/collections/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/collections/{$collectionId}/toggle-featured")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/collections/{$collectionId}")
        ->assertSuccessful();
});
