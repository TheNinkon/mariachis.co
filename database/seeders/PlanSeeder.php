<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Support\Entitlements\EntitlementKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = (array) config('monetization.plans', []);

        foreach ($plans as $code => $attributes) {
            $plan = Plan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'slug' => (string) Str::slug((string) ($attributes['slug'] ?? $code)),
                    'name' => (string) ($attributes['name'] ?? strtoupper((string) $code)),
                    'price_cop' => (int) ($attributes['price_cop'] ?? 0),
                    'billing_cycle' => 'monthly',
                    'description' => $attributes['description'] ?? 'Plan configurable por capacidades y limites.',
                    'badge_text' => $attributes['badge_text'] ?? null,
                    'is_public' => (bool) ($attributes['is_public'] ?? true),
                    'listing_limit' => (int) ($attributes['listing_limit'] ?? 1),
                    'included_cities' => (int) ($attributes['included_cities'] ?? 1),
                    'max_photos_per_listing' => (int) ($attributes['max_photos_per_listing'] ?? 5),
                    'max_videos_per_listing' => (int) ($attributes['max_videos_per_listing'] ?? 0),
                    'show_whatsapp' => (bool) ($attributes['show_whatsapp'] ?? false),
                    'show_phone' => (bool) ($attributes['show_phone'] ?? false),
                    'priority_level' => (int) ($attributes['priority_level'] ?? 0),
                    'allows_verification' => (bool) ($attributes['allows_verification'] ?? false),
                    'allows_featured_city' => (bool) ($attributes['allows_featured_city'] ?? false),
                    'allows_featured_home' => (bool) ($attributes['allows_featured_home'] ?? false),
                    'has_premium_badge' => (bool) ($attributes['has_premium_badge'] ?? false),
                    'has_advanced_stats' => (bool) ($attributes['has_advanced_stats'] ?? false),
                    'is_active' => true,
                    'sort_order' => (int) ($attributes['sort_order'] ?? 0),
                ]
            );

            $entitlements = array_replace(
                [
                    EntitlementKey::MAX_LISTINGS_TOTAL => (int) ($attributes['listing_limit'] ?? 1),
                    EntitlementKey::MAX_PHOTOS_PER_LISTING => (int) ($attributes['max_photos_per_listing'] ?? 5),
                    EntitlementKey::CAN_ADD_VIDEO => (int) ($attributes['max_videos_per_listing'] ?? 0) > 0,
                    EntitlementKey::MAX_VIDEOS_PER_LISTING => (int) ($attributes['max_videos_per_listing'] ?? 0),
                    EntitlementKey::CAN_SHOW_WHATSAPP => (bool) ($attributes['show_whatsapp'] ?? false),
                    EntitlementKey::CAN_SHOW_PHONE => (bool) ($attributes['show_phone'] ?? false),
                    EntitlementKey::MAX_CITIES_COVERED => (int) ($attributes['included_cities'] ?? 1),
                    EntitlementKey::MAX_ZONES_COVERED => max(5, (int) ($attributes['included_cities'] ?? 1) * 5),
                    EntitlementKey::PRIORITY_LEVEL => (int) ($attributes['priority_level'] ?? 0),
                    EntitlementKey::CAN_FEATURED_CITY => (bool) ($attributes['allows_featured_city'] ?? false),
                    EntitlementKey::CAN_FEATURED_HOME => (bool) ($attributes['allows_featured_home'] ?? false),
                    EntitlementKey::CAN_REQUEST_VERIFICATION => (bool) ($attributes['allows_verification'] ?? false),
                    EntitlementKey::HAS_PREMIUM_BADGE => (bool) ($attributes['has_premium_badge'] ?? false),
                    EntitlementKey::HAS_ADVANCED_STATS => (bool) ($attributes['has_advanced_stats'] ?? false),
                ],
                (array) ($attributes['entitlements'] ?? [])
            );

            foreach ($entitlements as $key => $value) {
                $plan->entitlements()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'value_type' => EntitlementKey::typeFor($key),
                        'metadata' => null,
                    ]
                );
            }
        }
    }
}
