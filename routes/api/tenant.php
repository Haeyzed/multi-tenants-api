<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\BrandController;
use App\Http\Controllers\Tenant\CartController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CustomerAuthController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerGroupController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\FlashSaleController;
use App\Http\Controllers\Tenant\HrController;
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
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Tenant\TeamInvitationController;
use App\Http\Controllers\Tenant\WaitlistController;
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

        Route::prefix('tax')->group(function (): void {
            Route::get('classes', [TaxController::class, 'indexClasses']);
            Route::post('classes', [TaxController::class, 'storeClass']);
            Route::get('classes/{taxClass}', [TaxController::class, 'showClass']);
            Route::put('classes/{taxClass}', [TaxController::class, 'updateClass']);
            Route::delete('classes/{taxClass}', [TaxController::class, 'destroyClass']);
            Route::post('classes/{taxClass}/rates', [TaxController::class, 'storeRate']);
            Route::put('rates/{taxRate}', [TaxController::class, 'updateRate']);
            Route::delete('rates/{taxRate}', [TaxController::class, 'destroyRate']);
            Route::post('rates/{taxRate}/rules', [TaxController::class, 'storeRule']);
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
            Route::put('{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
            Route::delete('{product}/variants/{variant}', [ProductController::class, 'destroyVariant']);
        });
        Route::apiResource('products', ProductController::class);

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
