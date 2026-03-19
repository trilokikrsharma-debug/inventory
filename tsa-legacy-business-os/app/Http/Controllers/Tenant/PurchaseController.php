<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->where('is_active', true)->orderBy('name')->get();

        $supplierMap = $suppliers->pluck('name', 'id');

        return view('tenant.purchases.index', compact('purchaseOrders', 'suppliers', 'products', 'supplierMap'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $lineTotal = (float) $validated['quantity'] * (float) $validated['unit_cost'];

            $purchaseOrder = PurchaseOrder::query()->create([
                'po_number' => 'PO-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'supplier_id' => $validated['supplier_id'],
                'status' => 'ordered',
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'subtotal' => $lineTotal,
                'total_amount' => $lineTotal,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $purchaseOrderItem = PurchaseOrderItem::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $validated['product_id'],
                'description' => $validated['description'],
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'],
                'line_total' => $lineTotal,
            ]);

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'],
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_number' => $purchaseOrder->po_number,
                'purchase_date' => $validated['order_date'],
                'status' => 'ordered',
                'subtotal' => $lineTotal,
                'total_amount' => $lineTotal,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $validated['product_id'],
                'description' => $validated['description'],
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'],
                'line_total' => $lineTotal,
            ]);
        });

        return back()->with('status', 'Purchase order created successfully.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder, $request): void {
            $lockedPurchase = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedPurchase->status, ['ordered', 'partial'], true)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock can only be received for ordered or partial purchase orders.',
                ]);
            }

            $item = PurchaseOrderItem::query()
                ->where('purchase_order_id', $lockedPurchase->id)
                ->whereNotNull('product_id')
                ->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'quantity' => 'This purchase order does not have a stock-linked product item.',
                ]);
            }

            $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();

            $product->update([
                'current_stock' => (int) $product->current_stock + (int) $validated['quantity'],
            ]);

            StockMovement::query()->create([
                'product_id' => $product->id,
                'movement_type' => 'in',
                'quantity' => (int) $validated['quantity'],
                'reference_type' => 'purchase_order',
                'reference_id' => $lockedPurchase->id,
                'performed_by' => $request->user()?->id,
                'notes' => 'Stock received against PO '.$lockedPurchase->po_number,
            ]);

            $lockedPurchase->update(['status' => 'received']);
            Purchase::query()
                ->where('purchase_order_id', $lockedPurchase->id)
                ->update(['status' => 'received']);
        });

        return back()->with('status', 'Stock received successfully.');
    }
}
