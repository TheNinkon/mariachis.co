<?php

namespace Tests\Feature;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\ListingPayment;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\ServiceType;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NequiPaymentSettingsService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MariachiListingPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mariachi_can_select_a_plan_and_submit_nequi_proof(): void
    {
        Storage::fake('public');
        $this->configureNequi();

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->active()->public()->firstOrFail();

        $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $listing->refresh();

        $this->assertSame($plan->code, $listing->selected_plan_code);
        $this->assertSame(MariachiListing::PAYMENT_NONE, $listing->payment_status);
        $this->assertSame(MariachiListing::STATUS_AWAITING_PAYMENT, $listing->status);
        $this->assertFalse($listing->is_active);

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.nequi.store', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'amount_cop' => $plan->price_cop,
                'proof_image' => UploadedFile::fake()->image('proof.png'),
                'reference_text' => 'NEQ-001',
            ])
            ->assertRedirect(route('mariachi.listings.plans', $listing));

        $listing->refresh();
        $payment = ListingPayment::query()->where('mariachi_listing_id', $listing->id)->latest('id')->firstOrFail();

        $this->assertSame(MariachiListing::PAYMENT_PENDING, $listing->payment_status);
        $this->assertSame(MariachiListing::STATUS_AWAITING_PAYMENT, $listing->status);
        $this->assertFalse($listing->is_active);
        $this->assertSame(ListingPayment::STATUS_PENDING, $payment->status);
        $this->assertSame($plan->code, $payment->plan_code);
        Storage::disk('public')->assertExists($payment->proof_path);

        $this->actingAs($mariachi)
            ->get(route('mariachi.listings.edit', $listing))
            ->assertRedirect(route('mariachi.listings.plans', $listing));
    }

    public function test_admin_can_approve_pending_payment_and_publish_the_listing(): void
    {
        Storage::fake('public');
        $this->configureNequi();

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->active()->public()->firstOrFail();
        $this->submitPendingPayment($mariachi, $listing, $plan);

        $this->actingAs($admin)
            ->patch(route('admin.listings.moderate', $listing), [
                'action' => 'approve',
            ])
            ->assertRedirect(route('admin.listings.show', $listing));

        $listing->refresh();
        $payment = ListingPayment::query()->where('mariachi_listing_id', $listing->id)->latest('id')->firstOrFail();

        $this->assertSame(MariachiListing::PAYMENT_APPROVED, $listing->payment_status);
        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->review_status);
        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertTrue($listing->is_active);
        $this->assertTrue($listing->isApprovedForMarketplace());
        $this->assertSame(ListingPayment::STATUS_APPROVED, $payment->status);
        $this->assertSame($admin->id, $payment->reviewed_by);

        $this->assertDatabaseHas('subscriptions', [
            'mariachi_profile_id' => $listing->mariachi_profile_id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_reject_pending_payment_and_mariachi_can_retry(): void
    {
        Storage::fake('public');
        $this->configureNequi();

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->active()->public()->firstOrFail();
        $this->submitPendingPayment($mariachi, $listing, $plan);

        $this->actingAs($admin)
            ->patch(route('admin.listings.moderate', $listing), [
                'action' => 'reject',
                'rejection_reason' => 'El comprobante no coincide con el monto esperado.',
            ])
            ->assertRedirect(route('admin.listings.show', $listing));

        $listing->refresh();

        $this->assertSame(MariachiListing::PAYMENT_REJECTED, $listing->payment_status);
        $this->assertSame(MariachiListing::STATUS_AWAITING_PAYMENT, $listing->status);
        $this->assertFalse($listing->is_active);

        $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
            ])
            ->assertOk();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.nequi.store', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'amount_cop' => $plan->price_cop,
                'proof_image' => UploadedFile::fake()->image('proof-retry.png'),
                'reference_text' => 'NEQ-002',
            ])
            ->assertRedirect(route('mariachi.listings.plans', $listing));

        $listing->refresh();

        $this->assertSame(MariachiListing::PAYMENT_PENDING, $listing->payment_status);
        $this->assertSame(2, ListingPayment::query()->where('mariachi_listing_id', $listing->id)->count());
        $this->assertSame(
            ListingPayment::STATUS_PENDING,
            ListingPayment::query()->where('mariachi_listing_id', $listing->id)->latest('id')->value('status')
        );
    }

    public function test_mariachi_can_pause_and_resume_only_after_listing_is_published(): void
    {
        Storage::fake('public');
        $this->configureNequi();

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->active()->public()->firstOrFail();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.pause', $listing))
            ->assertSessionHasErrors('listing');

        $this->submitPendingPayment($mariachi, $listing, $plan);

        $this->actingAs($admin)
            ->patch(route('admin.listings.moderate', $listing), [
                'action' => 'approve',
            ])
            ->assertRedirect();

        $listing->refresh();
        $subscription = Subscription::query()->where('mariachi_profile_id', $listing->mariachi_profile_id)->latest('id')->firstOrFail();
        $subscriptionEndsAt = optional($subscription->ends_at)->toISOString();
        $subscriptionRenewsAt = optional($subscription->renews_at)->toISOString();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.pause', $listing))
            ->assertRedirect();

        $listing->refresh();
        $subscription->refresh();

        $this->assertSame(MariachiListing::STATUS_PAUSED, $listing->status);
        $this->assertFalse($listing->is_active);
        $this->assertNotNull($listing->deactivated_at);
        $this->assertSame($subscriptionEndsAt, optional($subscription->ends_at)->toISOString());
        $this->assertSame($subscriptionRenewsAt, optional($subscription->renews_at)->toISOString());

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.resume', $listing))
            ->assertRedirect();

        $listing->refresh();
        $subscription->refresh();

        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertTrue($listing->is_active);
        $this->assertNull($listing->deactivated_at);
        $this->assertSame($subscriptionEndsAt, optional($subscription->ends_at)->toISOString());
        $this->assertSame($subscriptionRenewsAt, optional($subscription->renews_at)->toISOString());
    }

    private function configureNequi(): void
    {
        app(SystemSettingService::class)->putString(NequiPaymentSettingsService::KEY_PHONE, '3001234567');
        app(SystemSettingService::class)->putString(NequiPaymentSettingsService::KEY_BENEFICIARY_NAME, 'Mariachis.co');
    }

    private function createCompleteListing(User $mariachi): MariachiListing
    {
        $profile = MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Test',
            'responsible_name' => 'Carlos Test',
            'short_description' => 'Perfil listo para ventas',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'verification_status' => 'verified',
        ]);

        $listing = $profile->listings()->create([
            'title' => 'Mariachi Premium',
            'short_description' => 'Serenatas y eventos',
            'description' => 'Show completo con repertorio amplio.',
            'base_price' => 250000,
            'country' => 'Colombia',
            'state' => 'Bogota D.C.',
            'city_name' => 'Bogota',
            'address' => 'Cra 1 # 10-20',
            'latitude' => 4.60971,
            'longitude' => -74.08175,
            'travels_to_other_cities' => false,
            'status' => MariachiListing::STATUS_DRAFT,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'is_active' => false,
        ]);

        $listing->photos()->create([
            'path' => 'testing/listing-photo.jpg',
            'sort_order' => 1,
            'is_featured' => true,
        ]);

        $eventType = EventType::query()->create([
            'name' => 'Bodas '.$listing->id,
            'slug' => 'bodas-'.$listing->id,
            'icon' => 'rings',
            'sort_order' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);
        $serviceType = ServiceType::query()->create([
            'name' => 'Show completo '.$listing->id,
            'slug' => 'show-completo-'.$listing->id,
            'icon' => 'microphone',
            'sort_order' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);
        $groupSize = GroupSizeOption::query()->create([
            'name' => '5 integrantes '.$listing->id,
            'slug' => '5-integrantes-'.$listing->id,
            'icon' => 'users',
            'sort_order' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);
        $budgetRange = BudgetRange::query()->create([
            'name' => 'Premium '.$listing->id,
            'slug' => 'premium-'.$listing->id,
            'icon' => 'diamond',
            'sort_order' => 1,
            'is_featured' => true,
            'is_active' => true,
        ]);

        $listing->eventTypes()->sync([$eventType->id]);
        $listing->serviceTypes()->sync([$serviceType->id]);
        $listing->groupSizeOptions()->sync([$groupSize->id]);
        $listing->budgetRanges()->sync([$budgetRange->id]);

        return $listing;
    }

    private function submitPendingPayment(User $mariachi, MariachiListing $listing, Plan $plan): void
    {
        $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
            ])
            ->assertOk();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.nequi.store', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'amount_cop' => $plan->price_cop,
                'proof_image' => UploadedFile::fake()->image('proof.png'),
                'reference_text' => 'NEQ-001',
            ])
            ->assertRedirect();
    }
}
