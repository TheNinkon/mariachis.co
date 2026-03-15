<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\MariachiListing;
use App\Models\MariachiListingServiceArea;
use App\Models\MarketplaceCity;
use App\Services\Seo\SeoResolver;
use App\Support\PortalHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HtmlSitemapController extends Controller
{
    private const CITY_MIN_LISTINGS = 6;
    private const CITY_MIN_PROFILES = 3;
    private const EVENT_MIN_LISTINGS = 8;
    private const EVENT_MIN_CITIES = 3;
    private const CITY_EVENT_MIN_LISTINGS = 4;
    private const CITY_EVENT_MIN_PROFILES = 2;
    private const ZONE_MIN_LISTINGS = 3;
    private const ZONE_MIN_PROFILES = 2;
    private const SERVICE_MIN_LISTINGS = 8;
    private const SERVICE_MIN_CITIES = 2;

    public function show(Request $request, SeoResolver $seoResolver): View
    {
        $countrySlug = $this->defaultCountrySlug();

        return view('front.html-sitemap', [
            'seo' => $seoResolver->resolve($request, 'static_page', [
                'page_key' => 'html_sitemap',
                'title' => 'Explora mariachis en Colombia',
                'description' => 'Hub curado para descubrir ciudades, eventos, zonas y recursos clave del marketplace con oferta real.',
                'canonical' => route('seo.html-sitemap'),
                'og_type' => 'website',
            ]),
            'pageTitle' => 'Explora mariachis en Colombia',
            'pageLabel' => 'Mapa del sitio',
            'cities' => $this->curatedCities(),
            'events' => $this->curatedEvents(),
            'zonesByCity' => $this->curatedZonesByCity(),
            'cityEventLandings' => $this->curatedCityEventLandings(),
            'services' => $this->curatedServices($countrySlug),
            'resources' => $this->resourceLinks(),
            'partnerLinks' => $this->partnerLinks(),
        ]);
    }

    private function curatedCities(): Collection
    {
        $rows = MariachiListing::query()
            ->published()
            ->selectRaw('marketplace_city_id, city_name, count(*) as listings_count, count(distinct mariachi_profile_id) as profiles_count')
            ->whereNotNull('marketplace_city_id')
            ->groupBy('marketplace_city_id', 'city_name')
            ->get();

        $cityModels = MarketplaceCity::query()
            ->searchVisible()
            ->whereIn('id', $rows->pluck('marketplace_city_id')->filter()->all())
            ->get(['id', 'name', 'slug', 'is_featured', 'sort_order'])
            ->keyBy('id');

        return $rows
            ->map(function (MariachiListing $row) use ($cityModels): ?array {
                $city = $cityModels->get((int) $row->marketplace_city_id);

                if (! $city) {
                    return null;
                }

                $listingsCount = (int) $row->listings_count;
                $profilesCount = (int) $row->profiles_count;

                if (! $this->qualifiesCity($listingsCount, $profilesCount)) {
                    return null;
                }

                $score = $listingsCount + ($profilesCount * 3) + ($city->is_featured ? 6 : 0);

                return [
                    'name' => $city->name,
                    'url' => route('seo.landing.slug', ['slug' => $city->slug]),
                    'slug' => $city->slug,
                    'listings_count' => $listingsCount,
                    'profiles_count' => $profilesCount,
                    'score' => $score,
                ];
            })
            ->filter()
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], $item['listings_count'], $item['profiles_count'], $item['name']))
            ->take(12)
            ->values();
    }

    private function curatedEvents(): Collection
    {
        $rows = MariachiListing::query()
            ->published()
            ->join('event_type_mariachi_listing', 'event_type_mariachi_listing.mariachi_listing_id', '=', 'mariachi_listings.id')
            ->join('event_types', 'event_types.id', '=', 'event_type_mariachi_listing.event_type_id')
            ->where('event_types.is_active', true)
            ->selectRaw('event_types.id, event_types.name, event_types.slug, event_types.icon, count(distinct mariachi_listings.id) as listings_count, count(distinct mariachi_listings.mariachi_profile_id) as profiles_count, count(distinct lower(mariachi_listings.city_name)) as cities_count')
            ->groupBy('event_types.id', 'event_types.name', 'event_types.slug', 'event_types.icon')
            ->get();

        return $rows
            ->map(function ($row): ?array {
                $listingsCount = (int) $row->listings_count;
                $citiesCount = (int) $row->cities_count;

                if (! $this->qualifiesEvent($listingsCount, $citiesCount)) {
                    return null;
                }

                $slug = $row->slug ?: Str::slug($row->name);
                $score = $listingsCount + ($citiesCount * 4) + ((int) $row->profiles_count * 2);

                return [
                    'name' => $row->name,
                    'slug' => $slug,
                    'icon' => $row->icon,
                    'url' => route('seo.landing.slug', ['slug' => $slug]),
                    'listings_count' => $listingsCount,
                    'profiles_count' => (int) $row->profiles_count,
                    'cities_count' => $citiesCount,
                    'score' => $score,
                ];
            })
            ->filter()
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], $item['listings_count'], $item['cities_count'], $item['name']))
            ->take(12)
            ->values();
    }

    private function curatedZonesByCity(): Collection
    {
        $rows = MariachiListingServiceArea::query()
            ->join('mariachi_listings', 'mariachi_listings.id', '=', 'mariachi_listing_service_areas.mariachi_listing_id')
            ->join('marketplace_zones', 'marketplace_zones.id', '=', 'mariachi_listing_service_areas.marketplace_zone_id')
            ->join('marketplace_cities', 'marketplace_cities.id', '=', 'marketplace_zones.marketplace_city_id')
            ->where('marketplace_zones.is_active', true)
            ->where('marketplace_zones.show_in_search', true)
            ->where('marketplace_cities.is_active', true)
            ->where('marketplace_cities.show_in_search', true)
            ->where('mariachi_listings.status', MariachiListing::STATUS_ACTIVE)
            ->where('mariachi_listings.is_active', true)
            ->where('mariachi_listings.review_status', MariachiListing::REVIEW_APPROVED)
            ->selectRaw('marketplace_cities.name as city_name, marketplace_cities.slug as city_slug, marketplace_zones.name as zone_name, marketplace_zones.slug as zone_slug, count(distinct mariachi_listings.id) as listings_count, count(distinct mariachi_listings.mariachi_profile_id) as profiles_count')
            ->groupBy('marketplace_cities.name', 'marketplace_cities.slug', 'marketplace_zones.name', 'marketplace_zones.slug')
            ->get()
            ->map(function ($row): ?array {
                $listingsCount = (int) $row->listings_count;
                $profilesCount = (int) $row->profiles_count;

                if (! $this->qualifiesZone($listingsCount, $profilesCount)) {
                    return null;
                }

                return [
                    'city_name' => $row->city_name,
                    'city_slug' => $row->city_slug,
                    'zone_name' => $row->zone_name,
                    'zone_slug' => $row->zone_slug,
                    'url' => route('seo.landing.city-category', [
                        'citySlug' => $row->city_slug,
                        'scopeSlug' => $row->zone_slug,
                    ]),
                    'listings_count' => $listingsCount,
                    'profiles_count' => $profilesCount,
                    'score' => $listingsCount + ($profilesCount * 3),
                ];
            })
            ->filter()
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], $item['listings_count'], $item['profiles_count'], $item['zone_name']));

        return $rows
            ->groupBy('city_slug')
            ->map(function (Collection $zones): array {
                $first = $zones->first();

                return [
                    'city_name' => $first['city_name'],
                    'city_slug' => $first['city_slug'],
                    'city_url' => route('seo.landing.slug', ['slug' => $first['city_slug']]),
                    'zones' => $zones->take(5)->values(),
                    'score' => $zones->sum('score'),
                ];
            })
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], count($item['zones']), 0, $item['city_name']))
            ->take(6)
            ->values();
    }

    private function curatedCityEventLandings(): Collection
    {
        $rows = MariachiListing::query()
            ->published()
            ->join('event_type_mariachi_listing', 'event_type_mariachi_listing.mariachi_listing_id', '=', 'mariachi_listings.id')
            ->join('event_types', 'event_types.id', '=', 'event_type_mariachi_listing.event_type_id')
            ->leftJoin('marketplace_cities', 'marketplace_cities.id', '=', 'mariachi_listings.marketplace_city_id')
            ->where('event_types.is_active', true)
            ->selectRaw('marketplace_cities.name as indexed_city_name, marketplace_cities.slug as indexed_city_slug, mariachi_listings.city_name as fallback_city_name, event_types.name as event_name, event_types.slug as event_slug, count(distinct mariachi_listings.id) as listings_count, count(distinct mariachi_listings.mariachi_profile_id) as profiles_count')
            ->groupBy(
                'marketplace_cities.name',
                'marketplace_cities.slug',
                'mariachi_listings.city_name',
                'event_types.name',
                'event_types.slug',
            )
            ->get();

        return $rows
            ->map(function ($row): ?array {
                $listingsCount = (int) $row->listings_count;
                $profilesCount = (int) $row->profiles_count;

                if (! $this->qualifiesCityEvent($listingsCount, $profilesCount)) {
                    return null;
                }

                $cityName = (string) ($row->indexed_city_name ?: $row->fallback_city_name);
                $citySlug = filled($row->indexed_city_slug) ? (string) $row->indexed_city_slug : Str::slug($cityName);
                $eventSlug = $row->event_slug ?: Str::slug($row->event_name);

                return [
                    'city_name' => $cityName,
                    'event_name' => $row->event_name,
                    'url' => route('seo.landing.city-category', [
                        'citySlug' => $citySlug,
                        'scopeSlug' => $eventSlug,
                    ]),
                    'listings_count' => $listingsCount,
                    'profiles_count' => $profilesCount,
                    'score' => $listingsCount + ($profilesCount * 3),
                ];
            })
            ->filter()
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], $item['listings_count'], $item['profiles_count'], $item['city_name'].' '.$item['event_name']))
            ->take(12)
            ->values();
    }

    private function curatedServices(string $countrySlug): Collection
    {
        $rows = MariachiListing::query()
            ->published()
            ->join('mariachi_listing_service_type', 'mariachi_listing_service_type.mariachi_listing_id', '=', 'mariachi_listings.id')
            ->join('service_types', 'service_types.id', '=', 'mariachi_listing_service_type.service_type_id')
            ->where('service_types.is_active', true)
            ->selectRaw('service_types.name, service_types.slug, service_types.icon, count(distinct mariachi_listings.id) as listings_count, count(distinct mariachi_listings.mariachi_profile_id) as profiles_count, count(distinct lower(mariachi_listings.city_name)) as cities_count')
            ->groupBy('service_types.name', 'service_types.slug', 'service_types.icon')
            ->get();

        return $rows
            ->map(function ($row) use ($countrySlug): ?array {
                $listingsCount = (int) $row->listings_count;
                $citiesCount = (int) $row->cities_count;

                if (! $this->qualifiesService($listingsCount, $citiesCount)) {
                    return null;
                }

                $slug = $row->slug ?: Str::slug($row->name);

                return [
                    'name' => $row->name,
                    'icon' => $row->icon,
                    'url' => route('seo.landing.slug', ['slug' => $countrySlug]).'?service='.urlencode($slug),
                    'listings_count' => $listingsCount,
                    'cities_count' => $citiesCount,
                    'profiles_count' => (int) $row->profiles_count,
                    'score' => $listingsCount + ($citiesCount * 4),
                ];
            })
            ->filter()
            ->sortBy(fn (array $item): string => $this->rankKey($item['score'], $item['listings_count'], $item['cities_count'], $item['name']))
            ->take(10)
            ->values();
    }

    private function resourceLinks(): Collection
    {
        $links = collect([
            ['label' => 'Blog', 'url' => route('blog.index'), 'description' => 'Guias, ideas y recursos para contratar mariachis en Colombia.'],
            ['label' => 'Ayuda', 'url' => route('static.help'), 'description' => 'Preguntas frecuentes para clientes y mariachis dentro del marketplace.'],
            ['label' => 'Terminos', 'url' => route('static.terms'), 'description' => 'Condiciones generales para usar la plataforma.'],
            ['label' => 'Privacidad', 'url' => route('static.privacy'), 'description' => 'Como tratamos los datos personales y la actividad del usuario.'],
        ]);

        $publishedPost = BlogPost::query()
            ->published()
            ->latest('published_at')
            ->first(['title', 'slug']);

        if ($publishedPost) {
            $links->prepend([
                'label' => 'Ultimo recurso del blog',
                'url' => route('blog.show', ['slug' => $publishedPost->slug]),
                'description' => $publishedPost->title,
            ]);
        }

        return $links->take(5)->values();
    }

    private function partnerLinks(): Collection
    {
        return collect([
            [
                'label' => 'Publica tu anuncio',
                'url' => route('mariachi.register'),
                'description' => 'Crea tu cuenta partner y empieza a publicar en el marketplace.',
            ],
            [
                'label' => 'Acceso partner',
                'url' => PortalHosts::absoluteUrl(PortalHosts::partner(), '/login'),
                'description' => 'Entra al panel para editar anuncios, pagos y solicitudes.',
            ],
            [
                'label' => 'Como funciona',
                'url' => route('static.help'),
                'description' => 'Resumen del flujo para clientes y mariachis dentro de Mariachis.co.',
            ],
        ]);
    }

    private function qualifiesCity(int $listingsCount, int $profilesCount): bool
    {
        return ($listingsCount >= self::CITY_MIN_LISTINGS && $profilesCount >= self::CITY_MIN_PROFILES)
            || ($listingsCount >= 10 && $profilesCount >= 2);
    }

    private function qualifiesEvent(int $listingsCount, int $citiesCount): bool
    {
        return ($listingsCount >= self::EVENT_MIN_LISTINGS && $citiesCount >= self::EVENT_MIN_CITIES)
            || ($listingsCount >= 12 && $citiesCount >= 2);
    }

    private function qualifiesCityEvent(int $listingsCount, int $profilesCount): bool
    {
        return $listingsCount >= self::CITY_EVENT_MIN_LISTINGS
            && $profilesCount >= self::CITY_EVENT_MIN_PROFILES;
    }

    private function qualifiesZone(int $listingsCount, int $profilesCount): bool
    {
        return $listingsCount >= self::ZONE_MIN_LISTINGS
            && $profilesCount >= self::ZONE_MIN_PROFILES;
    }

    private function qualifiesService(int $listingsCount, int $citiesCount): bool
    {
        return ($listingsCount >= self::SERVICE_MIN_LISTINGS && $citiesCount >= self::SERVICE_MIN_CITIES)
            || ($listingsCount >= 12 && $citiesCount >= 1);
    }

    private function rankKey(int $score, int $primaryMetric, int $secondaryMetric, string $label): string
    {
        return sprintf(
            '%05d|%05d|%05d|%s',
            max(0, 99999 - $score),
            max(0, 99999 - $primaryMetric),
            max(0, 99999 - $secondaryMetric),
            mb_strtolower($label)
        );
    }

    private function defaultCountrySlug(): string
    {
        $configuredSlug = array_key_first((array) config('seo.country_pages', []));

        return $configuredSlug ?: Str::slug($this->defaultCountryName());
    }

    private function defaultCountryName(): string
    {
        $configuredCountries = array_keys((array) config('seo.country_pages', []));

        return $configuredCountries !== [] ? Str::headline((string) $configuredCountries[0]) : 'Colombia';
    }
}
