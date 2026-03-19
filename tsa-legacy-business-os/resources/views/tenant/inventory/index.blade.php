<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Products & Inventory</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Manage product catalog, pricing and stock movements.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tenant.categories.index') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Categories</a>
                <a href="{{ route('tenant.units.index') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Units</a>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <form method="GET" action="{{ route('tenant.inventory.index') }}" class="mb-4 flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by product name or SKU" class="w-full max-w-sm rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Search</button>
        </form>

        <h3 class="text-lg font-black">Add Product</h3>
        <form method="POST" action="{{ route('tenant.products.store') }}" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input name="name" placeholder="Name" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="sku" placeholder="SKU (optional)" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <select name="category_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="unit_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                @endforeach
            </select>
            <input name="selling_price" type="number" step="0.01" min="0" placeholder="Selling Price" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="cost_price" type="number" step="0.01" min="0" placeholder="Cost Price" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="tax_rate" type="number" step="0.01" min="0" placeholder="Tax %" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="reorder_level" type="number" min="0" placeholder="Reorder Level" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="current_stock" type="number" min="0" placeholder="Opening Stock" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create Product</button>
        </form>
    </div>

    <div class="mt-5 card">
        <h3 class="mb-3 text-lg font-black">Product List</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">SKU</th>
                        <th class="px-2 py-2">Name</th>
                        <th class="px-2 py-2">Category</th>
                        <th class="px-2 py-2">Unit</th>
                        <th class="px-2 py-2">Stock</th>
                        <th class="px-2 py-2">Price</th>
                        <th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b border-slate-100 align-top dark:border-slate-800">
                            <td class="px-2 py-2 font-semibold">{{ $product->sku }}</td>
                            <td class="px-2 py-2">
                                <div class="font-semibold">{{ $product->name }}</div>
                                <div class="text-xs text-slate-500">Reorder: {{ $product->reorder_level }}</div>
                            </td>
                            <td class="px-2 py-2">{{ $categoryMap[$product->category_id] ?? '-' }}</td>
                            <td class="px-2 py-2">{{ $unitMap[$product->unit_id] ?? ($product->unit ?: '-') }}</td>
                            <td class="px-2 py-2">{{ $product->current_stock }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $product->selling_price, 2) }}</td>
                            <td class="px-2 py-2">
                                <form method="POST" action="{{ route('tenant.products.stock', $product) }}" class="mb-2 flex gap-2">
                                    @csrf
                                    <select name="movement_type" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                        <option value="in">Stock In</option>
                                        <option value="out">Stock Out</option>
                                        <option value="adjustment">Adjust</option>
                                    </select>
                                    <input name="quantity" type="number" min="1" placeholder="Qty" class="w-20 rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900" required>
                                    <button class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Update</button>
                                </form>
                                <form method="POST" action="{{ route('tenant.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-5 text-center text-slate-500">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
</x-app-layout>
