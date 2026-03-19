<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Plans & Billing</h2>
    </x-slot>

    <div class="card">
        @if ($subscription)
            <div class="mb-4 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-700 dark:border-cyan-900 dark:bg-cyan-950/40 dark:text-cyan-200">
                Current subscription:
                <strong>{{ $subscription->plan->name ?? 'N/A' }}</strong>
                <span class="uppercase">({{ $subscription->status }})</span>
            </div>
        @endif

        <div
            id="tenant-billing-root"
            class="grid gap-4 md:grid-cols-3"
            data-select-url="{{ route('tenant.billing.select-plan') }}"
            data-checkout-url="{{ route('tenant.billing.checkout') }}"
            data-success-url="{{ route('tenant.billing.payment-success') }}"
            data-csrf-token="{{ csrf_token() }}"
        >
            @foreach ($plans as $plan)
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <p class="text-sm font-bold uppercase tracking-wide text-cyan-600">{{ $plan->name }}</p>
                    <p class="mt-2 text-3xl font-extrabold">Rs {{ number_format((float) $plan->monthly_price, 2) }}</p>
                    <p class="text-xs text-slate-500">monthly</p>

                    <ul class="mt-3 space-y-1 text-xs text-slate-600 dark:text-slate-300">
                        @foreach ($plan->features as $feature)
                            @if ($feature->pivot->is_enabled)
                                <li>- {{ $feature->name }}</li>
                            @endif
                        @endforeach
                    </ul>

                    <button
                        type="button"
                        class="js-select-plan mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                        data-plan-id="{{ $plan->id }}"
                    >
                        Select Plan
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mt-6">
        <h3 class="text-lg font-bold">Recent Payments</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">Invoice</th>
                        <th class="px-2 py-2">Amount</th>
                        <th class="px-2 py-2">Status</th>
                        <th class="px-2 py-2">Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-2 py-2">{{ $payment->invoice_number ?? '-' }}</td>
                            <td class="px-2 py-2">Rs {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="px-2 py-2 uppercase">{{ $payment->status }}</td>
                            <td class="px-2 py-2">{{ optional($payment->paid_at)->toDateTimeString() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-4 text-center text-slate-500">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @vite('resources/js/pages/tenant-billing.js')
</x-app-layout>
