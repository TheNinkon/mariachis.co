<?php

namespace App\Http\Middleware;

use App\Support\PortalHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            auth()->logout();

            return redirect()->route(PortalHosts::loginRouteNameForUser($user))->withErrors([
                'email' => $user->accessStatusMessage(),
            ]);
        }

        return $next($request);
    }
}
