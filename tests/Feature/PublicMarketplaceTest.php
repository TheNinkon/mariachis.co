<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'mariachi-de-prueba-listing',
            'title' => 'Mariachi de Prueba',
            'short_description' => 'Show para eventos.',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        $response = $this->get('/mariachi/'.$listing->slug);

        $response->assertOk();
        $response->assertSee('Mariachi de Prueba');
    }

    public function test_public_provider_handle_page_is_accessible_for_published_profiles(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Vargas de Bogotá',
            'responsible_name' => 'Responsable',
            'short_description' => 'Perfil oficial del grupo.',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
            'verification_status' => 'verified',
        ]);
        $profile->ensureSlug();

        MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'anuncio-vargas-bogota',
            'title' => 'Serenatas Mariachi Vargas',
            'short_description' => 'Anuncio principal',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        $response = $this->get('/@'.$profile->slug);

        $response->assertOk();
        $response->assertSee('Mariachi Vargas de Bogotá');
        $response->assertSee('Serenatas Mariachi Vargas');
        $response->assertSee('rel="canonical"', false);
    }

    public function test_public_provider_handle_page_is_accessible_for_active_profile_without_listings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Privado',
            'responsible_name' => 'Responsable',
            'short_description' => 'Perfil no publicado.',
            'profile_completed' => false,
            'profile_completion' => 20,
            'stage_status' => 'profile_incomplete',
        ]);
        $profile->ensureSlug();

        $this->get('/@'.$profile->slug)
            ->assertOk()
            ->assertSee('Mariachi Privado');
    }

    public function test_public_provider_handle_page_returns_404_when_owner_is_not_active(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_INACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Oculto',
            'responsible_name' => 'Responsable',
            'short_description' => 'Perfil con cuenta inactiva.',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);
        $profile->ensureSlug();

        $this->get('/@'.$profile->slug)->assertNotFound();
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

    public function test_home_event_categories_follow_editorial_visibility_rules_and_order(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $weddings = EventType::query()->create([
            'name' => 'Bodas',
            'slug' => 'bodas',
            'icon' => 'rings',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 20,
            'home_clicks_count' => 4,
        ]);

        $anniversaries = EventType::query()->create([
            'name' => 'Aniversarios',
            'slug' => 'aniversarios',
            'icon' => 'sparkles',
            'sort_order' => 2,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
            'home_clicks_count' => 1,
        ]);

        $seasonal = EventType::query()->create([
            'name' => 'Dia de la madre',
            'slug' => 'dia-de-la-madre',
            'icon' => 'flower',
            'sort_order' => 3,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 5,
            'seasonal_start_at' => now()->addMonth(),
            'seasonal_end_at' => now()->addMonths(2),
        ]);

        $notEnoughSupply = EventType::query()->create([
            'name' => 'Pedida de mano',
            'slug' => 'pedida-de-mano',
            'icon' => 'sparkles',
            'sort_order' => 4,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 15,
            'min_active_listings_required' => 2,
        ]);

        $hidden = EventType::query()->create([
            'name' => 'Evento oculto',
            'slug' => 'evento-oculto',
            'icon' => 'confetti',
            'sort_order' => 5,
            'is_active' => true,
            'is_visible_in_home' => false,
        ]);

        $firstListing = $this->createPublishedListing('Bogota', 'mariachi-bodas-home');
        $firstListing->eventTypes()->sync([$weddings->id]);

        $secondListing = $this->createPublishedListing('Bogota', 'mariachi-aniversarios-home');
        $secondListing->eventTypes()->sync([$anniversaries->id]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('home.event-category.redirect', ['eventType' => 'aniversarios']))
            ->assertSee(route('home.event-category.redirect', ['eventType' => 'bodas']))
            ->assertDontSee('dia-de-la-madre')
            ->assertDontSee('Pedida de mano')
            ->assertDontSee('Evento oculto');

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertLessThan(
            strpos($content, route('home.event-category.redirect', ['eventType' => 'bodas'])),
            strpos($content, route('home.event-category.redirect', ['eventType' => 'aniversarios']))
        );
    }

    public function test_home_event_category_redirect_tracks_clicks_and_redirects_to_landing(): void
    {
        $eventType = EventType::query()->create([
            'name' => 'Serenatas',
            'slug' => 'serenatas',
            'icon' => 'music-note',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
            'home_clicks_count' => 0,
        ]);

        $this->get(route('home.event-category.redirect', ['eventType' => $eventType->slug]))
            ->assertRedirect(route('seo.landing.slug', ['slug' => 'serenatas']));

        $this->assertSame(1, $eventType->fresh()->home_clicks_count);
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
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
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
