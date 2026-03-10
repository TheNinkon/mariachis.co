<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_mariachi_profile_page_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'slug' => 'mariachi-de-prueba',
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi de Prueba',
            'responsible_name' => 'Responsable',
            'short_description' => 'Show para eventos.',
            'full_description' => 'Descripcion completa para eventos.',
            'base_price' => 350000,
            'country' => 'Colombia',
            'state' => 'Cundinamarca',
            'postal_code' => '110111',
            'address' => 'Calle 1',
            'latitude' => 4.711,
            'longitude' => -74.072,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $response = $this->get('/mariachi/'.$profile->slug);

        $response->assertOk();
        $response->assertSee('Mariachi de Prueba');
    }

    public function test_city_zone_route_is_accessible(): void
    {
        $this->createPublishedListing('Bogota', 'mariachi-bogota-seo', 'Chapinero');

        $response = $this->get('/mariachis/bogota/chapinero');

        $response->assertOk();
        $response->assertSee('Mariachis en Chapinero');
    }

    public function test_single_slug_route_returns_404_for_invalid_slugs(): void
    {
        $this->get('/mariachis/login')->assertNotFound();
        $this->get('/mariachis/admin')->assertNotFound();
        $this->get('/mariachis/test')->assertNotFound();
    }

    public function test_reserved_slug_is_blocked_even_if_event_type_exists(): void
    {
        EventType::query()->create([
            'name' => 'Login',
            'is_active' => true,
        ]);

        $this->get('/mariachis/login')->assertNotFound();
    }

    public function test_city_scope_route_returns_404_when_scope_slug_is_invalid(): void
    {
        $this->createPublishedListing('Bogota', 'mariachi-bogota-seo', 'Chapinero');

        $this->get('/mariachis/bogota/admin')->assertNotFound();
        $this->get('/mariachis/bogota/inexistente')->assertNotFound();
    }

    private function createPublishedListing(string $cityName, string $listingSlug, ?string $zoneName = null): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => $cityName,
            'business_name' => 'Mariachi '.$cityName,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => $listingSlug,
            'title' => 'Listado '.$cityName,
            'city_name' => $cityName,
            'status' => MariachiListing::STATUS_ACTIVE,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        if ($zoneName) {
            $listing->serviceAreas()->create(['city_name' => $zoneName]);
        }

        return $listing;
    }
}
