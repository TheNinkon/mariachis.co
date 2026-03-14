<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use App\Services\SocialLoginSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDER_GOOGLE = 'google';
    private const PROVIDER_FACEBOOK = 'facebook';
    private const AUTH_PROVIDER_EMAIL = 'email';

    /**
     * @var list<string>
     */
    private const SUPPORTED_PROVIDERS = [
        self::PROVIDER_GOOGLE,
        self::PROVIDER_FACEBOOK,
    ];

    public function __construct(private readonly SocialLoginSettingsService $socialLoginSettings)
    {
    }

    public function redirect(string $provider): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);

        if (! $this->providerIsConfigured($provider)) {
            return redirect()
                ->route('client.login')
                ->withErrors([
                    'auth' => 'Este acceso social no está disponible ahora.',
                ]);
        }

        return $this->driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $provider = $this->normalizeProvider($provider);

        if (! $this->providerIsConfigured($provider)) {
            return redirect()
                ->route('client.login')
                ->withErrors([
                    'auth' => 'Este acceso social no está disponible ahora.',
                ]);
        }

        try {
            $providerUser = $this->driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('client.login')
                ->withErrors([
                    'auth' => 'No pudimos completar el acceso con '.$this->providerLabel($provider).'. Intenta de nuevo.',
                ]);
        }

        $providerId = trim((string) $providerUser->getId());
        $email = $this->normalizedEmail($providerUser->getEmail());

        if ($providerId === '') {
            return redirect()
                ->route('client.login')
                ->withErrors([
                    'auth' => 'No recibimos un identificador válido desde '.$this->providerLabel($provider).'.',
                ]);
        }

        $user = $this->findUserByProvider($provider, $providerId);

        if (! $user && $email !== null) {
            $user = $this->findUserByEmail($email);
        }

        if (! $user && $email === null) {
            return redirect()
                ->route('client.login')
                ->withErrors([
                    'auth' => 'No pudimos obtener tu email, usa login por correo.',
                ]);
        }

        if ($user) {
            if (! $user->isClient()) {
                return redirect()
                    ->route('client.login')
                    ->withErrors([
                        'auth' => 'Este correo ya está vinculado a otro tipo de acceso.',
                    ]);
            }

            if ($user->status !== User::STATUS_ACTIVE) {
                return redirect()
                    ->route('client.login')
                    ->withErrors([
                        'auth' => 'Tu cuenta está desactivada. Contacta a soporte.',
                    ]);
            }

            $this->syncExistingClient($user, $provider, $providerId, $providerUser, $email);
        } else {
            $user = $this->createClientFromProvider($provider, $providerId, $providerUser, $email);
        }

        ClientProfile::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($this->needsAccountCompletion($user)) {
            return redirect()
                ->route('client.login.complete-account')
                ->with('status', 'Acceso confirmado. Completa tu cuenta para dejar listo tu acceso.');
        }

        return redirect()
            ->intended(route('client.dashboard'))
            ->with('status', 'Acceso confirmado. Ya puedes continuar con tus solicitudes.');
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = Str::lower(trim($provider));

        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        return $provider;
    }

    private function driver(string $provider): mixed
    {
        $config = $this->socialLoginSettings->providerConfig($provider);

        config([
            "services.{$provider}.client_id" => $config['client_id'],
            "services.{$provider}.redirect" => $config['redirect'],
        ]);

        return Socialite::driver($provider);
    }

    private function providerIsConfigured(string $provider): bool
    {
        return $this->socialLoginSettings->isReady($provider);
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            self::PROVIDER_GOOGLE => 'Google',
            self::PROVIDER_FACEBOOK => 'Facebook',
            default => Str::title($provider),
        };
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    private function findUserByProvider(string $provider, string $providerId): ?User
    {
        return User::query()
            ->where('auth_provider', $provider)
            ->where('auth_provider_id', $providerId)
            ->first();
    }

    private function syncExistingClient(User $user, string $provider, string $providerId, ProviderUser $providerUser, ?string $email): void
    {
        $name = $this->resolveNameData($providerUser, $email);

        if ($user->auth_provider !== self::AUTH_PROVIDER_EMAIL) {
            $user->forceFill([
                'auth_provider' => $provider,
                'auth_provider_id' => $providerId,
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ]);
        }

        if ($email !== null && blank($user->email)) {
            $user->email = $email;
        }

        if (blank($user->first_name) && $name['first_name'] !== '') {
            $user->first_name = $name['first_name'];
        }

        if (blank($user->last_name) && $name['last_name'] !== '') {
            $user->last_name = $name['last_name'];
        }

        if (blank($user->name) && $name['name'] !== '') {
            $user->name = $name['name'];
        }

        $user->save();
    }

    private function createClientFromProvider(string $provider, string $providerId, ProviderUser $providerUser, string $email): User
    {
        $name = $this->resolveNameData($providerUser, $email);

        $user = new User([
            'name' => $name['name'],
            'first_name' => $name['first_name'],
            'last_name' => $name['last_name'],
            'email' => $email,
            'password' => Str::random(40),
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => $provider,
            'auth_provider_id' => $providerId,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * @return array{name:string, first_name:string, last_name:string}
     */
    private function resolveNameData(ProviderUser $providerUser, ?string $email): array
    {
        $raw = is_array($providerUser->user ?? null) ? $providerUser->user : [];
        $fallbackName = trim((string) ($providerUser->getName() ?? ''));

        $firstName = trim((string) ($raw['given_name'] ?? $raw['first_name'] ?? ''));
        $lastName = trim((string) ($raw['family_name'] ?? $raw['last_name'] ?? ''));

        if ($firstName === '' && $fallbackName !== '') {
            $parts = preg_split('/\s+/', $fallbackName) ?: [];
            $firstName = trim((string) array_shift($parts));
            $lastName = trim(implode(' ', $parts));
        }

        if ($firstName === '' && $email !== null) {
            $firstName = $this->displayNameFromEmail($email);
        }

        $name = trim($fallbackName !== '' ? $fallbackName : ($firstName.' '.$lastName));

        if ($name === '') {
            $name = $firstName !== '' ? $firstName : 'Cliente';
        }

        return [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    private function displayNameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $localPart) ?: 'Cliente';

        return Str::title(trim($normalized));
    }

    private function normalizedEmail(?string $email): ?string
    {
        $normalized = Str::lower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    private function needsAccountCompletion(User $user): bool
    {
        return trim((string) $user->first_name) === ''
            || trim((string) $user->last_name) === '';
    }
}
