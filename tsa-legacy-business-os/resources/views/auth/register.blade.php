<x-guest-layout>
    @php
        $reactProps = [
            'csrfToken' => csrf_token(),
            'registerUrl' => route('register'),
            'loginUrl' => route('login'),
            'pricingUrl' => route('marketing.pricing'),
            'old' => [
                'name' => old('name'),
                'email' => old('email'),
                'phone' => old('phone'),
            ],
            'errors' => $errors->toArray(),
        ];
    @endphp

    <div id="tsa-react-root" data-page="auth-register"></div>
    <script id="tsa-react-props" type="application/json">@json($reactProps)</script>
    @vite('resources/js/react/main.jsx')
</x-guest-layout>
