<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchaseOrders = PurchaseOrder::query()
            ->orderByDesc('order_date')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($purchaseOrders);
    }

    public function storePurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $po = DB::transaction(function () use ($validated, $request) {
            $subtotal = 0.0;

            $po = PurchaseOrder::query()->create([
                'po_number' => 'PO-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'supplier_id' => $validated['supplier_id'],
                'status' => 'ordered',
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_cost'];
                $subtotal += $lineTotal;

                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }

            $po->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);

            return $po->fresh();
        });

        return response()->json(['data' => $po], 201);
    }

    public function receiveStock(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder, $request): void {
            $purchaseOrder = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

            if (! in_array($purchaseOrder->status, ['ordered', 'partial'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Stock can only be received for ordered or partial purchase orders.',
                ]);
            }

            $allowedProductIds = PurchaseOrderItem::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->whereNotNull('product_id')
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $allowedProductMap = array_fill_keys($allowedProductIds, true);

            foreach ($validated['items'] as $item) {
                $productId = (int) $item['product_id'];

                if (! isset($allowedProductMap[$productId])) {
                    throw ValidationException::withMessages([
                        'items' => "Product {$productId} does not belong to this purchase order.",
                    ]);
                }

                $product = Product::query()->findOrFail($item['product_id']);
                $product->update([
                    'current_stock' => (int) $product->current_stock + (int) $item['quantity'],
                ]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'movement_type' => 'in',
                    'quantity' => (int) $item['quantity'],
                    'reference_type' => 'purchase_order',
                    'reference_id' => $purchaseOrder->id,
                    'performed_by' => $request->user()?->id,
                ]);
            }

            $submittedProductIds = collect($validated['items'])
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();

            $allProductsCovered = array_diff($allowedProductIds, $submittedProductIds) === [];

            $purchaseOrder->update([
                'status' => $allProductsCovered ? 'received' : 'partial',
            ]);
        });

        return response()->json(['message' => 'Stock received successfully.']);
    }
}
