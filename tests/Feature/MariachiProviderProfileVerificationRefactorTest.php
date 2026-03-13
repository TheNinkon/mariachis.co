<?php

namespace Tests\Feature;

use App\Models\MariachiProfile;
use App\Models\ProfileVerificationPayment;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MariachiProviderProfileVerificationRefactorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_partner_profile_view_shows_clean_fields_and_account_tabs(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->createProfile($mariachi);

        $this->actingAs($mariachi)
            ->get(route('mariachi.provider-profile.edit'))
            ->assertOk()
            ->assertSee('Nombre del grupo / marca')
            ->assertSee('Facturación y planes')
            ->assertSee('Seguridad')
            ->assertDontSee('Ciudad principal')
            ->assertDontSee('Nombre del responsable');
    }

    public function test_verification_purchase_creates_payment_and_request_without_plan_gating(): void
    {
        Storage::fake('public');
        config([
            'payments.nequi.phone' => '3001234567',
            'payments.nequi.beneficiary_name' => 'Mariachis.co',
        ]);

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi, [
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
        ]);

        $this->actingAs($mariachi)
            ->from(route('mariachi.verification.edit'))
            ->post(route('mariachi.verification.store'), [
                'plan_code' => 'verification-1m',
                'reference_text' => 'NEQUI-1234',
                'notes' => 'Somos la misma agrupacion del logo.',
                'proof_image' => UploadedFile::fake()->image('proof.png'),
                'id_document' => UploadedFile::fake()->image('id.png'),
                'identity_proof' => UploadedFile::fake()->image('group.png'),
            ])
            ->assertRedirect(route('mariachi.verification.edit'))
            ->assertSessionHas('status');

        $payment = ProfileVerificationPayment::query()->first();
        $request = VerificationRequest::query()->first();

        $this->assertNotNull($payment);
        $this->assertNotNull($request);
        $this->assertSame($profile->id, $payment->mariachi_profile_id);
        $this->assertSame(ProfileVerificationPayment::STATUS_PENDING, $payment->status);
        $this->assertSame($payment->id, $request->profile_verification_payment_id);
        $this->assertSame(VerificationRequest::STATUS_PENDING, $request->status);
        $this->assertSame('payment_pending', $profile->fresh()->verification_status);
    }

    public function test_verified_partner_can_update_premium_handle_and_profile_update_keeps_it_locked(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'email' => 'premium@example.com',
            'phone' => '3000000000',
        ]);
        $profile = $this->createProfile($mariachi, [
            'business_name' => 'Mariachi Original',
            'verification_status' => 'verified',
            'verification_expires_at' => now()->addMonth(),
        ]);
        $profile->ensureSlug();

        $this->actingAs($mariachi)
            ->from(route('mariachi.verification.edit'))
            ->patch(route('mariachi.verification.handle.update'), [
                'handle' => 'mariachi-premium',
            ])
            ->assertRedirect(route('mariachi.verification.edit'))
            ->assertSessionHas('status');

        $profile->refresh();
        $this->assertSame('mariachi-premium', $profile->slug);
        $this->assertTrue($profile->slug_locked);

        $this->actingAs($mariachi)
            ->from(route('mariachi.provider-profile.edit'))
            ->patch(route('mariachi.provider-profile.update'), [
                'business_name' => 'Mariachi Renombrado',
                'short_description' => 'Descripcion actualizada para el perfil.',
                'email' => 'premium@example.com',
                'phone' => '3000000000',
                'whatsapp' => '3000000001',
                'website' => 'https://example.com',
                'instagram' => 'https://instagram.com/mariachi',
                'facebook' => 'https://facebook.com/mariachi',
                'tiktok' => 'https://tiktok.com/@mariachi',
                'youtube' => 'https://youtube.com/@mariachi',
            ])
            ->assertRedirect(route('mariachi.provider-profile.edit'))
            ->assertSessionHas('status');

        $this->assertSame('mariachi-premium', $profile->fresh()->slug);
    }

    public function test_admin_approval_syncs_request_payment_and_profile_expiration(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make('password'),
        ]);
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi, [
            'verification_status' => 'payment_pending',
            'verification_expires_at' => null,
        ]);

        $payment = ProfileVerificationPayment::query()->create([
            'mariachi_profile_id' => $profile->id,
            'plan_code' => 'verification-3m',
            'duration_months' => 3,
            'amount_cop' => 56700,
            'method' => ProfileVerificationPayment::METHOD_NEQUI,
            'proof_path' => 'verification-payments/proofs/test.png',
            'status' => ProfileVerificationPayment::STATUS_PENDING,
            'reference_text' => 'ABC123',
        ]);

        $request = VerificationRequest::query()->create([
            'mariachi_profile_id' => $profile->id,
            'profile_verification_payment_id' => $payment->id,
            'status' => VerificationRequest::STATUS_PENDING,
            'id_document_path' => 'verification-docs/id/test.png',
            'identity_proof_path' => 'verification-docs/proof/test.png',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.profile-verifications.index'))
            ->patch(route('admin.profile-verifications.update', ['verificationRequest' => $request->id]), [
                'action' => 'approve',
                'note' => 'Todo validado',
            ])
            ->assertRedirect(route('admin.profile-verifications.index'))
            ->assertSessionHas('status');

        $payment->refresh();
        $request->refresh();
        $profile->refresh();

        $this->assertSame(ProfileVerificationPayment::STATUS_APPROVED, $payment->status);
        $this->assertSame(VerificationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('verified', $profile->verification_status);
        $this->assertNotNull($profile->verification_expires_at);
        $this->assertNotNull($payment->starts_at);
        $this->assertNotNull($payment->ends_at);
        $this->assertTrue($payment->ends_at->equalTo($payment->starts_at->copy()->addMonthsNoOverflow(3)));
        $this->assertTrue($profile->verification_expires_at->equalTo($payment->ends_at));
    }

    private function createProfile(User $mariachi, array $attributes = []): MariachiProfile
    {
        return MariachiProfile::query()->create(array_merge([
            'user_id' => $mariachi->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Test',
            'responsible_name' => 'Responsable Test',
            'short_description' => 'Perfil listo para pruebas',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'verification_status' => 'unverified',
        ], $attributes));
    }
}
