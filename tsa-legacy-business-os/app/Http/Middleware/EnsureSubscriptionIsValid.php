<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            abort(404, 'Tenant context is missing.');
        }

        $activeSubscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_PAST_DUE,
            ])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->latest('id')
            ->first();

        if (! $activeSubscription) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'Active subscription required.',
                ], 402);
            }

            return redirect()->route('tenant.billing.index')
                ->with('status', 'Please select a plan to continue.');
        }

        return $next($request);
    }
}
