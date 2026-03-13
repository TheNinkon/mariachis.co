<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GoogleMapsSettingsService;
use App\Services\NequiPaymentSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            ->patch(route('admin.system-settings.update'), $this->validPayload([
                'google_maps_api_key' => 'test-browser-key',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ]))
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
            ->patch(route('admin.system-settings.update'), $this->validPayload([
                'google_maps_api_key' => '',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ]))
            ->assertRedirect();

        $this->assertSame(
            'existing-browser-key',
            app(GoogleMapsSettingsService::class)->publicConfig()['browser_api_key']
        );

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), $this->validPayload([
                'google_maps_api_key' => '',
                'clear_google_maps_api_key' => '1',
                'google_places_country_restriction' => 'co',
                'marketplace_default_country_name' => 'Colombia',
                'marketplace_default_country_code' => 'CO',
            ]))
            ->assertRedirect();

        $this->assertSame('', app(GoogleMapsSettingsService::class)->publicConfig()['browser_api_key']);
    }

    public function test_admin_can_store_nequi_configuration(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.system-settings.update'), $this->validPayload([
                'nequi_phone' => '3001234567',
                'nequi_beneficiary_name' => 'Mariachis.co',
                'nequi_qr_image' => UploadedFile::fake()->image('nequi-qr.png'),
            ]))
            ->assertRedirect();

        $config = app(\App\Services\NequiPaymentSettingsService::class)->publicConfig();

        $this->assertSame('3001234567', $config['phone']);
        $this->assertSame('Mariachis.co', $config['beneficiary_name']);
        $this->assertNotNull($config['qr_image_path']);
        Storage::disk('public')->assertExists($config['qr_image_path']);

        $this->assertDatabaseHas('system_settings', [
            'key' => NequiPaymentSettingsService::KEY_PHONE,
            'value' => '3001234567',
            'is_encrypted' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'google_maps_api_key' => '',
            'google_places_country_restriction' => 'co',
            'marketplace_default_country_name' => 'Colombia',
            'marketplace_default_country_code' => 'CO',
            'mail_mailer' => 'log',
            'mail_smtp_host' => '',
            'mail_smtp_port' => '',
            'mail_smtp_username' => '',
            'mail_smtp_password' => '',
            'mail_smtp_encryption' => 'tls',
            'mail_from_address' => 'admin@example.com',
            'mail_from_name' => 'Mariachis.co',
            'nequi_phone' => '',
            'nequi_beneficiary_name' => '',
        ], $overrides);
    }
}
