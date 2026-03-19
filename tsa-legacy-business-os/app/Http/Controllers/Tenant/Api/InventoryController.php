<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\StockMovement;
use App\Services\Saas\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->when($request->filled('active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('active'));
            })
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($products);
    }

    public function storeProduct(Request $request, PlanLimitService $planLimitService)
    {
        $tenantId = (string) tenant('id');
        $planLimitService->enforce($tenantId, 'max_products', Product::query()->count(), 'products');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:120', 'unique:products,sku'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::query()->create($validated + [
            'current_stock' => 0,
            'unit' => 'pcs',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $product], 201);
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'movement_type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        DB::transaction(function () use ($validated, $product): void {
            $delta = in_array($validated['movement_type'], ['out'], true)
                ? -1 * (int) $validated['quantity']
                : (int) $validated['quantity'];

            $newStock = max(0, (int) $product->current_stock + $delta);

            $product->update(['current_stock' => $newStock]);

            StockMovement::query()->create([
                'product_id' => $product->id,
                'movement_type' => $validated['movement_type'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'performed_by' => $request->user()?->id,
            ]);
        });

        return response()->json(['message' => 'Stock updated successfully.']);
    }
}
