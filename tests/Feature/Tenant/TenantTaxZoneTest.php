<?php

declare(strict_types=1);

it('manages tenant tax zones with statistics options import export and toggles', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-zones", [
            'name' => 'United Kingdom',
            'country_code' => 'GB',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'United Kingdom')
        ->assertJsonPath('data.country_code', 'GB')
        ->assertJsonPath('data.is_default', true);

    $taxZoneId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1)
        ->assertJsonPath('data.default', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'United Kingdom')
        ->assertJsonPath('data.0.value', $taxZoneId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/match/address?country=GB")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $taxZoneId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/{$taxZoneId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/{$taxZoneId}", [
            'name' => 'UK (Updated)',
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'UK (Updated)')
        ->assertJsonPath('data.is_active', true);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/{$taxZoneId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-zones/{$taxZoneId}/restore")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $taxZoneId);
});
