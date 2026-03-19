<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Plans & Feature Flags</h2>
    </x-slot>

    <div class="card">
        <h3 class="text-lg font-bold">Create / Update Plan</h3>
        <form method="POST" action="{{ route('admin.plans.store') }}" class="mt-4 grid gap-3 md:grid-cols-4">
            @csrf
            <input name="name" required placeholder="Plan Name" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="slug" required placeholder="plan-slug" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="monthly_price" required type="number" step="0.01" placeholder="Monthly Price" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="yearly_price" type="number" step="0.01" placeholder="Yearly Price" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="currency" value="INR" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <input name="sort_order" value="1" type="number" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <select name="is_active" class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Save Plan</button>
        </form>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($plans as $plan)
            <div class="card">
                <h3 class="text-lg font-bold">{{ $plan->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">Rs {{ number_format((float) $plan->monthly_price, 2) }}/month</p>

                <form method="POST" action="{{ route('admin.plans.features.sync', $plan) }}" class="mt-4 space-y-2">
                    @csrf
                    @foreach ($features as $feature)
                        @php $existing = $plan->features->firstWhere('id', $feature->id); @endphp
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <span>{{ $feature->name }}</span>
                                <input type="checkbox" name="features[{{ $loop->index }}][is_enabled]" value="1" @checked(optional($existing?->pivot)->is_enabled)>
                            </div>
                            <input type="hidden" name="features[{{ $loop->index }}][feature_id]" value="{{ $feature->id }}">
                            <input type="text" name="features[{{ $loop->index }}][value][value]" value="{{ data_get($existing?->pivot?->value, 'value') }}" placeholder="Value (optional)" class="mt-2 w-full rounded border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                        </div>
                    @endforeach
                    <button class="w-full rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Update Features</button>
                </form>
            </div>
        @endforeach
    </div>
</x-app-layout>

