<?php

declare(strict_types=1);

it('manages tenant tax classes with statistics options import export and toggles', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-classes", [
            'name' => 'Standard Rate',
            'code' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Standard Rate')
        ->assertJsonPath('data.code', 'standard')
        ->assertJsonPath('data.is_default', true);

    $taxClassId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1)
        ->assertJsonPath('data.default', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Standard Rate')
        ->assertJsonPath('data.0.value', $taxClassId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/code/standard")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $taxClassId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/{$taxClassId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/{$taxClassId}", [
            'name' => 'Updated Standard',
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Standard')
        ->assertJsonPath('data.is_active', true);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/{$taxClassId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-classes/{$taxClassId}/restore")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $taxClassId);
});
