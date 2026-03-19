<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\FollowUp;
use App\Models\Tenant\Lead;
use App\Models\Tenant\Opportunity;
use App\Services\Saas\PlanLimitService;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'leads' => Lead::query()
                ->orderByDesc('id')
                ->paginate((int) $request->input('per_page', 20)),
            'opportunities' => Opportunity::query()
                ->orderByDesc('id')
                ->paginate((int) $request->input('per_page', 20)),
            'pending_follow_ups' => FollowUp::query()
                ->where('status', 'open')
                ->whereDate('due_at', '<=', now()->toDateString())
                ->count(),
        ]);
    }

    public function storeLead(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'source' => ['nullable', 'string', 'max:120'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $lead = Lead::query()->create($validated + [
            'status' => 'new',
            'assigned_to' => $request->user()?->id,
        ]);

        return response()->json(['data' => $lead], 201);
    }

    public function storeFollowUp(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'type' => ['required', 'in:call,email,meeting,task'],
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $followUp = FollowUp::query()->create($validated + [
            'status' => 'open',
        ]);

        return response()->json(['data' => $followUp], 201);
    }

    public function storeCustomer(Request $request, PlanLimitService $planLimitService)
    {
        $tenantId = (string) tenant('id');
        $planLimitService->enforce($tenantId, 'max_customers', Customer::query()->count(), 'customers');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'gstin' => ['nullable', 'string', 'max:32'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::query()->create($validated + [
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'credit_balance' => 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $customer], 201);
    }
}
