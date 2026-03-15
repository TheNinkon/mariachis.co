<?php

namespace Tests\Feature;

use App\Models\EventType;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlSitemapHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_sitemap_page_curates_only_hubs_with_real_supply(): void
    {
        $bogota = $this->createCity('Bogota', 'bogota');
        $cali = $this->createCity('Cali', 'cali');
        $medellin = $this->createCity('Medellin', 'medellin');
        $pereira = $this->createCity('Pereira', 'pereira');

        $chapinero = $this->createZone($bogota, 'Chapinero', 'chapinero');
        $centro = $this->createZone($pereira, 'Centro', 'centro');

        $bodas = EventType::query()->create([
            'name' => 'Bodas',
            'slug' => 'bodas',
            'is_active' => true,
        ]);

        $pedida = EventType::query()->create([
            'name' => 'Pedida de mano',
            'slug' => 'pedida-de-mano',
            'is_active' => true,
        ]);

        $homeService = ServiceType::query()->create([
            'name' => 'A domicilio',
            'slug' => 'a-domicilio',
            'is_active' => true,
        ]);

        $weakService = ServiceType::query()->create([
            'name' => 'Serenata sorpresa',
            'slug' => 'serenata-sorpresa',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $listing = $this->createPublishedListing($bogota, "bogota-listing-{$i}");
            $listing->eventTypes()->sync([$bodas->id]);
            $listing->serviceTypes()->sync([$homeService->id]);

            if ($i <= 4) {
                $listing->serviceAreas()->create([
                    'marketplace_zone_id' => $chapinero->id,
                    'city_name' => $chapinero->name,
                ]);
            }
        }

        for ($i = 1; $i <= 2; $i++) {
            $listing = $this->createPublishedListing($cali, "cali-listing-{$i}");
            $listing->eventTypes()->sync([$bodas->id]);
            $listing->serviceTypes()->sync([$homeService->id]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $listing = $this->createPublishedListing($medellin, "medellin-listing-{$i}");
            $listing->eventTypes()->sync([$bodas->id]);
            $listing->serviceTypes()->sync([$homeService->id]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $listing = $this->createPublishedListing($pereira, "pereira-listing-{$i}");
            $listing->eventTypes()->sync([$pedida->id]);
            $listing->serviceTypes()->sync([$weakService->id]);

            if ($i === 1) {
                $listing->serviceAreas()->create([
                    'marketplace_zone_id' => $centro->id,
                    'city_name' => $centro->name,
                ]);
            }
        }

        $response = $this->get(route('seo.html-sitemap'));

        $response->assertOk();
        $response->assertSee('Explora mariachis en Colombia');
        $response->assertSee(route('seo.landing.slug', ['slug' => 'bogota']));
        $response->assertDontSee(route('seo.landing.slug', ['slug' => 'pereira']));
        $response->assertSee(route('seo.landing.slug', ['slug' => 'bodas']));
        $response->assertDontSee(route('seo.landing.slug', ['slug' => 'pedida-de-mano']));
        $response->assertSee(route('seo.landing.city-category', ['citySlug' => 'bogota', 'scopeSlug' => 'chapinero']));
        $response->assertDontSee(route('seo.landing.city-category', ['citySlug' => 'pereira', 'scopeSlug' => 'centro']));
        $response->assertSee(route('seo.landing.city-category', ['citySlug' => 'bogota', 'scopeSlug' => 'bodas']));
        $response->assertSee(route('seo.landing.slug', ['slug' => 'colombia']).'?service=a-domicilio');
        $response->assertDontSee(route('seo.landing.slug', ['slug' => 'colombia']).'?service=serenata-sorpresa');
    }

    public function test_html_sitemap_url_is_exposed_in_xml_sitemap_and_home_footer_state(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('http://localhost/mapa-del-sitio');

        $this->get('/')
            ->assertOk()
            ->assertSee(route('seo.html-sitemap'));
    }

    private function createCity(string $name, string $slug): MarketplaceCity
    {
        return MarketplaceCity::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'show_in_search' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);
    }

    private function createZone(MarketplaceCity $city, string $name, string $slug): MarketplaceZone
    {
        return MarketplaceZone::query()->create([
            'marketplace_city_id' => $city->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'show_in_search' => true,
            'sort_order' => 1,
        ]);
    }

    private function createPublishedListing(MarketplaceCity $city, string $slug): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => $city->name,
            'country' => 'Colombia',
            'business_name' => 'Mariachi '.$slug,
            'responsible_name' => 'Responsable '.$slug,
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        return MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => $slug,
            'title' => 'Anuncio '.$slug,
            'short_description' => 'Anuncio de prueba.',
            'city_name' => $city->name,
            'marketplace_city_id' => $city->id,
            'country' => 'Colombia',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);
    }
}
