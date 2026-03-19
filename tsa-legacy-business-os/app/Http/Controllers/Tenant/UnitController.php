<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::query()->orderBy('name')->paginate(20);

        $productCountByUnit = Product::query()
            ->selectRaw('unit_id, COUNT(*) as total')
            ->whereNotNull('unit_id')
            ->groupBy('unit_id')
            ->pluck('total', 'unit_id');

        return view('tenant.units.index', compact('units', 'productCountByUnit'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32', Rule::unique('units', 'code')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Unit::query()->create([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Unit created successfully.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32', Rule::unique('units', 'code')->ignore($unit->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if (Product::query()->where('unit_id', $unit->id)->exists()) {
            return back()->withErrors([
                'unit' => 'Cannot delete unit linked to products.',
            ]);
        }

        $unit->delete();

        return back()->with('status', 'Unit deleted successfully.');
    }
}
