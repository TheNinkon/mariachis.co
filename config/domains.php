<?php

$appUrl = env('APP_URL', 'http://mariachis.co');
$rootDomain = env('ROOT_DOMAIN', parse_url($appUrl, PHP_URL_HOST) ?: 'mariachis.co');
$scheme = env('APP_SCHEME', parse_url($appUrl, PHP_URL_SCHEME) ?: 'http');
$publicHosts = [$rootDomain];

if (in_array($rootDomain, ['localhost', '127.0.0.1'], true)) {
    $publicHosts = ['localhost', '127.0.0.1'];
}

return [
    'scheme' => $scheme,
    'root' => $rootDomain,
    'admin' => env('ADMIN_DOMAIN', 'admin.'.$rootDomain),
    'partner' => env('PARTNER_DOMAIN', 'partner.'.$rootDomain),
    'public_hosts' => array_values(array_unique($publicHosts)),
];
