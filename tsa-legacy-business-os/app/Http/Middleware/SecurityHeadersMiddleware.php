<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
        ];

        $scriptSrc = [
            "'self'",
            'https://checkout.razorpay.com',
        ];

        $connectSrc = [
            "'self'",
            'https://api.razorpay.com',
        ];

        $frameSrc = [
            "'self'",
            'https://api.razorpay.com',
            'https://checkout.razorpay.com',
        ];

        if (App::environment('local')) {
            $viteOrigins = [
                'http://127.0.0.1:5173',
                'http://localhost:5173',
                'http://[::1]:5173',
            ];

            $viteSocketOrigins = [
                'ws://127.0.0.1:5173',
                'ws://localhost:5173',
                'ws://[::1]:5173',
            ];

            $scriptSrc = array_merge($scriptSrc, ["'unsafe-inline'", "'unsafe-eval'"]);
            $styleSrc = array_merge($styleSrc, $viteOrigins);
            $scriptSrc = array_merge($scriptSrc, $viteOrigins);
            $connectSrc = array_merge($connectSrc, $viteOrigins, $viteSocketOrigins);
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            "base-uri 'self'; ".
            "form-action 'self'; ".
            "frame-ancestors 'none'; ".
            "object-src 'none'; ".
            "img-src 'self' data: https:; ".
            'style-src '.implode(' ', array_unique($styleSrc)).'; '.
            "font-src 'self' data: https://fonts.gstatic.com; ".
            'script-src '.implode(' ', array_unique($scriptSrc)).'; '.
            'connect-src '.implode(' ', array_unique($connectSrc)).'; '.
            'frame-src '.implode(' ', array_unique($frameSrc)).';'
        );

        return $response;
    }
}
