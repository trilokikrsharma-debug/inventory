<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment('testing')) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $centralDomains = array_map('strtolower', Arr::wrap(config('tenancy.central_domains', [])));

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        if (App::environment('local') && in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $next($request);
        }

        abort(404);
    }
}
