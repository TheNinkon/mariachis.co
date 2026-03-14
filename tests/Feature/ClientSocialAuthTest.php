<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use App\Services\SocialLoginSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class ClientSocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_secret' => 'google-client-secret',
            'services.facebook.client_secret' => 'facebook-client-secret',
        ]);

        $settings = app(SystemSettingService::class);
        $settings->putString(SocialLoginSettingsService::KEY_GOOGLE_ENABLED, '1');
        $settings->putString(SocialLoginSettingsService::KEY_GOOGLE_CLIENT_ID, 'google-client-id');
        $settings->putString(SocialLoginSettingsService::KEY_GOOGLE_REDIRECT_URI, 'http://localhost:8000/auth/google/callback');
        $settings->putString(SocialLoginSettingsService::KEY_FACEBOOK_ENABLED, '1');
        $settings->putString(SocialLoginSettingsService::KEY_FACEBOOK_CLIENT_ID, 'facebook-client-id');
        $settings->putString(SocialLoginSettingsService::KEY_FACEBOOK_REDIRECT_URI, 'http://localhost:8000/auth/facebook/callback');
    }

    public function test_login_view_only_shows_providers_enabled_in_admin(): void
    {
        app(SystemSettingService::class)->putString(SocialLoginSettingsService::KEY_FACEBOOK_ENABLED, '0');

        $this->get(route('client.login'))
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertDontSee('Continuar con Facebook')
            ->assertDontSee('Continuar con Apple');
    }

    public function test_google_redirect_route_sends_guest_to_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('client.social.redirect', ['provider' => 'google']))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_callback_logs_in_existing_client_by_email_without_creating_duplicate(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente.social@example.com',
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
            'auth_provider' => 'email',
            'auth_provider_id' => null,
        ]);

        $providerUser = $this->makeProviderUser(
            id: 'google-123',
            email: $user->email,
            name: 'Cliente Social'
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($providerUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('client.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', $user->email)->count());
        $this->assertSame('email', $user->fresh()->auth_provider);
    }

    public function test_callback_creates_active_client_and_profile_when_user_does_not_exist(): void
    {
        $providerUser = $this->makeProviderUser(
            id: 'facebook-123',
            email: 'nuevo.social@example.com',
            name: 'Nuevo Cliente'
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($providerUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('facebook')
            ->andReturn($provider);

        $this->get(route('client.social.callback', ['provider' => 'facebook']))
            ->assertRedirect(route('client.dashboard'));

        $user = User::query()->where('email', 'nuevo.social@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_CLIENT, $user->role);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertSame('facebook', $user->auth_provider);
        $this->assertSame('facebook-123', $user->auth_provider_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_callback_returns_error_when_provider_does_not_supply_email_for_unknown_user(): void
    {
        $providerUser = $this->makeProviderUser(
            id: 'facebook-456',
            email: null,
            name: 'Sin Correo'
        );

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($providerUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('facebook')
            ->andReturn($provider);

        $this->from(route('client.login'))
            ->get(route('client.social.callback', ['provider' => 'facebook']))
            ->assertRedirect(route('client.login'))
            ->assertSessionHasErrors([
                'auth' => 'No pudimos obtener tu email, usa login por correo.',
            ]);

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_disabled_provider_route_returns_to_login(): void
    {
        app(SystemSettingService::class)->putString(SocialLoginSettingsService::KEY_FACEBOOK_ENABLED, '0');

        $this->from(route('client.login'))
            ->get(route('client.social.redirect', ['provider' => 'facebook']))
            ->assertRedirect(route('client.login'))
            ->assertSessionHasErrors('auth');
    }

    private function makeProviderUser(string $id, ?string $email, string $name): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->map([
            'id' => $id,
            'nickname' => null,
            'name' => $name,
            'email' => $email,
            'avatar' => null,
        ]);
        $user->user = array_filter([
            'sub' => $id,
            'email' => $email,
            'name' => $name,
            'given_name' => explode(' ', $name)[0] ?? $name,
            'family_name' => trim(str_replace(explode(' ', $name)[0] ?? $name, '', $name)),
        ], static fn ($value): bool => $value !== null);

        return $user;
    }
}
