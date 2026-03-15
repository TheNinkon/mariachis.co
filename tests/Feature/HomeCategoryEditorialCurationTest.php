<?php

namespace Tests\Feature;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomeCategoryEditorialCurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_categories_in_home_use_editorial_visibility_priority_limit_and_signals(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $domicilio = $this->createServiceType('A domicilio', 'a-domicilio', 20, true);
        $completo = $this->createServiceType('Show completo', 'show-completo', 10, true);
        $horas = $this->createServiceType('Mariachi por horas', 'mariachi-por-horas', 30, true);
        $sorpresa = $this->createServiceType('Serenata sorpresa', 'serenata-sorpresa', 40, true);
        $personalizado = $this->createServiceType('Servicio personalizado', 'servicio-personalizado', 50, true);
        $premium = $this->createServiceType('Formato premium', 'formato-premium', 60, true);
        $overflow = $this->createServiceType('Formato extendido', 'formato-extendido', 70, true);
        $hidden = $this->createServiceType('Oculto', 'oculto', 5, false);
        $seasonal = $this->createServiceType('Navidad', 'navidad', 1, true, [
            'seasonal_start_at' => now()->addMonth(),
            'seasonal_end_at' => now()->addMonths(2),
        ]);
        $supplyLocked = $this->createServiceType('Especial', 'especial', 15, true, [
            'min_active_listings_required' => 2,
        ]);

        $this->createPublishedListingWithRelations(serviceTypeIds: [$domicilio->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$completo->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$completo->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$horas->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$sorpresa->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$personalizado->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$premium->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$overflow->id]);
        $this->createPublishedListingWithRelations(serviceTypeIds: [$supplyLocked->id]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('home.service-category.redirect', ['serviceType' => 'show-completo']))
            ->assertSee(route('home.service-category.redirect', ['serviceType' => 'a-domicilio']))
            ->assertSee(route('home.service-category.redirect', ['serviceType' => 'formato-premium']))
            ->assertSee(route('home.service-category.redirect', ['serviceType' => 'servicio-personalizado']))
            ->assertDontSee(route('home.service-category.redirect', ['serviceType' => 'formato-extendido']))
            ->assertDontSee(route('home.service-category.redirect', ['serviceType' => 'oculto']))
            ->assertDontSee(route('home.service-category.redirect', ['serviceType' => 'navidad']))
            ->assertDontSee(route('home.service-category.redirect', ['serviceType' => 'especial']));

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertLessThan(
            strpos($content, route('home.service-category.redirect', ['serviceType' => 'a-domicilio'])),
            strpos($content, route('home.service-category.redirect', ['serviceType' => 'show-completo']))
        );
        $this->assertStringNotContainsString(route('home.service-category.redirect', ['serviceType' => 'formato-extendido']), $this->extractCategoryPanel($content, 'servicio', 6));
    }

    public function test_group_and_budget_categories_respect_visibility_seasonality_and_minimum_supply(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $groupVisible = GroupSizeOption::query()->create([
            'name' => '4 integrantes',
            'slug' => '4-integrantes',
            'icon' => 'users-4',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
        ]);

        $groupSeasonal = GroupSizeOption::query()->create([
            'name' => 'Mariachi navideño',
            'slug' => 'mariachi-navideno',
            'icon' => 'users-group',
            'sort_order' => 2,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 5,
            'seasonal_start_at' => now()->addDays(10),
            'seasonal_end_at' => now()->addDays(40),
        ]);

        $groupSupply = GroupSizeOption::query()->create([
            'name' => '8 integrantes',
            'slug' => '8-integrantes',
            'icon' => 'users-group',
            'sort_order' => 3,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 15,
            'min_active_listings_required' => 2,
        ]);

        $budgetVisible = BudgetRange::query()->create([
            'name' => 'Estandar',
            'slug' => 'estandar',
            'icon' => 'coins',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
        ]);

        $budgetHidden = BudgetRange::query()->create([
            'name' => 'Ultra premium',
            'slug' => 'ultra-premium',
            'icon' => 'diamond',
            'sort_order' => 2,
            'is_active' => true,
            'is_visible_in_home' => false,
            'home_priority' => 5,
        ]);

        $budgetSupply = BudgetRange::query()->create([
            'name' => 'Flexible',
            'slug' => 'flexible',
            'icon' => 'wallet',
            'sort_order' => 3,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 15,
            'min_active_listings_required' => 2,
        ]);

        $this->createPublishedListingWithRelations(groupSizeIds: [$groupVisible->id], budgetRangeIds: [$budgetVisible->id]);
        $this->createPublishedListingWithRelations(groupSizeIds: [$groupSupply->id], budgetRangeIds: [$budgetSupply->id]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('home.group-size-category.redirect', ['groupSizeOption' => '4-integrantes']))
            ->assertDontSee(route('home.group-size-category.redirect', ['groupSizeOption' => 'mariachi-navideno']))
            ->assertDontSee(route('home.group-size-category.redirect', ['groupSizeOption' => '8-integrantes']))
            ->assertSee(route('home.budget-category.redirect', ['budgetRange' => 'estandar']))
            ->assertDontSee(route('home.budget-category.redirect', ['budgetRange' => 'ultra-premium']))
            ->assertDontSee(route('home.budget-category.redirect', ['budgetRange' => 'flexible']));
    }

    public function test_service_group_and_budget_redirects_track_clicks(): void
    {
        $serviceType = $this->createServiceType('A domicilio', 'a-domicilio', 10, true);
        $groupSize = GroupSizeOption::query()->create([
            'name' => '5 integrantes',
            'slug' => '5-integrantes',
            'icon' => 'users-5',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
            'home_clicks_count' => 0,
        ]);
        $budgetRange = BudgetRange::query()->create([
            'name' => 'Premium',
            'slug' => 'premium',
            'icon' => 'diamond',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible_in_home' => true,
            'home_priority' => 10,
            'home_clicks_count' => 0,
        ]);

        $this->get(route('home.service-category.redirect', ['serviceType' => $serviceType->slug]))
            ->assertRedirect(route('seo.landing.slug', ['slug' => 'colombia']).'?service=a-domicilio');
        $this->get(route('home.group-size-category.redirect', ['groupSizeOption' => $groupSize->slug]))
            ->assertRedirect(route('seo.landing.slug', ['slug' => 'colombia']).'?group=5-integrantes');
        $this->get(route('home.budget-category.redirect', ['budgetRange' => $budgetRange->slug]))
            ->assertRedirect(route('seo.landing.slug', ['slug' => 'colombia']).'?budget=premium');

        $this->assertSame(1, $serviceType->fresh()->home_clicks_count);
        $this->assertSame(1, $groupSize->fresh()->home_clicks_count);
        $this->assertSame(1, $budgetRange->fresh()->home_clicks_count);
    }

    private function createServiceType(string $name, string $slug, int $priority, bool $visibleInHome, array $overrides = []): ServiceType
    {
        return ServiceType::query()->create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'settings',
            'sort_order' => $priority,
            'is_active' => true,
            'is_visible_in_home' => $visibleInHome,
            'home_priority' => $priority,
            'home_clicks_count' => 0,
        ], $overrides));
    }

    private function createPublishedListingWithRelations(array $eventTypeIds = [], array $serviceTypeIds = [], array $groupSizeIds = [], array $budgetRangeIds = []): MariachiListing
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi '.uniqid(),
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        $listing = MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'listing-'.uniqid(),
            'title' => 'Listado '.uniqid(),
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        if ($eventTypeIds !== []) {
            $listing->eventTypes()->sync($eventTypeIds);
        }

        if ($serviceTypeIds !== []) {
            $listing->serviceTypes()->sync($serviceTypeIds);
        }

        if ($groupSizeIds !== []) {
            $listing->groupSizeOptions()->sync($groupSizeIds);
        }

        if ($budgetRangeIds !== []) {
            $listing->budgetRanges()->sync($budgetRangeIds);
        }

        return $listing;
    }

    private function extractCategoryPanel(string $content, string $panelKey, int $expectedVisibleCount): string
    {
        $start = strpos($content, 'data-tab-panel="'.$panelKey.'"');
        $this->assertNotFalse($start);
        $slice = substr($content, $start, 5000);
        $this->assertIsString($slice);

        return $slice;
    }
}
