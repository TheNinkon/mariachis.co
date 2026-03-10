<?php

namespace App\Services;

class GoogleMapsSettingsService
{
    public const KEY_BROWSER_API = 'google_maps_api_key';
    public const KEY_COUNTRY_RESTRICTION = 'google_places_country_restriction';
    public const KEY_DEFAULT_COUNTRY_NAME = 'marketplace_default_country_name';
    public const KEY_DEFAULT_COUNTRY_CODE = 'marketplace_default_country_code';

    public function __construct(private readonly SystemSettingService $settings)
    {
    }

    /**
     * @return array{
     *     browser_api_key:string,
     *     places_country_restriction:string,
     *     default_country_name:string,
     *     default_country_code:string,
     *     enabled:bool
     * }
     */
    public function publicConfig(): array
    {
        $browserApiKey = trim((string) $this->settings->getString(
            self::KEY_BROWSER_API,
            (string) config('services.google.maps_api_key', '')
        ));
        $countryRestriction = strtolower(trim((string) $this->settings->getString(
            self::KEY_COUNTRY_RESTRICTION,
            (string) config('location.google_places_country_restriction', 'co')
        )));
        $defaultCountryName = trim((string) $this->settings->getString(
            self::KEY_DEFAULT_COUNTRY_NAME,
            (string) config('location.default_country_name', 'Colombia')
        ));
        $defaultCountryCode = strtoupper(trim((string) $this->settings->getString(
            self::KEY_DEFAULT_COUNTRY_CODE,
            (string) config('location.default_country_code', 'CO')
        )));

        return [
            'browser_api_key' => $browserApiKey,
            'places_country_restriction' => $countryRestriction !== '' ? $countryRestriction : 'co',
            'default_country_name' => $defaultCountryName !== '' ? $defaultCountryName : 'Colombia',
            'default_country_code' => $defaultCountryCode !== '' ? $defaultCountryCode : 'CO',
            'enabled' => $browserApiKey !== '',
        ];
    }
}
