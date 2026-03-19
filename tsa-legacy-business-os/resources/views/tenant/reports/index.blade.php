<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Business Reports</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Sales, stock and profit report snapshot.</p>
            </div>
            <form method="GET" action="{{ route('tenant.reports.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <button class="rounded-xl bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Apply</button>
            </form>
        </div>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Sales Report</p>
            <p class="mt-2 text-xl font-black text-cyan-600">Rs {{ number_format($salesTotal, 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Stock Report</p>
            <p class="mt-2 text-xl font-black text-emerald-600">Rs {{ number_format($inventoryValue, 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Purchase Report</p>
            <p class="mt-2 text-xl font-black text-amber-600">Rs {{ number_format($purchaseTotal, 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Profit Report</p>
            <p class="mt-2 text-xl font-black {{ $profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Rs {{ number_format($profit, 2) }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="card">
            <h3 class="mb-3 text-lg font-black">Daily Sales</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                            <th class="px-2 py-2">Date</th>
                            <th class="px-2 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salesRows as $row)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-2 py-2">{{ $row->date }}</td>
                                <td class="px-2 py-2 text-right">Rs {{ number_format((float) $row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-2 py-4 text-center text-slate-500">No sales data in selected range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 class="mb-3 text-lg font-black">Top Stock Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                            <th class="px-2 py-2">Product</th>
                            <th class="px-2 py-2">SKU</th>
                            <th class="px-2 py-2 text-right">Stock</th>
                            <th class="px-2 py-2 text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topStock as $item)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-2 py-2">{{ $item->name }}</td>
                                <td class="px-2 py-2">{{ $item->sku }}</td>
                                <td class="px-2 py-2 text-right">{{ $item->current_stock }}</td>
                                <td class="px-2 py-2 text-right">Rs {{ number_format((float) $item->current_stock * (float) $item->cost_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-4 text-center text-slate-500">No inventory data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
