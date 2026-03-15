<?php

$publicHosts = collect(config('domains.public_hosts', [config('domains.root')]))
    ->filter()
    ->all();

return [
    'trusted_hosts' => array_values(array_filter(array_unique(array_merge(
        $publicHosts,
        array_filter([
            config('domains.admin'),
            config('domains.partner'),
        ])
    )))),

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(), browsing-topics=()'),
        'content_security_policy' => env(
            'SECURITY_CONTENT_SECURITY_POLICY',
            "default-src 'self' https: http: data: blob:; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https: http:; img-src 'self' https: http: data: blob:; font-src 'self' https: http: data:; style-src 'self' 'unsafe-inline' https: http:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http: blob:; connect-src 'self' https: http: ws: wss:;"
        ),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', false),
        ],
    ],
];
