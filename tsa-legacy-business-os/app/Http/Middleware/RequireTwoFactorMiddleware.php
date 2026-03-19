<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_platform_admin) {
            return $next($request);
        }

        if ($request->routeIs('admin.two-factor.*')) {
            return $next($request);
        }

        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            return redirect()->route('admin.two-factor.setup');
        }

        if ($user->two_factor_confirmed_at && ! $request->session()->has('auth.two_factor_verified')) {
            return redirect()->route('admin.two-factor.challenge');
        }

        return $next($request);
    }
}
