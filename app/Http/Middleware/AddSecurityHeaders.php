<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = [
            'X-Frame-Options' => (string) config('security.headers.frame_options', 'SAMEORIGIN'),
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => (string) config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'),
            'Permissions-Policy' => (string) config('security.headers.permissions_policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=()'),
        ];

        foreach ($headers as $header => $value) {
            if ($value !== '') {
                $response->headers->set($header, $value);
            }
        }

        $contentSecurityPolicy = trim((string) config(
            'security.headers.content_security_policy',
            "default-src 'self' https: http: data: blob:; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https: http:; img-src 'self' https: http: data: blob:; font-src 'self' https: http: data:; style-src 'self' 'unsafe-inline' https: http:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http: blob:; connect-src 'self' https: http: ws: wss:;"
        ));
        if ($contentSecurityPolicy !== '') {
            $response->headers->set('Content-Security-Policy', $contentSecurityPolicy);
        }

        if ($request->isSecure() && config('security.headers.hsts.enabled', true)) {
            $maxAge = (int) config('security.headers.hsts.max_age', 31536000);
            $value = "max-age={$maxAge}";

            if (config('security.headers.hsts.include_subdomains', true)) {
                $value .= '; includeSubDomains';
            }

            if (config('security.headers.hsts.preload', false)) {
                $value .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }
}
