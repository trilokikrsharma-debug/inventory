@php($isTenant = request()->routeIs('tenant.*'))
@php($dashboardRoute = $isTenant ? 'tenant.dashboard' : 'dashboard')

<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-4">
            <a href="{{ route($dashboardRoute) }}" class="text-lg font-black text-slate-900 dark:text-white">
                TSA Legacy OS
            </a>

            @if (! $isTenant)
                <div class="hidden items-center gap-2 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    @role('super-admin')
                        <x-nav-link :href="route('admin.tenants.index')" :active="request()->routeIs('admin.tenants.*')">
                            Tenants
                        </x-nav-link>
                        <x-nav-link :href="route('admin.plans.index')" :active="request()->routeIs('admin.plans.*')">
                            Plans
                        </x-nav-link>
                    @endrole
                </div>
            @endif
        </div>

        <div class="hidden items-center gap-3 sm:flex">
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ $isTenant ? 'Tenant: '.tenant('id') : 'Central' }}
            </span>
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:text-white">
                        <span>{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                    @if (! $isTenant && request()->user()?->is_platform_admin)
                        <x-dropdown-link :href="route('admin.two-factor.setup')">MFA Setup</x-dropdown-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Logout
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button @click="open = !open" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 sm:hidden dark:text-slate-300 dark:hover:bg-slate-800">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="open" class="space-y-1 border-t border-slate-200 px-4 py-3 sm:hidden dark:border-slate-800">
        <x-responsive-nav-link :href="route($dashboardRoute)" :active="request()->routeIs('dashboard') || request()->routeIs('tenant.dashboard')">
            Dashboard
        </x-responsive-nav-link>

        @if ($isTenant)
            <x-responsive-nav-link :href="route('tenant.inventory.index')">Products</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.customers.index')">Customers</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.suppliers.index')">Suppliers</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.sales.index')">Sales</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.purchases.index')">Purchase</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.reports.index')">Reports</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.settings.index')">Settings</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tenant.billing.index')">Billing</x-responsive-nav-link>
        @else
            @role('super-admin')
                <x-responsive-nav-link :href="route('admin.tenants.index')">Tenants</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.plans.index')">Plans</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.two-factor.setup')">MFA Setup</x-responsive-nav-link>
            @endrole
        @endif

        <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                Logout
            </x-responsive-nav-link>
        </form>
    </div>
</nav>
