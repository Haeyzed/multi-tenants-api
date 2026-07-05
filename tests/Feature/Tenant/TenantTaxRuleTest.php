<?php

declare(strict_types=1);

it('manages tenant tax rules with statistics export and toggles', function (): void {
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

    $rateResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rates", [
            'name' => 'Standard VAT',
            'tax_class_id' => $taxClassId,
            'tax_zone_id' => $taxZoneId,
            'rate' => 20,
            'priority' => 1,
            'is_active' => true,
        ]);
    $rateResponse->assertCreated();
    $taxRateId = $rateResponse->json('data.id');

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rules", [
            'tax_rate_id' => $taxRateId,
            'applicable_type' => 'product',
            'applicable_id' => 1,
            'rule_type' => 'override',
            'adjustment_rate' => 0,
            'is_active' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.rule_type', 'override')
        ->assertJsonPath('data.applicable_type', 'product');

    $taxRuleId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/tax-rules/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rules/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/tax-rules/{$taxRuleId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/tax-rules/{$taxRuleId}")
        ->assertSuccessful();
});
