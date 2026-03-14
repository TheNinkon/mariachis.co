<?php

return [
    'wompi' => [
        'environment' => env('WOMPI_ENVIRONMENT', 'sandbox'),
        'public_key' => env('WOMPI_PUBLIC_KEY', ''),
        'private_key' => env('WOMPI_PRIVATE_KEY', ''),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET', ''),
        'events_secret' => env('WOMPI_EVENTS_SECRET', ''),
        'currency' => env('WOMPI_CURRENCY', 'COP'),
        'checkout_url' => env('WOMPI_CHECKOUT_URL', 'https://checkout.wompi.co/p/'),
        'sandbox_api_base_url' => env('WOMPI_SANDBOX_API_BASE_URL', 'https://sandbox.wompi.co/v1'),
        'production_api_base_url' => env('WOMPI_PRODUCTION_API_BASE_URL', 'https://production.wompi.co/v1'),
    ],
];
