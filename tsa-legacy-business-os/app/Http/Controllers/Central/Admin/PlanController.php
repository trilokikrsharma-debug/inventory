<?php

namespace App\Http\Controllers\Central\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Admin\StorePlanRequest;
use App\Models\FeatureFlag;
use App\Models\Plan;
use App\Support\CentralDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        $features = FeatureFlag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('central.admin.plans.index', compact('plans', 'features'));
    }

    public function store(StorePlanRequest $request)
    {
        $plan = Plan::query()->updateOrCreate(
            ['slug' => $request->string('slug')->toString()],
            $request->validated()
        );

        return back()->with('status', "Plan [{$plan->name}] saved successfully.");
    }

    public function syncFeatures(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'features' => ['array'],
            'features.*.feature_id' => ['required', 'integer', Rule::exists(CentralDatabase::table('feature_flags'), 'id')],
            'features.*.is_enabled' => ['nullable', 'boolean'],
            'features.*.value' => ['nullable', 'array'],
        ]);

        $payload = [];

        foreach ($validated['features'] ?? [] as $feature) {
            $payload[(int) $feature['feature_id']] = [
                'is_enabled' => (bool) ($feature['is_enabled'] ?? false),
                'value' => json_encode($feature['value'] ?? ['value' => null], JSON_THROW_ON_ERROR),
            ];
        }

        $plan->features()->sync($payload);

        return back()->with('status', 'Plan features updated.');
    }
}
