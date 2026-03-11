<?php

use App\Support\Entitlements\EntitlementKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->string('slug', 140)->nullable()->after('code');
            $table->text('description')->nullable()->after('billing_cycle');
            $table->string('badge_text', 80)->nullable()->after('description');
            $table->boolean('is_public')->default(true)->after('badge_text');
            $table->unique('slug');
        });

        Schema::create('plan_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'key']);
        });

        Schema::create('mariachi_entitlement_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['mariachi_profile_id', 'key'], 'mariachi_entitlement_overrides_unique');
        });

        $this->backfillPlanDescriptors();
        $this->backfillEntitlements();
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_entitlement_overrides');
        Schema::dropIfExists('plan_entitlements');

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn([
                'slug',
                'description',
                'badge_text',
                'is_public',
            ]);
        });
    }

    private function backfillPlanDescriptors(): void
    {
        DB::table('plans')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $plan): void {
                $baseSlug = Str::slug((string) ($plan->slug ?? $plan->code ?? $plan->name ?? 'plan'));
                if ($baseSlug === '') {
                    $baseSlug = 'plan';
                }

                $slug = $baseSlug;
                $counter = 2;

                while (
                    DB::table('plans')
                        ->where('slug', $slug)
                        ->where('id', '!=', $plan->id)
                        ->exists()
                ) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                DB::table('plans')
                    ->where('id', $plan->id)
                    ->update([
                        'slug' => $slug,
                        'description' => $plan->description ?: 'Plan configurable por capacidades y limites.',
                        'badge_text' => $plan->badge_text,
                        'is_public' => $plan->is_public ?? true,
                        'updated_at' => now(),
                    ]);
            });
    }

    private function backfillEntitlements(): void
    {
        $definitions = EntitlementKey::definitions();

        DB::table('plans')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $plan) use ($definitions): void {
                $values = [
                    EntitlementKey::MAX_LISTINGS_TOTAL => max(1, (int) $plan->listing_limit),
                    EntitlementKey::MAX_PHOTOS_PER_LISTING => max(0, (int) $plan->max_photos_per_listing),
                    EntitlementKey::CAN_ADD_VIDEO => (int) $plan->max_videos_per_listing > 0,
                    EntitlementKey::MAX_VIDEOS_PER_LISTING => max(0, (int) $plan->max_videos_per_listing),
                    EntitlementKey::CAN_SHOW_WHATSAPP => (bool) $plan->show_whatsapp,
                    EntitlementKey::CAN_SHOW_PHONE => (bool) $plan->show_phone,
                    EntitlementKey::MAX_CITIES_COVERED => max(1, (int) $plan->included_cities),
                    EntitlementKey::MAX_ZONES_COVERED => max(5, (int) $plan->included_cities * 5),
                    EntitlementKey::PRIORITY_LEVEL => max(0, (int) $plan->priority_level),
                    EntitlementKey::CAN_FEATURED_CITY => (bool) $plan->allows_featured_city,
                    EntitlementKey::CAN_FEATURED_HOME => (bool) $plan->allows_featured_home,
                    EntitlementKey::CAN_REQUEST_VERIFICATION => (bool) $plan->allows_verification,
                    EntitlementKey::HAS_PREMIUM_BADGE => (bool) $plan->has_premium_badge,
                    EntitlementKey::HAS_ADVANCED_STATS => (bool) $plan->has_advanced_stats,
                ];

                foreach ($values as $key => $value) {
                    DB::table('plan_entitlements')->updateOrInsert(
                        [
                            'plan_id' => $plan->id,
                            'key' => $key,
                        ],
                        [
                            'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'value_type' => $definitions[$key]['type'] ?? 'string',
                            'metadata' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }
};
