<?php

namespace App\Providers;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute(120)->by($request->ip()),
                Limit::perMinute(300)->by((string) optional($request->user())->id),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        Event::listen(Login::class, function (Login $event): void {
            if (App::environment('testing')) {
                return;
            }

            try {
                LoginActivity::query()->create([
                    'tenant_id' => tenant('id'),
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'status' => 'success',
                    'ip_address' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'attempted_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Login activity write failed', ['message' => $exception->getMessage()]);
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            if (App::environment('testing')) {
                return;
            }

            try {
                LoginActivity::query()->create([
                    'tenant_id' => tenant('id'),
                    'user_id' => $event->user?->id,
                    'email' => (string) request()->input('email'),
                    'status' => 'failed',
                    'ip_address' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'attempted_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Failed login activity write failed', ['message' => $exception->getMessage()]);
            }
        });
    }
}
