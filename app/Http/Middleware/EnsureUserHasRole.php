<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        if ($user->status !== 'active') {
            auth()->logout();

            $loginRoute = $request->is('mariachi/*') ? 'mariachi.login' : 'login';

            return redirect()->route($loginRoute)->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ]);
        }

        return $next($request);
    }
}
