<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\AttributeController;
use App\Http\Controllers\Tenant\AttributeSetController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BrandController;
use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CollectionController;
use App\Http\Controllers\Tenant\CustomerAuthController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerGroupController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\FlashSaleController;
use App\Http\Controllers\Tenant\HrController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\MediaController;
use App\Http\Controllers\Tenant\MediaFolderController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PositionController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\TagController;
use App\Http\Controllers\Tenant\TaxClassController;
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\TaxRateController;
use App\Http\Controllers\Tenant\TaxRuleController;
use App\Http\Controllers\Tenant\TaxZoneController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Tenant\TeamInvitationController;
use App\Http\Controllers\Tenant\UnitController;
use App\Http\Controllers\Tenant\WaitlistController;
use App\Http\Controllers\Tenant\WarehouseController;
use App\Http\Controllers\Tenant\WorldController;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/tenant')->group(function (): void {
    Route::get('health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Tenant API is operational.',
            'data' => ['tenant_id' => tenant('id')],
        ]);
    });

    // Staff/Admin Auth
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

    // Customer Auth
    Route::prefix('customer')->group(function (): void {
        Route::post('auth/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('auth/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('auth/forgot-password', [CustomerAuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
        Route::post('auth/reset-password', [CustomerAuthController::class, 'resetPassword'])->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
            Route::get('auth/me', [CustomerAuthController::class, 'me']);
            Route::put('auth/profile', [CustomerAuthController::class, 'updateProfile']);
            Route::put('auth/password', [CustomerAuthController::class, 'changePassword']);
        });
    });

    Route::get('checkout/queue-status', [FlashSaleController::class, 'queueStatus']);

    Route::post('payments/webhook/{provider}', [PaymentController::class, 'webhook']);

    Route::post('analytics/page-view', [AnalyticsController::class, 'recordPageView']);

    Route::post('team/invitations/accept', [TeamInvitationController::class, 'accept']);

    Route::prefix('world')->group(function (): void {
        Route::get('countries', [WorldController::class, 'countries']);
        Route::get('states', [WorldController::class, 'states']);
        Route::get('cities', [WorldController::class, 'cities']);
        Route::get('currencies', [WorldController::class, 'currencies']);
        Route::get('languages', [WorldController::class, 'languages']);
        Route::get('timezones', [WorldController::class, 'timezones']);
        Route::get('geolocate', [WorldController::class, 'geolocate']);
    });

    Route::get('settings/public', [SettingsController::class, 'showPublic']);
    Route::get('settings/business', [SettingsController::class, 'showBusiness']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);

        Route::get('onboarding', [OnboardingController::class, 'show']);
        Route::post('onboarding/steps/{step}/complete', [OnboardingController::class, 'completeStep']);
        Route::post('onboarding/complete', [OnboardingController::class, 'finish']);

        Route::get('settings', [SettingsController::class, 'index']);
        Route::get('settings/store', [SettingsController::class, 'showStore']);
        Route::get('settings/branding', [SettingsController::class, 'showBranding']);
        Route::get('settings/email', [SettingsController::class, 'showEmail']);
        Route::get('settings/notifications', [SettingsController::class, 'showNotifications']);
        Route::get('settings/invoice', [SettingsController::class, 'showInvoice']);

        Route::put('settings/business', [SettingsController::class, 'updateBusiness']);
        Route::put('settings/store', [SettingsController::class, 'updateStore']);
        Route::put('settings/branding', [SettingsController::class, 'updateBranding']);
        Route::put('settings/email', [SettingsController::class, 'updateEmail']);
        Route::put('settings/notifications', [SettingsController::class, 'updateNotifications']);
        Route::put('settings/invoice', [SettingsController::class, 'updateInvoice']);

        Route::apiResource('team/invitations', TeamInvitationController::class)->except(['update']);
        Route::post('team/invitations/{invitation}/resend', [TeamInvitationController::class, 'resend']);

        Route::bind('team', fn (string $value) => TenantUser::query()->findOrFail($value));

        Route::apiResource('team', TeamController::class);
        Route::post('team/{team}/suspend', [TeamController::class, 'suspend']);
        Route::post('team/{team}/unsuspend', [TeamController::class, 'unsuspend']);

        // -----------------------------------------------------------------------------
        // Customers & Customer Groups
        // -----------------------------------------------------------------------------

        Route::prefix('customers')->group(function (): void {
            Route::get('statistics', [CustomerController::class, 'statistics']);
            Route::get('options', [CustomerController::class, 'options']);
            Route::delete('bulk', [CustomerController::class, 'destroyMany']);
            Route::post('export', [CustomerController::class, 'export']);
            Route::get('import/sample', [CustomerController::class, 'importSample']);
            Route::post('import', [CustomerController::class, 'import']);
            Route::post('bulk-restore', [CustomerController::class, 'restoreMany']);
            Route::post('{customer}/restore', [CustomerController::class, 'restore'])->withTrashed();
            Route::delete('{customer}/force', [CustomerController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('customers', CustomerController::class);

        Route::prefix('customer-groups')->group(function (): void {
            Route::get('statistics', [CustomerGroupController::class, 'statistics']);
            Route::get('options', [CustomerGroupController::class, 'options']);
            Route::delete('bulk', [CustomerGroupController::class, 'destroyMany']);
            Route::post('export', [CustomerGroupController::class, 'export']);
            Route::get('import/sample', [CustomerGroupController::class, 'importSample']);
            Route::post('import', [CustomerGroupController::class, 'import']);
            Route::post('bulk-restore', [CustomerGroupController::class, 'restoreMany']);
            Route::post('{customer_group}/restore', [CustomerGroupController::class, 'restore'])->withTrashed();
            Route::delete('{customer_group}/force', [CustomerGroupController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('customer-groups', CustomerGroupController::class);

        // -----------------------------------------------------------------------------
        // Staff & Departments
        // -----------------------------------------------------------------------------

        Route::apiResource('staff', StaffController::class);

        Route::prefix('departments')->group(function (): void {
            Route::delete('bulk', [DepartmentController::class, 'destroyMany']);
            Route::post('bulk-restore', [DepartmentController::class, 'restoreMany']);
            Route::post('{department}/restore', [DepartmentController::class, 'restore'])->withTrashed();
            Route::delete('{department}/force', [DepartmentController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('departments', DepartmentController::class);

        Route::apiResource('positions', PositionController::class);

        // -----------------------------------------------------------------------------

        Route::prefix('hr')->group(function (): void {
            Route::put('staff/{staff}/profile', [HrController::class, 'upsertProfile']);
            Route::get('attendances', [HrController::class, 'attendances']);
            Route::post('staff/{staff}/clock-in', [HrController::class, 'clockIn']);
            Route::post('staff/{staff}/clock-out', [HrController::class, 'clockOut']);
            Route::get('leave-requests', [HrController::class, 'leaveRequests']);
            Route::post('staff/{staff}/leave-requests', [HrController::class, 'submitLeave']);
            Route::post('leave-requests/{leaveRequest}/approve', [HrController::class, 'approveLeave']);
            Route::post('leave-requests/{leaveRequest}/reject', [HrController::class, 'rejectLeave']);
            Route::post('leave-types', [HrController::class, 'storeLeaveType']);
            Route::put('staff/{staff}/payroll', [HrController::class, 'upsertPayroll']);
        });

        Route::prefix('tax-classes')->group(function (): void {
            Route::get('statistics', [TaxClassController::class, 'statistics']);
            Route::get('options', [TaxClassController::class, 'options']);
            Route::get('code/{code}', [TaxClassController::class, 'showByCode']);
            Route::put('reorder', [TaxClassController::class, 'reorder']);
            Route::delete('bulk', [TaxClassController::class, 'destroyMany']);
            Route::post('export', [TaxClassController::class, 'export']);
            Route::get('import/sample', [TaxClassController::class, 'importSample']);
            Route::post('import', [TaxClassController::class, 'import']);
            Route::post('bulk-restore', [TaxClassController::class, 'restoreMany']);
            Route::post('{taxClass}/set-default', [TaxClassController::class, 'setDefault']);
            Route::post('{taxClass}/toggle-active', [TaxClassController::class, 'toggleActive']);
            Route::get('{taxClass}/rates', [TaxClassController::class, 'rates']);
            Route::post('{taxClass}/restore', [TaxClassController::class, 'restore'])->withTrashed();
            Route::delete('{taxClass}/force', [TaxClassController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('tax-classes', TaxClassController::class);

        Route::prefix('tax-zones')->group(function (): void {
            Route::get('statistics', [TaxZoneController::class, 'statistics']);
            Route::get('options', [TaxZoneController::class, 'options']);
            Route::get('match/address', [TaxZoneController::class, 'matchByAddress']);
            Route::get('match/coordinates', [TaxZoneController::class, 'matchByCoordinates']);
            Route::put('reorder', [TaxZoneController::class, 'reorder']);
            Route::delete('bulk', [TaxZoneController::class, 'destroyMany']);
            Route::post('export', [TaxZoneController::class, 'export']);
            Route::get('import/sample', [TaxZoneController::class, 'importSample']);
            Route::post('import', [TaxZoneController::class, 'import']);
            Route::post('bulk-restore', [TaxZoneController::class, 'restoreMany']);
            Route::post('{taxZone}/set-default', [TaxZoneController::class, 'setDefault']);
            Route::post('{taxZone}/toggle-active', [TaxZoneController::class, 'toggleActive']);
            Route::get('{taxZone}/rates', [TaxZoneController::class, 'rates']);
            Route::post('{taxZone}/restore', [TaxZoneController::class, 'restore'])->withTrashed();
            Route::delete('{taxZone}/force', [TaxZoneController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('tax-zones', TaxZoneController::class);

        Route::prefix('tax-rates')->group(function (): void {
            Route::get('statistics', [TaxRateController::class, 'statistics']);
            Route::get('options', [TaxRateController::class, 'options']);
            Route::post('calculate', [TaxRateController::class, 'calculate']);
            Route::delete('bulk', [TaxRateController::class, 'destroyMany']);
            Route::post('export', [TaxRateController::class, 'export']);
            Route::get('import/sample', [TaxRateController::class, 'importSample']);
            Route::post('import', [TaxRateController::class, 'import']);
            Route::post('bulk-restore', [TaxRateController::class, 'restoreMany']);
            Route::post('{taxRate}/toggle-active', [TaxRateController::class, 'toggleActive']);
            Route::get('{taxRate}/rules', [TaxRateController::class, 'rules']);
            Route::post('{taxRate}/restore', [TaxRateController::class, 'restore'])->withTrashed();
            Route::delete('{taxRate}/force', [TaxRateController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('tax-rates', TaxRateController::class);

        Route::prefix('tax-rules')->group(function (): void {
            Route::get('statistics', [TaxRuleController::class, 'statistics']);
            Route::delete('bulk', [TaxRuleController::class, 'destroyMany']);
            Route::post('export', [TaxRuleController::class, 'export']);
            Route::post('{taxRule}/toggle-active', [TaxRuleController::class, 'toggleActive']);
        });
        Route::apiResource('tax-rules', TaxRuleController::class);

        Route::prefix('tax')->group(function (): void {
            Route::post('regions', [TaxController::class, 'storeRegion']);
            Route::post('calculate', [TaxController::class, 'calculate']);
        });

        // -----------------------------------------------------------------------------
        // Categories & Brands
        // -----------------------------------------------------------------------------

        Route::prefix('categories')->group(function (): void {
            Route::get('statistics', [CategoryController::class, 'statistics']);
            Route::get('options', [CategoryController::class, 'options']);
            Route::get('tree', [CategoryController::class, 'tree']);
            Route::get('tree-select', [CategoryController::class, 'treeForSelect']);
            Route::get('slug/{slug}', [CategoryController::class, 'showBySlug']);
            Route::put('reorder', [CategoryController::class, 'reorder']);
            Route::delete('bulk', [CategoryController::class, 'destroyMany']);
            Route::post('export', [CategoryController::class, 'export']);
            Route::get('import/sample', [CategoryController::class, 'importSample']);
            Route::post('import', [CategoryController::class, 'import']);
            Route::post('bulk-restore', [CategoryController::class, 'restoreMany']);
            Route::get('{category}/products', [CategoryController::class, 'products']);
            Route::get('{category}/breadcrumbs', [CategoryController::class, 'breadcrumbs']);
            Route::get('{category}/children', [CategoryController::class, 'children']);
            Route::get('{category}/descendants', [CategoryController::class, 'descendants']);
            Route::patch('{category}/move', [CategoryController::class, 'move']);
            Route::post('{category}/toggle-visibility', [CategoryController::class, 'toggleVisibility']);
            Route::post('{category}/toggle-featured', [CategoryController::class, 'toggleFeatured']);
            Route::post('{category}/update-products-count', [CategoryController::class, 'updateProductsCount']);
            Route::put('{category}/attribute-sets', [CategoryController::class, 'syncAttributeSets']);
            Route::post('{category}/attribute-sets', [CategoryController::class, 'assignAttributeSet']);
            Route::delete('{category}/attribute-sets/{attributeSet}', [CategoryController::class, 'removeAttributeSet']);
            Route::post('{category}/restore', [CategoryController::class, 'restore'])->withTrashed();
            Route::delete('{category}/force', [CategoryController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('categories', CategoryController::class);

        Route::prefix('brands')->group(function (): void {
            Route::get('statistics', [BrandController::class, 'statistics']);
            Route::get('options', [BrandController::class, 'options']);
            Route::get('slug/{slug}', [BrandController::class, 'showBySlug']);
            Route::put('reorder', [BrandController::class, 'reorder']);
            Route::delete('bulk', [BrandController::class, 'destroyMany']);
            Route::post('export', [BrandController::class, 'export']);
            Route::get('import/sample', [BrandController::class, 'importSample']);
            Route::post('import', [BrandController::class, 'import']);
            Route::post('bulk-restore', [BrandController::class, 'restoreMany']);
            Route::get('{brand}/products', [BrandController::class, 'products']);
            Route::post('{brand}/toggle-visibility', [BrandController::class, 'toggleVisibility']);
            Route::post('{brand}/toggle-featured', [BrandController::class, 'toggleFeatured']);
            Route::post('{brand}/update-products-count', [BrandController::class, 'updateProductsCount']);
            Route::post('{brand}/restore', [BrandController::class, 'restore'])->withTrashed();
            Route::delete('{brand}/force', [BrandController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('brands', BrandController::class);

        Route::prefix('attributes')->group(function (): void {
            Route::get('statistics', [AttributeController::class, 'statistics']);
            Route::get('options', [AttributeController::class, 'options']);
            Route::get('filterable', [AttributeController::class, 'filterable']);
            Route::get('variant-attributes', [AttributeController::class, 'variantAttributes']);
            Route::get('slug/{slug}', [AttributeController::class, 'showBySlug']);
            Route::get('code/{code}', [AttributeController::class, 'showByCode']);
            Route::put('reorder', [AttributeController::class, 'reorder']);
            Route::delete('bulk', [AttributeController::class, 'destroyMany']);
            Route::post('export', [AttributeController::class, 'export']);
            Route::get('import/sample', [AttributeController::class, 'importSample']);
            Route::post('import', [AttributeController::class, 'import']);
            Route::post('bulk-restore', [AttributeController::class, 'restoreMany']);
            Route::post('{attribute}/toggle-filterable', [AttributeController::class, 'toggleFilterable']);
            Route::post('{attribute}/toggle-variant', [AttributeController::class, 'toggleVariant']);
            Route::post('{attribute}/restore', [AttributeController::class, 'restore'])->withTrashed();
            Route::delete('{attribute}/force', [AttributeController::class, 'forceDestroy'])->withTrashed();
            Route::get('{attribute}/values', [AttributeController::class, 'values']);
            Route::post('{attribute}/values', [AttributeController::class, 'storeValue']);
            Route::put('{attribute}/values/reorder', [AttributeController::class, 'reorderValues']);
            Route::put('{attribute}/values/{value}', [AttributeController::class, 'updateValue']);
            Route::delete('{attribute}/values/{value}', [AttributeController::class, 'destroyValue']);
        });
        Route::apiResource('attributes', AttributeController::class);

        Route::prefix('attribute-sets')->group(function (): void {
            Route::get('statistics', [AttributeSetController::class, 'statistics']);
            Route::get('options', [AttributeSetController::class, 'options']);
            Route::get('slug/{slug}', [AttributeSetController::class, 'showBySlug']);
            Route::put('reorder', [AttributeSetController::class, 'reorder']);
            Route::delete('bulk', [AttributeSetController::class, 'destroyMany']);
            Route::post('export', [AttributeSetController::class, 'export']);
            Route::get('import/sample', [AttributeSetController::class, 'importSample']);
            Route::post('import', [AttributeSetController::class, 'import']);
            Route::get('{attribute_set}/attributes', [AttributeSetController::class, 'attributes']);
            Route::put('{attribute_set}/attributes', [AttributeSetController::class, 'syncAttributes']);
            Route::post('{attribute_set}/attributes/{attribute}', [AttributeSetController::class, 'attachAttribute']);
            Route::delete('{attribute_set}/attributes/{attribute}', [AttributeSetController::class, 'detachAttribute']);
            Route::get('{attribute_set}/categories', [AttributeSetController::class, 'categories']);
            Route::put('{attribute_set}/categories', [AttributeSetController::class, 'syncCategories']);
        });
        Route::apiResource('attribute-sets', AttributeSetController::class);

        Route::prefix('tags')->group(function (): void {
            Route::get('statistics', [TagController::class, 'statistics']);
            Route::get('options', [TagController::class, 'options']);
            Route::get('slug/{slug}', [TagController::class, 'showBySlug']);
            Route::put('reorder', [TagController::class, 'reorder']);
            Route::delete('bulk', [TagController::class, 'destroyMany']);
            Route::post('export', [TagController::class, 'export']);
            Route::get('import/sample', [TagController::class, 'importSample']);
            Route::post('import', [TagController::class, 'import']);
            Route::get('{tag}/products', [TagController::class, 'products']);
            Route::post('{tag}/toggle-visibility', [TagController::class, 'toggleVisibility']);
            Route::post('{tag}/update-products-count', [TagController::class, 'updateProductsCount']);
        });
        Route::apiResource('tags', TagController::class);

        Route::prefix('collections')->group(function (): void {
            Route::get('statistics', [CollectionController::class, 'statistics']);
            Route::get('options', [CollectionController::class, 'options']);
            Route::get('slug/{slug}', [CollectionController::class, 'showBySlug']);
            Route::put('reorder', [CollectionController::class, 'reorder']);
            Route::delete('bulk', [CollectionController::class, 'destroyMany']);
            Route::post('export', [CollectionController::class, 'export']);
            Route::get('import/sample', [CollectionController::class, 'importSample']);
            Route::post('import', [CollectionController::class, 'import']);
            Route::post('bulk-restore', [CollectionController::class, 'restoreMany']);
            Route::get('{collection}/products', [CollectionController::class, 'products']);
            Route::put('{collection}/products', [CollectionController::class, 'syncProducts']);
            Route::put('{collection}/products/reorder', [CollectionController::class, 'reorderProducts']);
            Route::post('{collection}/toggle-visibility', [CollectionController::class, 'toggleVisibility']);
            Route::post('{collection}/toggle-featured', [CollectionController::class, 'toggleFeatured']);
            Route::post('{collection}/refresh-automated', [CollectionController::class, 'refreshAutomated']);
            Route::post('{collection}/restore', [CollectionController::class, 'restore'])->withTrashed();
            Route::delete('{collection}/force', [CollectionController::class, 'forceDestroy'])->withTrashed();
        });
        Route::apiResource('collections', CollectionController::class);

        Route::prefix('suppliers')->group(function (): void {
            Route::get('statistics', [SupplierController::class, 'statistics']);
            Route::get('options', [SupplierController::class, 'options']);
            Route::get('slug/{slug}', [SupplierController::class, 'showBySlug']);
            Route::get('code/{code}', [SupplierController::class, 'showByCode']);
            Route::delete('bulk', [SupplierController::class, 'destroyMany']);
            Route::post('export', [SupplierController::class, 'export']);
            Route::get('import/sample', [SupplierController::class, 'importSample']);
            Route::post('import', [SupplierController::class, 'import']);
            Route::get('{supplier}/products', [SupplierController::class, 'products']);
            Route::post('{supplier}/toggle-active', [SupplierController::class, 'toggleActive']);
            Route::post('{supplier}/update-products-count', [SupplierController::class, 'updateProductsCount']);
            Route::post('{supplier}/restore', [SupplierController::class, 'restore'])->withTrashed();
            Route::delete('{supplier}/force', [SupplierController::class, 'forceDestroy'])->withTrashed();
            Route::get('{supplier}/contacts', [SupplierController::class, 'contacts']);
            Route::post('{supplier}/contacts', [SupplierController::class, 'storeContact']);
            Route::put('{supplier}/contacts/{contact}', [SupplierController::class, 'updateContact']);
            Route::delete('{supplier}/contacts/{contact}', [SupplierController::class, 'destroyContact']);
            Route::post('{supplier}/contacts/{contact}/set-primary', [SupplierController::class, 'setPrimaryContact']);
            Route::get('{supplier}/addresses', [SupplierController::class, 'addresses']);
            Route::post('{supplier}/addresses', [SupplierController::class, 'storeAddress']);
            Route::put('{supplier}/addresses/{address}', [SupplierController::class, 'updateAddress']);
            Route::delete('{supplier}/addresses/{address}', [SupplierController::class, 'destroyAddress']);
            Route::post('{supplier}/addresses/{address}/set-default', [SupplierController::class, 'setDefaultAddress']);
            Route::get('{supplier}/bank-accounts', [SupplierController::class, 'bankAccounts']);
            Route::post('{supplier}/bank-accounts', [SupplierController::class, 'storeBankAccount']);
            Route::put('{supplier}/bank-accounts/{bankAccount}', [SupplierController::class, 'updateBankAccount']);
            Route::delete('{supplier}/bank-accounts/{bankAccount}', [SupplierController::class, 'destroyBankAccount']);
            Route::post('{supplier}/bank-accounts/{bankAccount}/set-default', [SupplierController::class, 'setDefaultBankAccount']);
        });
        Route::apiResource('suppliers', SupplierController::class);

        Route::prefix('warehouses')->group(function (): void {
            Route::get('statistics', [WarehouseController::class, 'statistics']);
            Route::get('options', [WarehouseController::class, 'options']);
            Route::get('primary', [WarehouseController::class, 'primary']);
            Route::get('code/{code}', [WarehouseController::class, 'showByCode']);
            Route::delete('bulk', [WarehouseController::class, 'destroyMany']);
            Route::post('export', [WarehouseController::class, 'export']);
            Route::get('import/sample', [WarehouseController::class, 'importSample']);
            Route::post('import', [WarehouseController::class, 'import']);
            Route::post('{warehouse}/toggle-active', [WarehouseController::class, 'toggleActive']);
            Route::post('{warehouse}/set-primary', [WarehouseController::class, 'setPrimary']);
            Route::post('{warehouse}/restore', [WarehouseController::class, 'restore'])->withTrashed();
            Route::delete('{warehouse}/force', [WarehouseController::class, 'forceDestroy'])->withTrashed();
            Route::get('{warehouse}/zones', [WarehouseController::class, 'zones']);
            Route::post('{warehouse}/zones', [WarehouseController::class, 'storeZone']);
            Route::put('{warehouse}/zones/{zone}', [WarehouseController::class, 'updateZone']);
            Route::delete('{warehouse}/zones/{zone}', [WarehouseController::class, 'destroyZone']);
            Route::get('{warehouse}/locations', [WarehouseController::class, 'locations']);
            Route::post('{warehouse}/locations', [WarehouseController::class, 'storeLocation']);
            Route::put('{warehouse}/locations/{location}', [WarehouseController::class, 'updateLocation']);
            Route::delete('{warehouse}/locations/{location}', [WarehouseController::class, 'destroyLocation']);
        });
        Route::apiResource('warehouses', WarehouseController::class);

        Route::prefix('units')->group(function (): void {
            Route::get('statistics', [UnitController::class, 'statistics']);
            Route::get('options', [UnitController::class, 'options']);
            Route::get('type-options', [UnitController::class, 'typeOptions']);
            Route::get('code/{code}', [UnitController::class, 'showByCode']);
            Route::get('type/{type}', [UnitController::class, 'byType']);
            Route::get('type/{type}/base', [UnitController::class, 'baseUnit']);
            Route::post('convert', [UnitController::class, 'convert']);
            Route::put('reorder', [UnitController::class, 'reorder']);
            Route::delete('bulk', [UnitController::class, 'destroyMany']);
            Route::post('export', [UnitController::class, 'export']);
            Route::get('import/sample', [UnitController::class, 'importSample']);
            Route::post('import', [UnitController::class, 'import']);
            Route::post('{unit}/set-base', [UnitController::class, 'setBase']);
        });
        Route::apiResource('units', UnitController::class);

        // -----------------------------------------------------------------------------
        // Media Library
        // -----------------------------------------------------------------------------

        Route::prefix('media')->group(function (): void {
            Route::get('statistics', [MediaController::class, 'statistics']);
            Route::post('bulk-upload', [MediaController::class, 'bulkUpload']);
            Route::post('import-url', [MediaController::class, 'importFromUrl']);
            Route::post('move', [MediaController::class, 'move']);
            Route::post('copy', [MediaController::class, 'copy']);
            Route::patch('bulk', [MediaController::class, 'bulkUpdate']);
            Route::delete('bulk', [MediaController::class, 'bulkDestroy']);
            Route::post('{media}/move', [MediaController::class, 'moveOne']);
            Route::post('{media}/copy', [MediaController::class, 'copyOne']);
            Route::post('{media}/remove-background', [MediaController::class, 'removeBackground']);
        });
        Route::apiResource('media', MediaController::class)->parameters(['media' => 'media']);

        Route::prefix('media-folders')->group(function (): void {
            Route::get('tree', [MediaFolderController::class, 'tree']);
            Route::delete('bulk', [MediaFolderController::class, 'bulkDestroy']);
        });
        Route::apiResource('media-folders', MediaFolderController::class)->parameters(['media-folders' => 'folder']);

        // Products
        Route::prefix('products')->group(function (): void {
            Route::get('statistics', [ProductController::class, 'statistics']);
            Route::get('options', [ProductController::class, 'options']);
            Route::delete('bulk', [ProductController::class, 'destroyMany']);
            Route::post('export', [ProductController::class, 'export']);
            Route::post('bulk-restore', [ProductController::class, 'restoreMany']);
            Route::post('{product}/restore', [ProductController::class, 'restore'])->withTrashed();
            Route::delete('{product}/force', [ProductController::class, 'forceDestroy'])->withTrashed();
            Route::post('{product}/variants', [ProductController::class, 'storeVariant']);
            Route::post('{product}/variants/generate', [ProductController::class, 'generateVariants']);
            Route::put('{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
            Route::delete('{product}/variants/{variant}', [ProductController::class, 'destroyVariant']);
            Route::put('{product}/options', [ProductController::class, 'syncOptions']);
            Route::put('{product}/suppliers', [ProductController::class, 'syncSuppliers']);
            Route::put('{product}/relations', [ProductController::class, 'syncRelations']);
            Route::put('{product}/downloads', [ProductController::class, 'syncDownloads']);
            Route::put('{product}/bundle-items', [ProductController::class, 'syncBundleItems']);
            Route::put('{product}/service', [ProductController::class, 'syncService']);
            Route::put('{product}/subscription', [ProductController::class, 'syncSubscription']);
            Route::get('{product}/variants/{variant}/inventories', [InventoryController::class, 'variantInventories']);
        });
        Route::apiResource('products', ProductController::class);

        Route::prefix('inventories')->group(function (): void {
            Route::get('statistics', [InventoryController::class, 'statistics']);
            Route::get('movements', [InventoryController::class, 'movements']);
            Route::get('stock-alerts', [InventoryController::class, 'stockAlerts']);
            Route::post('{inventory}/adjust', [InventoryController::class, 'adjust']);
            Route::post('{inventory}/transfer', [InventoryController::class, 'transfer']);
        });
        Route::apiResource('inventories', InventoryController::class)->only(['index', 'show', 'update']);

        // -----------------------------------------------------------------------------

        Route::apiResource('flash-sales', FlashSaleController::class);
        Route::post('flash-sales/{flash_sale}/activate', [FlashSaleController::class, 'activate']);
        Route::post('flash-sales/{flash_sale}/end', [FlashSaleController::class, 'end']);
        Route::post('flash-sales/{flash_sale}/products', [FlashSaleController::class, 'attachProduct']);
        Route::delete('flash-sales/{flash_sale}/products/{flashSaleProduct}', [FlashSaleController::class, 'detachProduct']);
        Route::post('flash-sales/{flash_sale}/queue/join', [FlashSaleController::class, 'joinQueue']);

        Route::get('waitlists', [WaitlistController::class, 'index']);
        Route::post('products/{product}/waitlist/join', [WaitlistController::class, 'join']);
        Route::delete('waitlist-subscribers/{subscriber}', [WaitlistController::class, 'leave']);

        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::patch('cart/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);
        Route::delete('cart', [CartController::class, 'clear']);

        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('orders/{order}/refund', [OrderController::class, 'refund']);

        Route::post('orders/{order}/payments', [PaymentController::class, 'initiate']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);

        Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('analytics/flash-sales/{flash_sale}', [AnalyticsController::class, 'drop']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});
