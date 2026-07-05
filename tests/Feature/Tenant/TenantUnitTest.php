<?php

declare(strict_types=1);

it('manages tenant units with statistics options import export and conversion', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/units", [
            'name' => 'Kilogram',
            'code' => 'kg',
            'symbol' => 'kg',
            'type' => 'weight',
            'conversion_factor' => 1,
            'is_base' => true,
            'sort_order' => 1,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Kilogram')
        ->assertJsonPath('data.code', 'kg')
        ->assertJsonPath('data.is_base', true);

    $unitId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/units", [
            'name' => 'Gram',
            'code' => 'g',
            'symbol' => 'g',
            'type' => 'weight',
            'conversion_factor' => 0.001,
            'is_base' => false,
            'sort_order' => 2,
        ])
        ->assertCreated();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.base', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.value', $unitId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/code/kg")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $unitId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/type/weight")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/type/weight/base")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $unitId);

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/units/convert", [
            'value' => 1000,
            'from_code' => 'g',
            'to_code' => 'kg',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.value', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/units/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/units/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/units/{$unitId}", [
            'name' => 'Kilogram Updated',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Kilogram Updated');

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/units/{$unitId}")
        ->assertSuccessful();
});
