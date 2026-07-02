<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Middleware is defined in config/tenancy.php (`middleware` key).
| Domains are stored fully qualified (e.g. acme.multi-tenants-api.test).
|
*/

Route::middleware(config('tenancy.middleware'))
    ->prefix('api')
    ->group(function (): void {
        require __DIR__.'/api/tenant.php';
    });
