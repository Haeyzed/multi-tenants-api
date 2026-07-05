<?php

declare(strict_types=1);

it('manages tenant warehouses with nested zones and locations', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/warehouses", [
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'city' => 'Lagos',
            'country' => 'NG',
            'latitude' => 6.5244,
            'longitude' => 3.3792,
            'is_active' => true,
            'is_primary' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Main Warehouse')
        ->assertJsonPath('data.code', 'WH-MAIN')
        ->assertJsonPath('data.is_primary', true);

    $warehouseId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/warehouses/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.primary', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/warehouses/primary")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $warehouseId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/warehouses/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/warehouses/export")
        ->assertSuccessful();

    $zoneResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/zones", [
            'name' => 'Receiving',
            'code' => 'RCV',
            'zone_type' => 'receiving',
            'is_active' => true,
        ]);

    $zoneResponse->assertCreated()
        ->assertJsonPath('data.name', 'Receiving');

    $zoneId = $zoneResponse->json('data.id');

    $locationResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/locations", [
            'zone_id' => $zoneId,
            'code' => 'A-01-01',
            'name' => 'Shelf A1',
            'is_picking_location' => true,
        ]);

    $locationResponse->assertCreated()
        ->assertJsonPath('data.code', 'A-01-01');

    $locationId = $locationResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/zones")
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $zoneId);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/locations/{$locationId}", [
            'name' => 'Updated Shelf A1',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Shelf A1');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/locations/{$locationId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}/zones/{$zoneId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/warehouses/{$warehouseId}")
        ->assertSuccessful();
});
