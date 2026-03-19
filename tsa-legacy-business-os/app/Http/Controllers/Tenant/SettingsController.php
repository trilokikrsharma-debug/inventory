<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()
            ->with('domains')
            ->findOrFail((string) tenant('id'));

        $subscription = Subscription::query()
            ->with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        return view('tenant.settings.index', compact('tenant', 'subscription'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail((string) tenant('id'));
        $tenant->update($validated);

        return back()->with('status', 'Tenant settings updated successfully.');
    }
}
