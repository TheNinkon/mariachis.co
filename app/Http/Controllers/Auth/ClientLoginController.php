<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ClientMagicLinkMail;
use App\Models\ClientLoginToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ClientLoginController extends Controller
{
    private const AUTH_PROVIDER_EMAIL = 'email';
    private const AUTH_PROVIDER_MAGIC_LINK = 'magic_link';
    private const LOGIN_EMAIL_SESSION_KEY = 'client_login.email';
    private const MAGIC_LINK_TTL_MINUTES = 20;
    private const MAGIC_LINK_RESEND_COOLDOWN_SECONDS = 30;
    private const MAGIC_LINK_EMAIL_MAX_ATTEMPTS = 4;
    private const MAGIC_LINK_IP_MAX_ATTEMPTS = 12;
    private const MAGIC_LINK_DECAY_SECONDS = 900;

    public function create(): View
    {
        return view('front.auth.client-login');
    }

    public function showEmailForm(Request $request): View
    {
        $request->session()->forget([
            'client_login.magic_link_sent',
            'client_login.magic_link_sent_at',
        ]);

        return view('front.auth.client-login-email', [
            'email' => old('email', (string) $request->session()->get(self::LOGIN_EMAIL_SESSION_KEY, '')),
        ]);
    }

    public function captureEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $request->session()->put(self::LOGIN_EMAIL_SESSION_KEY, Str::lower(trim((string) $validated['email'])));
        $request->session()->forget([
            'client_login.magic_link_sent',
            'client_login.magic_link_sent_at',
        ]);

        return redirect()->route('client.login.email.options');
    }

    public function showEmailOptions(Request $request): RedirectResponse|View
    {
        $email = $this->rememberedEmail($request);

        if ($email === null) {
            return redirect()->route('client.login.email');
        }

        $matchedUser = $this->findUserByEmail($email);
        $magicLinkSent = (bool) $request->session()->get('client_login.magic_link_sent', false);
        $sentAt = (int) $request->session()->get('client_login.magic_link_sent_at', 0);
        $remainingCooldownSeconds = max(
            0,
            self::MAGIC_LINK_RESEND_COOLDOWN_SECONDS - (now()->timestamp - $sentAt)
        );

        if ($matchedUser && ! $matchedUser->isClient()) {
            return redirect()
                ->route('client.login.email')
                ->withErrors([
                    'auth' => 'Este correo ya está vinculado a otro tipo de acceso.',
                ]);
        }

        if ($matchedUser && $matchedUser->status !== User::STATUS_ACTIVE) {
            return redirect()
                ->route('client.login.email')
                ->withErrors([
                    'auth' => 'Tu cuenta está desactivada. Contacta a soporte.',
                ]);
        }

        return view('front.auth.client-login-options', [
            'email' => $email,
            'canUsePassword' => $matchedUser?->isClient() === true && $this->userHasPasswordAccess($matchedUser),
            'showPasswordOption' => $matchedUser === null
                || ($matchedUser->isClient() && $matchedUser->status === User::STATUS_ACTIVE),
            'magicLinkSent' => $magicLinkSent,
            'magicLinkTtlMinutes' => self::MAGIC_LINK_TTL_MINUTES,
            'magicLinkResendCooldownSeconds' => self::MAGIC_LINK_RESEND_COOLDOWN_SECONDS,
            'remainingCooldownSeconds' => $magicLinkSent ? $remainingCooldownSeconds : 0,
        ]);
    }

    public function showPasswordForm(Request $request): RedirectResponse|View
    {
        $email = $this->rememberedEmail($request);

        if ($email === null) {
            return redirect()->route('client.login.email');
        }

        $matchedUser = $this->findUserByEmail($email);

        if ($matchedUser && (! $matchedUser->isClient() || $matchedUser->status !== User::STATUS_ACTIVE)) {
            return redirect()
                ->route('client.login.email.options')
                ->withErrors([
                    'auth' => 'Este acceso solo está disponible para clientes activos.',
                ]);
        }

        return view('front.auth.client-login-password', [
            'email' => old('email', $email),
            'canUsePassword' => $matchedUser?->isClient() === true && $this->userHasPasswordAccess($matchedUser),
        ]);
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $email = $this->validatedEmail($request);

        $request->session()->put(self::LOGIN_EMAIL_SESSION_KEY, $email);
        $this->ensureMagicLinkRateLimit($request, $email);

        $user = $this->findUserByEmail($email);

        if ($user && ! $user->isClient()) {
            throw ValidationException::withMessages([
                'auth' => 'Este correo ya está vinculado a otro tipo de acceso.',
            ]);
        }

        if ($user && $user->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'auth' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ]);
        }

        $plainToken = Str::random(64);

        ClientLoginToken::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $tempUser = $user ?? $this->makePendingClientUser($email);

        ClientLoginToken::create([
            'user_id' => $user?->id,
            'email' => $email,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(self::MAGIC_LINK_TTL_MINUTES),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
        ]);

        try {
            Mail::to($email, $tempUser->display_name)->send(
                new ClientMagicLinkMail(
                    $tempUser,
                    route('client.login.magic', ['token' => $plainToken]),
                    self::MAGIC_LINK_TTL_MINUTES
                )
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'auth' => 'No pudimos enviar el enlace ahora mismo. Intenta de nuevo en un momento.',
                ]);
        }

        $this->hitMagicLinkRateLimit($request, $email);

        $request->session()->put('client_login.magic_link_sent', true);
        $request->session()->put('client_login.magic_link_sent_at', now()->timestamp);

        return redirect()
            ->route('client.login.email.options')
            ->with('status', 'Revisa '.$email.'. Te enviamos un enlace seguro para entrar o crear tu acceso.');
    }

    public function consumeMagicLink(Request $request, string $token): RedirectResponse
    {
        $loginToken = ClientLoginToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $loginToken || $loginToken->hasBeenUsed() || $loginToken->hasExpired()) {
            return redirect()
                ->route('client.login.email')
                ->withErrors([
                    'auth' => 'Este enlace ya no es válido. Solicita uno nuevo para continuar.',
                ]);
        }

        $loginToken->markAsUsed();

        $user = $loginToken->user
            ?? $this->findUserByEmail($loginToken->email);

        if (! $user) {
            $user = $this->createClientFromEmail($loginToken->email);
            $loginToken->forceFill([
                'user_id' => $user->id,
            ])->save();
        }

        if (! $this->canAccessClientPortal($user)) {
            return redirect()
                ->route('client.login.email')
                ->withErrors([
                    'auth' => 'Este acceso solo está disponible para clientes activos.',
                ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget([
            self::LOGIN_EMAIL_SESSION_KEY,
            'client_login.magic_link_sent',
            'client_login.magic_link_sent_at',
        ]);

        if ($this->needsAccountCompletion($user)) {
            return redirect()
                ->route('client.login.complete-account')
                ->with('status', 'Correo confirmado. Completa tu cuenta para dejar listo tu acceso.');
        }

        return redirect()
            ->intended(route('client.dashboard'))
            ->with('status', 'Acceso confirmado. Ya puedes continuar con tus solicitudes.');
    }

    public function showCompleteAccount(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (! $user || ! $user->isClient()) {
            return redirect()->route('client.login');
        }

        if (! $this->needsAccountCompletion($user)) {
            return redirect()->intended(route('client.dashboard'));
        }

        return view('front.auth.client-complete-account', [
            'user' => $user,
            'passwordAlreadySet' => (bool) $request->session()->get('client_onboarding.password_set', false),
        ]);
    }

    public function completeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $passwordAlreadySet = (bool) $request->session()->get('client_onboarding.password_set', false);

        if (! $user || ! $user->isClient()) {
            return redirect()->route('client.login');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->forceFill([
                'password' => $validated['password'],
                'auth_provider' => self::AUTH_PROVIDER_EMAIL,
            ]);
        }

        $user->save();

        $request->session()->forget('client_onboarding.password_set');

        return redirect()
            ->intended(route('client.dashboard'))
            ->with('status', $passwordAlreadySet
                ? 'Cuenta completada. Ya puedes empezar a usar Mariachis.co.'
                : (filled($validated['password'] ?? null)
                ? 'Cuenta completada. Ya puedes entrar con contraseña o enlace.'
                : 'Cuenta completada. Podrás seguir entrando con enlace seguro.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = Str::lower(trim((string) $credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user || ! $this->canAccessClientPortal($user, false)) {
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

        if (! $this->userHasPasswordAccess($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Primero entra con enlace seguro y, si quieres, luego define una contraseña.',
            ]);
        }

        $request->session()->forget([
            self::LOGIN_EMAIL_SESSION_KEY,
            'client_login.magic_link_sent',
            'client_login.magic_link_sent_at',
        ]);

        return redirect()->intended(route('client.dashboard'));
    }

    private function rememberedEmail(Request $request): ?string
    {
        $email = (string) $request->session()->get(self::LOGIN_EMAIL_SESSION_KEY, '');

        return $email !== '' ? $email : null;
    }

    private function validatedEmail(Request $request): string
    {
        $email = $request->input('email', $request->session()->get(self::LOGIN_EMAIL_SESSION_KEY, ''));

        return Str::lower(trim((string) validator(
            ['email' => $email],
            ['email' => ['required', 'email']]
        )->validate()['email']));
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->first();
    }

    private function canAccessClientPortal(User $user, bool $requireActive = true): bool
    {
        if (! $user->isClient()) {
            return false;
        }

        if ($requireActive && $user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }

    private function ensureMagicLinkRateLimit(Request $request, string $email): void
    {
        $emailKey = $this->magicLinkEmailKey($email);
        $ipKey = $this->magicLinkIpKey($request);

        if (RateLimiter::tooManyAttempts($emailKey, self::MAGIC_LINK_EMAIL_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($emailKey);

            throw ValidationException::withMessages([
                'auth' => 'Ya enviamos varios enlaces a este correo. Intenta de nuevo en '.$this->humanizeSeconds($seconds).'.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($ipKey, self::MAGIC_LINK_IP_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($ipKey);

            throw ValidationException::withMessages([
                'auth' => 'Has solicitado demasiados enlaces desde esta red. Intenta de nuevo en '.$this->humanizeSeconds($seconds).'.',
            ]);
        }
    }

    private function hitMagicLinkRateLimit(Request $request, string $email): void
    {
        RateLimiter::hit($this->magicLinkEmailKey($email), self::MAGIC_LINK_DECAY_SECONDS);
        RateLimiter::hit($this->magicLinkIpKey($request), self::MAGIC_LINK_DECAY_SECONDS);
    }

    private function magicLinkEmailKey(string $email): string
    {
        return 'client-login-magic:email:'.sha1($email);
    }

    private function magicLinkIpKey(Request $request): string
    {
        return 'client-login-magic:ip:'.sha1((string) $request->ip());
    }

    private function humanizeSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' segundos';
        }

        return (string) ceil($seconds / 60).' min';
    }

    private function makePendingClientUser(string $email): User
    {
        $name = $this->displayNameFromEmail($email);

        return new User([
            'name' => $name,
            'first_name' => $name,
            'email' => $email,
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => self::AUTH_PROVIDER_MAGIC_LINK,
        ]);
    }

    private function createClientFromEmail(string $email): User
    {
        $name = $this->displayNameFromEmail($email);

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(40),
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => self::AUTH_PROVIDER_MAGIC_LINK,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function displayNameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $localPart) ?: 'Cliente';

        return Str::title(trim($normalized));
    }

    private function needsAccountCompletion(User $user): bool
    {
        return trim((string) $user->first_name) === ''
            || trim((string) $user->last_name) === '';
    }

    private function userHasPasswordAccess(User $user): bool
    {
        return in_array((string) $user->auth_provider, ['', self::AUTH_PROVIDER_EMAIL], true);
    }
}
