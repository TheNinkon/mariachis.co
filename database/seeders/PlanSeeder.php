<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = (array) config('monetization.plans', []);

        foreach ($plans as $code => $attributes) {
            Plan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => (string) ($attributes['name'] ?? strtoupper((string) $code)),
                    'price_cop' => (int) ($attributes['price_cop'] ?? 0),
                    'billing_cycle' => 'monthly',
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
        }
    }
}
