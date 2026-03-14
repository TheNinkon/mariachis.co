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

        $defaults = [
            EntitlementKey::LISTING_TERM_PRIMARY_MONTHS => 1,
            EntitlementKey::LISTING_TERM_PRIMARY_DISCOUNT_PERCENT => 0,
            EntitlementKey::LISTING_TERM_SECONDARY_MONTHS => 3,
            EntitlementKey::LISTING_TERM_SECONDARY_DISCOUNT_PERCENT => 10,
            EntitlementKey::LISTING_TERM_TERTIARY_MONTHS => 12,
            EntitlementKey::LISTING_TERM_TERTIARY_DISCOUNT_PERCENT => 20,
        ];

        DB::table('plans')
            ->select('id')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $plan) use ($defaults): void {
                foreach ($defaults as $key => $value) {
                    $exists = DB::table('plan_entitlements')
                        ->where('plan_id', $plan->id)
                        ->where('key', $key)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('plan_entitlements')->insert([
                        'plan_id' => $plan->id,
                        'key' => $key,
                        'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'value_type' => EntitlementKey::typeFor($key),
                        'metadata' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
    }
};
