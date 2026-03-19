<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search')->toString();
                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();
        $units = Unit::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.inventory.index', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'categoryMap' => $categories->pluck('name', 'id'),
            'unitMap' => $units->pluck('code', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('products', 'sku')],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit = ! empty($validated['unit_id']) ? Unit::query()->find($validated['unit_id']) : null;

        Product::query()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? strtoupper(Str::random(10)),
            'category_id' => $validated['category_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'unit' => $unit?->code ?? 'pcs',
            'cost_price' => $validated['cost_price'] ?? 0,
            'selling_price' => $validated['selling_price'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'current_stock' => $validated['current_stock'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Product created successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $unit = ! empty($validated['unit_id']) ? Unit::query()->find($validated['unit_id']) : null;

        $product->update([
            ...$validated,
            'unit' => $unit?->code ?? $product->unit ?? 'pcs',
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Product updated successfully.');
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'movement_type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $delta = $validated['movement_type'] === 'out'
            ? -1 * (int) $validated['quantity']
            : (int) $validated['quantity'];

        $newStock = max(0, (int) $product->current_stock + $delta);
        $product->update(['current_stock' => $newStock]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'movement_type' => $validated['movement_type'],
            'quantity' => (int) $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
            'performed_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Stock updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('status', 'Product deleted successfully.');
    }
}
