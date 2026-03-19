<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TSA Legacy Business OS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased" style="font-family: Manrope, sans-serif;">
        @php($isTenant = request()->routeIs('tenant.*'))
        <div class="min-h-screen {{ $isTenant ? 'lg:flex' : '' }}">
            @if ($isTenant)
                @include('layouts.tenant-sidebar')
            @endif

            <div class="min-w-0 flex-1">
                @include('layouts.navigation')

                @isset($header)
                    <header class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="mx-auto max-w-7xl px-4 pb-8 pt-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                            <p class="font-semibold">Please fix the following:</p>
                            <ul class="mt-1 list-inside list-disc text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
