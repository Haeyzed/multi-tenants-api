<?php

declare(strict_types=1);

use App\Models\Tenant\Brand;
use App\Models\Tenant\Category;

it('manages tenant brands with statistics options and export routes', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands", [
            'name' => 'Acme',
            'is_visible' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Acme')
        ->assertJsonPath('data.slug', 'acme');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.visible', 1)
        ->assertJsonPath('data.hidden', 0);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Acme')
        ->assertJsonPath('data.0.value', $createResponse->json('data.id'));

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands/export")
        ->assertSuccessful();
});

it('manages tenant categories with statistics options and export routes', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories", [
            'name' => 'Electronics',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Electronics')
        ->assertJsonPath('data.slug', 'electronics');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.visible', 1)
        ->assertJsonPath('data.hidden', 0)
        ->assertJsonPath('data.root', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Electronics');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories/export")
        ->assertSuccessful();
});

it('filters brands and categories by visibility array', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Brand::factory()->create(['name' => 'Visible Brand', 'is_visible' => true]);
    Brand::factory()->create(['name' => 'Hidden Brand', 'is_visible' => false]);
    Category::factory()->create(['name' => 'Visible Category', 'is_visible' => true]);
    Category::factory()->create(['name' => 'Hidden Category', 'is_visible' => false]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands?is_visible[]=visible")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories?is_visible[]=hidden")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});
