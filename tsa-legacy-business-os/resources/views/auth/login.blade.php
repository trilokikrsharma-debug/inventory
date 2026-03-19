<x-guest-layout>
    @php
        $reactProps = [
            'csrfToken' => csrf_token(),
            'loginUrl' => route('login'),
            'registerUrl' => route('register'),
            'forgotPasswordUrl' => Route::has('password.request') ? route('password.request') : null,
            'pricingUrl' => route('marketing.pricing'),
            'status' => session('status'),
            'old' => [
                'email' => old('email'),
                'remember' => old('remember'),
            ],
            'errors' => $errors->toArray(),
        ];
    @endphp

    <div id="tsa-react-root" data-page="auth-login"></div>
    <script id="tsa-react-props" type="application/json">@json($reactProps)</script>
    @vite('resources/js/react/main.jsx')
</x-guest-layout>
