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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ClientLoginController extends Controller
{
    private const LOGIN_EMAIL_SESSION_KEY = 'client_login.email';
    private const MAGIC_LINK_TTL_MINUTES = 20;
    private const MAGIC_LINK_EMAIL_MAX_ATTEMPTS = 4;
    private const MAGIC_LINK_IP_MAX_ATTEMPTS = 12;
    private const MAGIC_LINK_DECAY_SECONDS = 900;

    public function create(): View
    {
        return view('front.auth.client-login');
    }

    public function showEmailForm(Request $request): View
    {
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

        return redirect()->route('client.login.email.options');
    }

    public function showEmailOptions(Request $request): RedirectResponse|View
    {
        $email = $this->rememberedEmail($request);

        if ($email === null) {
            return redirect()->route('client.login.email');
        }

        return view('front.auth.client-login-options', [
            'email' => $email,
        ]);
    }

    public function showPasswordForm(Request $request): RedirectResponse|View
    {
        $email = $this->rememberedEmail($request);

        if ($email === null) {
            return redirect()->route('client.login.email');
        }

        return view('front.auth.client-login-password', [
            'email' => old('email', $email),
        ]);
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $email = $this->validatedEmail($request);

        $request->session()->put(self::LOGIN_EMAIL_SESSION_KEY, $email);
        $this->ensureMagicLinkRateLimit($request, $email);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && $this->canAccessClientPortal($user)) {
            $plainToken = Str::random(64);

            ClientLoginToken::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            ClientLoginToken::create([
                'user_id' => $user->id,
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(self::MAGIC_LINK_TTL_MINUTES),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            ]);

            try {
                Mail::to($email, $user->display_name)->send(
                    new ClientMagicLinkMail(
                        $user,
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
        }

        $this->hitMagicLinkRateLimit($request, $email);

        return redirect()
            ->route('client.login.email.options')
            ->with('status', 'Si existe una cuenta activa para '.$email.', te enviamos un enlace seguro para entrar.');
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
            ?? User::query()->whereRaw('LOWER(email) = ?', [Str::lower($loginToken->email)])->first();

        if (! $user || ! $this->canAccessClientPortal($user)) {
            return redirect()
                ->route('client.login.email')
                ->withErrors([
                    'auth' => 'Este acceso solo está disponible para clientes activos.',
                ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(self::LOGIN_EMAIL_SESSION_KEY);

        return redirect()
            ->intended(route('client.dashboard'))
            ->with('status', 'Acceso confirmado. Ya puedes continuar con tus solicitudes.');
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

        $request->session()->forget(self::LOGIN_EMAIL_SESSION_KEY);

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
}
