<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search')->toString();
                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $purchaseHistory = PurchaseOrder::query()
            ->selectRaw('supplier_id, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as total_spend')
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        return view('tenant.suppliers.index', compact('suppliers', 'purchaseHistory'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')],
            'phone' => ['nullable', 'string', 'max:32'],
            'gstin' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Supplier::query()->create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Supplier created successfully.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'gstin' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplier->update([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if (PurchaseOrder::query()->where('supplier_id', $supplier->id)->exists()) {
            return back()->withErrors([
                'supplier' => 'Cannot delete supplier linked to purchases.',
            ]);
        }

        $supplier->delete();

        return back()->with('status', 'Supplier deleted successfully.');
    }
}
