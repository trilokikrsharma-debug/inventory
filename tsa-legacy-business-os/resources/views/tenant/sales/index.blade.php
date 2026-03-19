<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Sales & Invoices</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Create invoices, track status and collect payments.</p>
            </div>
        </div>
    </x-slot>

    <div class="card">
        <h3 class="text-lg font-black">Create Invoice</h3>
        <form method="POST" action="{{ route('tenant.sales.store') }}" class="mt-3 grid gap-3 md:grid-cols-5">
            @csrf
            <select name="customer_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Customer (optional)</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="date" name="due_date" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <select name="product_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="">Product (optional)</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                @endforeach
            </select>
            <input name="description" placeholder="Line description" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <input type="number" step="0.01" min="0.01" name="quantity" placeholder="Qty" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="number" step="0.01" min="0" name="unit_price" placeholder="Unit Price" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input type="number" step="0.01" min="0" name="tax_percent" placeholder="Tax %" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="notes" placeholder="Notes (optional)" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Create Invoice</button>
        </form>
    </div>

    <div class="mt-5 card">
        <h3 class="mb-3 text-lg font-black">Recent Invoices</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">Invoice #</th>
                        <th class="px-2 py-2">Customer</th>
                        <th class="px-2 py-2">Date</th>
                        <th class="px-2 py-2">Status</th>
                        <th class="px-2 py-2">Total</th>
                        <th class="px-2 py-2">Balance</th>
                        <th class="px-2 py-2">Payment</th>
                        <th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php($summary = $paymentSummary[$invoice->id] ?? null)
                        <tr class="border-b border-slate-100 align-top dark:border-slate-800">
                            <td class="px-2 py-2 font-semibold">{{ $invoice->invoice_number }}</td>
                            <td class="px-2 py-2">{{ $customerMap[$invoice->customer_id] ?? '-' }}</td>
                            <td class="px-2 py-2">{{ $invoice->invoice_date }}</td>
                            <td class="px-2 py-2 uppercase">{{ $invoice->status }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $invoice->balance_due, 2) }}</td>
                            <td class="px-2 py-2 text-xs">
                                <div>Paid: Rs {{ number_format((float) ($summary->total_paid ?? $invoice->paid_amount), 2) }}</div>
                                <div class="text-slate-500">Last: {{ $summary->last_payment_at ?? '-' }}</div>
                            </td>
                            <td class="px-2 py-2">
                                @if (in_array($invoice->status, ['issued', 'partial', 'overdue'], true))
                                    <form method="POST" action="{{ route('tenant.sales.payments.store', $invoice) }}" class="mb-2 grid gap-1">
                                        @csrf
                                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900" required>
                                        <select name="payment_method" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                            <option value="cash">Cash</option>
                                            <option value="bank">Bank</option>
                                            <option value="upi">UPI</option>
                                            <option value="card">Card</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <button class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Add Payment</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('tenant.sales.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-2 py-5 text-center text-slate-500">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
