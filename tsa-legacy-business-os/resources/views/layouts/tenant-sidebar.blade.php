<aside class="hidden w-72 flex-col border-r border-slate-200 bg-slate-950 text-slate-100 lg:flex">
    <div class="border-b border-slate-800 px-5 py-5">
        <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">TSA Legacy</p>
        <h2 class="mt-1 text-lg font-black">Business OS</h2>
        <p class="mt-1 text-xs text-slate-400">Tenant: {{ tenant('id') }}</p>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4 text-sm">
        <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Dashboard</a>
        <a href="{{ route('tenant.inventory.index') }}" class="{{ request()->routeIs('tenant.inventory.*') || request()->routeIs('tenant.products.*') || request()->routeIs('tenant.categories.*') || request()->routeIs('tenant.units.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Products</a>
        <a href="{{ route('tenant.customers.index') }}" class="{{ request()->routeIs('tenant.customers.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Customers</a>
        <a href="{{ route('tenant.suppliers.index') }}" class="{{ request()->routeIs('tenant.suppliers.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Suppliers</a>
        <a href="{{ route('tenant.sales.index') }}" class="{{ request()->routeIs('tenant.sales.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Sales</a>
        <a href="{{ route('tenant.purchases.index') }}" class="{{ request()->routeIs('tenant.purchases.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Purchase</a>
        <a href="{{ route('tenant.reports.index') }}" class="{{ request()->routeIs('tenant.reports.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Reports</a>
        <a href="{{ route('tenant.settings.index') }}" class="{{ request()->routeIs('tenant.settings.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Settings</a>
        <a href="{{ route('tenant.billing.index') }}" class="{{ request()->routeIs('tenant.billing.*') ? 'bg-cyan-500/20 text-cyan-200' : 'text-slate-200 hover:bg-white/10' }} block rounded-xl px-3 py-2 font-semibold">Billing</a>
    </nav>

    <div class="border-t border-slate-800 px-4 py-4">
        <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm text-slate-200 hover:bg-white/10">Profile</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="mt-1 w-full rounded-xl bg-rose-600 px-3 py-2 text-left text-sm font-semibold text-white hover:bg-rose-500">Logout</button>
        </form>
    </div>
</aside>
