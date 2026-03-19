<?php

namespace App\Http\Controllers\Central\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CentralDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->with(['domains', 'activeSubscription.plan'])
            ->latest()
            ->paginate(20);

        return view('central.admin.tenants.index', compact('tenants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique(CentralDatabase::table('tenants'), 'slug')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'domain' => ['required', 'string', 'max:255', Rule::unique(CentralDatabase::table('domains'), 'domain')],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique(CentralDatabase::table('users'), 'email')],
            'owner_password' => ['required', 'string', 'min:10'],
        ]);

        CentralDatabase::connection()->transaction(function () use ($validated): void {
            $owner = User::query()->create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'is_platform_admin' => false,
            ]);

            $tenant = Tenant::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => 'trialing',
                'owner_user_id' => $owner->id,
                'trial_ends_at' => now()->addDays(14),
                'onboarded_at' => now(),
                'data' => [
                    'created_via' => 'admin_panel',
                ],
            ]);

            $tenant->domains()->create([
                'domain' => strtolower($validated['domain']),
            ]);

            CentralDatabase::connection()->table('tenant_user')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'is_owner' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Tenant created. Database provisioning is queued.');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,active,suspended,trialing,canceled'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($validated);

        return back()->with('status', 'Tenant updated successfully.');
    }
}
