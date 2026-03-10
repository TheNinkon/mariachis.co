<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly GoogleMapsSettingsService $googleMapsSettings
    ) {
    }

    public function edit(): View
    {
        return view('content.admin.system-settings', [
            'googleMaps' => $this->googleMapsSettings->publicConfig(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_maps_api_key' => ['nullable', 'string', 'max:2048'],
            'clear_google_maps_api_key' => ['nullable', 'boolean'],
            'google_places_country_restriction' => ['required', 'string', 'size:2'],
            'marketplace_default_country_name' => ['required', 'string', 'max:120'],
            'marketplace_default_country_code' => ['required', 'string', 'size:2'],
        ]);

        if ($request->boolean('clear_google_maps_api_key')) {
            $this->settings->putString(GoogleMapsSettingsService::KEY_BROWSER_API, null, true);
        } elseif (filled($validated['google_maps_api_key'] ?? null)) {
            $this->settings->putString(
                GoogleMapsSettingsService::KEY_BROWSER_API,
                $validated['google_maps_api_key'],
                true
            );
        }

        $this->settings->putString(
            GoogleMapsSettingsService::KEY_COUNTRY_RESTRICTION,
            strtolower($validated['google_places_country_restriction'])
        );
        $this->settings->putString(
            GoogleMapsSettingsService::KEY_DEFAULT_COUNTRY_NAME,
            $validated['marketplace_default_country_name']
        );
        $this->settings->putString(
            GoogleMapsSettingsService::KEY_DEFAULT_COUNTRY_CODE,
            strtoupper($validated['marketplace_default_country_code'])
        );

        return back()->with('status', 'Configuracion de Google Maps actualizada.');
    }
}
