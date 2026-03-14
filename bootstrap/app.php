<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ConfigurePortalSession;
use App\Http\Middleware\EnsureClientAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/public.php',
            __DIR__ . '/../routes/admin.php',
            __DIR__ . '/../routes/partner.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            ConfigurePortalSession::class,
            LocaleMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'pagos/wompi/webhook',
        ]);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'client' => EnsureClientAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
