<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('client.login');
        }

        if ($user->role !== User::ROLE_CLIENT) {
            abort(403);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            auth()->logout();

            return redirect()->route('client.login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ]);
        }

        return $next($request);
    }
}
