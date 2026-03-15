<?php

namespace Tests\Feature;

use App\Models\AccountActivationPayment;
use App\Models\AccountActivationPlan;
use App\Models\MariachiProfile;
use App\Models\ProfileVerificationPayment;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMariachiManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_mariachis_index_show_and_edit_pages(): void
    {
        $admin = $this->createAdmin();
        $mariachi = $this->createMariachi();

        $this->actingAs($admin)
            ->get(route('admin.mariachis.index'))
            ->assertOk()
            ->assertSee('datatables-users', false);

        $this->actingAs($admin)
            ->get(route('admin.mariachis.show', $mariachi))
            ->assertOk()
            ->assertSee('datatable-listings', false)
            ->assertSee('Pagos del perfil', false);

        $this->actingAs($admin)
            ->get(route('admin.mariachis.edit', $mariachi))
            ->assertOk()
            ->assertSee('Guardar cambios', false);
    }

    public function test_admin_mariachi_profile_shows_activation_and_verification_payments(): void
    {
        $admin = $this->createAdmin();
        $mariachi = $this->createMariachi([
            'email' => 'perfil.pagos@example.com',
            'status' => User::STATUS_PENDING_ACTIVATION,
        ]);
        $profile = $mariachi->mariachiProfile;
        $activationPlan = AccountActivationPlan::query()->create([
            'code' => 'ACTIVACION_TEST',
            'name' => 'Activacion partner',
            'billing_type' => 'one_time',
            'amount_cop' => 18900,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        AccountActivationPayment::query()->create([
            'user_id' => $mariachi->id,
            'account_activation_plan_id' => $activationPlan->id,
            'amount_cop' => 18900,
            'method' => AccountActivationPayment::METHOD_WOMPI,
            'checkout_reference' => 'ACT-ADMIN-001',
            'provider_transaction_id' => 'wompi-activation-001',
            'provider_transaction_status' => 'APPROVED',
            'status' => AccountActivationPayment::STATUS_APPROVED,
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $verificationPayment = ProfileVerificationPayment::query()->create([
            'mariachi_profile_id' => $profile->id,
            'plan_code' => 'verified',
            'duration_months' => 1,
            'amount_cop' => 29900,
            'method' => ProfileVerificationPayment::METHOD_WOMPI,
            'checkout_reference' => 'VER-ADMIN-001',
            'provider_transaction_id' => 'wompi-verification-001',
            'provider_transaction_status' => 'PENDING',
            'proof_path' => null,
            'status' => ProfileVerificationPayment::STATUS_PENDING,
        ]);

        VerificationRequest::query()->create([
            'mariachi_profile_id' => $profile->id,
            'profile_verification_payment_id' => $verificationPayment->id,
            'status' => VerificationRequest::STATUS_PENDING,
            'id_document_path' => 'verification/id-document.png',
            'identity_proof_path' => 'verification/identity-proof.png',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mariachis.show', $mariachi))
            ->assertOk()
            ->assertSee('Pagos del perfil', false)
            ->assertSee('Activacion de cuenta', false)
            ->assertSee('Verificacion de perfil', false)
            ->assertSee('ACT-ADMIN-001', false)
            ->assertSee('VER-ADMIN-001', false)
            ->assertSee('Solicitud pendiente', false);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMariachi(array $attributes = []): User
    {
        $mariachi = User::factory()->create(array_merge([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ], $attributes));

        MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'business_name' => 'Mariachi Perfil Admin',
            'city_name' => 'Bogota',
            'profile_completed' => true,
            'profile_completion' => 82,
            'stage_status' => 'provider_ready',
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        return $mariachi->fresh(['mariachiProfile']);
    }
}
