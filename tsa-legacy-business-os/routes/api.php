<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Central\Billing\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:login');
    Route::post('/auth/token/revoke', [AuthTokenController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('/webhooks/razorpay', WebhookController::class)->middleware('throttle:webhooks')->name('api.webhooks.razorpay');
});
