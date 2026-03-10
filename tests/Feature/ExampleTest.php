<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Marketplace de mariachis en Colombia');
    }

    public function test_city_seo_page_is_accessible_when_city_slug_exists(): void
    {
        $this->createPublishedListing('Bogota', 'mariachi-example-bogota');

        $response = $this->get('/mariachis/bogota');

        $response->assertOk();
        $response->assertSee('Mariachis en Bogota');
    }

    public function test_city_seo_page_returns_404_for_unknown_slug(): void
    {
        $this->get('/mariachis/test')->assertNotFound();
    }

    private function createPublishedListing(string $cityName, string $listingSlug): MariachiListing
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

        return MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => $listingSlug,
            'title' => 'Listado '.$cityName,
            'city_name' => $cityName,
            'status' => MariachiListing::STATUS_ACTIVE,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);
    }
}
