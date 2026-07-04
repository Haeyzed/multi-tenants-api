<?php

declare(strict_types=1);

use App\Models\Tenant\Brand;
use App\Models\Tenant\Category;
use App\Services\Tenant\CategoryService;

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

it('manages category hierarchy depth path tree and safe deletion', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);

    $service = app(CategoryService::class);

    $root = $service->create(['name' => 'Root', 'is_visible' => true, 'sort_order' => 1]);
    $child = $service->create([
        'name' => 'Child',
        'parent_id' => $root->id,
        'is_visible' => true,
        'sort_order' => 1,
    ]);
    $grandchild = $service->create([
        'name' => 'Grandchild',
        'parent_id' => $child->id,
        'is_visible' => true,
        'sort_order' => 1,
    ]);

    expect($root->fresh()->depth)->toBe(0)
        ->and($child->fresh()->depth)->toBe(1)
        ->and($grandchild->fresh()->depth)->toBe(2)
        ->and($child->fresh()->path)->toBe("{$root->id}/{$child->id}")
        ->and($grandchild->fresh()->path)->toBe("{$root->id}/{$child->id}/{$grandchild->id}");

    $tree = $service->getTree();
    expect($tree)->toHaveCount(1)
        ->and($tree[0]['children'][0]['name'])->toBe('Child');

    expect($service->getDescendants($root))->toHaveCount(2)
        ->and($service->getBreadcrumbs($grandchild))->toHaveCount(3);

    expect(fn () => $service->delete($root))
        ->toThrow(DomainException::class, 'Cannot delete category with children.');

    expect($service->deleteMany([$root->id, $child->id, $grandchild->id]))->toBe(1)
        ->and(Category::query()->count())->toBe(2);

    tenancy()->end();
});

it('exposes brand and category extension endpoints', function (): void {
    $ctx = initializeTenantForTest();

    $brandResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands", [
            'name' => 'Nike',
            'is_visible' => true,
            'sort_order' => 1,
        ])
        ->assertCreated();

    $brandId = $brandResponse->json('data.id');
    $brandSlug = $brandResponse->json('data.slug');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/brands/slug/{$brandSlug}")
        ->assertSuccessful()
        ->assertJsonPath('data.slug', $brandSlug);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/brands/{$brandId}/toggle-visibility")
        ->assertSuccessful()
        ->assertJsonPath('data.is_visible', false);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/brands/reorder", [
            'ids' => [$brandId],
        ])
        ->assertSuccessful();

    $categoryResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories", [
            'name' => 'Shoes',
            'is_visible' => true,
            'sort_order' => 1,
        ])
        ->assertCreated();

    $categoryId = $categoryResponse->json('data.id');
    $categorySlug = $categoryResponse->json('data.slug');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/tree")
        ->assertSuccessful()
        ->assertJsonPath('data.tree.0.slug', $categorySlug);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/slug/{$categorySlug}")
        ->assertSuccessful()
        ->assertJsonPath('data.slug', $categorySlug);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/{$categoryId}/breadcrumbs")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/categories/{$categoryId}/toggle-featured")
        ->assertSuccessful()
        ->assertJsonPath('data.is_featured', true);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/categories/{$categoryId}/products")
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
