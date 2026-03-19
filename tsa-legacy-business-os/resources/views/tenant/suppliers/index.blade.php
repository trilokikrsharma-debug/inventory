<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Suppliers</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Vendor management with purchase history insights.</p>
            </div>
            <form method="GET" action="{{ route('tenant.suppliers.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search supplier" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <button class="rounded-xl bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Search</button>
            </form>
        </div>
    </x-slot>

    <div class="card">
        <h3 class="text-lg font-black">Add Supplier</h3>
        <form method="POST" action="{{ route('tenant.suppliers.store') }}" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <input name="name" placeholder="Supplier name" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="email" placeholder="Email" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="phone" placeholder="Phone" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="gstin" placeholder="GSTIN" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create</button>
        </form>
    </div>

    <div class="mt-5 card">
        <h3 class="mb-3 text-lg font-black">Supplier List</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">Supplier</th>
                        <th class="px-2 py-2">Contact</th>
                        <th class="px-2 py-2">Orders</th>
                        <th class="px-2 py-2">Total Spend</th>
                        <th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        @php($history = $purchaseHistory[$supplier->id] ?? null)
                        <tr class="border-b border-slate-100 align-top dark:border-slate-800">
                            <td class="px-2 py-2 font-semibold">{{ $supplier->name }}</td>
                            <td class="px-2 py-2 text-xs">
                                <div>{{ $supplier->phone ?: '-' }}</div>
                                <div class="text-slate-500">{{ $supplier->email ?: '-' }}</div>
                            </td>
                            <td class="px-2 py-2">{{ (int) ($history->orders ?? 0) }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) ($history->total_spend ?? 0), 2) }}</td>
                            <td class="px-2 py-2">
                                <form method="POST" action="{{ route('tenant.suppliers.update', $supplier) }}" class="grid gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input name="name" value="{{ $supplier->name }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="email" value="{{ $supplier->email }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="phone" value="{{ $supplier->phone }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="gstin" value="{{ $supplier->gstin }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active) class="rounded border-slate-300 dark:border-slate-700">
                                        Active
                                    </label>
                                    <button class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Update</button>
                                </form>
                                <form method="POST" action="{{ route('tenant.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?');" class="mt-1">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-5 text-center text-slate-500">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>
