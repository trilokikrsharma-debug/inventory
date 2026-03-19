<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Workspace Settings</h2>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h3 class="text-lg font-black">Tenant Profile</h3>
            <form method="POST" action="{{ route('tenant.settings.update') }}" class="mt-4 grid gap-3 md:grid-cols-2">
                @csrf
                @method('PATCH')
                <div class="md:col-span-2">
                    <label class="text-xs uppercase tracking-wide text-slate-500">Workspace Name</label>
                    <input name="name" value="{{ old('name', $tenant->name) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Email</label>
                    <input name="email" value="{{ old('email', $tenant->email) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Phone</label>
                    <input name="phone" value="{{ old('phone', $tenant->phone) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div class="md:col-span-2">
                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save Settings</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 class="text-lg font-black">Account Snapshot</h3>
            <div class="mt-4 space-y-2 text-sm">
                <p><span class="font-semibold">Tenant ID:</span> {{ $tenant->id }}</p>
                <p><span class="font-semibold">Status:</span> {{ strtoupper($tenant->status) }}</p>
                <p><span class="font-semibold">Domain(s):</span></p>
                <ul class="list-inside list-disc text-xs text-slate-500 dark:text-slate-300">
                    @forelse ($tenant->domains as $domain)
                        <li>{{ $domain->domain }}</li>
                    @empty
                        <li>No domain mapped.</li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                <p class="text-xs uppercase tracking-wide text-slate-500">Subscription</p>
                @if ($subscription)
                    <p class="mt-2 text-sm font-semibold">{{ $subscription->plan->name ?? 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Status: {{ strtoupper($subscription->status) }}</p>
                    <p class="text-xs text-slate-500">Ends: {{ optional($subscription->ends_at)->toDateString() ?? 'N/A' }}</p>
                    <a href="{{ route('tenant.billing.index') }}" class="mt-3 inline-flex rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Open Billing</a>
                @else
                    <p class="mt-2 text-xs text-amber-600">No subscription linked.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
