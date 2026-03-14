<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SocialLoginSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSocialLoginSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_social_login_configuration(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.social-login-settings.update'), [
                'google_enabled' => '1',
                'google_client_id' => 'google-client-id',
                'google_redirect_uri' => 'http://localhost:8000/auth/google/callback',
                'facebook_enabled' => '1',
                'facebook_client_id' => 'facebook-app-id',
                'facebook_redirect_uri' => 'http://localhost:8000/auth/facebook/callback',
            ])
            ->assertRedirect();

        $config = app(SocialLoginSettingsService::class)->publicConfig();

        $this->assertTrue($config['google']['enabled']);
        $this->assertSame('google-client-id', $config['google']['client_id']);
        $this->assertSame('http://localhost:8000/auth/google/callback', $config['google']['redirect']);
        $this->assertTrue($config['facebook']['enabled']);
        $this->assertSame('facebook-app-id', $config['facebook']['client_id']);
        $this->assertSame('http://localhost:8000/auth/facebook/callback', $config['facebook']['redirect']);
    }

    public function test_admin_page_shows_callback_copy_buttons_and_no_apple_form(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.social-login-settings.edit'))
            ->assertOk()
            ->assertSee('Copiar callback URL')
            ->assertSee('Google')
            ->assertSee('Facebook')
            ->assertSee('Apple')
            ->assertDontSee('APPLE_CLIENT_ID');
    }

    public function test_disabled_provider_is_hidden_from_client_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.social-login-settings.update'), [
                'google_enabled' => '1',
                'google_client_id' => 'google-client-id',
                'google_redirect_uri' => 'http://localhost:8000/auth/google/callback',
                'facebook_enabled' => '0',
                'facebook_client_id' => 'facebook-app-id',
                'facebook_redirect_uri' => 'http://localhost:8000/auth/facebook/callback',
            ]);

        config([
            'services.google.client_secret' => 'google-secret',
            'services.facebook.client_secret' => 'facebook-secret',
        ]);

        app(SystemSettingService::class)->putString(SocialLoginSettingsService::KEY_FACEBOOK_ENABLED, '0');

        auth()->logout();

        $this->get(route('client.login'))
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertDontSee('Continuar con Facebook')
            ->assertDontSee('Continuar con Apple');
    }
}
