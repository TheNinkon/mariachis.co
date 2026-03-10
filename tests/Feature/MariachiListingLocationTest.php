<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\User;
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
}
