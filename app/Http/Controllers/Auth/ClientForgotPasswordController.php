<?php

namespace App\Http\Controllers\Auth;

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT
            ? redirect()
                ->route('client.password.request')
                ->with('client_password.email', $email)
                ->with('client_password.link_sent', true)
            : back()->withInput()->withErrors(['email' => __($status)]);
    }
}
