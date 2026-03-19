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
        @php($isEnterpriseLogin = request()->routeIs('login'))
        <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,.25),transparent_45%),radial-gradient(circle_at_bottom_left,_rgba(14,116,144,.25),transparent_45%)]"></div>
            <div class="relative z-10 flex min-h-screen flex-col px-4 py-10 {{ $isEnterpriseLogin ? 'items-stretch justify-start lg:justify-center' : 'items-center justify-center' }}">
                <a href="{{ route('marketing.home') }}" class="mb-6 flex items-center gap-3 text-white {{ $isEnterpriseLogin ? 'mx-auto w-full max-w-6xl' : '' }}">
                    <x-application-logo class="h-12 w-12 fill-current text-cyan-400" />
                    <span class="text-xl font-extrabold tracking-tight">TSA Legacy Business OS</span>
                </a>

                @if ($isEnterpriseLogin)
                    <div class="mx-auto w-full max-w-7xl">
                        {{ $slot }}
                    </div>
                @else
                    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-white/10 p-7 shadow-2xl backdrop-blur">
                        {{ $slot }}
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
