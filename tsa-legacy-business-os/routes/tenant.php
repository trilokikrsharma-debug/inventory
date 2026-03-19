<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\Api\AccountingController;
use App\Http\Controllers\Tenant\Api\CrmController;
use App\Http\Controllers\Tenant\Api\HrController;
use App\Http\Controllers\Tenant\Api\InventoryController;
use App\Http\Controllers\Tenant\Api\PurchaseController as PurchaseApiController;
use App\Http\Controllers\Tenant\Api\ReportsController as ReportsApiController;
use App\Http\Controllers\Tenant\Api\SalesController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\UnitController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::redirect('/app', '/dashboard');

    Route::middleware(['auth', 'tenant.active'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('tenant.dashboard');

        Route::get('/inventory', [ProductController::class, 'index'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.inventory.index');
        Route::post('/inventory/products', [ProductController::class, 'store'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.products.store');
        Route::patch('/inventory/products/{product}', [ProductController::class, 'update'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.products.update');
        Route::post('/inventory/products/{product}/stock', [ProductController::class, 'adjustStock'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.products.stock');
        Route::delete('/inventory/products/{product}', [ProductController::class, 'destroy'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.products.destroy');

        Route::get('/inventory/categories', [CategoryController::class, 'index'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.categories.index');
        Route::post('/inventory/categories', [CategoryController::class, 'store'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.categories.store');
        Route::patch('/inventory/categories/{category}', [CategoryController::class, 'update'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.categories.update');
        Route::delete('/inventory/categories/{category}', [CategoryController::class, 'destroy'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.categories.destroy');

        Route::get('/inventory/units', [UnitController::class, 'index'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.units.index');
        Route::post('/inventory/units', [UnitController::class, 'store'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.units.store');
        Route::patch('/inventory/units/{unit}', [UnitController::class, 'update'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.units.update');
        Route::delete('/inventory/units/{unit}', [UnitController::class, 'destroy'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.units.destroy');

        Route::get('/customers', [CustomerController::class, 'index'])->name('tenant.customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('tenant.customers.store');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('tenant.customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('tenant.customers.destroy');

        Route::get('/suppliers', [SupplierController::class, 'index'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.suppliers.store');
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->middleware('feature:inventory_enabled')
            ->name('tenant.suppliers.destroy');

        Route::get('/sales', [InvoiceController::class, 'index'])->name('tenant.sales.index');
        Route::post('/sales/invoices', [InvoiceController::class, 'store'])->name('tenant.sales.store');
        Route::post('/sales/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('tenant.sales.payments.store');
        Route::delete('/sales/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('tenant.sales.destroy');

        Route::get('/purchases', [PurchaseController::class, 'index'])->name('tenant.purchases.index');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('tenant.purchases.store');
        Route::post('/purchases/{purchaseOrder}/receive', [PurchaseController::class, 'receive'])->name('tenant.purchases.receive');

        Route::get('/reports', [ReportController::class, 'index'])->name('tenant.reports.index');

        Route::get('/settings', [SettingsController::class, 'index'])->name('tenant.settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('tenant.settings.update');

        Route::get('/billing', [BillingController::class, 'index'])->name('tenant.billing.index');
        Route::post('/billing/select-plan', [BillingController::class, 'selectPlan'])->name('tenant.billing.select-plan');
        Route::post('/billing/checkout', [BillingController::class, 'createCheckout'])->name('tenant.billing.checkout');
        Route::post('/billing/payment-success', [BillingController::class, 'paymentSuccess'])->name('tenant.billing.payment-success');
    });

    Route::prefix('api/v1')
        ->middleware(['auth:sanctum', 'tenant.active', 'subscription.valid', 'throttle:api'])
        ->name('tenant.api.')
        ->group(function () {
            Route::get('/inventory/products', [InventoryController::class, 'index'])->middleware('feature:inventory_enabled');
            Route::post('/inventory/products', [InventoryController::class, 'storeProduct'])->middleware('feature:inventory_enabled');
            Route::post('/inventory/adjust-stock', [InventoryController::class, 'adjustStock'])->middleware('feature:inventory_enabled');

            Route::get('/sales/invoices', [SalesController::class, 'index']);
            Route::post('/sales/invoices', [SalesController::class, 'storeInvoice']);
            Route::post('/sales/invoices/{invoice}/payments', [SalesController::class, 'receivePayment']);

            Route::get('/purchases/orders', [PurchaseApiController::class, 'index']);
            Route::post('/purchases/orders', [PurchaseApiController::class, 'storePurchaseOrder']);
            Route::post('/purchases/orders/{purchaseOrder}/receive', [PurchaseApiController::class, 'receiveStock']);

            Route::get('/crm', [CrmController::class, 'index'])->middleware('feature:crm_enabled');
            Route::post('/crm/customers', [CrmController::class, 'storeCustomer'])->middleware('feature:crm_enabled');
            Route::post('/crm/leads', [CrmController::class, 'storeLead'])->middleware('feature:crm_enabled');
            Route::post('/crm/follow-ups', [CrmController::class, 'storeFollowUp'])->middleware('feature:crm_enabled');

            Route::get('/accounting/summary', [AccountingController::class, 'summary'])->middleware('feature:accounting_enabled');
            Route::post('/accounting/ledger-entries', [AccountingController::class, 'storeLedgerEntry'])->middleware('feature:accounting_enabled');

            Route::get('/hr', [HrController::class, 'index'])->middleware('feature:hr_enabled');
            Route::post('/hr/employees', [HrController::class, 'storeEmployee'])->middleware('feature:hr_enabled');
            Route::post('/hr/attendance', [HrController::class, 'markAttendance'])->middleware('feature:hr_enabled');

            Route::get('/reports/sales-analytics', [ReportsApiController::class, 'salesAnalytics']);
            Route::get('/reports/inventory-valuation', [ReportsApiController::class, 'inventoryValuation']);
            Route::post('/reports/snapshots', [ReportsApiController::class, 'exportSnapshot']);
        });
});
