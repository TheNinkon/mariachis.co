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
        array $metadata = [],
        bool $publishListing = false,
        int $durationMonths = 1,
        ?int $baseAmountCop = null
    ): Subscription {
        return DB::transaction(function () use ($profile, $plan, $listing, $source, $metadata, $publishListing, $durationMonths, $baseAmountCop): Subscription {
            $normalizedDuration = max(1, $durationMonths);
            $resolvedBaseAmount = $baseAmountCop ?? ((int) $plan->price_cop * $normalizedDuration);

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
                'renews_at' => now()->addMonthsNoOverflow($normalizedDuration),
                'base_amount_cop' => $resolvedBaseAmount,
                'extra_city_amount_cop' => (int) config('monetization.additional_city_price_cop', 9900),
                'metadata' => array_merge([
                    'source' => $source,
                    'currency' => 'COP',
                    'duration_months' => $normalizedDuration,
                ], $metadata),
            ]);

            $profile->update([
                'subscription_plan_code' => $plan->code,
                'subscription_listing_limit' => $plan->listing_limit,
                'subscription_active' => true,
                'default_mariachi_listing_id' => $profile->default_mariachi_listing_id ?: $listing?->id,
            ]);

            if ($listing && $publishListing) {
                $listing->update([
                    'selected_plan_code' => $plan->code,
                    'plan_duration_months' => $normalizedDuration,
                    'plan_selected_at' => now(),
                    'status' => MariachiListing::STATUS_ACTIVE,
                    'is_active' => true,
                    'activated_at' => $listing->activated_at ?? now(),
                    'plan_expires_at' => ($listing->activated_at ?? now())->copy()->addMonthsNoOverflow($normalizedDuration),
                    'deactivated_at' => null,
                ]);
            }

            return $subscription;
        });
    }
}
