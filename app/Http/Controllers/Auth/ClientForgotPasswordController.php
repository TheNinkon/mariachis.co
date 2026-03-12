<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientForgotPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('front.auth.client-forgot-password', [
            'email' => old('email', (string) session('client_password.email', (string) $request->query('email', ''))),
            'linkSent' => (bool) session('client_password.link_sent', false),
            'intent' => (string) session('client_password.intent', 'reset'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && ! $user->isClient()) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Este correo ya está vinculado a otro tipo de acceso.',
                ]);
        }

        if ($user && $user->status !== User::STATUS_ACTIVE) {
            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
                ]);
        }

        $createdNow = false;

        if (! $user) {
            $user = $this->createClientAccount($email);
            $createdNow = true;
        }

        $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT
            ? redirect()
                ->route('client.password.request')
                ->with('client_password.email', $email)
                ->with('client_password.link_sent', true)
                ->with('client_password.intent', $createdNow ? 'create' : 'reset')
            : back()->withInput()->withErrors([
                'email' => $status === Password::INVALID_USER
                    ? 'No pudimos preparar tu acceso con este correo.'
                    : __($status),
            ]);
    }

    private function createClientAccount(string $email): User
    {
        $name = $this->displayNameFromEmail($email);

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(40),
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => 'email',
        ]);
    }

    private function displayNameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $localPart) ?: 'Cliente';

        return Str::title(trim($normalized));
    }
}
