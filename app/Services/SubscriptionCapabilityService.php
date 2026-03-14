<?php

namespace App\Services;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionCapabilityService
{
    public function __construct(
        private readonly EntitlementsService $entitlementsService
    ) {
    }

    /**
     * @return array{
     *   code:string,
     *   name:string,
     *   price_cop:int,
     *   listing_limit:int,
     *   included_cities:int,
     *   max_photos_per_listing:int,
     *   max_videos_per_listing:int,
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
    public function resolveCapabilities(MariachiProfile $profile, ?MariachiListing $listing = null): array
    {
        return $this->entitlementsService->legacyCapabilityPayload($profile, $listing);
    }

    public function listingLimit(MariachiProfile $profile): int
    {
        return $this->entitlementsService->listingLimit($profile);
    }

    public function includedCities(MariachiProfile $profile): int
    {
        return $this->entitlementsService->includedCities($profile);
    }

    public function maxPhotosPerListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return $this->entitlementsService->maxPhotosPerListing($profile, $listing);
    }

    public function maxVideosPerListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return $this->entitlementsService->maxVideosPerListing($profile, $listing);
    }

    public function canShowWhatsApp(MariachiProfile $profile): bool
    {
        return $this->entitlementsService->canShowWhatsApp($profile);
    }

    public function canShowPhone(MariachiProfile $profile): bool
    {
        return $this->entitlementsService->canShowPhone($profile);
    }

    public function maxCitiesForListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return $this->entitlementsService->maxCitiesForListing($profile, $listing);
    }

    public function additionalCitySlots(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        return $this->entitlementsService->additionalCitySlots($profile, $listing);
    }

    public function resolvePlanForProfile(MariachiProfile $profile): ?Plan
    {
        return $this->entitlementsService->resolvePlanForProfile($profile);
    }

    public function resolveActiveSubscription(MariachiProfile $profile): ?Subscription
    {
        return $this->entitlementsService->resolveActiveSubscription($profile);
    }
}
