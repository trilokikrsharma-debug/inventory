<?php

use App\Http\Controllers\Central\Admin\PlanController;
use App\Http\Controllers\Central\Admin\TenantController;
use App\Http\Controllers\Central\Admin\TwoFactorController;
use App\Http\Controllers\Central\MarketingController;
use App\Http\Controllers\Central\PlatformDashboardController;
use App\Http\Controllers\Central\PricingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', MarketingController::class)->name('marketing.home');
Route::get('/pricing', PricingController::class)->name('marketing.pricing');

Route::middleware('central.domain')->group(function () {
    Route::get('/platform/dashboard', PlatformDashboardController::class)
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::middleware(['auth', 'throttle:admin', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
        Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');
        Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

        Route::middleware('two-factor')->group(function () {
            Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::patch('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');

            Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
            Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
            Route::post('/plans/{plan}/features', [PlanController::class, 'syncFeatures'])->name('plans.features.sync');
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
