<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            abort(404, 'Tenant context is missing.');
        }

        $subscription = Subscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->latest('id')
            ->first();

        $isEnabled = $subscription?->plan?->featureEnabled($feature, false) ?? false;

        if (! $isEnabled) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => "Feature [{$feature}] is not available on your current plan.",
                ], 403);
            }

            abort(403, "Feature [{$feature}] is not available on your current plan.");
        }

        return $next($request);
    }
}
