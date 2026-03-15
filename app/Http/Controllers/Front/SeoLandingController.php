<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BudgetRange;
use App\Models\ClientRecentView;
use App\Models\EventType;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\MariachiListing;
use App\Models\MariachiListingServiceArea;
use App\Models\MariachiReview;
use App\Services\Seo\SeoResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoLandingController extends Controller
{
    public function showBySlug(Request $request, string $slug): View
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        if (! $normalizedSlug || $this->isReservedSeoSlug($normalizedSlug)) {
            abort(404);
        }

        $countryName = $this->resolveCountryNameBySlug($normalizedSlug);

        if ($countryName) {
            return $this->renderLanding($request, 'country', null, null, null, $countryName);
        }

        $eventType = $this->resolveEventTypeBySlug($normalizedSlug);

        if ($eventType) {
            return $this->renderLanding($request, 'category', null, null, $eventType);
        }

        $zoneName = $this->resolveZoneNameBySlug($normalizedSlug);

        if ($zoneName) {
            return $this->renderLanding($request, 'zone', null, $zoneName, null);
        }

        $cityName = $this->resolveCityNameFromSlug($normalizedSlug);

        if (! $cityName) {
            abort(404);
        }

        return $this->renderLanding($request, 'city', $cityName, null, null);
    }

    public function showCityCategory(Request $request, string $citySlug, string $scopeSlug): View
    {
        $normalizedCitySlug = $this->normalizeSlug($citySlug);
        $normalizedScopeSlug = $this->normalizeSlug($scopeSlug);

        if (! $normalizedCitySlug || ! $normalizedScopeSlug) {
            abort(404);
        }

        if ($this->isReservedSeoSlug($normalizedCitySlug) || $this->isReservedSeoSlug($normalizedScopeSlug)) {
            abort(404);
        }

        $cityName = $this->resolveCityNameFromSlug($normalizedCitySlug);

        if (! $cityName) {
            abort(404);
        }

        $eventType = $this->resolveEventTypeBySlug($normalizedScopeSlug);

        if ($eventType) {
            return $this->renderLanding($request, 'city_category', $cityName, null, $eventType);
        }

        $zoneName = $this->resolveZoneNameByCityAndSlug($cityName, $normalizedScopeSlug);

        if (! $zoneName) {
            abort(404);
        }

        return $this->renderLanding($request, 'zone', $cityName, $zoneName, null);
    }

    private function renderLanding(
        Request $request,
        string $mode,
        ?string $cityName,
        ?string $zoneName,
        ?EventType $eventType,
        ?string $countryName = null
    ): View {
        if ($mode === 'country' && ! $countryName) {
            $countryName = $this->defaultCountryName();
        }

        $scopeQuery = $this->publishedListingsQuery();

        if ($countryName) {
            $scopeQuery->whereRaw('LOWER(country) = ?', [mb_strtolower($countryName)]);
        }

        if ($cityName) {
            $scopeQuery->whereRaw('LOWER(city_name) = ?', [mb_strtolower($cityName)]);
        }

        if ($zoneName) {
            $scopeQuery->whereHas('serviceAreas', function (Builder $query) use ($zoneName): void {
                $query->whereRaw('LOWER(city_name) = ?', [mb_strtolower($zoneName)]);
            });
        }

        if ($eventType) {
            $scopeQuery->whereHas('eventTypes', function (Builder $builder) use ($eventType): void {
                $builder->where('event_types.id', $eventType->id);
            });
        }

        $contextProfiles = (clone $scopeQuery)->get();
        $filterOptions = $this->buildFilterOptions($contextProfiles);
        $selectedFilters = $this->resolveSelectedFilters($request, $filterOptions, $zoneName);
        $sortOptions = $this->sortOptions();
        $selectedSort = $this->resolveSort($request, $sortOptions);

        $resultsQuery = (clone $scopeQuery);
        $this->applyFiltersToQuery($resultsQuery, $selectedFilters, $filterOptions);
        $this->applySortToQuery($resultsQuery, $selectedSort);

        $faqProfiles = (clone $resultsQuery)->with('faqs')->get();
        $profiles = $resultsQuery
            ->paginate(12)
            ->withQueryString();

        $currentZoneSlug = $zoneName ? Str::slug($zoneName) : ($selectedFilters['zone'] ?? null);

        $nearbyZones = $filterOptions['zones']
            ->reject(fn (array $zone): bool => $currentZoneSlug && $zone['slug'] === $currentZoneSlug)
            ->take(12)
            ->values();

        $cityReviews = $this->cityReviews($cityName, $zoneName, $eventType, $countryName);
        $recentViews = $this->recentViews($request, $cityName, $zoneName, $eventType, $countryName);
        $faqItems = $this->buildFaqItems($faqProfiles, $cityName, $zoneName, $eventType, $countryName);

        $countryCityStats = $mode === 'country'
            ? $this->cityStatsFromProfiles($contextProfiles)
            : collect();
        $featuredCountryCities = $mode === 'country'
            ? $this->featuredCountryCities($countryCityStats, $countryName, 24)
            : collect();
        $countryCitiesByLetter = $mode === 'country'
            ? $this->groupCitiesByLetter($countryCityStats)
            : collect();

        $popularCities = $this->topCities(12, $countryName);
        $popularEvents = $this->topEventTypes(10);
        $popularBudgetRanges = $this->topBudgetRanges(10);

        $relatedBlogPosts = $this->relatedBlogPosts($cityName, $zoneName, $eventType?->id);
        $topFilterChips = $this->buildTopFilterChips($contextProfiles);

        [$title, $subtitle, $description] = $this->buildSeoTexts($mode, $cityName, $zoneName, $eventType, $contextProfiles, $countryName);
        $canonical = match ($mode) {
            'country' => route('seo.landing.slug', ['slug' => Str::slug($countryName ?: $this->defaultCountryName())]),
            'city' => route('seo.landing.slug', ['slug' => Str::slug((string) $cityName)]),
            'category' => route('seo.landing.slug', ['slug' => (string) ($eventType?->slug ?: Str::slug((string) $eventType?->name))]),
            'city_category' => route('seo.landing.city-category', [
                'citySlug' => Str::slug((string) $cityName),
                'scopeSlug' => (string) ($eventType?->slug ?: Str::slug((string) $eventType?->name)),
            ]),
            default => route('seo.landing.city-category', [
                'citySlug' => Str::slug((string) ($cityName ?: $countryName ?: 'colombia')),
                'scopeSlug' => Str::slug((string) $zoneName),
            ]),
        };
        $catalogCity = $cityName ? $this->resolveMarketplaceCity($cityName) : null;
        $catalogZone = $zoneName ? $this->resolveMarketplaceZone($cityName, $zoneName) : null;
        $entityType = match ($mode) {
            'city' => 'city',
            'zone' => 'zone',
            'category', 'city_category' => $eventType ? 'event_type' : null,
            default => null,
        };
        $entityId = match ($entityType) {
            'city' => $catalogCity?->id,
            'zone' => $catalogZone?->id,
            'event_type' => $eventType?->id,
            default => null,
        };
        $resolvedCityLabel = $cityName ?: ($catalogZone?->city?->name ?: ($countryName ?: $this->defaultCountryName()));
        $seo = app(SeoResolver::class)->resolve($request, 'landing', [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og_type' => 'website',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'country' => $countryName ?: $this->defaultCountryName(),
            'city' => $resolvedCityLabel,
            'city_name' => $resolvedCityLabel,
            'city_slug' => $catalogCity?->slug ?: ($cityName ? Str::slug($cityName) : null),
            'zone' => $zoneName,
            'zone_name' => $zoneName,
            'event' => $eventType?->name ? mb_strtolower($eventType->name) : null,
            'event_name' => $eventType?->name ? mb_strtolower($eventType->name) : null,
            'listing_count' => (string) $contextProfiles->count(),
            'min_price' => $this->formatSeoPrice($contextProfiles->whereNotNull('base_price')->min('base_price')),
            'max_price' => $this->formatSeoPrice($contextProfiles->whereNotNull('base_price')->max('base_price')),
            'canonical_path' => parse_url($canonical, PHP_URL_PATH) ?: null,
        ]);

        return view('front.seo-landing', [
            'mode' => $mode,
            'countryName' => $countryName,
            'countrySlug' => $countryName ? Str::slug($countryName) : null,
            'cityName' => $cityName,
            'citySlug' => $cityName ? Str::slug($cityName) : null,
            'zoneName' => $zoneName,
            'eventType' => $eventType,
            'h1' => $title,
            'subtitle' => $subtitle,
            'seo' => $seo,
            'seoTitle' => $title,
            'seoDescription' => $description,
            'profiles' => $profiles,
            'totalResults' => (int) $profiles->total(),
            'contextResults' => $contextProfiles->count(),
            'filterOptions' => $filterOptions,
            'selectedFilters' => $selectedFilters,
            'sortOptions' => $sortOptions,
            'selectedSort' => $selectedSort,
            'nearbyZones' => $nearbyZones,
            'cityReviews' => $cityReviews,
            'recentViews' => $recentViews,
            'faqItems' => $faqItems,
            'featuredCountryCities' => $featuredCountryCities,
            'countryCitiesByLetter' => $countryCitiesByLetter,
            'popularCities' => $popularCities,
            'popularEvents' => $popularEvents,
            'popularBudgetRanges' => $popularBudgetRanges,
            'relatedBlogPosts' => $relatedBlogPosts,
            'topFilterChips' => $topFilterChips,
            'viewMode' => $this->resolveViewMode($request),
        ]);
    }

    private function publishedListingsQuery(): Builder
    {
        return MariachiListing::query()
            ->with([
                'mariachiProfile.user:id,name,first_name,last_name,status,role',
                'photos',
                'serviceAreas',
                'faqs',
                'eventTypes:id,name',
                'serviceTypes:id,name',
                'groupSizeOptions:id,name,sort_order',
                'budgetRanges:id,name',
            ])
            ->published()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '');
    }

    /**
     * @return array{
     *   events:Collection<int,array{name:string,slug:string,count:int}>,
     *   cities:Collection<int,array{name:string,slug:string,count:int}>,
     *   services:Collection<int,array{name:string,slug:string,count:int}>,
     *   groups:Collection<int,array{name:string,slug:string,count:int}>,
     *   budgets:Collection<int,array{name:string,slug:string,count:int}>,
     *   zones:Collection<int,array{name:string,slug:string,count:int}>
     * }
     */
    private function buildFilterOptions(Collection $profiles): array
    {
        return [
            'events' => $this->countOptionsByName($profiles->flatMap(fn (MariachiListing $profile): Collection => $profile->eventTypes->pluck('name'))),
            'cities' => $this->countOptionsByName($profiles->pluck('city_name')),
            'services' => $this->countOptionsByName($profiles->flatMap(fn (MariachiListing $profile): Collection => $profile->serviceTypes->pluck('name'))),
            'groups' => $this->countOptionsByName($profiles->flatMap(fn (MariachiListing $profile): Collection => $profile->groupSizeOptions->pluck('name'))),
            'budgets' => $this->countOptionsByName($profiles->flatMap(fn (MariachiListing $profile): Collection => $profile->budgetRanges->pluck('name'))),
            'zones' => $this->countOptionsByName($profiles->flatMap(fn (MariachiListing $profile): Collection => $profile->serviceAreas->pluck('city_name'))),
        ];
    }

    /**
     * @param  Collection<int, string>  $names
     * @return Collection<int, array{name:string,slug:string,count:int}>
     */
    private function countOptionsByName(Collection $names): Collection
    {
        return $names
            ->filter()
            ->countBy()
            ->sortDesc()
            ->map(fn (int $count, string $name): array => [
                'name' => $name,
                'slug' => Str::slug($name),
                'count' => $count,
            ])
            ->values();
    }

    /**
     * @param  array{
     *   events:Collection<int,array{name:string,slug:string,count:int}>,
     *   cities:Collection<int,array{name:string,slug:string,count:int}>,
     *   services:Collection<int,array{name:string,slug:string,count:int}>,
     *   groups:Collection<int,array{name:string,slug:string,count:int}>,
     *   budgets:Collection<int,array{name:string,slug:string,count:int}>,
     *   zones:Collection<int,array{name:string,slug:string,count:int}>
     * }  $filterOptions
     * @return array{event:?string,city:?string,service:?string,group:?string,budget:?string,zone:?string}
     */
    private function resolveSelectedFilters(Request $request, array $filterOptions, ?string $zoneName): array
    {
        $selected = [
            'event' => $this->sanitizeSelectedSlug((string) $request->query('event', ''), $filterOptions['events']),
            'city' => $this->sanitizeSelectedSlug((string) $request->query('city', ''), $filterOptions['cities']),
            'service' => $this->sanitizeSelectedSlug((string) $request->query('service', ''), $filterOptions['services']),
            'group' => $this->sanitizeSelectedSlug((string) $request->query('group', ''), $filterOptions['groups']),
            'budget' => $this->sanitizeSelectedSlug((string) $request->query('budget', ''), $filterOptions['budgets']),
            'zone' => $this->sanitizeSelectedSlug((string) $request->query('zone', ''), $filterOptions['zones']),
        ];

        if ($zoneName && ! $selected['zone']) {
            $zoneSlug = Str::slug($zoneName);
            $zoneIsAvailable = $filterOptions['zones']->contains(fn (array $zone): bool => $zone['slug'] === $zoneSlug);
            if ($zoneIsAvailable) {
                $selected['zone'] = $zoneSlug;
            }
        }

        return $selected;
    }

    /**
     * @param  Collection<int,array{name:string,slug:string,count:int}>  $options
     */
    private function sanitizeSelectedSlug(string $value, Collection $options): ?string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            return null;
        }

        return $options->contains(fn (array $item): bool => $item['slug'] === $slug)
            ? $slug
            : null;
    }

    /**
     * @param  array{event:?string,city:?string,service:?string,group:?string,budget:?string,zone:?string}  $selectedFilters
     * @param  array{
     *   events:Collection<int,array{name:string,slug:string,count:int}>,
     *   cities:Collection<int,array{name:string,slug:string,count:int}>,
     *   services:Collection<int,array{name:string,slug:string,count:int}>,
     *   groups:Collection<int,array{name:string,slug:string,count:int}>,
     *   budgets:Collection<int,array{name:string,slug:string,count:int}>,
     *   zones:Collection<int,array{name:string,slug:string,count:int}>
     * }  $filterOptions
     */
    private function applyFiltersToQuery(Builder $query, array $selectedFilters, array $filterOptions): void
    {
        $this->applyRelationFilter($query, 'eventTypes', $selectedFilters['event'], $filterOptions['events']);
        $this->applyRelationFilter($query, 'serviceTypes', $selectedFilters['service'], $filterOptions['services']);
        $this->applyRelationFilter($query, 'groupSizeOptions', $selectedFilters['group'], $filterOptions['groups']);
        $this->applyRelationFilter($query, 'budgetRanges', $selectedFilters['budget'], $filterOptions['budgets']);

        if ($selectedFilters['city']) {
            $cityName = $filterOptions['cities']
                ->firstWhere('slug', $selectedFilters['city'])['name']
                ?? null;

            if ($cityName) {
                $query->whereRaw('LOWER(city_name) = ?', [mb_strtolower($cityName)]);
            }
        }

        if ($selectedFilters['zone']) {
            $zoneName = $filterOptions['zones']
                ->firstWhere('slug', $selectedFilters['zone'])['name']
                ?? null;

            if ($zoneName) {
                $query->whereHas('serviceAreas', function (Builder $builder) use ($zoneName): void {
                    $builder->whereRaw('LOWER(city_name) = ?', [mb_strtolower($zoneName)]);
                });
            }
        }
    }

    /**
     * @param  Collection<int,array{name:string,slug:string,count:int}>  $options
     */
    private function applyRelationFilter(Builder $query, string $relation, ?string $slug, Collection $options): void
    {
        if (! $slug) {
            return;
        }

        $name = $options->firstWhere('slug', $slug)['name'] ?? null;

        if (! $name) {
            return;
        }

        $query->whereHas($relation, function (Builder $builder) use ($name): void {
            $builder->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        });
    }

    private function cityReviews(?string $cityName, ?string $zoneName, ?EventType $eventType, ?string $countryName = null): Collection
    {
        $query = MariachiReview::query()
            ->with([
                'clientUser:id,name,first_name,last_name',
                'mariachiProfile:id,user_id,business_name,slug,city_name',
                'mariachiListing:id,mariachi_profile_id,title,slug,city_name',
                'mariachiProfile.user:id,name,first_name,last_name',
                'photos',
            ])
            ->publicVisible();

        if ($countryName) {
            $query->whereHas('mariachiListing', function (Builder $builder) use ($countryName): void {
                $builder->whereRaw('LOWER(country) = ?', [mb_strtolower($countryName)]);
            });
        }

        if ($cityName) {
            $query->whereHas('mariachiListing', function (Builder $builder) use ($cityName): void {
                $builder->whereRaw('LOWER(city_name) = ?', [mb_strtolower($cityName)]);
            });
        }

        if ($zoneName) {
            $query->whereHas('mariachiListing.serviceAreas', function (Builder $builder) use ($zoneName): void {
                $builder->whereRaw('LOWER(city_name) = ?', [mb_strtolower($zoneName)]);
            });
        }

        if ($eventType) {
            $query->whereHas('mariachiListing.eventTypes', function (Builder $builder) use ($eventType): void {
                $builder->where('event_types.id', $eventType->id);
            });
        }

        return $query
            ->latest('moderated_at')
            ->latest('created_at')
            ->take(6)
            ->get();
    }

    private function recentViews(Request $request, ?string $cityName, ?string $zoneName, ?EventType $eventType, ?string $countryName = null): Collection
    {
        $user = $request->user();

        if (! $user || ! $user->isClient()) {
            return collect();
        }

        $query = ClientRecentView::query()
            ->with([
                'mariachiListing.mariachiProfile.user:id,name,first_name,last_name,status,role',
                'mariachiListing.photos',
                'mariachiListing.eventTypes:id,name',
                'mariachiProfile.user:id,name,first_name,last_name,status,role',
                'mariachiProfile.photos',
                'mariachiProfile.eventTypes:id,name',
            ])
            ->where('user_id', $user->id)
            ->whereHas('mariachiListing', function (Builder $builder) use ($cityName, $zoneName, $eventType, $countryName): void {
                $builder->published();

                if ($countryName) {
                    $builder->whereRaw('LOWER(country) = ?', [mb_strtolower($countryName)]);
                }

                if ($cityName) {
                    $builder->whereRaw('LOWER(city_name) = ?', [mb_strtolower($cityName)]);
                }

                if ($zoneName) {
                    $builder->whereHas('serviceAreas', function (Builder $serviceAreaQuery) use ($zoneName): void {
                        $serviceAreaQuery->whereRaw('LOWER(city_name) = ?', [mb_strtolower($zoneName)]);
                    });
                }

                if ($eventType) {
                    $builder->whereHas('eventTypes', function (Builder $eventTypeQuery) use ($eventType): void {
                        $eventTypeQuery->where('event_types.id', $eventType->id);
                    });
                }
            })
            ->latest('last_viewed_at')
            ->take(6);

        return $query->get();
    }

    /**
     * @param  Collection<int, MariachiListing>  $profiles
     * @return array<int, array{question:string,answer:string}>
     */
    private function buildFaqItems(Collection $profiles, ?string $cityName, ?string $zoneName, ?EventType $eventType, ?string $countryName = null): array
    {
        $listingFaqs = $profiles
            ->flatMap(function (MariachiListing $profile): Collection {
                $faqs = $profile->relationLoaded('faqs')
                    ? $profile->faqs
                    : $profile->faqs()->orderBy('sort_order')->get();

                return $faqs
                    ->where('is_visible', true)
                    ->map(fn ($faq): array => [
                        'question' => trim((string) $faq->question),
                        'answer' => trim((string) $faq->answer),
                    ])
                    ->filter(fn (array $faq): bool => $faq['question'] !== '' && $faq['answer'] !== '');
            })
            ->groupBy(fn (array $faq): string => Str::of($faq['question'])->lower()->squish()->toString())
            ->map(function (Collection $items): ?array {
                $question = $items->first()['question'] ?? null;
                $answer = $items
                    ->groupBy(fn (array $faq): string => Str::of($faq['answer'])->lower()->squish()->toString())
                    ->sortByDesc(fn (Collection $answers): int => $answers->count())
                    ->first()?->first()['answer'] ?? null;

                if (! $question || ! $answer) {
                    return null;
                }

                return [
                    'question' => $question,
                    'answer' => $answer,
                    'count' => $items->count(),
                ];
            })
            ->filter()
            ->sortByDesc('count')
            ->take(4)
            ->values()
            ->map(fn (array $faq): array => [
                'question' => $faq['question'],
                'answer' => $faq['answer'],
            ])
            ->all();

        if ($listingFaqs !== []) {
            return $listingFaqs;
        }

        $cityLabel = $cityName ?: ($countryName ?: 'tu ciudad');
        $zoneLabel = $zoneName ?: $cityLabel;
        $eventLabel = $eventType?->name ? mb_strtolower($eventType->name) : 'eventos privados y corporativos';

        return [
            [
                'question' => 'Cuanto cuesta contratar mariachis en '.$cityLabel.'?',
                'answer' => 'El valor depende del numero de integrantes, duracion, repertorio y desplazamiento. En esta pagina puedes comparar perfiles con precio base y cotizar directo.',
            ],
            [
                'question' => 'Con cuanta anticipacion debo reservar en '.$zoneLabel.'?',
                'answer' => 'Para fines de semana y fechas especiales, se recomienda reservar con varios dias de anticipacion para asegurar disponibilidad.',
            ],
            [
                'question' => 'Puedo encontrar mariachis para '.$eventLabel.'?',
                'answer' => 'Si. Usa los filtros por tipo de evento, servicio, tamano del grupo y presupuesto para acotar opciones segun tu necesidad.',
            ],
            [
                'question' => 'Como contacto al mariachi?',
                'answer' => 'Cada anuncio tiene acceso directo para solicitar cotizacion y conversar. Asi la resena y la contratacion nacen de una interaccion real.',
            ],
        ];
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function buildSeoTexts(
        string $mode,
        ?string $cityName,
        ?string $zoneName,
        ?EventType $eventType,
        Collection $contextProfiles,
        ?string $countryName = null
    ): array {
        $countryLabel = $countryName ?: $this->defaultCountryName();
        $eventPhrase = $this->buildSeoEventPhrase($contextProfiles, $eventType);

        if ($mode === 'country') {
            $title = 'Mariachis en '.$countryLabel;
            $subtitle = '';
            $description = 'Encuentra mariachis en '.$countryLabel.' y explora anuncios por ciudad, tipo de evento y presupuesto con datos reales del marketplace.';

            return [$title, $subtitle, $description];
        }

        if ($mode === 'zone') {
            $locationLabel = $zoneName ?: 'la zona';

            if ($cityName) {
                $locationLabel .= ', '.$cityName;
            }

            $title = 'Los mejores mariachis en '.$locationLabel.' para '.$eventPhrase;
            $subtitle = '';
            $description = 'Descubre mariachis en '.$locationLabel.' para '.$eventPhrase.'. Compara anuncios activos, precios y disponibilidad real.';

            return [$title, $subtitle, $description];
        }

        if ($mode === 'city_category' && $cityName && $eventType) {
            $eventLabel = mb_strtolower($eventType->name);
            $title = 'Los mejores mariachis en '.$cityName.' para '.$eventLabel;
            $subtitle = '';
            $description = 'Encuentra mariachis en '.$cityName.' para '.$eventLabel.' con anuncios activos, precios y contacto directo.';

            return [$title, $subtitle, $description];
        }

        if ($mode === 'category' && $eventType) {
            $eventLabel = mb_strtolower($eventType->name);
            $title = 'Los mejores mariachis en '.$countryLabel.' para '.$eventLabel;
            $subtitle = '';
            $description = 'Explora anuncios activos de mariachis en '.$countryLabel.' para '.$eventLabel.' por ciudad, con precio base y disponibilidad.';

            return [$title, $subtitle, $description];
        }

        $cityLabel = $cityName ?: $countryLabel;
        $title = 'Los mejores mariachis en '.$cityLabel.' para '.$eventPhrase;
        $subtitle = '';
        $description = 'Encuentra y contrata mariachis en '.$cityLabel.' para '.$eventPhrase.'. Compara anuncios reales, precios y disponibilidad en un solo lugar.';

        return [$title, $subtitle, $description];
    }

    private function buildSeoEventPhrase(Collection $contextProfiles, ?EventType $eventType, int $limit = 3): string
    {
        $eventLabels = $this->topEventLabelsFromProfiles($contextProfiles, $limit);

        if ($eventType?->name) {
            array_unshift($eventLabels, mb_strtolower(trim($eventType->name)));
        }

        $eventLabels = collect($eventLabels)
            ->filter(fn (string $label): bool => $label !== '')
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        if ($eventLabels === []) {
            $eventLabels = $this->topEventTypes($limit)
                ->pluck('name')
                ->map(fn (string $name): string => mb_strtolower(trim($name)))
                ->filter()
                ->values()
                ->all();
        }

        if ($eventLabels === []) {
            return 'eventos';
        }

        return $this->joinLabelsForSeo($eventLabels);
    }

    /**
     * @return array<int,string>
     */
    private function topEventLabelsFromProfiles(Collection $profiles, int $limit = 3): array
    {
        return $profiles
            ->flatMap(fn (MariachiListing $profile): Collection => $profile->eventTypes->pluck('name'))
            ->filter()
            ->map(fn (string $name): string => mb_strtolower(trim($name)))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<int,string>  $labels
     */
    private function joinLabelsForSeo(array $labels): string
    {
        if (count($labels) === 1) {
            return $labels[0];
        }

        if (count($labels) === 2) {
            return $labels[0].' y '.$labels[1];
        }

        $lastLabel = array_pop($labels);

        return implode(', ', $labels).' y '.$lastLabel;
    }

    private function defaultCountryName(): string
    {
        $configuredCountryName = trim((string) config('seo.default_country_name', 'Colombia'));

        return $configuredCountryName !== ''
            ? $configuredCountryName
            : 'Colombia';
    }

    private function resolveCountryNameBySlug(string $slug): ?string
    {
        $countryPages = collect(config('seo.country_pages', []))
            ->mapWithKeys(function (mixed $countryName, mixed $countrySlug): array {
                $normalizedSlug = Str::slug((string) $countrySlug);
                $normalizedCountryName = trim((string) $countryName);

                if ($normalizedSlug === '' || $normalizedCountryName === '') {
                    return [];
                }

                return [$normalizedSlug => $normalizedCountryName];
            });

        if ($countryPages->isEmpty()) {
            $defaultCountryName = $this->defaultCountryName();
            $countryPages = collect([Str::slug($defaultCountryName) => $defaultCountryName]);
        }

        return $countryPages->get($slug);
    }

    /**
     * @return Collection<int,array{name:string,slug:string,count:int}>
     */
    private function cityStatsFromProfiles(Collection $profiles): Collection
    {
        return $profiles
            ->filter(fn (MariachiListing $profile): bool => filled($profile->city_name))
            ->groupBy(fn (MariachiListing $profile): string => mb_strtolower(trim((string) $profile->city_name)))
            ->map(function (Collection $items): array {
                $displayName = $items
                    ->pluck('city_name')
                    ->filter()
                    ->map(fn (string $city): string => trim($city))
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                $cityName = trim((string) ($displayName ?: $items->first()->city_name));

                return [
                    'name' => $cityName,
                    'slug' => Str::slug($cityName),
                    'count' => $items->count(),
                ];
            })
            ->filter(fn (array $city): bool => $city['slug'] !== '')
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @param  Collection<int,array{name:string,slug:string,count:int}>  $cityStats
     * @return Collection<int,array{name:string,slug:string,count:int}>
     */
    private function featuredCountryCities(Collection $cityStats, ?string $countryName, int $limit = 24): Collection
    {
        $countrySlug = Str::slug((string) $countryName);
        $configuredFeatured = collect(config('seo.country_featured_cities.'.$countrySlug, []))
            ->map(fn (mixed $value): string => Str::slug((string) $value))
            ->filter()
            ->values();

        $featuredCities = $configuredFeatured
            ->map(fn (string $slug): ?array => $cityStats->firstWhere('slug', $slug))
            ->filter()
            ->values();

        $featuredSlugs = $featuredCities->pluck('slug')->all();

        return $featuredCities
            ->merge($cityStats->reject(fn (array $city): bool => in_array($city['slug'], $featuredSlugs, true)))
            ->take($limit)
            ->values();
    }

    /**
     * @param  Collection<int,array{name:string,slug:string,count:int}>  $cityStats
     * @return Collection<string,Collection<int,array{name:string,slug:string,count:int}>>
     */
    private function groupCitiesByLetter(Collection $cityStats): Collection
    {
        return $cityStats
            ->sortBy('name')
            ->groupBy(function (array $city): string {
                $name = trim((string) ($city['name'] ?? ''));
                $firstCharacter = $name !== ''
                    ? mb_substr($name, 0, 1)
                    : '';
                $letter = mb_strtoupper($firstCharacter);

                return $letter !== '' ? $letter : '#';
            })
            ->sortKeys()
            ->map(fn (Collection $items): Collection => $items->values());
    }

    private function resolveEventTypeBySlug(string $slug): ?EventType
    {
        $columns = ['id', 'name'];
        if (Schema::hasColumn('event_types', 'slug')) {
            $columns[] = 'slug';
        }

        return EventType::query()
            ->where('is_active', true)
            ->get($columns)
            ->first(fn (EventType $eventType): bool => (($eventType->slug ?? null) ?: Str::slug($eventType->name)) === $slug);
    }

    private function resolveCityNameFromSlug(string $slug): ?string
    {
        return $this->publishedCityNames()
            ->first(fn (string $city): bool => Str::slug($city) === $slug);
    }

    private function resolveMarketplaceCity(string $cityName): ?MarketplaceCity
    {
        return MarketplaceCity::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)])
            ->first();
    }

    private function resolveZoneNameBySlug(string $zoneSlug): ?string
    {
        return MariachiListingServiceArea::query()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->whereHas('listing', function (Builder $query): void {
                $query->published();
            })
            ->pluck('city_name')
            ->unique()
            ->values()
            ->first(fn (string $zone): bool => Str::slug($zone) === $zoneSlug);
    }

    private function resolveZoneNameByCityAndSlug(string $cityName, string $zoneSlug): ?string
    {
        $profiles = $this->publishedListingsQuery()
            ->whereRaw('LOWER(city_name) = ?', [mb_strtolower($cityName)])
            ->get();

        return $profiles
            ->flatMap(fn (MariachiListing $profile): Collection => $profile->serviceAreas->pluck('city_name'))
            ->filter()
            ->unique()
            ->values()
            ->first(fn (string $zone): bool => Str::slug($zone) === $zoneSlug);
    }

    private function resolveMarketplaceZone(?string $cityName, string $zoneName): ?MarketplaceZone
    {
        return MarketplaceZone::query()
            ->with('city:id,name,slug')
            ->when($cityName, function (Builder $query) use ($cityName): void {
                $query->whereHas('city', function (Builder $cityQuery) use ($cityName): void {
                    $cityQuery->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)]);
                });
            })
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($zoneName)])
            ->orderBy('id')
            ->first();
    }

    private function publishedCityNames(): Collection
    {
        return MariachiListing::query()
            ->published()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->pluck('city_name')
            ->unique()
            ->values();
    }

    private function topCities(int $limit, ?string $countryName = null): Collection
    {
        $query = MariachiListing::query()
            ->published();

        if ($countryName) {
            $query->whereRaw('LOWER(country) = ?', [mb_strtolower($countryName)]);
        }

        return $query
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->pluck('city_name')
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->map(fn (int $count, string $city): array => [
                'name' => $city,
                'slug' => Str::slug($city),
                'count' => $count,
            ])
            ->values();
    }

    private function formatSeoPrice(mixed $value): string
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return 'cotización directa';
        }

        return '$'.number_format((float) $value, 0, ',', '.').' COP';
    }

    private function topEventTypes(int $limit): Collection
    {
        return EventType::query()
            ->where('is_active', true)
            ->withCount(['mariachiListings as active_profiles_count' => function (Builder $query): void {
                $query->published();
            }])
            ->orderByDesc('active_profiles_count')
            ->orderBy('name')
            ->take($limit)
            ->get(['id', 'name']);
    }

    private function topBudgetRanges(int $limit): Collection
    {
        return BudgetRange::query()
            ->where('is_active', true)
            ->withCount(['mariachiListings as active_profiles_count' => function (Builder $query): void {
                $query->published();
            }])
            ->orderByDesc('active_profiles_count')
            ->orderBy('name')
            ->take($limit)
            ->get(['id', 'name']);
    }

    private function relatedBlogPosts(?string $cityName, ?string $zoneName, ?int $eventTypeId): Collection
    {
        $query = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
            ])
            ->published()
            ->latest('published_at')
            ->latest('id');

        if ($cityName || $zoneName || $eventTypeId) {
            $query->where(function (Builder $builder) use ($cityName, $zoneName, $eventTypeId): void {
                if ($cityName) {
                    $builder->orWhereHas('cities', function (Builder $cityQuery) use ($cityName): void {
                        $cityQuery->whereRaw('LOWER(blog_cities.name) = ?', [mb_strtolower($cityName)]);
                    });
                }

                if ($zoneName) {
                    $builder->orWhereHas('zones', function (Builder $zoneQuery) use ($zoneName): void {
                        $zoneQuery->whereRaw('LOWER(blog_zones.name) = ?', [mb_strtolower($zoneName)]);
                    });
                }

                if ($eventTypeId) {
                    $builder->orWhereHas('eventTypes', function (Builder $eventTypeQuery) use ($eventTypeId): void {
                        $eventTypeQuery->where('event_types.id', $eventTypeId);
                    });
                }
            });
        }

        return $query
            ->take(4)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'city_name', 'zone_name', 'event_type_id', 'published_at']);
    }

    private function resolveViewMode(Request $request): string
    {
        $view = (string) $request->query('view', 'gallery');

        return in_array($view, ['list', 'gallery', 'map'], true)
            ? $view
            : 'gallery';
    }

    /**
     * @param  array<string,string>  $sortOptions
     */
    private function resolveSort(Request $request, array $sortOptions): string
    {
        $sort = Str::slug((string) $request->query('sort', 'featured'), '_');

        return array_key_exists($sort, $sortOptions)
            ? $sort
            : 'featured';
    }

    /**
     * @return array<string,string>
     */
    private function sortOptions(): array
    {
        return [
            'featured' => 'Destacados',
            'price_asc' => 'Precio (de menor a mayor)',
            'price_desc' => 'Precio (de mayor a menor)',
            'completion_desc' => 'Perfil mas completo',
            'newest' => 'Novedades',
        ];
    }

    private function applySortToQuery(Builder $query, string $selectedSort): void
    {
        match ($selectedSort) {
            'price_asc' => $query
                ->orderByRaw('CASE WHEN base_price IS NULL THEN 1 ELSE 0 END')
                ->orderBy('base_price')
                ->orderByDesc('updated_at'),
            'price_desc' => $query
                ->orderByRaw('CASE WHEN base_price IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('base_price')
                ->orderByDesc('updated_at'),
            'completion_desc' => $query
                ->orderByDesc('listing_completion')
                ->orderByDesc('updated_at'),
            'newest' => $query
                ->orderByDesc('created_at'),
            default => $query
                ->orderByDesc('updated_at')
                ->orderByDesc('listing_completion'),
        };
    }

    private function buildTopFilterChips(Collection $profiles): Collection
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
            ->take(8)
            ->map(fn (int $count, string $label): array => [
                'label' => $label,
                'slug' => Str::slug($label),
                'count' => $count,
            ])
            ->values();
    }

    private function normalizeSlug(string $value): ?string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : null;
    }

    private function isReservedSeoSlug(string $slug): bool
    {
        static $reservedSlugs = null;

        if ($reservedSlugs === null) {
            $reservedSlugs = collect(config('seo.reserved_slugs', []))
                ->map(fn (mixed $value): string => Str::slug((string) $value))
                ->filter()
                ->values()
                ->all();
        }

        return in_array($slug, $reservedSlugs, true);
    }
}
