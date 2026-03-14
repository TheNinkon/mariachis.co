<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MariachiWelcomeVerifyMail;
use App\Models\AccountActivationPayment;
use App\Models\MariachiProfile;
use App\Models\User;
use App\Services\AccountActivationCatalogService;
use App\Services\WompiPaymentFlowService;
use App\Services\WompiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class MariachiRegistrationController extends Controller
{
    private const DEFAULT_PHONE_COUNTRY_ISO2 = 'CO';

    public function __construct(
        private readonly AccountActivationCatalogService $activationCatalog,
        private readonly WompiPaymentFlowService $paymentFlows,
        private readonly WompiService $wompi
    ) {
    }

    public function create(): View
    {
        $pageConfigs = ['myLayout' => 'blank'];

        return view('content.authentications.auth-register-basic', [
            'pageConfigs' => $pageConfigs,
            'phoneCountryOptions' => $this->phoneCountryOptions(),
            'defaultPhoneCountryIso2' => self::DEFAULT_PHONE_COUNTRY_ISO2,
            'step' => 'register',
            'activationPlan' => $this->activationCatalog->activePlan(),
            'activationUser' => null,
            'activationPayment' => null,
            'wompi' => $this->wompi->publicConfig(),
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
        $activationPlan = $this->activationCatalog->activePlan();

        if (! $activationPlan) {
            return back()
                ->withInput()
                ->withErrors([
                    'register' => 'No hay un paquete inicial de activacion disponible en este momento. Intenta mas tarde.',
                ]);
        }

        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $formattedPhone,
            'password' => $validated['password'],
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'activation_token' => Str::random(64),
            'auth_provider' => 'email',
        ]);

        $profile = MariachiProfile::create([
            'user_id' => $user->id,
            'business_name' => $user->display_name,
            'city_name' => null,
            'profile_completed' => false,
            'profile_completion' => 10,
            'stage_status' => 'onboarding',
        ]);
        $profile->ensureSlug();

        $activationUrl = route('mariachi.activation.show', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]);

        $mailStatus = 'Cuenta creada. Ahora envia el pago de activacion para que el admin habilite tu acceso.';

        try {
            Mail::to($user->email, $user->display_name)->send(
                new MariachiWelcomeVerifyMail($user, $activationUrl, 7)
            );
        } catch (Throwable $exception) {
            report($exception);

            $mailStatus = 'Cuenta creada. No pudimos enviar el correo en este momento, pero ya puedes continuar con la activacion desde esta pantalla.';
        }

        return redirect()
            ->route('mariachi.activation.show', ['user' => $user->id, 'token' => $user->activation_token])
            ->with('status', $mailStatus);
    }

    public function activation(User $user, string $token): View|RedirectResponse
    {
        if (! $this->isValidActivationUser($user, $token)) {
            abort(404);
        }

        $pageConfigs = ['myLayout' => 'blank'];
        $activationPlan = $this->activationCatalog->activePlan();
        $activationPayment = $user->activationPayments()->with('plan')->latest('id')->first();

        return view('content.authentications.auth-register-basic', [
            'pageConfigs' => $pageConfigs,
            'phoneCountryOptions' => $this->phoneCountryOptions(),
            'defaultPhoneCountryIso2' => self::DEFAULT_PHONE_COUNTRY_ISO2,
            'step' => 'activation',
            'activationPlan' => $activationPlan,
            'activationUser' => $user,
            'activationToken' => $token,
            'activationPayment' => $activationPayment,
            'wompi' => $this->wompi->publicConfig(),
        ]);
    }

    public function startActivationCheckout(Request $request, User $user, string $token): RedirectResponse
    {
        if (! $this->isValidActivationUser($user, $token)) {
            abort(404);
        }

        if ($user->status === User::STATUS_ACTIVE) {
            return back()->with('status', 'Tu cuenta ya esta activa. Ya puedes iniciar sesion.');
        }

        $activationPlan = $this->activationCatalog->activePlan();

        if (! $activationPlan) {
            return back()->withErrors([
                'activation' => 'No hay un paquete de activacion disponible en este momento.',
            ]);
        }

        if (! $this->paymentFlows->isConfigured()) {
            return back()->withErrors([
                'activation' => 'Wompi no esta configurado en este momento. Intenta mas tarde o contacta a soporte.',
            ]);
        }

        return redirect()->away(
            $this->paymentFlows->beginActivationCheckout($user, $activationPlan)
        );
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

        if ($user->requiresActivation() && filled($user->activation_token)) {
            return redirect()
                ->route('mariachi.activation.show', ['user' => $user->id, 'token' => $user->activation_token])
                ->with('status', 'Correo verificado. Ahora envia el pago de activacion para habilitar tu cuenta.');
        }

        return redirect()
            ->route('mariachi.login')
            ->with('status', 'Correo verificado correctamente. Ya puedes iniciar sesion.');
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

    private function isValidActivationUser(User $user, string $token): bool
    {
        return $user->isMariachi()
            && filled($user->activation_token)
            && hash_equals((string) $user->activation_token, $token);
    }
}
