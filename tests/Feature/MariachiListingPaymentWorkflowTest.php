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
use App\Models\User;
use App\Services\PlanAssignmentService;
use Carbon\Carbon;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MariachiListingPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        $this->configureWompi();
    }

    public function test_initial_purchase_creates_pending_payment_and_moves_listing_to_payment_stage(): void
    {
        $mariachi = $this->createMariachiUser();
        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->where('code', 'pro')->firstOrFail();

        $preview = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('checkout.operation_type', ListingPayment::OPERATION_INITIAL)
            ->assertJsonPath('checkout.amount_cop', 59900)
            ->json();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'term_months' => 1,
                'amount_cop' => $preview['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $listing->refresh();
        $payment = $listing->payments()->latest('id')->firstOrFail();

        $this->assertSame(MariachiListing::STATUS_AWAITING_PAYMENT, $listing->status);
        $this->assertSame(MariachiListing::PAYMENT_PENDING, $listing->payment_status);
        $this->assertFalse($listing->is_active);
        $this->assertSame('pro', $listing->selected_plan_code);
        $this->assertSame(ListingPayment::OPERATION_INITIAL, $payment->operation_type);
        $this->assertSame('pro', $payment->targetPlanCode());
        $this->assertNull($payment->source_plan_code);
        $this->assertSame(59900, $payment->chargedAmountCop());
        $this->assertSame(ListingPayment::STATUS_PENDING, $payment->status);
    }

    public function test_active_listing_upgrade_keeps_listing_live_until_payment_is_approved(): void
    {
        $mariachi = $this->createMariachiUser();
        $admin = $this->createAdminUser();
        $listing = $this->createActiveApprovedListing($mariachi, 'basic', now()->subDays(10), now()->addDays(20));
        $targetPlan = Plan::query()->where('code', 'pro')->firstOrFail();

        $previewResponse = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('checkout.operation_type', ListingPayment::OPERATION_UPGRADE)
            ->json();

        $this->assertGreaterThan(0, $previewResponse['checkout']['applied_credit_cop']);
        $this->assertLessThan(59900, $previewResponse['checkout']['amount_cop']);

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
                'amount_cop' => $previewResponse['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $listing->refresh();
        $pendingPayment = $listing->payments()->latest('id')->firstOrFail();

        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->review_status);
        $this->assertSame(MariachiListing::PAYMENT_APPROVED, $listing->payment_status);
        $this->assertTrue($listing->is_active);
        $this->assertSame('basic', $listing->selected_plan_code);
        $this->assertSame(ListingPayment::OPERATION_UPGRADE, $pendingPayment->operation_type);
        $this->assertSame('basic', $pendingPayment->source_plan_code);
        $this->assertSame('pro', $pendingPayment->targetPlanCode());

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $pendingPayment), [
                'action' => 'approve',
            ])
            ->assertRedirect();

        $listing->refresh();
        $pendingPayment->refresh();

        $this->assertSame(ListingPayment::STATUS_APPROVED, $pendingPayment->status);
        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->review_status);
        $this->assertSame(MariachiListing::PAYMENT_APPROVED, $listing->payment_status);
        $this->assertTrue($listing->is_active);
        $this->assertSame('pro', $listing->selected_plan_code);
        $this->assertSame(1, $listing->plan_duration_months);
        $this->assertNotNull($listing->plan_expires_at);
        $this->assertTrue($listing->plan_expires_at->greaterThan(now()->addDays(25)));
    }

    public function test_rejected_upgrade_keeps_current_active_plan_intact(): void
    {
        $mariachi = $this->createMariachiUser();
        $admin = $this->createAdminUser();
        $listing = $this->createActiveApprovedListing($mariachi, 'basic', now()->subDays(7), now()->addDays(23));
        $targetPlan = Plan::query()->where('code', 'premium')->firstOrFail();

        $previewResponse = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('checkout.operation_type', ListingPayment::OPERATION_UPGRADE)
            ->json();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
                'amount_cop' => $previewResponse['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $payment = $listing->fresh()->payments()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $payment), [
                'action' => 'reject',
                'rejection_reason' => 'El cobro no coincide con la transacción esperada.',
            ])
            ->assertRedirect();

        $listing->refresh();
        $payment->refresh();

        $this->assertSame(ListingPayment::STATUS_REJECTED, $payment->status);
        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->review_status);
        $this->assertSame(MariachiListing::PAYMENT_APPROVED, $listing->payment_status);
        $this->assertTrue($listing->is_active);
        $this->assertSame('basic', $listing->selected_plan_code);
    }

    public function test_retry_creates_a_new_payment_linked_to_the_rejected_attempt(): void
    {
        $mariachi = $this->createMariachiUser();
        $admin = $this->createAdminUser();
        $listing = $this->createCompleteListing($mariachi);
        $plan = Plan::query()->where('code', 'pro')->firstOrFail();

        $initialPreview = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->json();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'term_months' => 1,
                'amount_cop' => $initialPreview['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $firstPayment = $listing->fresh()->payments()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $firstPayment), [
                'action' => 'reject',
                'rejection_reason' => 'Prueba rechazada para habilitar reintento.',
            ])
            ->assertRedirect();

        $retryPreview = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $plan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('checkout.operation_type', ListingPayment::OPERATION_RETRY)
            ->json();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $plan->code,
                'term_months' => 1,
                'amount_cop' => $retryPreview['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $listing->refresh();
        $payments = $listing->payments()->latest('id')->get();
        $retryPayment = $payments->first();

        $this->assertCount(2, $payments);
        $this->assertSame(ListingPayment::OPERATION_RETRY, $retryPayment->operation_type);
        $this->assertSame($firstPayment->id, $retryPayment->retry_of_payment_id);
        $this->assertSame(ListingPayment::STATUS_PENDING, $retryPayment->status);
        $this->assertSame(MariachiListing::STATUS_AWAITING_PAYMENT, $listing->status);
        $this->assertSame(MariachiListing::PAYMENT_PENDING, $listing->payment_status);
    }

    public function test_active_listing_does_not_fall_back_to_pending_when_starting_an_upgrade(): void
    {
        $mariachi = $this->createMariachiUser();
        $listing = $this->createActiveApprovedListing($mariachi, 'basic', now()->subDays(5), now()->addDays(25));
        $targetPlan = Plan::query()->where('code', 'premium')->firstOrFail();

        $previewResponse = $this->actingAs($mariachi)
            ->postJson(route('mariachi.listings.plans.select', $listing), [
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
            ])
            ->assertOk()
            ->json();

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.payments.wompi.checkout', $listing), [
                'listing_id' => $listing->id,
                'plan_code' => $targetPlan->code,
                'term_months' => 1,
                'amount_cop' => $previewResponse['checkout']['amount_cop'],
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame(MariachiListing::STATUS_ACTIVE, $listing->status);
        $this->assertSame(MariachiListing::REVIEW_APPROVED, $listing->review_status);
        $this->assertSame(MariachiListing::PAYMENT_APPROVED, $listing->payment_status);
        $this->assertTrue($listing->is_active);
        $this->assertSame('basic', $listing->selected_plan_code);
    }

    private function configureWompi(): void
    {
        config()->set('payments.wompi.environment', 'sandbox');
        config()->set('payments.wompi.public_key', 'pub_test_123');
        config()->set('payments.wompi.private_key', 'prv_test_123');
        config()->set('payments.wompi.integrity_secret', 'test_integrity_123');
        config()->set('payments.wompi.events_secret', 'test_events_123');
        config()->set('payments.wompi.currency', 'COP');
        config()->set('payments.wompi.checkout_url', 'https://checkout.wompi.co/p/');
        config()->set('payments.wompi.sandbox_api_base_url', 'https://sandbox.wompi.co/v1');
        config()->set('payments.wompi.production_api_base_url', 'https://production.wompi.co/v1');
    }

    private function createMariachiUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'phone' => '+57 3100001234',
        ]);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
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
            'listing_completion' => 100,
            'listing_completed' => true,
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

    private function createActiveApprovedListing(
        User $mariachi,
        string $planCode,
        Carbon $activatedAt,
        Carbon $expiresAt
    ): MariachiListing {
        $listing = $this->createCompleteListing($mariachi);
        $profile = $listing->mariachiProfile;
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();

        app(PlanAssignmentService::class)->assignToProfile(
            $profile,
            $plan,
            $listing,
            'seed_active_listing',
            [],
            true,
            1,
            (int) $plan->price_cop,
            $activatedAt
        );

        $listing->update([
            'selected_plan_code' => $planCode,
            'plan_duration_months' => 1,
            'plan_selected_at' => $activatedAt,
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'activated_at' => $activatedAt,
            'plan_expires_at' => $expiresAt,
            'submitted_for_review_at' => $activatedAt,
            'reviewed_at' => $activatedAt,
            'deactivated_at' => null,
        ]);

        $listing->payments()->create([
            'mariachi_profile_id' => $profile->id,
            'plan_code' => $planCode,
            'duration_months' => 1,
            'amount_cop' => (int) $plan->price_cop,
            'method' => ListingPayment::METHOD_WOMPI,
            'checkout_reference' => 'SEED-'.$listing->id.'-'.strtoupper($planCode),
            'status' => ListingPayment::STATUS_APPROVED,
            'operation_type' => ListingPayment::OPERATION_INITIAL,
            'source_plan_code' => null,
            'target_plan_code' => $planCode,
            'subtotal_amount_cop' => (int) $plan->price_cop,
            'discount_amount_cop' => 0,
            'base_amount_cop' => (int) $plan->price_cop,
            'applied_credit_cop' => 0,
            'final_amount_cop' => (int) $plan->price_cop,
            'reviewed_at' => $activatedAt,
        ]);

        return $listing->fresh();
    }
}
