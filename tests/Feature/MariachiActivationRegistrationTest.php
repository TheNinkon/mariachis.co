<?php

namespace Tests\Feature;

use App\Models\AccountActivationPayment;
use App\Models\AccountActivationPlan;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function test_pending_activation_user_cannot_login_until_admin_approval(): void
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

    public function test_pending_user_can_submit_nequi_activation_proof(): void
    {
        Storage::fake('public');
        config([
            'payments.nequi.phone' => '3001234567',
            'payments.nequi.beneficiary_name' => 'Mariachis.co',
        ]);

        $plan = $this->ensureActivationPlan();
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'activation_token' => str_repeat('b', 64),
        ]);

        $this->post(route('mariachi.activation.payments.nequi.store', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]), [
            'proof_image' => UploadedFile::fake()->image('activation-proof.png'),
            'reference_text' => 'NEQUI-5678',
        ])->assertRedirect(route('mariachi.activation.show', [
            'user' => $user->id,
            'token' => $user->activation_token,
        ]));

        $payment = AccountActivationPayment::query()->firstOrFail();

        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame($plan->id, $payment->account_activation_plan_id);
        $this->assertSame(AccountActivationPayment::STATUS_PENDING_REVIEW, $payment->status);
        $this->assertSame($plan->amount_cop, $payment->amount_cop);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_admin_can_approve_activation_payment_and_enable_login(): void
    {
        $plan = $this->ensureActivationPlan();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_PENDING_ACTIVATION,
            'activation_token' => str_repeat('c', 64),
        ]);

        $payment = AccountActivationPayment::query()->create([
            'user_id' => $user->id,
            'account_activation_plan_id' => $plan->id,
            'amount_cop' => $plan->amount_cop,
            'method' => AccountActivationPayment::METHOD_NEQUI,
            'proof_path' => 'activation-payments/proofs/test.png',
            'status' => AccountActivationPayment::STATUS_PENDING_REVIEW,
            'reference_text' => 'NEQUI-APPROVE',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.account-activation-payments.update', $payment), [
                'action' => 'approve',
            ])
            ->assertRedirect();

        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
        $this->assertNotNull($user->fresh()->activation_paid_at);
        $this->assertSame(AccountActivationPayment::STATUS_APPROVED, $payment->fresh()->status);
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
}
