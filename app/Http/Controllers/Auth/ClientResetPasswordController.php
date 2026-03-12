<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ClientResetPasswordController extends Controller
{
    public function create(Request $request): View
    {
        $email = (string) $request->email;
        $user = $email !== ''
            ? User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first()
            : null;
        $isAccountCreationFlow = $user instanceof User
            && $user->isClient()
            && (
                trim((string) $user->first_name) === ''
                || trim((string) $user->last_name) === ''
                || $user->email_verified_at === null
            );

        return view('front.auth.client-reset-password', [
            'request' => $request,
            'isAccountCreationFlow' => $isAccountCreationFlow,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', PasswordRule::defaults()],
        ]);

        $resetUser = null;
        $requiresCompletion = false;

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) use (&$resetUser, &$requiresCompletion): void {
                $requiresCompletion = trim((string) $user->first_name) === ''
                    || trim((string) $user->last_name) === '';

                $user->forceFill([
                    'password' => $password,
                    'auth_provider' => 'email',
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => [__($status)]]);
        }

        if (! $resetUser instanceof User || ! $resetUser->isClient()) {
            return redirect()->route('client.login')->with('status', __($status));
        }

        Auth::login($resetUser, true);
        $request->session()->regenerate();

        if ($requiresCompletion) {
            $request->session()->put('client_onboarding.password_set', true);

            return redirect()
                ->route('client.login.complete-account')
                ->with('status', 'Contraseña creada. Ahora completa tu nombre para dejar lista tu cuenta.');
        }

        $request->session()->forget('client_onboarding.password_set');

        return redirect()
            ->intended(route('client.dashboard'))
            ->with('status', 'Contraseña actualizada. Ya puedes continuar.');
    }
}
