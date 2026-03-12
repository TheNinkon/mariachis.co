<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PortalHosts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(Request $request): View
    {
        $pageConfigs = ['myLayout' => 'blank'];

        return view('content.authentications.auth-forgot-password-basic', [
            'pageConfigs' => $pageConfigs,
            'portal' => $this->portal($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
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
