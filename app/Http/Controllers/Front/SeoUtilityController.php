<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\EventType;
use App\Models\MariachiListing;
use App\Models\MariachiListingServiceArea;
use App\Models\MariachiProfile;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\SeoPage;
use App\Services\Seo\SeoPageCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SeoUtilityController extends Controller
{
    public function __construct(private readonly SeoPageCatalog $catalog)
    {
    }

    public function sitemap(Request $request): Response
    {
        $entries = collect()
            ->push([
                'loc' => route('home'),
                'lastmod' => now()->toDateString(),
            ])
            ->push([
                'loc' => route('seo.html-sitemap'),
                'lastmod' => now()->toDateString(),
            ])
            ->merge($this->staticPageEntries())
            ->merge($this->blogEntries())
            ->merge($this->landingEntries())
            ->merge($this->listingEntries())
            ->merge($this->profileEntries())
            ->unique('loc')
            ->values();

        return response()
            ->view('front.seo.sitemap', ['entries' => $entries], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(Request $request): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /partner',
            'Disallow: /auth',
            'Disallow: /login',
            'Disallow: /registro',
            'Disallow: /recuperar-contrasena',
            'Disallow: /restablecer-contrasena',
            'Disallow: /mi-cuenta',
            'Disallow: /cliente',
            'Disallow: /lista-de-deseos',
            'Disallow: /vistos-recientemente',
            '',
            'Sitemap: '.route('seo.sitemap'),
        ];

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string}>
     */
    private function staticPageEntries(): Collection
    {
        $this->catalog->syncDefaults();

        return SeoPage::query()
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->get(['path', 'robots', 'updated_at'])
            ->filter(fn (SeoPage $page): bool => ! str_contains((string) $page->robots, 'noindex'))
            ->map(function (SeoPage $page): array {
                return [
                    'loc' => url($page->path),
                    'lastmod' => optional($page->updated_at)->toDateString(),
                ];
            });
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string}>
     */
    private function blogEntries(): Collection
    {
        $index = collect([[
            'loc' => route('blog.index'),
            'lastmod' => now()->toDateString(),
        ]]);

        $posts = BlogPost::query()
            ->published()
            ->get(['slug', 'updated_at']);

        return $index->merge(
            $posts->map(fn (BlogPost $post): array => [
                'loc' => route('blog.show', ['slug' => $post->slug]),
                'lastmod' => optional($post->updated_at)->toDateString(),
            ])
        );
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string}>
     */
    private function landingEntries(): Collection
    {
        $entries = collect();

        foreach (array_keys((array) config('seo.country_pages', [])) as $countrySlug) {
            $entries->push([
                'loc' => route('seo.landing.slug', ['slug' => $countrySlug]),
                'lastmod' => now()->toDateString(),
            ]);
        }

        $cityIds = MariachiListing::query()
            ->published()
            ->whereNotNull('marketplace_city_id')
            ->distinct()
            ->pluck('marketplace_city_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter();

        $entries = $entries->merge(
            MarketplaceCity::query()
                ->searchVisible()
                ->whereIn('id', $cityIds->all())
                ->get(['name', 'slug', 'updated_at'])
                ->map(fn (MarketplaceCity $city): array => [
                    'loc' => route('seo.landing.slug', ['slug' => $city->slug]),
                    'lastmod' => optional($city->updated_at)->toDateString(),
                ])
        );

        $zoneIds = MariachiListingServiceArea::query()
            ->join('mariachi_listings', 'mariachi_listings.id', '=', 'mariachi_listing_service_areas.mariachi_listing_id')
            ->whereNotNull('mariachi_listing_service_areas.marketplace_zone_id')
            ->where('mariachi_listings.status', MariachiListing::STATUS_ACTIVE)
            ->where('mariachi_listings.is_active', true)
            ->where('mariachi_listings.review_status', MariachiListing::REVIEW_APPROVED)
            ->distinct()
            ->pluck('mariachi_listing_service_areas.marketplace_zone_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter();

        $entries = $entries->merge(
            MarketplaceZone::query()
                ->with('city:id,name,slug')
                ->searchVisible()
                ->whereIn('id', $zoneIds->all())
                ->get(['id', 'marketplace_city_id', 'name', 'slug', 'updated_at'])
                ->filter(fn (MarketplaceZone $zone): bool => filled($zone->city?->slug))
                ->map(fn (MarketplaceZone $zone): array => [
                    'loc' => route('seo.landing.city-category', [
                        'citySlug' => $zone->city->slug,
                        'scopeSlug' => $zone->slug,
                    ]),
                    'lastmod' => optional($zone->updated_at)->toDateString(),
                ])
        );

        $entries = $entries->merge(
            EventType::query()
                ->active()
                ->whereHas('mariachiListings', fn ($query) => $query->published())
                ->get(['name', 'slug', 'updated_at'])
                ->map(fn (EventType $eventType): array => [
                    'loc' => route('seo.landing.slug', ['slug' => $eventType->slug ?: str($eventType->name)->slug()->toString()]),
                    'lastmod' => optional($eventType->updated_at)->toDateString(),
                ])
        );

        return $entries;
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string}>
     */
    private function listingEntries(): Collection
    {
        return MariachiListing::query()
            ->published()
            ->get(['slug', 'updated_at'])
            ->map(fn (MariachiListing $listing): array => [
                'loc' => route('mariachi.public.show', ['slug' => $listing->slug]),
                'lastmod' => optional($listing->updated_at)->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string}>
     */
    private function profileEntries(): Collection
    {
        return MariachiProfile::query()
            ->published()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get(['slug', 'updated_at'])
            ->map(fn (MariachiProfile $profile): array => [
                'loc' => route('mariachi.provider.public.show', ['handle' => $profile->slug]),
                'lastmod' => optional($profile->updated_at)->toDateString(),
            ]);
    }
}
