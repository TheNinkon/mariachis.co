<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientLoginController extends Controller
{
    public function create(): View
    {
        return view('front.auth.client-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_CLIENT) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Este acceso es solo para clientes registrados.',
            ]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ]);
        }

        return redirect()->intended(route('client.dashboard'));
    }
}
