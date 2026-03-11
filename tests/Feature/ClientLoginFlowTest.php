<?php

namespace Tests\Feature;

use App\Mail\ClientMagicLinkMail;
use App\Models\ClientLoginToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_magic_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('client.login.magic.send'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('client.login.email.options'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('client_login_tokens', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        Mail::assertSent(ClientMagicLinkMail::class, function (ClientMagicLinkMail $mail) use ($user): bool {
            return $mail->hasTo($user->email) && $mail->user->is($user);
        });
    }

    public function test_guest_email_can_request_magic_link_and_account_is_created_on_consume(): void
    {
        Mail::fake();

        $email = 'nuevo-cliente@example.com';

        $response = $this->post(route('client.login.magic.send'), [
            'email' => $email,
        ]);

        $response->assertRedirect(route('client.login.email.options'));
        $response->assertSessionHas('status');

        $token = ClientLoginToken::query()
            ->where('email', $email)
            ->first();

        $this->assertNotNull($token);
        $this->assertNull($token->user_id);

        Mail::assertSent(ClientMagicLinkMail::class, fn (ClientMagicLinkMail $mail): bool => $mail->hasTo($email));

        $rawToken = 'nuevo-token-cliente';
        $token->forceFill([
            'token_hash' => hash('sha256', $rawToken),
        ])->save();

        $consumeResponse = $this->get(route('client.login.magic', ['token' => $rawToken]));

        $consumeResponse->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_magic_link_logs_in_active_client(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $rawToken = 'demo-magic-token-123';
        $token = ClientLoginToken::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(20),
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->get(route('client.login.magic', ['token' => $rawToken]));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($token->fresh()->used_at);
    }

    public function test_magic_link_does_not_log_in_inactive_client(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_INACTIVE,
        ]);

        $rawToken = 'inactive-magic-token';
        $token = ClientLoginToken::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addMinutes(20),
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->get(route('client.login.magic', ['token' => $rawToken]));

        $response->assertRedirect(route('client.login.email'));
        $response->assertSessionHasErrors('auth');
        $this->assertGuest();
        $this->assertNotNull($token->fresh()->used_at);
    }

    public function test_password_login_rejects_non_client_users(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->from(route('client.login.password'))->post(route('client.login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('client.login.password'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_options_view_hides_password_path_for_unknown_email(): void
    {
        $this->withSession([
            'client_login.email' => 'nuevo@example.com',
        ]);

        $response = $this->get(route('client.login.email.options'));

        $response->assertOk();
        $response->assertSee('la crearemos cuando confirmes el enlace', false);
        $response->assertSee('Aún no disponible', false);
    }
}
