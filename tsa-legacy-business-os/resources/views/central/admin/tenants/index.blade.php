<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Tenant Administration</h2>
    </x-slot>

    <div class="card">
        <h3 class="text-lg font-bold">Create Tenant</h3>
        <form method="POST" action="{{ route('admin.tenants.store') }}" class="mt-4 grid gap-3 md:grid-cols-4">
            @csrf
            <input name="name" required placeholder="Business Name" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="slug" required placeholder="tenant-slug" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="domain" required placeholder="tenant.tsalegacy.shop" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="email" type="email" placeholder="business@email.com" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="owner_name" required placeholder="Owner Name" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="owner_email" required type="email" placeholder="owner@email.com" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="owner_password" required type="password" placeholder="Owner Password" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Provision Tenant
            </button>
        </form>
    </div>

    <div class="card mt-6">
        <h3 class="text-lg font-bold">All Tenants</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                        <th class="px-2 py-2">Name</th>
                        <th class="px-2 py-2">Slug</th>
                        <th class="px-2 py-2">Domain</th>
                        <th class="px-2 py-2">Plan</th>
                        <th class="px-2 py-2">Status</th>
                        <th class="px-2 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tenants as $tenant)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-2 py-2">{{ $tenant->name }}</td>
                            <td class="px-2 py-2">{{ $tenant->slug }}</td>
                            <td class="px-2 py-2">{{ $tenant->domains->first()->domain ?? '-' }}</td>
                            <td class="px-2 py-2">{{ $tenant->activeSubscription->plan->name ?? '-' }}</td>
                            <td class="px-2 py-2 uppercase">{{ $tenant->status }}</td>
                            <td class="px-2 py-2">
                                <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                        @foreach (['pending','active','suspended','trialing','canceled'] as $status)
                                            <option value="{{ $status }}" @selected($tenant->status === $status)>{{ strtoupper($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-slate-900 px-3 py-1 text-xs font-semibold text-white dark:bg-slate-100 dark:text-slate-900">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tenants->links() }}</div>
    </div>
</x-app-layout>

