<?php

namespace App\Services;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionCapabilityService
{
    /**
     * @return array{
     *   code:string,
     *   name:string,
     *   price_cop:int,
     *   listing_limit:int,
     *   included_cities:int,
     *   max_photos_per_listing:int,
     *   max_videos_per_listing:int,
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
    public function resolveCapabilities(MariachiProfile $profile): array
    {
        $plan = $this->resolvePlanForProfile($profile);
        if ($plan) {
            return [
                'code' => (string) $plan->code,
                'name' => (string) $plan->name,
                'price_cop' => (int) $plan->price_cop,
                'listing_limit' => max(1, (int) $plan->listing_limit),
                'included_cities' => max(1, (int) $plan->included_cities),
                'max_photos_per_listing' => max(0, (int) $plan->max_photos_per_listing),
                'max_videos_per_listing' => max(0, (int) $plan->max_videos_per_listing),
                'show_whatsapp' => (bool) $plan->show_whatsapp,
                'show_phone' => (bool) $plan->show_phone,
                'priority_level' => (int) $plan->priority_level,
                'allows_verification' => (bool) $plan->allows_verification,
                'allows_featured_city' => (bool) $plan->allows_featured_city,
                'allows_featured_home' => (bool) $plan->allows_featured_home,
                'has_premium_badge' => (bool) $plan->has_premium_badge,
                'has_advanced_stats' => (bool) $plan->has_advanced_stats,
            ];
        }

        $code = $this->normalizeLegacyPlanCode($profile);
        $defaults = (array) config("monetization.plans.$code", config('monetization.plans.basic', []));

        return [
            'code' => $code,
            'name' => (string) ($defaults['name'] ?? strtoupper($code)),
            'price_cop' => (int) ($defaults['price_cop'] ?? 0),
            'listing_limit' => max(1, (int) ($defaults['listing_limit'] ?? 1)),
            'included_cities' => max(1, (int) ($defaults['included_cities'] ?? 1)),
            'max_photos_per_listing' => max(0, (int) ($defaults['max_photos_per_listing'] ?? 5)),
            'max_videos_per_listing' => max(0, (int) ($defaults['max_videos_per_listing'] ?? 0)),
            'show_whatsapp' => (bool) ($defaults['show_whatsapp'] ?? false),
            'show_phone' => (bool) ($defaults['show_phone'] ?? false),
            'priority_level' => (int) ($defaults['priority_level'] ?? 0),
            'allows_verification' => (bool) ($defaults['allows_verification'] ?? false),
            'allows_featured_city' => (bool) ($defaults['allows_featured_city'] ?? false),
            'allows_featured_home' => (bool) ($defaults['allows_featured_home'] ?? false),
            'has_premium_badge' => (bool) ($defaults['has_premium_badge'] ?? false),
            'has_advanced_stats' => (bool) ($defaults['has_advanced_stats'] ?? false),
        ];
    }

    public function listingLimit(MariachiProfile $profile): int
    {
        return $this->resolveCapabilities($profile)['listing_limit'];
    }

    public function includedCities(MariachiProfile $profile): int
    {
        return $this->resolveCapabilities($profile)['included_cities'];
    }

    public function maxPhotosPerListing(MariachiProfile $profile): int
    {
        return $this->resolveCapabilities($profile)['max_photos_per_listing'];
    }

    public function maxVideosPerListing(MariachiProfile $profile): int
    {
        return $this->resolveCapabilities($profile)['max_videos_per_listing'];
    }

    public function canShowWhatsApp(MariachiProfile $profile): bool
    {
        return $this->resolveCapabilities($profile)['show_whatsapp'];
    }

    public function canShowPhone(MariachiProfile $profile): bool
    {
        return $this->resolveCapabilities($profile)['show_phone'];
    }

    public function maxCitiesForListing(MariachiProfile $profile, ?MariachiListing $listing = null): int
    {
        $included = $this->includedCities($profile);
        $extraSlots = $this->additionalCitySlots($profile, $listing);

        return max(1, $included + $extraSlots);
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

    public function resolveActiveSubscription(MariachiProfile $profile): ?Subscription
    {
        if (! $profile->relationLoaded('activeSubscription')) {
            $profile->load('activeSubscription.plan');
        } elseif ($profile->activeSubscription && ! $profile->activeSubscription->relationLoaded('plan')) {
            $profile->activeSubscription->load('plan');
        }

        return $profile->activeSubscription;
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
}
