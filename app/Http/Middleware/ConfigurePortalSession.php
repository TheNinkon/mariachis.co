<?php

namespace App\Http\Middleware;

use App\Support\PortalHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigurePortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.cookie' => PortalHosts::sessionCookieForRequest($request),
            'session.domain' => null,
        ]);

        return $next($request);
    }
}
