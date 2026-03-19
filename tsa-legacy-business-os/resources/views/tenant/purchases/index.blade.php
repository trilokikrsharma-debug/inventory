<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Purchase Orders</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Create purchases and receive stock into inventory.</p>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <h3 class="text-lg font-black">Create Purchase Entry</h3>
        <form method="POST" action="{{ route('tenant.purchases.store') }}" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <select name="supplier_id" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
            <input type="date" name="order_date" value="{{ now()->toDateString() }}" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="date" name="expected_date" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <select name="product_id" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                @endforeach
            </select>
            <input name="description" placeholder="Description" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="number" step="0.01" min="0.01" name="quantity" placeholder="Quantity" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="number" step="0.01" min="0" name="unit_cost" placeholder="Unit Cost" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="notes" placeholder="Notes (optional)" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create Purchase</button>
        </form>
    </div>

    <div class="mt-5 card">
        <h3 class="mb-3 text-lg font-black">Purchase History</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">PO #</th>
                        <th class="px-2 py-2">Supplier</th>
                        <th class="px-2 py-2">Order Date</th>
                        <th class="px-2 py-2">Status</th>
                        <th class="px-2 py-2">Total</th>
                        <th class="px-2 py-2">Receive Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $purchaseOrder)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-2 py-2 font-semibold">{{ $purchaseOrder->po_number }}</td>
                            <td class="px-2 py-2">{{ $supplierMap[$purchaseOrder->supplier_id] ?? '-' }}</td>
                            <td class="px-2 py-2">{{ $purchaseOrder->order_date }}</td>
                            <td class="px-2 py-2 uppercase">{{ $purchaseOrder->status }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                            <td class="px-2 py-2">
                                @if (in_array($purchaseOrder->status, ['ordered', 'partial'], true))
                                    <form method="POST" action="{{ route('tenant.purchases.receive', $purchaseOrder) }}" class="flex gap-2">
                                        @csrf
                                        <input type="number" min="1" name="quantity" placeholder="Qty" required class="w-20 rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                        <button class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Receive</button>
                                    </form>
                                @else
                                    <span class="text-xs text-emerald-600">Received</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-2 py-5 text-center text-slate-500">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $purchaseOrders->links() }}
        </div>
    </div>
</x-app-layout>
