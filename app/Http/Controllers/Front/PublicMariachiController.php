<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ClientFavorite;
use App\Models\ClientRecentView;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\MariachiReview;
use App\Services\MariachiProfileStatsService;
use App\Services\Seo\SeoResolver;
use App\Services\SubscriptionCapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicMariachiController extends Controller
{
    public function show(
        Request $request,
        string $slug,
        MariachiProfileStatsService $statsService,
        SubscriptionCapabilityService $capabilityService,
        SeoResolver $seoResolver
    ): View
    {
        $profile = MariachiListing::query()
            ->with([
                'mariachiProfile.user:id,name,first_name,last_name,email,phone,status,role',
                'photos',
                'videos',
                'serviceAreas',
                'faqs',
                'eventTypes:id,name',
                'serviceTypes:id,name',
                'groupSizeOptions:id,name,sort_order',
                'budgetRanges:id,name',
            ])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();
        $providerProfile = $profile->mariachiProfile;
        $planCapabilities = $providerProfile
            ? $capabilityService->resolveCapabilities($providerProfile)
            : $capabilityService->resolveCapabilities(new MariachiProfile());

        $name = $profile->business_name ?: $profile->user?->display_name;
        $city = $profile->city_name ?: 'Colombia';
        $citySlug = Str::slug($city);

        $relatedProfiles = MariachiListing::query()
            ->with(['mariachiProfile.user:id,name,first_name,last_name', 'photos', 'eventTypes:id,name'])
            ->published()
            ->where('id', '!=', $profile->id)
            ->whereRaw('LOWER(city_name) = ?', [mb_strtolower($city)])
            ->take(10)
            ->get();

        $publicReviews = MariachiReview::query()
            ->with([
                'clientUser:id,name,first_name,last_name',
                'photos',
            ])
            ->where(function ($query) use ($profile): void {
                $query->where('mariachi_listing_id', $profile->id)
                    ->orWhere(function ($nestedQuery) use ($profile): void {
                        $nestedQuery->whereNull('mariachi_listing_id')
                            ->where('mariachi_profile_id', $profile->mariachi_profile_id);
                    });
            })
            ->publicVisible()
            ->latest('moderated_at')
            ->latest('created_at')
            ->get();

        $reviewsTotal = $publicReviews->count();
        $averageRating = round((float) ($publicReviews->avg('rating') ?? 0), 1);
        $ratingDistribution = collect([5, 4, 3, 2, 1])
            ->mapWithKeys(fn (int $rating): array => [$rating => (int) $publicReviews->where('rating', $rating)->count()]);

        $reviewPhotoGallery = $publicReviews
            ->flatMap(fn (MariachiReview $review) => $review->photos->map(fn ($photo): array => [
                'src' => asset('storage/'.$photo->path),
                'review_id' => $review->id,
            ]))
            ->take(24)
            ->values();

        $galleryPhotos = $profile->photos
            ->values();
        $featuredPhoto = $galleryPhotos->firstWhere('is_featured', true) ?? $galleryPhotos->first();
        $secondaryPhotos = $galleryPhotos
            ->filter(fn ($photo): bool => ! $featuredPhoto || $photo->id !== $featuredPhoto->id)
            ->values();

        $coverageAreas = $profile->serviceAreas
            ->pluck('city_name')
            ->filter()
            ->unique()
            ->values();

        $coverageLinks = $coverageAreas
            ->map(fn (string $zone): array => [
                'name' => $zone,
                'slug' => Str::slug($zone),
            ]);

        $eventTypeLinks = $profile->eventTypes
            ->map(fn ($eventType): array => [
                'name' => $eventType->name,
                'slug' => Str::slug($eventType->name),
            ]);

        $socialLinks = collect([
            ['label' => 'Sitio web', 'url' => $profile->website],
            ['label' => 'Instagram', 'url' => $profile->instagram],
            ['label' => 'Facebook', 'url' => $profile->facebook],
            ['label' => 'TikTok', 'url' => $profile->tiktok],
            ['label' => 'YouTube', 'url' => $profile->youtube],
        ])->filter(fn (array $item): bool => filled($item['url']))->values();

        $youtubeEmbeds = $profile->videos
            ->pluck('url')
            ->map(fn (string $url): ?string => $this->toYoutubeEmbedUrl($url))
            ->filter()
            ->values();

        $publicPhone = $planCapabilities['show_phone'] ? $profile->user?->phone : null;
        $whatsAppPhone = $planCapabilities['show_whatsapp'] ? ($profile->whatsapp ?: $profile->user?->phone) : null;
        $normalizedWhatsApp = $this->normalizePhoneForUrl($whatsAppPhone);
        $normalizedPhone = $this->normalizePhoneForUrl($publicPhone);

        $isFavorited = false;
        $quoteDefaults = [
            'contact_name' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'event_city' => '',
        ];

        if ($request->user()) {
            $authUser = $request->user();
            $quoteDefaults['contact_name'] = (string) ($authUser->display_name ?? '');
            $quoteDefaults['contact_email'] = (string) ($authUser->email ?? '');
            $quoteDefaults['contact_phone'] = (string) ($authUser->phone ?? '');

            if ($authUser->isClient()) {
                $clientUser = $authUser;

                ClientRecentView::query()->updateOrCreate(
                    [
                        'user_id' => $clientUser->id,
                        'mariachi_profile_id' => $profile->mariachi_profile_id,
                        'mariachi_listing_id' => $profile->id,
                    ],
                    [
                        'last_viewed_at' => now(),
                    ]
                );

                $isFavorited = ClientFavorite::query()
                    ->where('user_id', $clientUser->id)
                    ->where('mariachi_listing_id', $profile->id)
                    ->exists();

                $quoteDefaults['event_city'] = (string) ($clientUser->clientProfile?->city_name ?? '');
            }
        }

        $recentlyViewedListings = $this->recentlyViewedListings($request, $profile);
        $seoHelpfulLinks = $this->buildHelpfulSeoLinks($profile, $relatedProfiles, $citySlug);

        $viewKey = 'mariachi_listing_viewed_'.$profile->id;
        $lastSeen = (int) $request->session()->get($viewKey, 0);
        if (($lastSeen === 0 || now()->timestamp - $lastSeen >= 1800) && $profile->mariachiProfile) {
            $statsService->incrementViews($profile->mariachiProfile);
            $request->session()->put($viewKey, now()->timestamp);
        }

        $seoTitle = $name.' | Mariachi en '.$city;
        $seoDescription = $profile->short_description
            ?: 'Conoce el anuncio de '.$name.' en '.$city.'. Revisa fotos, servicios, cobertura y contacto directo.';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $name,
            'description' => (string) ($profile->short_description ?: $profile->full_description ?: ''),
            'areaServed' => array_values(array_filter(array_merge(
                [$profile->city_name],
                $profile->serviceAreas->pluck('city_name')->all()
            ))),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $profile->city_name,
                'addressRegion' => $profile->state,
                'addressCountry' => $profile->country,
                'postalCode' => $profile->postal_code,
                'streetAddress' => $profile->address,
            ],
            'url' => route('mariachi.public.show', ['slug' => $profile->slug]),
        ];

        if ($whatsAppPhone || $publicPhone) {
            $schema['telephone'] = $whatsAppPhone ?: $publicPhone;
        }

        if ($reviewsTotal > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $averageRating,
                'reviewCount' => $reviewsTotal,
            ];
        }

        return view('front.mariachi-show', [
            'profile' => $profile,
            'seo' => $seoResolver->resolve($request, 'listing', [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical' => route('mariachi.public.show', ['slug' => $profile->slug]),
                'og_image' => $featuredPhoto?->path,
                'og_type' => 'website',
                'jsonld' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'h1' => $name,
            'schemaJson' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'citySlug' => $citySlug,
            'featuredPhoto' => $featuredPhoto,
            'secondaryPhotos' => $secondaryPhotos,
            'coverageAreas' => $coverageAreas,
            'coverageLinks' => $coverageLinks,
            'eventTypeLinks' => $eventTypeLinks,
            'socialLinks' => $socialLinks,
            'youtubeEmbeds' => $youtubeEmbeds,
            'relatedProfiles' => $relatedProfiles,
            'recentlyViewedListings' => $recentlyViewedListings,
            'seoHelpfulLinks' => $seoHelpfulLinks,
            'mapEmbedUrl' => $this->buildMapEmbedUrl($profile),
            'whatsappUrl' => $normalizedWhatsApp
                ? 'https://wa.me/'.$normalizedWhatsApp.'?text='.rawurlencode('Hola '.$name.', vi tu anuncio en mariachis.co y quiero cotizar mi evento.')
                : null,
            'phoneUrl' => $normalizedPhone ? 'tel:+'.$normalizedPhone : null,
            'isFavorited' => $isFavorited,
            'quoteDefaults' => $quoteDefaults,
            'publicReviews' => $publicReviews,
            'reviewsTotal' => $reviewsTotal,
            'averageRating' => $averageRating,
            'ratingDistribution' => $ratingDistribution,
            'reviewPhotoGallery' => $reviewPhotoGallery,
            'isVerifiedProfile' => $providerProfile?->verification_status === 'verified',
            'hasPremiumBadge' => (bool) $planCapabilities['has_premium_badge'],
        ]);
    }

    private function normalizePhoneForUrl(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function toYoutubeEmbedUrl(string $url): ?string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return null;
        }

        $parsed = parse_url($trimmed);
        if (! is_array($parsed)) {
            return null;
        }

        $host = (string) ($parsed['host'] ?? '');
        $path = (string) ($parsed['path'] ?? '');

        if (Str::contains($host, 'youtu.be')) {
            $id = ltrim($path, '/');

            return $id !== '' ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
        }

        if (Str::contains($host, 'youtube.com')) {
            parse_str((string) ($parsed['query'] ?? ''), $query);
            $id = (string) ($query['v'] ?? '');

            if ($id !== '') {
                return 'https://www.youtube-nocookie.com/embed/'.$id;
            }

            if (Str::startsWith($path, '/embed/')) {
                return 'https://www.youtube-nocookie.com'.$path;
            }
        }

        return null;
    }

    private function buildMapEmbedUrl(MariachiListing $profile): string
    {
        if ($profile->latitude && $profile->longitude) {
            return 'https://www.google.com/maps?q='.$profile->latitude.','.$profile->longitude.'&output=embed';
        }

        $query = collect([$profile->address, $profile->city_name, $profile->state, $profile->country])
            ->filter()
            ->implode(', ');

        if ($query === '') {
            $query = 'Colombia';
        }

        return 'https://www.google.com/maps?q='.rawurlencode($query).'&output=embed';
    }

    private function recentlyViewedListings(Request $request, MariachiListing $currentListing): Collection
    {
        $user = $request->user();
        if (! $user || ! $user->isClient()) {
            return collect();
        }

        return ClientRecentView::query()
            ->with([
                'mariachiListing.mariachiProfile.user:id,name,first_name,last_name',
                'mariachiListing.photos',
                'mariachiListing.eventTypes:id,name',
            ])
            ->where('user_id', $user->id)
            ->where('mariachi_listing_id', '!=', $currentListing->id)
            ->latest('last_viewed_at')
            ->limit(12)
            ->get()
            ->map(fn (ClientRecentView $view): ?MariachiListing => $view->mariachiListing)
            ->filter(fn (?MariachiListing $listing): bool => $listing instanceof MariachiListing && $listing->isApprovedForMarketplace())
            ->unique('id')
            ->values();
    }

    private function buildHelpfulSeoLinks(MariachiListing $profile, Collection $relatedProfiles, string $citySlug): Collection
    {
        $cityLabel = $profile->city_name ?: 'Colombia';
        $cityLink = route('seo.landing.slug', ['slug' => $citySlug]);

        return collect([
            [
                'label' => 'Mariachis en '.$cityLabel,
                'url' => $cityLink,
            ],
            filled($profile->zone_name) ? [
                'label' => 'Mariachis en '.$profile->zone_name,
                'url' => route('seo.landing.city-category', [
                    'citySlug' => $citySlug,
                    'scopeSlug' => Str::slug((string) $profile->zone_name),
                ]),
            ] : null,
        ])
            ->filter()
            ->concat(
                $profile->eventTypes
                    ->take(4)
                    ->map(fn ($eventType): array => [
                        'label' => $eventType->name.' en '.$cityLabel,
                        'url' => route('seo.landing.city-category', [
                            'citySlug' => $citySlug,
                            'scopeSlug' => Str::slug($eventType->name),
                        ]),
                    ])
            )
            ->concat(
                $relatedProfiles
                    ->take(10)
                    ->map(fn (MariachiListing $listing): array => [
                        'label' => ($listing->business_name ?: $listing->user?->display_name ?: $listing->title).' en '.($listing->city_name ?: $cityLabel),
                        'url' => route('mariachi.public.show', ['slug' => $listing->slug]),
                    ])
            )
            ->unique('url')
            ->take(20)
            ->values();
    }
}
