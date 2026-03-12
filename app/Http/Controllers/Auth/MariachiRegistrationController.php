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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class MariachiRegistrationController extends Controller
{
    private const VERIFY_TTL_DAYS = 7;
    private const DEFAULT_PHONE_COUNTRY_ISO2 = 'CO';

    public function create(): View
    {
        $pageConfigs = ['myLayout' => 'blank'];

        return view('content.authentications.auth-register-basic', [
            'pageConfigs' => $pageConfigs,
            'phoneCountryOptions' => $this->phoneCountryOptions(),
            'defaultPhoneCountryIso2' => self::DEFAULT_PHONE_COUNTRY_ISO2,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $phoneCountryOptions = $this->phoneCountryOptions();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_country_iso2' => ['required', 'string', 'size:2', Rule::in(array_column($phoneCountryOptions, 'iso2'))],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9\s\-()]{6,20}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $phoneCountry = $this->findPhoneCountryOption($validated['phone_country_iso2'], $phoneCountryOptions);
        $formattedPhone = $this->formatPhone(
            (string) ($phoneCountry['dial_code'] ?? '+57'),
            $validated['phone_number']
        );

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $formattedPhone,
            'password' => $validated['password'],
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => 'email',
        ]);

        MariachiProfile::create([
            'user_id' => $user->id,
            'city_name' => null,
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

    /**
     * @return array<int, array{iso2:string,name:string,dial_code:string}>
     */
    private function phoneCountryOptions(): array
    {
        $options = config('phone_country_codes', []);

        return is_array($options) ? $options : [];
    }

    /**
     * @param  array<int, array{iso2:string,name:string,dial_code:string}>  $options
     * @return array{iso2:string,name:string,dial_code:string}|null
     */
    private function findPhoneCountryOption(string $iso2, array $options): ?array
    {
        foreach ($options as $option) {
            if (($option['iso2'] ?? null) === $iso2) {
                return $option;
            }
        }

        return null;
    }

    private function formatPhone(string $dialCode, string $phoneNumber): string
    {
        $normalizedDialCode = '+'.ltrim((string) preg_replace('/\D+/', '', $dialCode), '0');
        $normalizedPhoneNumber = (string) preg_replace('/\D+/', '', $phoneNumber);

        return trim($normalizedDialCode.' '.$normalizedPhoneNumber);
    }
}
