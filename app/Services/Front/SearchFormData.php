<?php

namespace App\Services\Front;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiListing;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SearchFormData
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

    /**
     * @param  Collection<int, MariachiListing>  $publishedProfiles
     * @param  Collection<int, array{
     *   city:string,
     *   slug:string,
     *   count:int,
     *   profiles:Collection<int, MariachiListing>
     * }>|null  $cityShowcase
     * @return array{
     *   eventTypes:\Illuminate\Support\Collection,
     *   serviceTypes:\Illuminate\Support\Collection,
     *   groupSizeOptions:\Illuminate\Support\Collection,
     *   budgetRanges:\Illuminate\Support\Collection,
     *   searchCityOptions:Collection<int, array{
     *     id:int,
     *     name:string,
     *     slug:string,
     *     count:int,
     *     sort_order:int,
     *     zones:Collection<int, array{id:int,name:string,slug:string,count:int,sort_order:int}>
     *   }>,
     *   countryLandingSlug:string
     * }
     */
    public function forPublishedProfiles(Collection $publishedProfiles, ?Collection $cityShowcase = null): array
    {
        $publishedFilter = fn (Builder $query): Builder => $query->published();

        $searchCityOptions = $this->buildSearchLocationTree($publishedProfiles);
        $resolvedCityShowcase = $cityShowcase ?? $this->buildCityShowcase($publishedProfiles);

        if ($searchCityOptions->isEmpty()) {
            $searchCityOptions = $this->buildSearchLocationFallback($resolvedCityShowcase);
        }

        return [
            'eventTypes' => $this->catalogOptionsQuery(EventType::query(), 'event_types', $publishedFilter)->get(
                $this->catalogColumns('event_types')
            ),
            'serviceTypes' => $this->catalogOptionsQuery(ServiceType::query(), 'service_types', $publishedFilter)->get(
                $this->catalogColumns('service_types')
            ),
            'groupSizeOptions' => $this->catalogOptionsQuery(GroupSizeOption::query(), 'group_size_options', $publishedFilter)->get(
                $this->catalogColumns('group_size_options')
            ),
            'budgetRanges' => $this->catalogOptionsQuery(BudgetRange::query(), 'budget_ranges', $publishedFilter)->get(
                $this->catalogColumns('budget_ranges')
            ),
            'searchCityOptions' => $searchCityOptions->values(),
            'countryLandingSlug' => Str::slug(config('seo.default_country_name', 'Colombia')),
        ];
    }

    /**
     * @return array{
     *   eventTypes:\Illuminate\Support\Collection,
     *   serviceTypes:\Illuminate\Support\Collection,
     *   groupSizeOptions:\Illuminate\Support\Collection,
     *   budgetRanges:\Illuminate\Support\Collection,
     *   searchCityOptions:Collection<int, array{
     *     id:int,
     *     name:string,
     *     slug:string,
     *     count:int,
     *     sort_order:int,
     *     zones:Collection<int, array{id:int,name:string,slug:string,count:int,sort_order:int}>
     *   }>,
     *   countryLandingSlug:string,
     *   cityLinks:Collection<int, array{
     *     id:int,
     *     name:string,
     *     slug:string,
     *     count:int,
     *     sort_order:int,
     *     zones:Collection<int, array{id:int,name:string,slug:string,count:int,sort_order:int}>
     *   }>
     * }
     */
    public function forFallback(): array
    {
        $publishedProfiles = $this->publishedListingsForSearch();
        $cityShowcase = $this->buildCityShowcase($publishedProfiles);
        $payload = $this->forPublishedProfiles($publishedProfiles, $cityShowcase);

        return [
            ...$payload,
            'cityLinks' => $payload['searchCityOptions']->take(12)->values(),
        ];
    }

    /**
     * @return Collection<int, MariachiListing>
     */
    private function publishedListingsForSearch(): Collection
    {
        return MariachiListing::query()
            ->with([
                'marketplaceCity:id,name,slug,is_active,show_in_search,sort_order',
                'serviceAreas.marketplaceZone:id,marketplace_city_id,name,slug,is_active,show_in_search,sort_order',
                'serviceAreas.marketplaceZone.city:id,name,slug,is_active,show_in_search,sort_order',
            ])
            ->published()
            ->latest('updated_at')
            ->get([
                'id',
                'city_name',
                'marketplace_city_id',
                'updated_at',
            ]);
    }

    /**
     * @param  Collection<int, MariachiListing>  $profiles
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
                    ->map(fn (array $zone): array => [
                        'id' => (int) $zone['id'],
                        'name' => $zone['name'],
                        'slug' => $zone['slug'],
                        'count' => count($zone['profile_ids']),
                        'sort_order' => (int) $zone['sort_order'],
                    ])
                    ->filter(fn (array $zone): bool => $zone['count'] > 0)
                    ->sortBy(fn (array $zone): string => str_pad((string) $zone['sort_order'], 8, '0', STR_PAD_LEFT).'|'.$zone['name'])
                    ->values();

                return [
                    'id' => (int) $city['id'],
                    'name' => (string) $city['name'],
                    'slug' => (string) $city['slug'],
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
     * @param  Collection<int, array{
     *   city:string,
     *   slug:string,
     *   count:int,
     *   profiles:Collection<int, MariachiListing>
     * }>  $cityShowcase
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

    /**
     * @param  Builder  $query
     */
    private function catalogOptionsQuery($query, string $table, callable $publishedFilter): Builder
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

    /**
     * @param  Collection<int, MariachiListing>  $profiles
     * @return Collection<int, array{
     *   city:string,
     *   slug:string,
     *   count:int,
     *   profiles:Collection<int, MariachiListing>
     * }>
     */
    private function buildCityShowcase(Collection $profiles): Collection
    {
        $cities = [];

        foreach ($profiles as $profile) {
            if (! filled($profile->city_name)) {
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
}
