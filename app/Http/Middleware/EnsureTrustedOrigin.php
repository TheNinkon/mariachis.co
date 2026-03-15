<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrustedOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->routeIs('mariachi.wompi.webhook')) {
            return $next($request);
        }

        if ($request->header('Sec-Fetch-Site') === 'same-origin') {
            return $next($request);
        }

        $trustedHosts = collect(Arr::wrap(config('security.trusted_hosts', [])))
            ->filter()
            ->map(static fn (string $host): string => mb_strtolower(trim($host)))
            ->push(mb_strtolower((string) $request->getHost()))
            ->unique()
            ->values();

        foreach (['Origin', 'Referer'] as $header) {
            $value = trim((string) $request->headers->get($header, ''));

            if ($value === '') {
                continue;
            }

            $host = mb_strtolower((string) parse_url($value, PHP_URL_HOST));

            if ($host === '' || ! $trustedHosts->contains($host)) {
                abort(403, 'Origen no permitido.');
            }
        }

        return $next($request);
    }
}
