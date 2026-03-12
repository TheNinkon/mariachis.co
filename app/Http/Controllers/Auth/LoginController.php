<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PortalHosts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        $pageConfigs = ['myLayout' => 'blank'];
        $portal = $this->portal($request);

        return view('content.authentications.auth-login-basic', [
            'pageConfigs' => $pageConfigs,
            'portal' => $portal,
        ]);
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

        if ($request->user()?->status !== User::STATUS_ACTIVE) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Contacta a soporte.',
            ]);
        }

        $userRole = (string) $request->user()?->role;
        $portal = $this->portal($request);

        if ($userRole === User::ROLE_CLIENT) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Para clientes, usa el acceso público en /login.',
            ]);
        }

        if ($portal === 'mariachi' && $userRole !== User::ROLE_MARIACHI) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Este acceso es exclusivo para mariachis.',
            ]);
        }

        if ($portal === 'admin' && ! in_array($userRole, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Este acceso es exclusivo para administración y soporte.',
            ]);
        }

        return redirect()->intended($this->redirectByRole($userRole));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user?->role === User::ROLE_CLIENT) {
            return redirect()->route('home');
        }

        if ($user?->role === User::ROLE_MARIACHI) {
            return redirect()->route('mariachi.login');
        }

        return redirect()->route('login');
    }

    private function redirectByRole(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_STAFF => route('staff.dashboard'),
            User::ROLE_MARIACHI => route('mariachi.metrics'),
            User::ROLE_CLIENT => route('client.dashboard'),
            default => route('login'),
        };
    }

    private function portal(Request $request): string
    {
        $portal = (string) $request->route('portal', '');

        if ($portal !== '') {
            return $portal;
        }

        return PortalHosts::portalFromRequest($request) === PortalHosts::PORTAL_PARTNER
            ? 'mariachi'
            : 'admin';
    }
}
