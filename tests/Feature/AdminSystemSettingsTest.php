<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GoogleMapsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_google_maps_configuration(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), [
                'google_maps_api_key' => 'test-browser-key',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ])
            ->assertRedirect();

        $setting = SystemSetting::query()
            ->where('key', GoogleMapsSettingsService::KEY_BROWSER_API)
            ->firstOrFail();

        $this->assertTrue($setting->is_encrypted);
        $this->assertNotSame('test-browser-key', $setting->value);

        $config = app(GoogleMapsSettingsService::class)->publicConfig();

        $this->assertSame('test-browser-key', $config['browser_api_key']);
        $this->assertSame('co', $config['places_country_restriction']);
        $this->assertSame('Colombia', $config['default_country_name']);
        $this->assertSame('CO', $config['default_country_code']);
    }

    public function test_blank_api_key_keeps_existing_secret_until_admin_requests_clear(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        app(\App\Services\SystemSettingService::class)->putString(
            GoogleMapsSettingsService::KEY_BROWSER_API,
            'existing-browser-key',
            true
        );

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), [
                'google_maps_api_key' => '',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ])
            ->assertRedirect();

        $this->assertSame(
            'existing-browser-key',
            app(GoogleMapsSettingsService::class)->publicConfig()['browser_api_key']
        );

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), [
                'google_maps_api_key' => '',
                'clear_google_maps_api_key' => '1',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ])
            ->assertRedirect();

        $this->assertSame('', app(GoogleMapsSettingsService::class)->publicConfig()['browser_api_key']);
    }
}
