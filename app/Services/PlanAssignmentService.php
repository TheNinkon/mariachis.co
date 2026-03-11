<?php

namespace App\Services;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class PlanAssignmentService
{
    public function assignToProfile(
        MariachiProfile $profile,
        Plan $plan,
        ?MariachiListing $listing = null,
        string $source = 'manual_selection',
        array $metadata = []
    ): Subscription {
        return DB::transaction(function () use ($profile, $plan, $listing, $source, $metadata): Subscription {
            $profile->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update([
                    'status' => Subscription::STATUS_REPLACED,
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            $subscription = $profile->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'renews_at' => now()->addMonth(),
                'base_amount_cop' => $plan->price_cop,
                'extra_city_amount_cop' => (int) config('monetization.additional_city_price_cop', 9900),
                'metadata' => array_merge([
                    'source' => $source,
                    'currency' => 'COP',
                ], $metadata),
            ]);

            $profile->update([
                'subscription_plan_code' => $plan->code,
                'subscription_listing_limit' => $plan->listing_limit,
                'subscription_active' => true,
                'default_mariachi_listing_id' => $profile->default_mariachi_listing_id ?: $listing?->id,
            ]);

            if ($listing) {
                $listing->update([
                    'selected_plan_code' => $plan->code,
                    'plan_selected_at' => now(),
                    'status' => MariachiListing::STATUS_ACTIVE,
                    'is_active' => true,
                    'activated_at' => $listing->activated_at ?? now(),
                    'deactivated_at' => null,
                ]);
            }

            return $subscription;
        });
    }
}
