<?php

namespace Tests\Feature;

use App\Models\AccountActivationPayment;
use App\Models\AccountActivationPlan;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MariachiActivationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_pending_activation_user_and_redirects_to_activation_step(): void
    {
        $plan = $this->ensureActivationPlan();

        $response = $this->post(route('mariachi.register.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Macias',
            'email' => 'juan.activation@example.com',
            'phone_country_iso2' => 'CO',
            'phone_number' => '3001234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $user = User::query()->where('email', 'juan.activation@example.com')->firstOrFail();

        $response->assertRedirect(route('mariachi.activation.show', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]));

        $this->assertGuest();
        $this->assertSame(User::STATUS_PENDING_ACTIVATION, $user->status);
        $this->assertNotNull($user->activation_token);
        $this->assertNull($user->activation_paid_at);
        $this->assertSame($plan->amount_cop, AccountActivationPlan::query()->active()->firstOrFail()->amount_cop);

        $profile = MariachiProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Juan Macias', $profile->business_name);
        $this->assertMatchesRegularExpression('/^m-[a-z0-9]{8}$/', (string) $profile->slug);
    }

    public function test_pending_activation_user_cannot_login_until_payment_is_approved(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'password' => Hash::make('Password123!'),
            'activation_token' => str_repeat('a', 64),
        ]);

        $this->from(route('mariachi.login'))
            ->post(route('mariachi.login.attempt'), [
                'email' => $user->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('mariachi.login'))
            ->assertSessionHasErrors([
                'email' => 'Tu cuenta requiere activacion (pago pendiente).',
            ]);

        $this->assertGuest();
    }

    public function test_pending_user_can_start_wompi_activation_checkout(): void
    {
        $this->configureWompi();

        $plan = $this->ensureActivationPlan();
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'activation_token' => str_repeat('b', 64),
            'email' => 'activation@example.com',
            'phone' => '+57 3001234567',
        ]);

        $response = $this->post(route('mariachi.activation.payments.wompi.checkout', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]));

        $response->assertStatus(302);

        $payment = AccountActivationPayment::query()->firstOrFail();

        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame($plan->id, $payment->account_activation_plan_id);
        $this->assertSame($plan->amount_cop, $payment->amount_cop);
        $this->assertSame(AccountActivationPayment::METHOD_WOMPI, $payment->method);
        $this->assertSame(AccountActivationPayment::STATUS_PENDING_REVIEW, $payment->status);
        $this->assertNotEmpty($payment->checkout_reference);

        $this->assertWompiCheckoutRedirect($response->headers->get('Location'), [
            'amount-in-cents' => (string) ($plan->amount_cop * 100),
            'reference' => $payment->checkout_reference,
        ]);
    }

    public function test_wompi_webhook_approves_activation_payment_and_enables_login(): void
    {
        $this->configureWompi();

        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'activation_token' => str_repeat('c', 64),
            'email' => 'approval@example.com',
        ]);

        $this->ensureActivationPlan();

        $this->post(route('mariachi.activation.payments.wompi.checkout', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]))->assertStatus(302);

        $payment = AccountActivationPayment::query()->firstOrFail();

        $payload = $this->wompiEventPayload([
            'id' => 'wompi-tx-activation-1',
            'status' => 'APPROVED',
            'reference' => $payment->checkout_reference,
            'amount_in_cents' => $payment->amount_cop * 100,
            'currency' => 'COP',
            'status_message' => 'Payment approved',
        ]);

        $this->postJson(route('mariachi.wompi.webhook'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $payment->refresh();
        $user->refresh();

        $this->assertSame(AccountActivationPayment::STATUS_APPROVED, $payment->status);
        $this->assertSame('wompi-tx-activation-1', $payment->provider_transaction_id);
        $this->assertSame('APPROVED', $payment->provider_transaction_status);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->activation_paid_at);
    }

    public function test_admin_can_manage_activation_plan_from_packages_section(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $plan = $this->ensureActivationPlan();

        $this->actingAs($admin)
            ->put(route('admin.account-activation-plans.update', $plan), [
                'code' => $plan->code,
                'name' => 'Activacion partner',
                'billing_type' => 'one_time',
                'amount_cop' => 19900,
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.account-activation-plans.index'))
            ->assertSessionHas('status');

        $this->assertSame('Activacion partner', $plan->fresh()->name);
        $this->assertSame(19900, $plan->fresh()->amount_cop);
    }

    private function ensureActivationPlan(): AccountActivationPlan
    {
        return AccountActivationPlan::query()->updateOrCreate(
            ['code' => 'ACTIVACION_CUENTA'],
            [
                'name' => 'Activacion de cuenta',
                'billing_type' => AccountActivationPlan::BILLING_TYPE_ONE_TIME,
                'amount_cop' => 18900,
                'is_active' => true,
                'sort_order' => 10,
            ]
        );
    }

    private function configureWompi(): void
    {
        config([
            'payments.wompi.environment' => 'sandbox',
            'payments.wompi.public_key' => 'pub_test_checkout_activation',
            'payments.wompi.integrity_secret' => 'test_integrity_secret',
            'payments.wompi.events_secret' => 'test_events_secret',
            'payments.wompi.currency' => 'COP',
            'payments.wompi.checkout_url' => 'https://checkout.wompi.co/p/',
            'payments.wompi.sandbox_api_base_url' => 'https://sandbox.wompi.co/v1',
        ]);
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function wompiEventPayload(array $transaction): array
    {
        $timestamp = '2026-03-14T12:00:00.000Z';
        $properties = [
            'transaction.id',
            'transaction.status',
            'transaction.reference',
            'transaction.amount_in_cents',
        ];

        $concatenated = collect($properties)
            ->map(fn (string $property): string => (string) data_get(['transaction' => $transaction], $property))
            ->implode('');

        return [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => $transaction,
            ],
            'signature' => [
                'properties' => $properties,
                'checksum' => hash('sha256', $concatenated.$timestamp.config('payments.wompi.events_secret')),
            ],
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @param  array<string, string>  $expected
     */
    private function assertWompiCheckoutRedirect(?string $location, array $expected): void
    {
        $this->assertNotNull($location);
        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $location);

        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('pub_test_checkout_activation', $query['public-key'] ?? null);
        $this->assertSame('COP', $query['currency'] ?? null);
        $this->assertArrayHasKey('signature:integrity', $query);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $query[$key] ?? null);
        }
    }
}
