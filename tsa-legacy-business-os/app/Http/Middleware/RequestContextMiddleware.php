<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('tenant_id', tenant('id'));

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        if (
            ! App::environment('testing')
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $request->user()
        ) {
            try {
                AuditLog::query()->create([
                    'tenant_id' => tenant('id'),
                    'user_id' => $request->user()->id,
                    'action' => strtolower($request->method()),
                    'resource_type' => $request->path(),
                    'resource_id' => null,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'context' => [
                        'request_id' => $requestId,
                        'url' => $request->fullUrl(),
                        'payload' => $request->except(['password', 'password_confirmation', 'token', 'secret']),
                        'referer' => $request->header('referer'),
                    ],
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Audit log write failed', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
