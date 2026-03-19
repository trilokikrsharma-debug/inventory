<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Platform Control Center</h2>
            <a href="{{ route('marketing.pricing') }}" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">
                View Pricing
            </a>
        </div>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Tenants</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">{{ number_format($metrics['tenants'] ?? 0) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Active Tenants</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ number_format($metrics['active_tenants'] ?? 0) }}</p>
        </div>
        <div class="metric">
            <p class="text-xs uppercase tracking-wide text-slate-500">Available Plans</p>
            <p class="mt-2 text-3xl font-extrabold text-cyan-600">{{ number_format($metrics['plans'] ?? 0) }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card">
            <h3 class="text-lg font-bold">Launch Checklist</h3>
            <ul class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <li>1. Configure Razorpay live credentials and webhook secret.</li>
                <li>2. Point wildcard DNS: <code>*.tsalegacy.shop</code> to load balancer.</li>
                <li>3. Enable Cloud Run min instances and Redis queue workers.</li>
                <li>4. Run <code>php artisan db:seed</code> for starter/pro/enterprise plans.</li>
            </ul>
        </div>
        <div class="card">
            <h3 class="text-lg font-bold">Quick Access</h3>
            <div class="mt-4 grid gap-2">
                <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Manage Tenants</a>
                <a href="{{ route('admin.plans.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Manage Plans</a>
                <a href="{{ route('profile.edit') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Security Settings</a>
            </div>
        </div>
    </div>
</x-app-layout>

