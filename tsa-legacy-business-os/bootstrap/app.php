<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\EnsureSubscriptionIsValid;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\RequestContextMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'tenant.active' => EnsureTenantIsActive::class,
            'subscription.valid' => EnsureSubscriptionIsValid::class,
            'feature' => EnsureFeatureEnabled::class,
            'central.domain' => EnsureCentralDomain::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'two-factor' => \App\Http\Middleware\RequireTwoFactorMiddleware::class,
        ]);

        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(RequestContextMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
