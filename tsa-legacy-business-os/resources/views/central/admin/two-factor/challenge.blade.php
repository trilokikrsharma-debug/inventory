<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Admin Two-Factor Challenge</h2>
    </x-slot>

    <div class="mx-auto max-w-lg card">
        <h3 class="text-lg font-black">Enter OTP to Continue</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Your admin routes are protected with MFA. Enter the 6-digit code from your authenticator app.
        </p>

        <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="mt-4 space-y-3">
            @csrf
            <input name="otp" maxlength="6" minlength="6" placeholder="Enter 6-digit OTP" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
            <button class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Verify OTP</button>
        </form>

        <a href="{{ route('admin.two-factor.setup') }}" class="mt-4 inline-flex text-xs font-semibold text-cyan-600 hover:text-cyan-500">
            Need to reconfigure authenticator?
        </a>
    </div>
</x-app-layout>
