<?php

namespace Tests\Feature;

use App\Models\CatalogSuggestion;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\User;
use Database\Seeders\MarketplaceZoneSeederBogotaMedellin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MariachiListingLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_update_stores_google_location_fields_internally(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Centro',
            'responsible_name' => 'Carlos',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        $city = MarketplaceCity::query()->create([
            'name' => 'Bogota',
            'slug' => 'bogota',
            'is_active' => true,
            'sort_order' => 1,
            'is_featured' => true,
            'show_in_search' => true,
        ]);

        $zone = MarketplaceZone::query()->create([
            'marketplace_city_id' => $city->id,
            'name' => 'Chapinero',
            'slug' => 'chapinero',
            'is_active' => true,
            'sort_order' => 1,
            'show_in_search' => true,
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'title' => 'Mariachi para eventos',
            'short_description' => 'Serenatas y bodas',
            'description' => 'Show completo.',
            'base_price' => 350000,
            'status' => MariachiListing::STATUS_DRAFT,
            'is_active' => false,
        ]);

        $payload = [
            'place_id' => 'abc123',
            'formatted_address' => 'Cra. 13 #85-32, Bogota, Colombia',
            'geometry' => ['lat' => 4.6757, 'lng' => -74.0483],
        ];

        $this->actingAs($user)
            ->patch(route('mariachi.listings.update', ['listing' => $listing->id]), [
                'title' => 'Mariachi para eventos',
                'short_description' => 'Serenatas y bodas',
                'description' => 'Show completo.',
                'base_price' => 350000,
                'address' => 'Cra. 13 #85-32, Bogota, Colombia',
                'city_name' => 'Bogota',
                'zone_name' => 'Chapinero',
                'state' => 'Bogota D.C.',
                'marketplace_city_id' => $city->id,
                'primary_marketplace_zone_id' => $zone->id,
                'latitude' => '4.6757000',
                'longitude' => '-74.0483000',
                'google_place_id' => 'abc123',
                'google_location_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => MariachiListing::STATUS_DRAFT,
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame('Colombia', $listing->country);
        $this->assertSame('Bogota', $listing->city_name);
        $this->assertSame('Chapinero', $listing->zone_name);
        $this->assertSame($city->id, $listing->marketplace_city_id);
        $this->assertSame('abc123', $listing->google_place_id);
        $this->assertSame('Bogota D.C.', $listing->state);
        $this->assertSame('Cra. 13 #85-32, Bogota, Colombia', $listing->address);
        $this->assertNotNull($listing->latitude);
        $this->assertNotNull($listing->longitude);
        $this->assertSame('abc123', $listing->google_location_payload['place_id'] ?? null);
        $this->assertCount(1, $listing->serviceAreas);
        $this->assertSame($zone->id, $listing->serviceAreas->first()->marketplace_zone_id);
    }

    public function test_autosave_creates_zone_suggestion_with_city_context_when_zone_is_not_in_catalog(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Centro',
            'responsible_name' => 'Carlos',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        $city = MarketplaceCity::query()->create([
            'name' => 'Bogota',
            'slug' => 'bogota',
            'is_active' => true,
            'sort_order' => 1,
            'is_featured' => true,
            'show_in_search' => true,
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'title' => 'Mariachi para eventos',
            'short_description' => 'Serenatas y bodas',
            'status' => MariachiListing::STATUS_DRAFT,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'is_active' => false,
        ]);

        $payload = [
            'marketplace_city_id' => $city->id,
            'city_name' => 'Bogota',
            'zone_name' => 'Bosa',
            'suggest_zone' => 'Bosa',
            'autosave_sync' => '1',
        ];

        $this->actingAs($user)
            ->patch(route('mariachi.listings.autosave', ['listing' => $listing->id]), $payload)
            ->assertOk();

        $suggestion = CatalogSuggestion::query()
            ->where('catalog_type', CatalogSuggestion::TYPE_ZONE)
            ->where('proposed_slug', 'bosa')
            ->firstOrFail();

        $this->assertSame('Bosa', $suggestion->proposed_name);
        $this->assertSame(CatalogSuggestion::STATUS_PENDING, $suggestion->status);
        $this->assertSame($city->id, $suggestion->context_data['marketplace_city_id'] ?? null);

        $this->actingAs($user)
            ->patch(route('mariachi.listings.autosave', ['listing' => $listing->id]), $payload)
            ->assertOk();

        $this->assertSame(
            1,
            CatalogSuggestion::query()
                ->where('catalog_type', CatalogSuggestion::TYPE_ZONE)
                ->where('proposed_slug', 'bosa')
                ->where('context_data->marketplace_city_id', $city->id)
                ->count()
        );
    }

    public function test_official_locality_seeder_populates_bogota_and_medellin_catalogs(): void
    {
        $this->seed(MarketplaceZoneSeederBogotaMedellin::class);

        $bogota = MarketplaceCity::query()->where('slug', 'bogota')->firstOrFail();
        $medellin = MarketplaceCity::query()->where('slug', 'medellin')->firstOrFail();

        $this->assertSame('Bogotá', $bogota->name);
        $this->assertSame('Medellín', $medellin->name);
        $this->assertSame(20, MarketplaceZone::query()->where('marketplace_city_id', $bogota->id)->count());
        $this->assertSame(16, MarketplaceZone::query()->where('marketplace_city_id', $medellin->id)->count());
        $this->assertDatabaseHas('marketplace_zones', [
            'marketplace_city_id' => $bogota->id,
            'slug' => 'bosa',
            'name' => 'Bosa',
        ]);
        $this->assertDatabaseHas('marketplace_zones', [
            'marketplace_city_id' => $bogota->id,
            'slug' => 'san-cristobal',
            'name' => 'San Cristóbal',
        ]);
        $this->assertDatabaseHas('marketplace_zones', [
            'marketplace_city_id' => $medellin->id,
            'slug' => 'belen',
            'name' => 'Belén',
        ]);
    }

    public function test_custom_faqs_are_stored_after_the_three_system_rows(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Centro',
            'responsible_name' => 'Carlos',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'title' => 'Mariachi para eventos',
            'short_description' => 'Serenatas y bodas',
            'description' => 'Show completo.',
            'base_price' => 350000,
            'status' => MariachiListing::STATUS_DRAFT,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('mariachi.listings.update', ['listing' => $listing->id]), [
                'title' => $listing->title,
                'short_description' => $listing->short_description,
                'description' => $listing->description,
                'base_price' => 350000,
                'faq_question' => ['¿Cuánto dura el show?'],
                'faq_answer' => ['La duración se acuerda según el evento.'],
            ])
            ->assertRedirect();

        $listing->refresh()->load('faqs');
        $renderedFaqs = $listing->renderedFaqRows();

        $this->assertDatabaseHas('mariachi_listing_faqs', [
            'mariachi_listing_id' => $listing->id,
            'question' => '¿Cuánto dura el show?',
            'sort_order' => 4,
        ]);
        $this->assertCount(4, $renderedFaqs);
        $this->assertTrue($renderedFaqs->take(3)->every(fn (array $faq): bool => $faq['is_system'] === true));
        $this->assertFalse($renderedFaqs->last()['is_system']);
        $this->assertSame('¿Cuánto dura el show?', $renderedFaqs->last()['question']);
    }
}
