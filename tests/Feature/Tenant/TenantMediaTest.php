<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('lists and uploads tenant media library files', function (): void {
    $ctx = initializeTenantForTest();

    $uploadResponse = $this->withToken($ctx->token)
        ->post("http://{$ctx->domain}/api/v1/tenant/media", [
            'file' => UploadedFile::fake()->image('product.jpg'),
            'title' => 'Product Photo',
            'alt_text' => 'A product image',
        ]);

    $uploadResponse->assertCreated()
        ->assertJsonPath('data.title', 'Product Photo')
        ->assertJsonPath('data.alt_text', 'A product image')
        ->assertJsonPath('data.collection', 'library')
        ->assertJsonPath('data.mime_type', 'image/jpeg');

    $mediaUrl = $uploadResponse->json('data.url');
    expect($mediaUrl)->toContain('.multi-tenants-api.test/tenancy/assets/');

    $mediaId = $uploadResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/media")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $mediaId)
        ->assertJsonPath('data.0.title', 'Product Photo');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/media/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.images', 1);
});

it('imports media from a remote URL', function (): void {
    $ctx = initializeTenantForTest();

    $imageContent = UploadedFile::fake()->image('logo.jpg')->getContent();

    Http::fake([
        'https://example.com/logo.png' => Http::response($imageContent, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $response = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/media/import-url", [
            'url' => 'https://example.com/logo.png',
            'title' => 'Remote Logo',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Remote Logo')
        ->assertJsonPath('data.mime_type', 'image/jpeg');
});

it('manages tenant media folders', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/media-folders", [
            'name' => 'Product Images',
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Product Images')
        ->assertJsonPath('data.path', 'Product Images');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/media-folders/tree")
        ->assertSuccessful()
        ->assertJsonPath('data.tree.0.name', 'Product Images');
});
