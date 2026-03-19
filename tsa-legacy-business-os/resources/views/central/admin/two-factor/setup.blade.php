<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Admin Two-Factor Setup</h2>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="card">
            <h3 class="text-lg font-black">Step 1: Scan QR in Authenticator App</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Use Google Authenticator / Authy to scan this QR code.</p>
            <div class="mt-4 rounded-xl bg-white p-4 shadow">
                <img src="{{ $qrCode }}" alt="Two-factor QR Code" class="h-56 w-56">
            </div>
            <p class="mt-3 text-xs text-slate-500">Backup manual key: <span class="font-semibold">{{ $secret }}</span></p>
        </div>

        <div class="card">
            <h3 class="text-lg font-black">Step 2: Confirm OTP</h3>
            <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="mt-4 space-y-3">
                @csrf
                <input name="otp" maxlength="6" minlength="6" placeholder="Enter 6-digit OTP" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Confirm & Enable</button>
            </form>

            @if ($alreadyConfirmed)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    Two-factor is already enabled on your account.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.two-factor.disable') }}" class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-700">
                @csrf
                <label class="text-xs uppercase tracking-wide text-slate-500">Disable 2FA (requires password)</label>
                <input type="password" name="password" placeholder="Account password" class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                <button class="mt-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">Disable Two-Factor</button>
            </form>
        </div>
    </div>
</x-app-layout>
