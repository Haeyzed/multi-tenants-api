<?php

declare(strict_types=1);

it('manages tenant suppliers with nested contacts addresses and bank accounts', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers", [
            'name' => 'Acme Supplies',
            'code' => 'ACME-001',
            'contact_email' => 'info@acme.test',
            'is_active' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Acme Supplies')
        ->assertJsonPath('data.code', 'ACME-001');

    $supplierId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/suppliers/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/suppliers/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Acme Supplies')
        ->assertJsonPath('data.0.value', $supplierId);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/suppliers/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers/export")
        ->assertSuccessful();

    $contactResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/contacts", [
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'is_primary' => true,
        ]);

    $contactResponse->assertCreated()
        ->assertJsonPath('data.name', 'Jane Doe')
        ->assertJsonPath('data.is_primary', true);

    $contactId = $contactResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/contacts")
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $contactId);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/contacts/{$contactId}", [
            'name' => 'Jane Smith',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Jane Smith');

    $addressResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/addresses", [
            'type' => 'office',
            'address_line_1' => '123 Main St',
            'city' => 'Lagos',
            'country' => 'NG',
            'is_default' => true,
        ]);

    $addressResponse->assertCreated()
        ->assertJsonPath('data.city', 'Lagos');

    $addressId = $addressResponse->json('data.id');

    $bankResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/bank-accounts", [
            'account_name' => 'Acme Supplies Ltd',
            'account_number' => '0123456789',
            'bank_name' => 'First Bank',
            'currency' => 'NGN',
            'is_default' => true,
        ]);

    $bankResponse->assertCreated()
        ->assertJsonPath('data.bank_name', 'First Bank');

    $bankAccountId = $bankResponse->json('data.id');

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/toggle-active")
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/contacts/{$contactId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/addresses/{$addressId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}/bank-accounts/{$bankAccountId}")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/suppliers/{$supplierId}")
        ->assertSuccessful();
});
