<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiListingServiceArea;
use App\Models\MariachiProfile;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSearchLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_search_renders_unique_cities_with_grouped_zones(): void
    {
        $bogota = MarketplaceCity::query()->create([
            'name' => 'Bogota',
            'slug' => 'bogota',
            'is_active' => true,
            'sort_order' => 1,
            'is_featured' => true,
            'show_in_search' => true,
        ]);

        $medellin = MarketplaceCity::query()->create([
            'name' => 'Medellin',
            'slug' => 'medellin',
            'is_active' => true,
            'sort_order' => 2,
            'is_featured' => true,
            'show_in_search' => true,
        ]);

        $chapinero = MarketplaceZone::query()->create([
            'marketplace_city_id' => $bogota->id,
            'name' => 'Chapinero',
            'slug' => 'chapinero',
            'is_active' => true,
            'sort_order' => 1,
            'show_in_search' => true,
        ]);

        $usaquen = MarketplaceZone::query()->create([
            'marketplace_city_id' => $bogota->id,
            'name' => 'Usaquen',
            'slug' => 'usaquen',
            'is_active' => true,
            'sort_order' => 2,
            'show_in_search' => true,
        ]);

        $laureles = MarketplaceZone::query()->create([
            'marketplace_city_id' => $medellin->id,
            'name' => 'Laureles',
            'slug' => 'laureles',
            'is_active' => true,
            'sort_order' => 1,
            'show_in_search' => true,
        ]);

        $this->createPublishedListing($bogota, [$chapinero, $usaquen], 'mariachi-bogota-a');
        $this->createPublishedListing($bogota, [$chapinero], 'mariachi-bogota-b');
        $this->createPublishedListing($medellin, [$laureles], 'mariachi-medellin-a');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Buscar por nombre o por categoría');
        $response->assertSee('Dónde');
        $response->assertSee('Buscar');

        $content = $response->getContent();

        $this->assertSame(
            1,
            preg_match_all('/city-dropdown-item--city[^>]*data-city-value="Bogotá"[^>]*data-city-option-slug="bogota"/u', $content)
        );
        $this->assertSame(
            1,
            preg_match_all('/city-dropdown-item--city[^>]*data-city-value="Medellín"[^>]*data-city-option-slug="medellin"/u', $content)
        );
        $this->assertStringContainsString('data-zone-label="Chapinero"', $content);
        $this->assertStringContainsString('data-zone-label="Usaquén"', $content);
        $this->assertStringContainsString('data-zone-label="Laureles"', $content);
    }

    /**
     * @param  array<int, MarketplaceZone>  $zones
     */
    private function createPublishedListing(MarketplaceCity $city, array $zones, string $slug): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => $city->name,
            'business_name' => 'Mariachi '.$slug,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'marketplace_city_id' => $city->id,
            'slug' => $slug,
            'title' => 'Listado '.$slug,
            'short_description' => 'Perfil para '.$city->name,
            'description' => 'Listado activo para pruebas de buscador.',
            'country' => 'Colombia',
            'state' => 'Test',
            'city_name' => $city->name,
            'address' => 'Direccion prueba',
            'latitude' => 4.0000000,
            'longitude' => -74.0000000,
            'base_price' => 300000,
            'status' => MariachiListing::STATUS_ACTIVE,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        foreach ($zones as $zone) {
            MariachiListingServiceArea::query()->create([
                'mariachi_listing_id' => $listing->id,
                'marketplace_zone_id' => $zone->id,
                'city_name' => $zone->name,
            ]);
        }

        return $listing;
    }
}
