<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing | TSA Legacy Business OS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/react/main.jsx'])
</head>
<body style="font-family: Manrope, sans-serif;">
    @php
        $reactProps = [
            'routes' => [
                'home' => route('marketing.home'),
                'login' => route('login'),
                'register' => route('register'),
            ],
            'plans' => $plans->map(function ($plan) {
                return [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'monthly_price' => (float) $plan->monthly_price,
                    'features' => $plan->features
                        ->filter(fn ($feature) => (bool) ($feature->pivot->is_enabled ?? false))
                        ->map(fn ($feature) => $feature->name)
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
        ];
    @endphp

    <div id="tsa-react-root" data-page="marketing-pricing"></div>
    <script id="tsa-react-props" type="application/json">@json($reactProps)</script>
</body>
</html>
