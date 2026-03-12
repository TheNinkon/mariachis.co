<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiListing;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\ServiceType;
use App\Services\Front\SearchFormData;
use App\Services\Front\TrustpilotProfileData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const LOCATION_LABEL_OVERRIDES = [
        'bogota' => 'Bogotá',
        'ciudad-jardin' => 'Ciudad Jardín',
        'centro-historico' => 'Centro Histórico',
        'medellin' => 'Medellín',
        'norte-centro-historico' => 'Norte Centro Histórico',
        'usaquen' => 'Usaquén',
    ];

    public function __invoke(SearchFormData $searchFormData, TrustpilotProfileData $trustpilotProfileData): View
    {
        $publishedProfiles = MariachiListing::query()
            ->with([
                'mariachiProfile.user:id,name,first_name,last_name,status,role',
                'marketplaceCity:id,name,slug,is_active,show_in_search,sort_order',
                'photos',
                'serviceAreas.marketplaceZone:id,marketplace_city_id,name,slug,is_active,show_in_search,sort_order',
                'serviceAreas.marketplaceZone.city:id,name,slug,is_active,show_in_search,sort_order',
                'eventTypes:id,name',
                'serviceTypes:id,name',
                'budgetRanges:id,name',
            ])
            ->published()
            ->latest('updated_at')
            ->get();

        $zones = $this->buildZones($publishedProfiles);
        $featuredProfiles = $publishedProfiles->take(8)->values();
        $cityShowcase = $this->buildCityShowcase($publishedProfiles);
        $searchFormPayload = $searchFormData->forPublishedProfiles($publishedProfiles, $cityShowcase);

        $featuredTags = $this->buildFeaturedTags($featuredProfiles);

        $popularCities = $cityShowcase->take(12)->map(fn (array $city): array => [
            'name' => $city['city'],
            'slug' => Str::slug($city['city']),
        ]);

        $popularEvents = EventType::query()
            ->where('is_active', true)
            ->withCount(['mariachiListings as active_profiles_count' => function ($query): void {
                $query->published();
            }])
            ->orderByDesc('active_profiles_count')
            ->orderBy('name')
            ->take(10)
            ->get(['id', 'name']);

        $popularBudgetRanges = BudgetRange::query()
            ->where('is_active', true)
            ->withCount(['mariachiListings as active_profiles_count' => function ($query): void {
                $query->published();
            }])
            ->orderByDesc('active_profiles_count')
            ->orderBy('name')
            ->take(10)
            ->get(['id', 'name']);

        $latestBlogPosts = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
            ])
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->take(3)
            ->get();

        $trustpilot = $trustpilotProfileData->get();
        $trustpilotReviews = collect($trustpilot['reviews'] ?? [])
            ->map(function ($review): ?array {
                if (! is_array($review)) {
                    return null;
                }

                $stars = (int) ($review['stars'] ?? $review['rating'] ?? 0);
                if (! in_array($stars, [4, 5], true)) {
                    return null;
                }

                $publishedAt = $review['published_at'] ?? $review['created_at'] ?? null;
                $publishedDate = $publishedAt ? CarbonImmutable::parse($publishedAt) : null;

                return [
                    'stars' => $stars,
                    'title' => trim((string) ($review['title'] ?? $review['headline'] ?? '')),
                    'excerpt' => trim((string) ($review['excerpt'] ?? $review['text'] ?? $review['body'] ?? '')),
                    'author' => trim((string) ($review['author'] ?? $review['consumer_name'] ?? '')),
                    'published_at' => $publishedDate?->timestamp ?? 0,
                    'published_label' => $publishedDate?->diffForHumans() ?? '',
                ];
            })
            ->filter()
            ->sortByDesc('published_at')
            ->take(3)
            ->values();

        return view('front.home', [
            'zones' => $zones,
            'featuredProfiles' => $featuredProfiles,
            'featuredTags' => $featuredTags,
            'cityShowcase' => $cityShowcase,
            'popularCities' => $popularCities,
            'popularEvents' => $popularEvents,
            'popularBudgetRanges' => $popularBudgetRanges,
            'latestBlogPosts' => $latestBlogPosts,
            'publishedProfilesCount' => $publishedProfiles->count(),
            'trustpilot' => $trustpilot,
            'trustpilotReviews' => $trustpilotReviews,
            ...$searchFormPayload,
        ]);
    }

    /**
     * @return Collection<int, array{
     *   id:int,
     *   name:string,
     *   slug:string,
     *   count:int,
     *   sort_order:int,
     *   zones:Collection<int, array{id:int,name:string,slug:string,count:int,sort_order:int}>
     * }>
     */
    private function buildSearchLocationTree(Collection $profiles): Collection
    {
        $catalogCities = MarketplaceCity::query()
            ->searchVisible()
            ->get(['id', 'name', 'slug', 'sort_order'])
            ->mapWithKeys(function (MarketplaceCity $city): array {
                $cityName = $this->normalizeLocationLabel((string) $city->name);
                $citySlug = $this->normalizeLocationSlug((string) ($city->slug ?: $cityName));

                if ($citySlug === '') {
                    return [];
                }

                return [
                    $citySlug => [
                        'id' => (int) $city->id,
                        'name' => $cityName,
                        'slug' => $citySlug,
                        'sort_order' => (int) ($city->sort_order ?? 9999),
                    ],
                ];
            });

        $cities = [];

        foreach ($profiles as $profile) {
            if (! $profile instanceof MariachiListing) {
                continue;
            }

            $profileId = (int) $profile->id;
            $primaryCitySlug = '';

            $city = $profile->marketplaceCity;
            if ($city && $city->is_active && $city->show_in_search) {
                $primaryCityName = $this->normalizeLocationLabel((string) $city->name);
                $primaryCitySlug = $this->normalizeLocationSlug((string) ($city->slug ?: $primaryCityName));
                if ($primaryCitySlug !== '') {
                    $catalogCity = $catalogCities->get($primaryCitySlug);
                    $this->ensureSearchCityNode(
                        $cities,
                        $primaryCitySlug,
                        (int) ($catalogCity['id'] ?? $city->id),
                        (string) ($catalogCity['name'] ?? $primaryCityName),
                        (string) ($catalogCity['slug'] ?? $primaryCitySlug),
                        (int) ($catalogCity['sort_order'] ?? $city->sort_order ?? 9999)
                    );
                    $cities[$primaryCitySlug]['profile_ids'][$profileId] = true;
                }
            } elseif (filled($profile->city_name)) {
                $primaryCityName = $this->normalizeLocationLabel((string) $profile->city_name);
                $primaryCitySlug = $this->normalizeLocationSlug($primaryCityName);
                if ($primaryCitySlug !== '') {
                    $catalogCity = $catalogCities->get($primaryCitySlug);
                    $this->ensureSearchCityNode(
                        $cities,
                        $primaryCitySlug,
                        (int) ($catalogCity['id'] ?? 0),
                        (string) ($catalogCity['name'] ?? $primaryCityName),
                        (string) ($catalogCity['slug'] ?? $primaryCitySlug),
                        (int) ($catalogCity['sort_order'] ?? 9999)
                    );
                    $cities[$primaryCitySlug]['profile_ids'][$profileId] = true;
                }
            }

            foreach ($profile->serviceAreas as $serviceArea) {
                /** @var MarketplaceZone|null $zone */
                $zone = $serviceArea->marketplaceZone;
                if ($zone && $zone->is_active && $zone->show_in_search) {
                    $zoneName = $this->normalizeLocationLabel((string) $zone->name);
                    $zoneSlug = $this->normalizeLocationSlug((string) ($zone->slug ?: $zoneName));
                    if ($zoneSlug === '') {
                        continue;
                    }

                    $zoneCity = $zone->city;
                    if ($zoneCity && (! $zoneCity->is_active || ! $zoneCity->show_in_search)) {
                        continue;
                    }

                    $zoneCityName = $this->normalizeLocationLabel((string) ($zoneCity?->name ?: ($profile->city_name ?: '')));
                    $zoneCitySlug = $this->normalizeLocationSlug((string) ($zoneCity?->slug ?: $zoneCityName));
                    if ($zoneCitySlug === '') {
                        continue;
                    }

                    $catalogCity = $catalogCities->get($zoneCitySlug);
                    $this->ensureSearchCityNode(
                        $cities,
                        $zoneCitySlug,
                        (int) ($catalogCity['id'] ?? ($zoneCity?->id ?? 0)),
                        (string) ($catalogCity['name'] ?? $zoneCityName),
                        (string) ($catalogCity['slug'] ?? $zoneCitySlug),
                        (int) ($catalogCity['sort_order'] ?? ($zoneCity?->sort_order ?? 9999))
                    );

                    $cities[$zoneCitySlug]['profile_ids'][$profileId] = true;

                    if (! isset($cities[$zoneCitySlug]['zones'][$zoneSlug])) {
                        $cities[$zoneCitySlug]['zones'][$zoneSlug] = [
                            'id' => (int) $zone->id,
                            'name' => $zoneName,
                            'slug' => $zoneSlug,
                            'sort_order' => (int) ($zone->sort_order ?? 9999),
                            'profile_ids' => [],
                        ];
                    }

                    $cities[$zoneCitySlug]['zones'][$zoneSlug]['profile_ids'][$profileId] = true;
                    continue;
                }

                $legacyZoneName = $this->normalizeLocationLabel((string) $serviceArea->city_name);
                $legacyZoneSlug = $this->normalizeLocationSlug($legacyZoneName);
                if ($legacyZoneSlug === '' || $primaryCitySlug === '' || $legacyZoneSlug === $primaryCitySlug) {
                    continue;
                }

                if (! isset($cities[$primaryCitySlug])) {
                    continue;
                }

                if (! isset($cities[$primaryCitySlug]['zones'][$legacyZoneSlug])) {
                    $cities[$primaryCitySlug]['zones'][$legacyZoneSlug] = [
                        'id' => 0,
                        'name' => $legacyZoneName,
                        'slug' => $legacyZoneSlug,
                        'sort_order' => 9999,
                        'profile_ids' => [],
                    ];
                }

                $cities[$primaryCitySlug]['zones'][$legacyZoneSlug]['profile_ids'][$profileId] = true;
            }
        }

        return collect($cities)
            ->map(function (array $city): array {
                $zones = collect($city['zones'])
                    ->map(function (array $zone): array {
                        return [
                            'id' => (int) $zone['id'],
                            'name' => $zone['name'],
                            'slug' => $zone['slug'],
                            'sort_order' => (int) $zone['sort_order'],
                            'count' => count($zone['profile_ids']),
                        ];
                    })
                    ->filter(fn (array $zone): bool => $zone['count'] > 0)
                    ->sortBy(fn (array $zone): string => str_pad((string) $zone['sort_order'], 8, '0', STR_PAD_LEFT).'|'.$zone['name'])
                    ->values();

                return [
                    'id' => (int) $city['id'],
                    'name' => $city['name'],
                    'slug' => $city['slug'],
                    'count' => count($city['profile_ids']),
                    'sort_order' => (int) $city['sort_order'],
                    'zones' => $zones,
                ];
            })
            ->filter(fn (array $city): bool => $city['count'] > 0)
            ->sortBy(fn (array $city): string => str_pad((string) $city['sort_order'], 8, '0', STR_PAD_LEFT).'|'.$city['name'])
            ->values();
    }

    /**
     * @return Collection<int, array{
     *   id:int,
     *   name:string,
     *   slug:string,
     *   count:int,
     *   sort_order:int,
     *   zones:Collection<int, array{id:int,name:string,slug:string,count:int,sort_order:int}>
     * }>
     */
    private function buildSearchLocationFallback(Collection $cityShowcase): Collection
    {
        return $cityShowcase
            ->map(fn (array $city): array => [
                'id' => 0,
                'name' => (string) $city['city'],
                'slug' => (string) $city['slug'],
                'count' => (int) $city['count'],
                'sort_order' => 9999,
                'zones' => collect(),
            ])
            ->filter(fn (array $city): bool => $city['slug'] !== '')
            ->values();
    }

    /**
     * @param  array<string,array{
     *   id:int,
     *   name:string,
     *   slug:string,
     *   sort_order:int,
     *   profile_ids:array<int,bool>,
     *   zones:array<string,array{id:int,name:string,slug:string,sort_order:int,profile_ids:array<int,bool>}>
     * }>  $cities
     */
    private function ensureSearchCityNode(
        array &$cities,
        string $cityKey,
        int $cityId,
        string $cityName,
        string $citySlug,
        int $sortOrder
    ): void {
        if (! isset($cities[$cityKey])) {
            $cities[$cityKey] = [
                'id' => $cityId,
                'name' => $cityName,
                'slug' => $citySlug,
                'sort_order' => $sortOrder,
                'profile_ids' => [],
                'zones' => [],
            ];

            return;
        }

        if ($cities[$cityKey]['id'] === 0 && $cityId > 0) {
            $cities[$cityKey]['id'] = $cityId;
        }

        if ($cityName !== '') {
            $cities[$cityKey]['name'] = $cityName;
        }

        if ($citySlug !== '') {
            $cities[$cityKey]['slug'] = $citySlug;
        }

        $cities[$cityKey]['sort_order'] = min((int) $cities[$cityKey]['sort_order'], $sortOrder);
    }

    private function buildZones(Collection $profiles): Collection
    {
        $zones = [];

        foreach ($profiles as $profile) {
            if (! $profile instanceof MariachiListing) {
                continue;
            }

            foreach ($profile->serviceAreas as $serviceArea) {
                $zone = $serviceArea->marketplaceZone;

                if (! $zone || ! $zone->is_active || ! $zone->show_in_search) {
                    continue;
                }

                $zoneId = (int) $zone->id;

                if (! isset($zones[$zoneId])) {
                    $cityName = $this->normalizeLocationLabel((string) ($zone->city?->name ?: $profile->city_name));
                    $citySlug = $this->normalizeLocationSlug((string) ($zone->city?->slug ?: $cityName));
                    $zones[$zoneId] = [
                        'id' => $zoneId,
                        'name' => $this->normalizeLocationLabel((string) $zone->name),
                        'slug' => $this->normalizeLocationSlug((string) ($zone->slug ?: $zone->name)),
                        'city_name' => $cityName,
                        'city_slug' => $citySlug,
                        'sort_order' => (int) ($zone->sort_order ?? 0),
                        'cover_path' => null,
                        'profile_ids' => [],
                    ];
                }

                if (! $zones[$zoneId]['cover_path']) {
                    $zonePhoto = $profile->photos->firstWhere('is_featured', true) ?? $profile->photos->first();
                    if ($zonePhoto && filled($zonePhoto->path)) {
                        $zones[$zoneId]['cover_path'] = (string) $zonePhoto->path;
                    }
                }

                $zones[$zoneId]['profile_ids'][(int) $profile->id] = true;
            }
        }

        return collect($zones)
            ->map(function (array $zone): array {
                return [
                    'name' => $zone['name'],
                    'slug' => $zone['slug'],
                    'city_name' => $zone['city_name'],
                    'city_slug' => $zone['city_slug'],
                    'sort_order' => $zone['sort_order'],
                    'cover_url' => $zone['cover_path'] ? asset('storage/'.$zone['cover_path']) : null,
                    'count' => count($zone['profile_ids']),
                ];
            })
            ->filter(fn (array $zone): bool => $zone['count'] > 0)
            ->sortBy(fn (array $zone): string => str_pad((string) $zone['sort_order'], 8, '0', STR_PAD_LEFT).'|'.$zone['name'])
            ->values()
            ->take(8);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function catalogOptionsQuery($query, string $table, callable $publishedFilter)
    {
        $query
            ->where('is_active', true)
            ->whereHas('mariachiListings', $publishedFilter);

        if (Schema::hasColumn($table, 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    private function catalogColumns(string $table): array
    {
        $columns = ['id', 'name'];

        if (Schema::hasColumn($table, 'slug')) {
            $columns[] = 'slug';
        }

        if (Schema::hasColumn($table, 'icon')) {
            $columns[] = 'icon';
        }

        if (Schema::hasColumn($table, 'sort_order')) {
            $columns[] = 'sort_order';
        }

        return $columns;
    }

    private function buildCityShowcase(Collection $profiles): Collection
    {
        $cities = [];

        foreach ($profiles as $profile) {
            if (! $profile instanceof MariachiListing || ! filled($profile->city_name)) {
                continue;
            }

            $catalogCity = $profile->marketplaceCity;
            $city = $this->normalizeLocationLabel((string) ($catalogCity?->name ?: $profile->city_name));
            $slug = $this->normalizeLocationSlug((string) ($catalogCity?->slug ?: $city));

            if ($slug === '') {
                continue;
            }

            if (! isset($cities[$slug])) {
                $cities[$slug] = [
                    'city' => $city,
                    'slug' => $slug,
                    'count' => 0,
                    'profiles' => collect(),
                ];
            }

            $cities[$slug]['count']++;
            $cities[$slug]['profiles']->push($profile);
        }

        return collect($cities)
            ->map(function (array $city): array {
                /** @var Collection<int, MariachiListing> $profiles */
                $profiles = $city['profiles'];
                $sorted = $profiles->sortByDesc('updated_at')->values();

                return [
                    'city' => $city['city'],
                    'slug' => $city['slug'],
                    'count' => $city['count'],
                    'profiles' => $sorted->take(3),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    private function normalizeLocationLabel(string $value): string
    {
        $clean = (string) Str::of($value)->squish();

        if ($clean === '') {
            return '';
        }

        $normalized = (string) Str::of(mb_strtolower($clean))->title();
        $slug = $this->normalizeLocationSlug($normalized);

        return self::LOCATION_LABEL_OVERRIDES[$slug] ?? $normalized;
    }

    private function normalizeLocationSlug(string $value): string
    {
        return Str::slug((string) Str::of($value)->squish());
    }

    private function buildFeaturedTags(Collection $profiles): Collection
    {
        return $profiles
            ->flatMap(function (MariachiListing $profile): Collection {
                return collect()
                    ->merge($profile->eventTypes->pluck('name'))
                    ->merge($profile->serviceTypes->pluck('name'))
                    ->merge($profile->budgetRanges->pluck('name'));
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(6)
            ->map(fn (string $label): array => [
                'label' => $label,
                'slug' => Str::slug($label),
            ])
            ->values();
    }
}
