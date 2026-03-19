<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Units</h2>
            <a href="{{ route('tenant.inventory.index') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Back to Products</a>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="card">
            <h3 class="text-lg font-black">Add Unit</h3>
            <form method="POST" action="{{ route('tenant.units.store') }}" class="mt-3 space-y-3">
                @csrf
                <input name="name" placeholder="Unit name" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <input name="code" placeholder="Code (e.g. PCS, KG)" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900"></textarea>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 dark:border-slate-700">
                    Active
                </label>
                <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save Unit</button>
            </form>
        </div>

        <div class="card lg:col-span-2">
            <h3 class="mb-3 text-lg font-black">Unit List</h3>
            <div class="space-y-2">
                @forelse ($units as $unit)
                    <div class="rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-700">
                        <div class="grid gap-2 md:grid-cols-6">
                            <form method="POST" action="{{ route('tenant.units.update', $unit) }}" class="grid gap-2 md:col-span-5 md:grid-cols-5">
                                @csrf
                                @method('PATCH')
                                <input name="name" value="{{ $unit->name }}" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <input name="code" value="{{ $unit->code }}" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <input name="description" value="{{ $unit->description }}" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($unit->is_active) class="rounded border-slate-300 dark:border-slate-700">
                                    Active
                                </label>
                                <button class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Update</button>
                            </form>
                            <form method="POST" action="{{ route('tenant.units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?');" class="md:col-span-1">
                                @csrf
                                @method('DELETE')
                                <button class="w-full rounded-lg bg-rose-600 px-3 py-1 text-xs font-semibold text-white hover:bg-rose-500">Delete</button>
                            </form>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Products mapped: {{ $productCountByUnit[$unit->id] ?? 0 }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No units found.</p>
                @endforelse
            </div>
            <div class="mt-4">
                {{ $units->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
