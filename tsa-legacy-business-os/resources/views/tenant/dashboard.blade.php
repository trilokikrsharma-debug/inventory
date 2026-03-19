<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">ERP Dashboard</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Operational summary across inventory, sales, purchases and customers.</p>
            </div>
            <a href="{{ route('tenant.billing.index') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Subscription & Billing
            </a>
        </div>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Products</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ number_format($stats['total_products']) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Customers</p>
            <p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ number_format($stats['total_customers']) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Sales</p>
            <p class="mt-2 text-2xl font-black text-cyan-600">Rs {{ number_format($stats['total_sales'], 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Purchases</p>
            <p class="mt-2 text-2xl font-black text-amber-600">Rs {{ number_format($stats['total_purchases'], 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Inventory Stock Value</p>
            <p class="mt-2 text-2xl font-black text-emerald-600">Rs {{ number_format($stats['inventory_stock_value'], 2) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-black">Recent Invoices</h3>
                <a href="{{ route('tenant.sales.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-500">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                            <th class="px-2 py-2">Invoice #</th>
                            <th class="px-2 py-2">Date</th>
                            <th class="px-2 py-2">Status</th>
                            <th class="px-2 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $invoice)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-2 py-2 font-semibold">{{ $invoice->invoice_number }}</td>
                                <td class="px-2 py-2">{{ $invoice->invoice_date }}</td>
                                <td class="px-2 py-2 uppercase">{{ $invoice->status }}</td>
                                <td class="px-2 py-2 text-right">Rs {{ number_format((float) $invoice->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-5 text-center text-slate-500">No invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-black">Recent Customers</h3>
                <a href="{{ route('tenant.customers.index') }}" class="text-xs font-semibold text-cyan-600 hover:text-cyan-500">View all</a>
            </div>
            <div class="space-y-2">
                @forelse ($recentCustomers as $customer)
                    <div class="rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $customer->name }}</p>
                        <p class="text-xs text-slate-500">{{ $customer->phone ?: ($customer->email ?: 'No contact') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No customers added yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card">
            <h3 class="text-lg font-black">Subscription Health</h3>
            @if ($subscription)
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    Plan: <span class="font-semibold">{{ $subscription->plan->name ?? 'N/A' }}</span>
                </p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    Status: <span class="font-semibold uppercase">{{ $subscription->status }}</span>
                </p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    Ends at: <span class="font-semibold">{{ optional($subscription->ends_at)->toDateString() ?? 'N/A' }}</span>
                </p>
            @else
                <p class="mt-3 text-sm text-amber-600">No active subscription linked yet.</p>
            @endif
        </div>
        <div class="card">
            <h3 class="text-lg font-black">Quick Operations</h3>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="{{ route('tenant.inventory.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Manage Products</a>
                <a href="{{ route('tenant.customers.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Manage Customers</a>
                <a href="{{ route('tenant.suppliers.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Manage Suppliers</a>
                <a href="{{ route('tenant.purchases.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Create Purchase</a>
                <a href="{{ route('tenant.sales.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Create Sale</a>
                <a href="{{ route('tenant.reports.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">View Reports</a>
            </div>
        </div>
    </div>
</x-app-layout>
