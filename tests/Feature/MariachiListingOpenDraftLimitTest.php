<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\User;
use App\Services\EntitlementsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MariachiListingOpenDraftLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_route_creates_placeholder_draft_and_redirects_directly_to_editor(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        $response = $this->actingAs($mariachi)
            ->get(route('mariachi.listings.create'));

        $listing = $profile->listings()->latest('id')->first();
        $this->assertNotNull($listing);

        $response
            ->assertRedirect(route('mariachi.listings.edit', ['listing' => $listing->id]))
            ->assertSessionHas('status');

        $this->assertSame('Nuevo anuncio', $listing->title);
        $this->assertSame('Completa la informacion del anuncio', $listing->short_description);
        $this->assertNull($listing->base_price);
        $this->assertSame(MariachiListing::STATUS_DRAFT, $listing->status);
        $this->assertSame(MariachiListing::REVIEW_DRAFT, $listing->review_status);
        $this->assertSame(MariachiListing::PAYMENT_NONE, $listing->payment_status);
        $this->assertFalse((bool) $listing->is_active);
        $this->assertNotSame('', (string) $listing->slug);
    }

    public function test_mariachi_cannot_create_a_sixth_open_draft(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        foreach (range(1, 5) as $index) {
            $this->createListing($profile, [
                'title' => 'Borrador '.$index,
            ]);
        }

        $this->actingAs($mariachi)
            ->post(route('mariachi.listings.store'), $this->draftPayload('Borrador bloqueado'))
            ->assertRedirect(route('mariachi.listings.index'))
            ->assertSessionHasErrors('open_drafts');

        $this->assertSame(5, $profile->listings()->count());
        $this->assertSame(5, $profile->listings()->openDrafts()->count());
    }

    public function test_create_route_respects_the_open_draft_limit(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        foreach (range(1, 5) as $index) {
            $this->createListing($profile, [
                'title' => 'Borrador '.$index,
            ]);
        }

        $this->actingAs($mariachi)
            ->get(route('mariachi.listings.create'))
            ->assertRedirect(route('mariachi.listings.index'))
            ->assertSessionHasErrors('open_drafts');

        $this->assertSame(5, $profile->listings()->count());
    }

    public function test_payment_in_review_frees_a_slot_for_another_draft(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        $listings = collect();
        foreach (range(1, 5) as $index) {
            $listings->push($this->createListing($profile, [
                'title' => 'Borrador '.$index,
            ]));
        }

        $listings->first()->update([
            'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
            'payment_status' => MariachiListing::PAYMENT_PENDING,
            'review_status' => MariachiListing::REVIEW_DRAFT,
        ]);

        $this->assertSame(4, $profile->listings()->openDrafts()->count());

        $response = $this->actingAs($mariachi)
            ->post(route('mariachi.listings.store'), $this->draftPayload('Nuevo borrador habilitado'));

        $newListing = $profile->listings()->latest('id')->first();

        $response->assertRedirect(route('mariachi.listings.edit', ['listing' => $newListing->id]));
        $this->assertSame(6, $profile->listings()->count());
        $this->assertSame(5, $profile->listings()->openDrafts()->count());
    }

    public function test_partner_listing_index_renders_open_draft_metrics(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        $this->createListing($profile, [
            'title' => 'Listado visible',
        ]);

        $this->actingAs($mariachi)
            ->get(route('mariachi.listings.index'))
            ->assertOk()
            ->assertSee('Borradores abiertos')
            ->assertSee('Listado de anuncios')
            ->assertSee('Listado visible');
    }

    public function test_listing_entitlements_are_resolved_from_selected_plan_code(): void
    {
        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = $this->createProfile($mariachi);

        $basicPlan = Plan::query()->where('code', 'basic')->with('entitlements')->firstOrFail();
        $premiumPlan = Plan::query()->where('code', 'premium')->with('entitlements')->firstOrFail();

        $basicListing = $this->createListing($profile, [
            'title' => 'Plan basic',
            'selected_plan_code' => $basicPlan->code,
        ]);
        $premiumListing = $this->createListing($profile, [
            'title' => 'Plan premium',
            'selected_plan_code' => $premiumPlan->code,
        ]);

        $entitlements = app(EntitlementsService::class);

        $this->assertSame(
            (int) ($basicPlan->entitlementValue('max_photos_per_listing', $basicPlan->max_photos_per_listing) ?? $basicPlan->max_photos_per_listing),
            $entitlements->maxPhotosPerListing($profile, $basicListing)
        );
        $this->assertSame(
            (int) ($premiumPlan->entitlementValue('max_photos_per_listing', $premiumPlan->max_photos_per_listing) ?? $premiumPlan->max_photos_per_listing),
            $entitlements->maxPhotosPerListing($profile, $premiumListing)
        );
        $this->assertSame(
            (int) ($basicPlan->entitlementValue('max_zones_covered', max(5, $basicPlan->included_cities * 5)) ?? max(5, $basicPlan->included_cities * 5)),
            $entitlements->maxZonesCovered($profile, $basicListing)
        );
        $this->assertSame(
            (int) ($premiumPlan->entitlementValue('max_zones_covered', max(5, $premiumPlan->included_cities * 5)) ?? max(5, $premiumPlan->included_cities * 5)),
            $entitlements->maxZonesCovered($profile, $premiumListing)
        );
    }

    private function createProfile(User $mariachi): MariachiProfile
    {
        return MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Draft Tester',
            'responsible_name' => 'Carlos Tester',
            'short_description' => 'Perfil listo para borradores',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'verification_status' => 'verified',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createListing(MariachiProfile $profile, array $attributes = []): MariachiListing
    {
        return $profile->listings()->create(array_merge([
            'title' => 'Borrador base',
            'short_description' => 'Descripcion corta de prueba',
            'country' => 'Colombia',
            'status' => MariachiListing::STATUS_DRAFT,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'is_active' => false,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function draftPayload(string $title): array
    {
        return [
            'title' => $title,
            'short_description' => 'Descripcion corta para '.$title,
            'base_price' => 250000,
        ];
    }
}
