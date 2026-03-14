<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SocialLoginSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialLoginSettingsController extends Controller
{
    public function __construct(
        private readonly SocialLoginSettingsService $socialLoginSettings,
        private readonly SystemSettingService $settings
    ) {
    }

    public function edit(): View
    {
        return view('content.admin.social-login-settings', [
            'providers' => $this->socialLoginSettings->publicConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_enabled' => ['nullable', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:2048'],
            'google_redirect_uri' => ['nullable', 'url', 'max:2048'],
            'facebook_enabled' => ['nullable', 'boolean'],
            'facebook_client_id' => ['nullable', 'string', 'max:2048'],
            'facebook_redirect_uri' => ['nullable', 'url', 'max:2048'],
        ]);

        $this->settings->putString(
            SocialLoginSettingsService::KEY_GOOGLE_ENABLED,
            $request->boolean('google_enabled') ? '1' : '0'
        );
        $this->settings->putString(
            SocialLoginSettingsService::KEY_GOOGLE_CLIENT_ID,
            $validated['google_client_id'] ?? null
        );
        $this->settings->putString(
            SocialLoginSettingsService::KEY_GOOGLE_REDIRECT_URI,
            $validated['google_redirect_uri'] ?? null
        );

        $this->settings->putString(
            SocialLoginSettingsService::KEY_FACEBOOK_ENABLED,
            $request->boolean('facebook_enabled') ? '1' : '0'
        );
        $this->settings->putString(
            SocialLoginSettingsService::KEY_FACEBOOK_CLIENT_ID,
            $validated['facebook_client_id'] ?? null
        );
        $this->settings->putString(
            SocialLoginSettingsService::KEY_FACEBOOK_REDIRECT_URI,
            $validated['facebook_redirect_uri'] ?? null
        );

        return back()->with('status', 'Social Login actualizado.');
    }
}
