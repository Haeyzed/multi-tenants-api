<?php

declare(strict_types=1);

it('manages tenant tax rates with statistics import export and toggles', function (): void {
    $ctx = initializeTenantForTest();

    $classResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-classes", [
            'name' => 'Standard',
            'code' => 'standard',
            'is_active' => true,
        ]);
    $classResponse->assertCreated();
    $taxClassId = $classResponse->json('data.id');

    $zoneResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-zones", [
            'name' => 'United Kingdom',
            'country_code' => 'GB',
            'is_active' => true,
        ]);
    $zoneResponse->assertCreated();
    $taxZoneId = $zoneResponse->json('data.id');

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates", [
            'name' => 'Standard VAT',
            'tax_class_id' => $taxClassId,
            'tax_zone_id' => $taxZoneId,
            'rate' => 20,
            'priority' => 1,
            'is_active' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Standard VAT')
        ->assertJsonPath('data.rate', '20.0000');

    $taxRateId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/calculate", [
            'amount' => 100,
            'tax_class_id' => $taxClassId,
            'tax_zone_id' => $taxZoneId,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.tax_total', 20);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/{$taxRateId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/{$taxRateId}", [
            'name' => 'Updated VAT',
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated VAT');

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/{$taxRateId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates/{$taxRateId}/restore")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $taxRateId);
});
