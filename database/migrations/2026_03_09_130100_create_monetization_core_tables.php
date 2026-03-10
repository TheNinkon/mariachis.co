<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('business_name');
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 140);
            $table->unsignedInteger('price_cop');
            $table->string('billing_cycle', 40)->default('monthly');
            $table->unsignedSmallInteger('listing_limit')->default(1);
            $table->unsignedSmallInteger('included_cities')->default(1);
            $table->unsignedSmallInteger('max_photos_per_listing')->default(5);
            $table->unsignedSmallInteger('max_videos_per_listing')->default(0);
            $table->boolean('show_whatsapp')->default(false);
            $table->boolean('show_phone')->default(false);
            $table->unsignedTinyInteger('priority_level')->default(0);
            $table->boolean('allows_verification')->default(false);
            $table->boolean('allows_featured_city')->default(false);
            $table->boolean('allows_featured_home')->default(false);
            $table->boolean('has_premium_badge')->default(false);
            $table->boolean('has_advanced_stats')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('base_amount_cop');
            $table->unsignedInteger('extra_city_amount_cop')->default((int) config('monetization.additional_city_price_cop', 9900));
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mariachi_profile_id', 'status']);
            $table->index(['plan_id', 'status']);
        });

        Schema::create('subscription_cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mariachi_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('city_name', 120)->nullable();
            $table->unsignedInteger('extra_price_cop')->default((int) config('monetization.additional_city_price_cop', 9900));
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subscription_id', 'is_active']);
            $table->index(['mariachi_listing_id', 'is_active']);
            $table->unique(['subscription_id', 'mariachi_listing_id', 'city_name'], 'subscription_city_unique');
        });

        Schema::create('ad_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promotion_type', 40);
            $table->string('city_name', 120)->nullable();
            $table->unsignedInteger('price_cop');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 40)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'status']);
            $table->index(['promotion_type', 'status']);
            $table->index(['city_name', 'status']);
        });

        Schema::create('verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40)->default('pending');
            $table->string('id_document_path');
            $table->string('identity_proof_path');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['mariachi_profile_id', 'status']);
        });

        Schema::create('media_hashes', function (Blueprint $table): void {
            $table->id();
            $table->string('media_type', 40);
            $table->unsignedBigInteger('media_id');
            $table->foreignId('mariachi_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mariachi_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('hash_algorithm', 20)->default('sha256');
            $table->string('hash_value', 128);
            $table->boolean('is_duplicate')->default(false);
            $table->unsignedBigInteger('duplicate_of_media_hash_id')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['media_type', 'media_id']);
            $table->index('hash_value');
            $table->index(['mariachi_listing_id', 'hash_value']);
            $table->index(['mariachi_profile_id', 'hash_value']);
        });

        $this->seedDefaultPlans();
        $this->backfillProfileSubscriptions();
    }

    public function down(): void
    {
        Schema::dropIfExists('media_hashes');
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('ad_promotions');
        Schema::dropIfExists('subscription_cities');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }

    private function seedDefaultPlans(): void
    {
        $now = now();
        $plans = (array) config('monetization.plans', []);

        foreach ($plans as $code => $attributes) {
            DB::table('plans')->updateOrInsert(
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function backfillProfileSubscriptions(): void
    {
        $plansByCode = DB::table('plans')
            ->get(['id', 'code', 'price_cop', 'listing_limit'])
            ->keyBy('code');

        $defaultPlan = $plansByCode->get('basic');
        if (! $defaultPlan) {
            return;
        }

        DB::table('mariachi_profiles')
            ->select(['id', 'subscription_plan_code', 'subscription_active', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->lazy()
            ->each(function (object $profile) use ($plansByCode, $defaultPlan): void {
                $legacyCode = (string) ($profile->subscription_plan_code ?? '');
                $normalizedCode = match ($legacyCode) {
                    'starter' => 'basic',
                    'plus' => 'pro',
                    'pro' => 'premium',
                    default => in_array($legacyCode, ['basic', 'pro', 'premium'], true) ? $legacyCode : 'basic',
                };

                $targetPlan = $plansByCode->get($normalizedCode) ?? $defaultPlan;
                if (! $targetPlan) {
                    return;
                }

                $isActive = (bool) ($profile->subscription_active ?? true);
                $status = $isActive ? 'active' : 'inactive';
                $startsAt = $profile->created_at ?: now();
                $updatedAt = $profile->updated_at ?: now();

                DB::table('subscriptions')->updateOrInsert(
                    ['mariachi_profile_id' => $profile->id],
                    [
                        'plan_id' => $targetPlan->id,
                        'status' => $status,
                        'starts_at' => $startsAt,
                        'renews_at' => null,
                        'ends_at' => $isActive ? null : $updatedAt,
                        'cancelled_at' => $isActive ? null : $updatedAt,
                        'base_amount_cop' => (int) $targetPlan->price_cop,
                        'extra_city_amount_cop' => (int) config('monetization.additional_city_price_cop', 9900),
                        'metadata' => json_encode(['migrated_from_legacy' => true], JSON_THROW_ON_ERROR),
                        'created_at' => $startsAt,
                        'updated_at' => $updatedAt,
                    ]
                );

                DB::table('mariachi_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'subscription_plan_code' => $targetPlan->code,
                        'subscription_listing_limit' => (int) $targetPlan->listing_limit,
                    ]);
            });
    }
};
