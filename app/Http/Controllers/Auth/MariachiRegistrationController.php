<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MariachiWelcomeVerifyMail;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class MariachiRegistrationController extends Controller
{
    private const VERIFY_TTL_DAYS = 7;

    public function create(): View
    {
        $pageConfigs = ['myLayout' => 'blank'];

        return view('content.authentications.auth-register-basic', ['pageConfigs' => $pageConfigs]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => 'email',
        ]);

        MariachiProfile::create([
            'user_id' => $user->id,
            'city_name' => 'Pendiente',
            'profile_completed' => false,
            'profile_completion' => 10,
            'stage_status' => 'onboarding',
        ]);

        $verifyUrl = URL::temporarySignedRoute(
            'mariachi.register.verify',
            now()->addDays(self::VERIFY_TTL_DAYS),
            [
                'user' => $user->id,
                'hash' => sha1((string) $user->email),
            ]
        );

        $mailStatus = 'Cuenta creada. Revisa tu correo para verificar tu acceso y continuar con tu panel mariachi.';

        try {
            Mail::to($user->email, $user->display_name)->send(
                new MariachiWelcomeVerifyMail($user, $verifyUrl, self::VERIFY_TTL_DAYS)
            );
        } catch (Throwable $exception) {
            report($exception);

            $mailStatus = 'Cuenta creada. No pudimos enviar el correo de verificacion en este momento, pero ya puedes entrar a tu panel.';
        }

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('mariachi.provider-profile.edit')
            ->with('status', $mailStatus);
    }

    public function verifyEmail(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless($user->isMariachi(), 404);
        abort_unless(hash_equals(sha1((string) $user->email), $hash), 403);

        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('mariachi.provider-profile.edit')
            ->with('status', 'Correo verificado correctamente. Ya puedes completar tu perfil y empezar a publicar.');
    }
}
