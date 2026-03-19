<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Customers</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Manage customer ledger and credit balance tracking.</p>
            </div>
            <form method="GET" action="{{ route('tenant.customers.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <button class="rounded-xl bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Search</button>
            </form>
        </div>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Active Customers</p>
            <p class="mt-2 text-3xl font-black">{{ number_format($totals['active']) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Credit Limit</p>
            <p class="mt-2 text-2xl font-black text-cyan-600">Rs {{ number_format((float) $totals['credit_limit'], 2) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase text-slate-500">Credit Balance</p>
            <p class="mt-2 text-2xl font-black text-amber-600">Rs {{ number_format((float) $totals['credit_balance'], 2) }}</p>
        </div>
    </div>

    <div class="mt-5 card">
        <h3 class="text-lg font-black">Add Customer</h3>
        <form method="POST" action="{{ route('tenant.customers.store') }}" class="mt-3 grid gap-3 md:grid-cols-6">
            @csrf
            <input name="name" placeholder="Name" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="email" placeholder="Email" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="phone" placeholder="Phone" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="gstin" placeholder="GSTIN" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="number" step="0.01" min="0" name="credit_limit" placeholder="Credit Limit" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create</button>
        </form>
    </div>

    <div class="mt-5 card">
        <h3 class="mb-3 text-lg font-black">Customer List</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">Name</th>
                        <th class="px-2 py-2">Contact</th>
                        <th class="px-2 py-2">Credit Limit</th>
                        <th class="px-2 py-2">Credit Balance</th>
                        <th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b border-slate-100 align-top dark:border-slate-800">
                            <td class="px-2 py-2 font-semibold">{{ $customer->name }}</td>
                            <td class="px-2 py-2 text-xs">
                                <div>{{ $customer->phone ?: '-' }}</div>
                                <div class="text-slate-500">{{ $customer->email ?: '-' }}</div>
                            </td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $customer->credit_limit, 2) }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $customer->credit_balance, 2) }}</td>
                            <td class="px-2 py-2">
                                <form method="POST" action="{{ route('tenant.customers.update', $customer) }}" class="grid gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input name="name" value="{{ $customer->name }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="email" value="{{ $customer->email }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="phone" value="{{ $customer->phone }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="credit_limit" type="number" step="0.01" min="0" value="{{ $customer->credit_limit }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <input name="credit_balance" type="number" step="0.01" min="0" value="{{ $customer->credit_balance }}" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="checkbox" name="is_active" value="1" @checked($customer->is_active) class="rounded border-slate-300 dark:border-slate-700">
                                        Active
                                    </label>
                                    <button class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Update</button>
                                </form>
                                <form method="POST" action="{{ route('tenant.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?');" class="mt-1">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-5 text-center text-slate-500">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
