<?php

declare(strict_types=1);

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerGroup;

it('manages tenant customers with statistics options and export routes', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $group = CustomerGroup::factory()->create(['name' => 'VIP']);
    tenancy()->end();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customers", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'customer_group_id' => $group->id,
            'is_active' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.last_name', 'Doe')
        ->assertJsonPath('data.full_name', 'Jane Doe')
        ->assertJsonPath('data.customer_group_id', $group->id);

    $customerId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1)
        ->assertJsonPath('data.inactive', 0);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Jane Doe')
        ->assertJsonPath('data.0.value', $customerId);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/customers/{$customerId}", [
            'first_name' => 'Janet',
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.first_name', 'Janet')
        ->assertJsonPath('data.is_active', false);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customers/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/customers/{$customerId}")
        ->assertSuccessful();
});

it('manages tenant customer groups with statistics options and export routes', function (): void {
    $ctx = initializeTenantForTest();

    $createResponse = $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customer-groups", [
            'name' => 'Wholesale',
            'description' => 'Wholesale buyers',
            'discount_percentage' => 10,
            'is_active' => true,
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Wholesale')
        ->assertJsonPath('data.slug', 'wholesale')
        ->assertJsonPath('data.discount_percent', '10.00')
        ->assertJsonPath('data.discount_percentage', '10.00');

    $groupId = $createResponse->json('data.id');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.active', 1)
        ->assertJsonPath('data.inactive', 0);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/options")
        ->assertSuccessful()
        ->assertJsonPath('data.0.label', 'Wholesale')
        ->assertJsonPath('data.0.value', $groupId);

    $this->withToken($ctx->token)
        ->putJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/{$groupId}", [
            'name' => 'Wholesale Plus',
            'discount_percentage' => 15,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Wholesale Plus')
        ->assertJsonPath('data.discount_percent', '15.00');

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/import/sample")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->postJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/export")
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/{$groupId}")
        ->assertSuccessful();
});

it('filters customers and customer groups by active status array', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    Customer::factory()->create(['first_name' => 'Active', 'last_name' => 'Customer', 'is_active' => true]);
    Customer::factory()->create(['first_name' => 'Inactive', 'last_name' => 'Customer', 'is_active' => false]);
    CustomerGroup::factory()->create(['name' => 'Active Group', 'is_active' => true]);
    CustomerGroup::factory()->create(['name' => 'Inactive Group', 'is_active' => false]);
    tenancy()->end();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers?is_active[]=active")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers?is_active[]=inactive")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups?is_active[]=active")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups?is_active[]=inactive")
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

it('bulk deletes customers and customer groups', function (): void {
    $ctx = initializeTenantForTest();

    tenancy()->initialize($ctx->tenant);
    $customers = Customer::factory()->count(2)->create();
    $groups = CustomerGroup::factory()->count(2)->create();
    tenancy()->end();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/customers/bulk", [
            'ids' => $customers->pluck('id')->all(),
        ])
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->deleteJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/bulk", [
            'ids' => $groups->pluck('id')->all(),
        ])
        ->assertSuccessful();

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customers/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 0);

    $this->withToken($ctx->token)
        ->getJson("http://{$ctx->domain}/api/v1/tenant/customer-groups/statistics")
        ->assertSuccessful()
        ->assertJsonPath('data.total', 0);
});
