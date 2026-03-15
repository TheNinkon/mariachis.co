<?php

use App\Support\Entitlements\EntitlementKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        $now = now();

        $plans = DB::table('plans')
            ->select(['id', 'listing_limit'])
            ->orderBy('id')
            ->get();

        foreach ($plans as $plan) {
            $legacyPublishedLimit = DB::table('plan_entitlements')
                ->where('plan_id', $plan->id)
                ->where('key', EntitlementKey::MAX_LISTINGS_TOTAL)
                ->value('value');

            $publishedLimit = 0;
            if ($legacyPublishedLimit !== null && $legacyPublishedLimit !== '') {
                $publishedLimit = max(0, (int) $legacyPublishedLimit);
            } elseif ($plan->listing_limit !== null) {
                $publishedLimit = max(0, (int) $plan->listing_limit);
            }

            $this->upsertEntitlement(
                planId: (int) $plan->id,
                key: EntitlementKey::MAX_LISTINGS_TOTAL,
                value: $publishedLimit,
                now: $now
            );

            $this->upsertEntitlement(
                planId: (int) $plan->id,
                key: EntitlementKey::MAX_PUBLISHED_LISTINGS,
                value: 0,
                now: $now
            );

            $this->upsertEntitlement(
                planId: (int) $plan->id,
                key: EntitlementKey::MAX_OPEN_DRAFTS,
                value: EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS),
                now: $now
            );

            $this->upsertEntitlement(
                planId: (int) $plan->id,
                key: EntitlementKey::CAN_REQUEST_VERIFICATION,
                value: false,
                now: $now
            );

            DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'listing_limit' => 0,
                    'allows_verification' => false,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        DB::table('plan_entitlements')
            ->whereIn('key', [
                EntitlementKey::MAX_PUBLISHED_LISTINGS,
                EntitlementKey::MAX_OPEN_DRAFTS,
            ])
            ->delete();
    }

    private function upsertEntitlement(int $planId, string $key, mixed $value, $now): void
    {
        DB::table('plan_entitlements')->updateOrInsert(
            [
                'plan_id' => $planId,
                'key' => $key,
            ],
            [
                'value' => $value,
                'value_type' => EntitlementKey::typeFor($key),
                'metadata' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
};
