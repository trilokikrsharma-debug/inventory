<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Tenant\User as TenantUser;
use App\Support\CentralDatabase;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant context is missing.');
        }

        if (! in_array($tenant->status, ['active', 'trialing'], true)) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'message' => 'Tenant account is not active.',
                ], 403);
            }

            abort(403, 'Tenant account is not active.');
        }

        if ($request->user()) {
            $isMember = CentralDatabase::connection()
                ->table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $isMember) {
                if ($request->expectsJson()) {
                    return new JsonResponse([
                        'message' => 'User is not assigned to this tenant.',
                    ], 403);
                }

                abort(403, 'User is not assigned to this tenant.');
            }

            // Keep a tenant-local user row in sync so tenant-table foreign keys
            // (created_by/performed_by) remain valid and module actions don't fail.
            try {
                $this->syncTenantUserRecord($request->user());
            } catch (\Throwable $exception) {
                report($exception);

                if ($request->expectsJson()) {
                    return new JsonResponse([
                        'message' => 'Tenant workspace is still provisioning. Please retry in a moment.',
                    ], 503);
                }

                abort(503, 'Tenant workspace is still provisioning. Please retry in a moment.');
            }
        }

        return $next($request);
    }

    private function syncTenantUserRecord(User $user): void
    {
        TenantUser::query()->updateOrCreate(
            ['id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'is_platform_admin' => (bool) $user->is_platform_admin,
                'is_active' => (bool) $user->is_active,
                'last_login_at' => $user->last_login_at,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'remember_token' => $user->remember_token,
            ]
        );
    }
}
