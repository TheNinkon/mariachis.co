<?php

namespace App\Services;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Entitlements\EntitlementKey;
use Illuminate\Support\Arr;

class EntitlementsService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(MariachiProfile $profile): array
    {
        $plan = $this->resolvePlanForProfile($profile);

        $entitlements = EntitlementKey::defaults();
        $entitlements = array_replace($entitlements, $this->legacyConfigEntitlements($profile));

        if ($plan) {
            $entitlements = array_replace(
                $entitlements,
                $this->mapPlanColumnsToEntitlements($plan),
                $this->planEntitlements($plan)
            );
        }

        $resolved = array_replace($entitlements, $this->profileOverrides($profile));

        foreach ($resolved as $key => $value) {
            $resolved[$key] = EntitlementKey::normalize($key, $value);
        }

        return $resolved;
    }

    /**
     * @return array{
     *   plan:?Plan,
     *   code:string,
     *   slug:?string,
     *   name:string,
     *   description:?string,
     *   badge_text:?string,
     *   is_public:bool,
     *   billing_cycle:string,
     *   price_cop:int,
     *   entitlements:array<string,mixed>
     * }
     */
    public function summary(MariachiProfile $profile): array
    {
        $plan = $this->resolvePlanForProfile($profile);
        $entitlements = $this->resolve($profile);

        return [
            'plan' => $plan,
            'code' => (string) ($plan?->code ?: $this->normalizeLegacyPlanCode($profile)),
            'slug' => $plan?->slug,
            'name' => (string) ($plan?->name ?: Arr::get(config('monetization.plans.'.$this->normalizeLegacyPlanCode($profile)), 'name', 'Plan activo')),
            'description' => $plan?->description,
            'badge_text' => $plan?->badge_text,
            'is_public' => (bool) ($plan?->is_public ?? true),
            'billing_cycle' => (string) ($plan?->billing_cycle ?: 'monthly'),
            'price_cop' => (int) ($plan?->price_cop ?? 0),
            'entitlements' => $entitlements,
        ];
    }

    /**
     * Payload compatible con la UI actual mientras migramos llamadas antiguas.
     *
     * @return array{
     *   code:string,
     *   slug:?string,
     *   name:string,
     *   description:?string,
     *   badge_text:?string,
     *   is_public:bool,
     *   price_cop:int,
     *   max_open_drafts:int,
     *   max_published_listings:int,
     *   listing_limit:int,
     *   included_cities:int,
     *   max_photos_per_listing:int,
     *   max_videos_per_listing:int,
     *   can_add_video:bool,
     *   max_zones_covered:int,
     *   max_event_types:int,
     *   max_service_types:int,
     *   max_group_sizes:int,
     *   max_budget_ranges:int,
     *   show_whatsapp:bool,
     *   show_phone:bool,
     *   priority_level:int,
     *   allows_verification:bool,
     *   allows_featured_city:bool,
     *   allows_featured_home:bool,
     *   has_premium_badge:bool,
     *   has_advanced_stats:bool
     * }
     */
    public function legacyCapabilityPayload(MariachiProfile $profile, ?MariachiListing $listing = null): array
    {
        $plan = $this->resolvePlanForListing($profile, $listing);
        $entitlements = $this->resolveForListing($profile, $listing);
        $planCode = (string) ($plan?->code ?: ($listing?->selected_plan_code ?: $this->normalizeLegacyPlanCode($profile)));

        return [
            'code' => $planCode,
            'slug' => $plan?->slug,
            'name' => (string) ($plan?->name ?: Arr::get(config('monetization.plans.'.$planCode), 'name', 'Plan activo')),
            'description' => $plan?->description,
            'badge_text' => $plan?->badge_text,
            'is_public' => (bool) ($plan?->is_public ?? true),
            'price_cop' => (int) ($plan?->price_cop ?? 0),
            'max_open_drafts' => (int) $entitlements[EntitlementKey::MAX_OPEN_DRAFTS],
            'max_published_listings' => (int) $entitlements[EntitlementKey::MAX_PUBLISHED_LISTINGS],
            'listing_limit' => (int) $entitlements[EntitlementKey::MAX_PUBLISHED_LISTINGS],
            'included_cities' => (int) $entitlements[EntitlementKey::MAX_CITIES_COVERED],
            'max_photos_per_listing' => (int) $entitlements[EntitlementKey::MAX_PHOTOS_PER_LISTING],
            'max_videos_per_listing' => (int) $entitlements[EntitlementKey::MAX_VIDEOS_PER_LISTING],
            'can_add_video' => (bool) $entitlements[EntitlementKey::CAN_ADD_VIDEO],
            'max_zones_covered' => (int) $entitlements[EntitlementKey::MAX_ZONES_COVERED],
            'max_event_types' => (int) $entitlements[EntitlementKey::MAX_EVENT_TYPES],
            'max_service_types' => (int) $entitlements[EntitlementKey::MAX_SERVICE_TYPES],
            'max_group_sizes' => (int) $entitlements[EntitlementKey::MAX_GROUP_SIZES],
            'max_budget_ranges' => (int) $entitlements[EntitlementKey::MAX_BUDGET_RANGES],
            'show_whatsapp' => (bool) $entitlements[EntitlementKey::CAN_SHOW_WHATSAPP],
            'show_phone' => (bool) $entitlements[EntitlementKey::CAN_SHOW_PHONE],
            'priority_level' => (int) $entitlements[EntitlementKey::PRIORITY_LEVEL],
            'allows_verification' => false,
            'allows_featured_city' => (bool) $entitlements[EntitlementKey::CAN_FEATURED_CITY],
            'allows_featured_home' => (bool) $entitlements[EntitlementKey::CAN_FEATURED_HOME],
            'has_premium_badge' => (bool) $entitlements[EntitlementKey::HAS_PREMIUM_BADGE],
            'has_advanced_stats' => (bool) $entitlements[EntitlementKey::HAS_ADVANCED_STATS],
        ];
    }

    public function listingLimit(MariachiProfile $profile): int
    {
        return $this->publishedListingsLimit($profile);
    }

    public function publishedListingsLimit(MariachiProfile $profile): int
    {
        return max(0, (int) $this->resolve($profile)[EntitlementKey::MAX_PUBLISHED_LISTINGS]);
    }

    public function openDraftLimit(MariachiProfile $profile): int
    {
        return max(0, (int) $this->resolve($profile)[EntitlementKey::MAX_OPEN_DRAFTS]);
    }

    public function includedCities(MariachiProfile $profile): int
    {
        return (int) $this->resolve($profile)[EntitlementKey::MAX_CITIES_COVERED];
    }

    public function maxPhotosPerListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return (int) $this->resolveForListing($profile, $listing)[EntitlementKey::MAX_PHOTOS_PER_LISTING];
    }

    public function canAddVideo(MariachiProfile $profile, ?MariachiListing $listing = null): bool
    {
        return (bool) $this->resolveForListing($profile, $listing)[EntitlementKey::CAN_ADD_VIDEO];
    }

    public function maxVideosPerListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        $entitlements = $this->resolveForListing($profile, $listing);

        return (bool) $entitlements[EntitlementKey::CAN_ADD_VIDEO]
            ? (int) $entitlements[EntitlementKey::MAX_VIDEOS_PER_LISTING]
            : 0;
    }

    public function canShowWhatsApp(MariachiProfile $profile): bool
    {
        return (bool) $this->resolve($profile)[EntitlementKey::CAN_SHOW_WHATSAPP];
    }

    public function canShowPhone(MariachiProfile $profile): bool
    {
        return (bool) $this->resolve($profile)[EntitlementKey::CAN_SHOW_PHONE];
    }

    public function maxZonesCovered(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return (int) $this->resolveForListing($profile, $listing)[EntitlementKey::MAX_ZONES_COVERED];
    }

    public function additionalCitySlots(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        $subscription = $this->resolveActiveSubscription($profile);
        if (! $subscription) {
            return 0;
        }

        return $subscription->additionalCities()
            ->active()
            ->where(function ($query) use ($listing): void {
                $query->whereNull('mariachi_listing_id');

                if ($listing) {
                    $query->orWhere('mariachi_listing_id', $listing->id);
                }
            })
            ->count();
    }

    public function maxCitiesForListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return max(
            1,
            (int) $this->resolveForListing($profile, $listing)[EntitlementKey::MAX_CITIES_COVERED] + $this->additionalCitySlots($profile, $listing)
        );
    }

    public function resolvePlanForProfile(MariachiProfile $profile): ?Plan
    {
        $subscription = $this->resolveActiveSubscription($profile);
        if ($subscription?->plan?->is_active) {
            return $subscription->plan;
        }

        $legacyCode = $this->normalizeLegacyPlanCode($profile);

        return Plan::query()
            ->active()
            ->where('code', $legacyCode)
            ->first();
    }

    public function resolvePlanForListing(MariachiProfile $profile, ?MariachiListing $listing = null): ?Plan
    {
        $selectedPlanCode = trim((string) ($listing?->selected_plan_code ?? ''));
        if ($selectedPlanCode !== '') {
            return Plan::query()
                ->with('entitlements')
                ->where('code', $selectedPlanCode)
                ->first();
        }

        return $this->resolvePlanForProfile($profile);
    }

    public function resolveActiveSubscription(MariachiProfile $profile): ?Subscription
    {
        if (! $profile->relationLoaded('activeSubscription')) {
            $profile->load('activeSubscription.plan.entitlements');
        } elseif ($profile->activeSubscription && ! $profile->activeSubscription->relationLoaded('plan')) {
            $profile->activeSubscription->load('plan.entitlements');
        } elseif ($profile->activeSubscription?->plan && ! $profile->activeSubscription->plan->relationLoaded('entitlements')) {
            $profile->activeSubscription->plan->load('entitlements');
        }

        return $profile->activeSubscription;
    }

    /**
     * @return array<int, string>
     */
    public function profileAdjustmentIssues(MariachiProfile $profile): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function listingAdjustmentIssues(MariachiListing $listing): array
    {
        $profile = $listing->mariachiProfile;
        if (! $profile) {
            return [];
        }

        $issues = [];
        $maxPhotos = $this->maxPhotosPerListing($profile, $listing);
        $maxVideos = $this->maxVideosPerListing($profile, $listing);
        $maxZones = $this->maxZonesCovered($profile, $listing);

        $photoCount = $listing->relationLoaded('photos')
            ? $listing->photos->count()
            : $listing->photos()->count();

        if ($photoCount > $maxPhotos) {
            $issues[] = sprintf(
                'Este anuncio tiene %d foto(s) y tu plan permite %d.',
                $photoCount,
                $maxPhotos
            );
        }

        $videoCount = $listing->relationLoaded('videos')
            ? $listing->videos->count()
            : $listing->videos()->count();

        if (! $this->canAddVideo($profile) && $videoCount > 0) {
            $issues[] = 'Tu plan actual no incluye videos y este anuncio ya tiene videos cargados.';
        } elseif ($videoCount > $maxVideos) {
            $issues[] = sprintf(
                'Este anuncio tiene %d video(s) y tu plan permite %d.',
                $videoCount,
                $maxVideos
            );
        }

        $zoneCount = $listing->relationLoaded('serviceAreas')
            ? $listing->serviceAreas->count()
            : $listing->serviceAreas()->count();

        if ($zoneCount > $maxZones) {
            $issues[] = sprintf(
                'Este anuncio cubre %d zona(s) y tu plan permite %d.',
                $zoneCount,
                $maxZones
            );
        }

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    public function publicationBlockers(MariachiListing $listing): array
    {
        return $this->listingAdjustmentIssues($listing);
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyConfigEntitlements(MariachiProfile $profile): array
    {
        $code = $this->normalizeLegacyPlanCode($profile);
        $plan = (array) config('monetization.plans.'.$code, config('monetization.plans.basic', []));
        $nested = (array) ($plan['entitlements'] ?? []);

        return array_replace($this->mapLegacyPayloadToEntitlements($plan), $nested);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPlanColumnsToEntitlements(Plan $plan): array
    {
        return [
            EntitlementKey::MAX_LISTINGS_TOTAL => (int) $plan->entitlementValue(
                EntitlementKey::MAX_LISTINGS_TOTAL,
                $plan->entitlementValue(EntitlementKey::MAX_PUBLISHED_LISTINGS, $plan->listing_limit)
            ),
            EntitlementKey::MAX_PUBLISHED_LISTINGS => (int) $plan->entitlementValue(
                EntitlementKey::MAX_PUBLISHED_LISTINGS,
                $plan->entitlementValue(EntitlementKey::MAX_LISTINGS_TOTAL, $plan->listing_limit)
            ),
            EntitlementKey::MAX_OPEN_DRAFTS => (int) $plan->entitlementValue(
                EntitlementKey::MAX_OPEN_DRAFTS,
                EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS)
            ),
            EntitlementKey::MAX_PHOTOS_PER_LISTING => (int) $plan->max_photos_per_listing,
            EntitlementKey::CAN_ADD_VIDEO => (int) $plan->max_videos_per_listing > 0,
            EntitlementKey::MAX_VIDEOS_PER_LISTING => (int) $plan->max_videos_per_listing,
            EntitlementKey::CAN_SHOW_WHATSAPP => (bool) $plan->show_whatsapp,
            EntitlementKey::CAN_SHOW_PHONE => (bool) $plan->show_phone,
            EntitlementKey::MAX_CITIES_COVERED => max(1, (int) $plan->included_cities),
            EntitlementKey::MAX_ZONES_COVERED => max(5, (int) $plan->included_cities * 5),
            EntitlementKey::MAX_EVENT_TYPES => (int) $plan->entitlementValue(EntitlementKey::MAX_EVENT_TYPES, 3),
            EntitlementKey::MAX_SERVICE_TYPES => (int) $plan->entitlementValue(EntitlementKey::MAX_SERVICE_TYPES, 1),
            EntitlementKey::MAX_GROUP_SIZES => (int) $plan->entitlementValue(EntitlementKey::MAX_GROUP_SIZES, 1),
            EntitlementKey::MAX_BUDGET_RANGES => (int) $plan->entitlementValue(EntitlementKey::MAX_BUDGET_RANGES, 3),
            EntitlementKey::PRIORITY_LEVEL => (int) $plan->priority_level,
            EntitlementKey::CAN_FEATURED_CITY => (bool) $plan->allows_featured_city,
            EntitlementKey::CAN_FEATURED_HOME => (bool) $plan->allows_featured_home,
            EntitlementKey::CAN_REQUEST_VERIFICATION => false,
            EntitlementKey::HAS_PREMIUM_BADGE => (bool) $plan->has_premium_badge,
            EntitlementKey::HAS_ADVANCED_STATS => (bool) $plan->has_advanced_stats,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private function mapLegacyPayloadToEntitlements(array $plan): array
    {
        $includedCities = max(1, (int) ($plan['included_cities'] ?? 1));
        $maxVideos = max(0, (int) ($plan['max_videos_per_listing'] ?? 0));
        $publishedListings = max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_PUBLISHED_LISTINGS] ?? null)
            ?? (($plan['entitlements'][EntitlementKey::MAX_LISTINGS_TOTAL] ?? null)
                ?? ($plan['listing_limit'] ?? 0))));
        $openDrafts = max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_OPEN_DRAFTS] ?? null)
            ?? EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS)));

        return [
            EntitlementKey::MAX_LISTINGS_TOTAL => $publishedListings,
            EntitlementKey::MAX_PUBLISHED_LISTINGS => $publishedListings,
            EntitlementKey::MAX_OPEN_DRAFTS => $openDrafts,
            EntitlementKey::MAX_PHOTOS_PER_LISTING => max(0, (int) ($plan['max_photos_per_listing'] ?? 5)),
            EntitlementKey::CAN_ADD_VIDEO => $maxVideos > 0,
            EntitlementKey::MAX_VIDEOS_PER_LISTING => $maxVideos,
            EntitlementKey::CAN_SHOW_WHATSAPP => (bool) ($plan['show_whatsapp'] ?? false),
            EntitlementKey::CAN_SHOW_PHONE => (bool) ($plan['show_phone'] ?? false),
            EntitlementKey::MAX_CITIES_COVERED => $includedCities,
            EntitlementKey::MAX_ZONES_COVERED => max(5, $includedCities * 5),
            EntitlementKey::MAX_EVENT_TYPES => max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_EVENT_TYPES] ?? null) ?? 3)),
            EntitlementKey::MAX_SERVICE_TYPES => max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_SERVICE_TYPES] ?? null) ?? 1)),
            EntitlementKey::MAX_GROUP_SIZES => max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_GROUP_SIZES] ?? null) ?? 1)),
            EntitlementKey::MAX_BUDGET_RANGES => max(0, (int) (($plan['entitlements'][EntitlementKey::MAX_BUDGET_RANGES] ?? null) ?? 3)),
            EntitlementKey::PRIORITY_LEVEL => max(0, (int) ($plan['priority_level'] ?? 0)),
            EntitlementKey::CAN_FEATURED_CITY => (bool) ($plan['allows_featured_city'] ?? false),
            EntitlementKey::CAN_FEATURED_HOME => (bool) ($plan['allows_featured_home'] ?? false),
            EntitlementKey::CAN_REQUEST_VERIFICATION => false,
            EntitlementKey::HAS_PREMIUM_BADGE => (bool) ($plan['has_premium_badge'] ?? false),
            EntitlementKey::HAS_ADVANCED_STATS => (bool) ($plan['has_advanced_stats'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planEntitlements(Plan $plan): array
    {
        if (! $plan->relationLoaded('entitlements')) {
            $plan->load('entitlements');
        }

        return $plan->entitlements
            ->mapWithKeys(fn ($entitlement): array => [$entitlement->key => $entitlement->value])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function profileOverrides(MariachiProfile $profile): array
    {
        if (! $profile->relationLoaded('entitlementOverrides')) {
            $profile->load('entitlementOverrides');
        }

        return $profile->entitlementOverrides
            ->mapWithKeys(fn ($override): array => [$override->key => $override->value])
            ->all();
    }

    private function normalizeLegacyPlanCode(MariachiProfile $profile): string
    {
        $code = (string) ($profile->subscription_plan_code ?? '');

        return match ($code) {
            'starter' => 'basic',
            'plus' => 'pro',
            'pro' => (int) ($profile->subscription_listing_limit ?? 0) >= 6 ? 'premium' : 'pro',
            'basic', 'premium' => $code,
            default => 'basic',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveForListing(MariachiProfile $profile, ?MariachiListing $listing = null): array
    {
        $plan = $this->resolvePlanForListing($profile, $listing);

        $entitlements = EntitlementKey::defaults();
        $entitlements = array_replace($entitlements, $this->legacyConfigEntitlements($profile));

        if ($plan) {
            $entitlements = array_replace(
                $entitlements,
                $this->mapPlanColumnsToEntitlements($plan),
                $this->planEntitlements($plan)
            );
        }

        $resolved = array_replace($entitlements, $this->profileOverrides($profile));

        foreach ($resolved as $key => $value) {
            $resolved[$key] = EntitlementKey::normalize($key, $value);
        }

        return $resolved;
    }
}
