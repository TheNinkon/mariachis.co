<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->string('verification_status', 40)->default('unverified');
            $table->text('verification_notes')->nullable();
            $table->string('subscription_plan_code', 40)->default('starter');
            $table->unsignedSmallInteger('subscription_listing_limit')->default(1);
            $table->boolean('subscription_active')->default(true);
            $table->unsignedBigInteger('default_mariachi_listing_id')->nullable();
        });

        Schema::create('mariachi_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title', 180);
            $table->string('short_description', 280)->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();

            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city_name', 120)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('travels_to_other_cities')->default(false);

            $table->unsignedTinyInteger('listing_completion')->default(0);
            $table->boolean('listing_completed')->default(false);

            $table->string('status', 30)->default('draft');
            $table->boolean('is_active')->default(false);
            $table->string('selected_plan_code', 40)->nullable();
            $table->timestamp('plan_selected_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();

            $table->boolean('watermark_enabled')->default(false);
            $table->boolean('image_hashing_enabled')->default(true);
            $table->boolean('has_duplicate_images')->default(false);

            $table->timestamps();

            $table->index(['mariachi_profile_id', 'status']);
            $table->index(['mariachi_profile_id', 'is_active']);
            $table->index(['city_name', 'is_active']);
        });

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->foreign('default_mariachi_listing_id')
                ->references('id')
                ->on('mariachi_listings')
                ->nullOnDelete();
        });

        Schema::create('mariachi_listing_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->string('image_hash', 80)->nullable();
            $table->timestamp('watermark_applied_at')->nullable();
            $table->string('watermark_version', 40)->nullable();
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'sort_order']);
            $table->index(['image_hash']);
        });

        Schema::create('mariachi_listing_videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('platform', 50)->default('external');
            $table->timestamps();
        });

        Schema::create('mariachi_listing_service_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->string('city_name');
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'city_name'], 'ml_service_area_city_idx');
        });

        Schema::create('mariachi_listing_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->string('question', 240);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'sort_order'], 'ml_faq_sort_idx');
        });

        Schema::create('event_type_mariachi_listing', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['event_type_id', 'mariachi_listing_id'], 'event_listing_unique');
        });

        Schema::create('mariachi_listing_service_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_listing_id', 'service_type_id'], 'listing_service_unique');
        });

        Schema::create('budget_range_mariachi_listing', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_range_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_listing_id', 'budget_range_id'], 'listing_budget_unique');
        });

        Schema::create('group_size_option_mariachi_listing', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_size_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_listing_id', 'group_size_option_id'], 'listing_group_size_unique');
        });

        Schema::table('quote_conversations', function (Blueprint $table): void {
            $table->foreignId('mariachi_listing_id')->nullable()->after('mariachi_profile_id')->constrained()->nullOnDelete();
            $table->index(['mariachi_listing_id', 'status']);
        });

        Schema::table('mariachi_reviews', function (Blueprint $table): void {
            $table->foreignId('mariachi_listing_id')->nullable()->after('mariachi_profile_id')->constrained()->nullOnDelete();
            $table->index(['mariachi_listing_id', 'moderation_status']);
        });

        Schema::table('client_favorites', function (Blueprint $table): void {
            $table->index('user_id', 'client_favorites_user_idx');
            $table->index('mariachi_profile_id', 'client_favorites_profile_idx');
            $table->dropUnique('client_favorites_user_id_mariachi_profile_id_unique');
            $table->foreignId('mariachi_listing_id')->nullable()->after('mariachi_profile_id')->constrained()->nullOnDelete();
            $table->unique(['user_id', 'mariachi_listing_id'], 'client_favorites_user_listing_unique');
        });

        Schema::table('client_recent_views', function (Blueprint $table): void {
            $table->index('user_id', 'client_recent_views_user_idx');
            $table->index('mariachi_profile_id', 'client_recent_views_profile_idx');
            $table->dropUnique('client_recent_views_user_id_mariachi_profile_id_unique');
            $table->foreignId('mariachi_listing_id')->nullable()->after('mariachi_profile_id')->constrained()->nullOnDelete();
            $table->unique(['user_id', 'mariachi_listing_id'], 'client_recent_views_user_listing_unique');
        });

        $this->backfillListingsFromLegacyProfiles();
    }

    public function down(): void
    {
        Schema::table('client_recent_views', function (Blueprint $table): void {
            $table->dropUnique('client_recent_views_user_listing_unique');
            $table->dropConstrainedForeignId('mariachi_listing_id');
            $table->dropIndex('client_recent_views_user_idx');
            $table->dropIndex('client_recent_views_profile_idx');
            $table->unique(['user_id', 'mariachi_profile_id']);
        });

        Schema::table('client_favorites', function (Blueprint $table): void {
            $table->dropUnique('client_favorites_user_listing_unique');
            $table->dropConstrainedForeignId('mariachi_listing_id');
            $table->dropIndex('client_favorites_user_idx');
            $table->dropIndex('client_favorites_profile_idx');
            $table->unique(['user_id', 'mariachi_profile_id']);
        });

        Schema::table('mariachi_reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mariachi_listing_id');
        });

        Schema::table('quote_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mariachi_listing_id');
        });

        Schema::dropIfExists('group_size_option_mariachi_listing');
        Schema::dropIfExists('budget_range_mariachi_listing');
        Schema::dropIfExists('mariachi_listing_service_type');
        Schema::dropIfExists('event_type_mariachi_listing');
        Schema::dropIfExists('mariachi_listing_faqs');
        Schema::dropIfExists('mariachi_listing_service_areas');
        Schema::dropIfExists('mariachi_listing_videos');
        Schema::dropIfExists('mariachi_listing_photos');

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->dropForeign(['default_mariachi_listing_id']);
        });

        Schema::dropIfExists('mariachi_listings');

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_status',
                'verification_notes',
                'subscription_plan_code',
                'subscription_listing_limit',
                'subscription_active',
                'default_mariachi_listing_id',
            ]);
        });
    }

    private function backfillListingsFromLegacyProfiles(): void
    {
        $profiles = DB::table('mariachi_profiles')
            ->select([
                'id',
                'business_name',
                'slug',
                'short_description',
                'full_description',
                'base_price',
                'country',
                'state',
                'city_name',
                'postal_code',
                'address',
                'latitude',
                'longitude',
                'travels_to_other_cities',
                'profile_completion',
                'profile_completed',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->get();

        foreach ($profiles as $profile) {
            $existingListingId = DB::table('mariachi_listings')
                ->where('mariachi_profile_id', $profile->id)
                ->value('id');

            $listingId = $existingListingId ?: $this->createListingFromProfile($profile);

            DB::table('mariachi_profiles')
                ->where('id', $profile->id)
                ->update(['default_mariachi_listing_id' => $listingId]);

            DB::table('quote_conversations')
                ->where('mariachi_profile_id', $profile->id)
                ->whereNull('mariachi_listing_id')
                ->update(['mariachi_listing_id' => $listingId]);

            DB::table('mariachi_reviews')
                ->where('mariachi_profile_id', $profile->id)
                ->whereNull('mariachi_listing_id')
                ->update(['mariachi_listing_id' => $listingId]);

            DB::table('client_favorites')
                ->where('mariachi_profile_id', $profile->id)
                ->whereNull('mariachi_listing_id')
                ->update(['mariachi_listing_id' => $listingId]);

            DB::table('client_recent_views')
                ->where('mariachi_profile_id', $profile->id)
                ->whereNull('mariachi_listing_id')
                ->update(['mariachi_listing_id' => $listingId]);

            $this->copyListingMedia($profile->id, $listingId);
            $this->copyListingPivots($profile->id, $listingId);
        }
    }

    private function createListingFromProfile(object $profile): int
    {
        $titleBase = trim((string) ($profile->business_name ?: 'Servicio de mariachi'));
        $defaultTitle = $titleBase !== '' ? $titleBase : 'Servicio de mariachi';
        $baseSlug = trim((string) ($profile->slug ?: Str::slug($defaultTitle)));
        $safeBaseSlug = $baseSlug !== '' ? $baseSlug : 'mariachi-'.$profile->id;
        $slug = $this->ensureUniqueListingSlug($safeBaseSlug);

        $isActive = (bool) $profile->profile_completed;
        $status = $isActive ? 'active' : 'draft';

        return (int) DB::table('mariachi_listings')->insertGetId([
            'mariachi_profile_id' => $profile->id,
            'slug' => $slug,
            'title' => $defaultTitle,
            'short_description' => $profile->short_description,
            'description' => $profile->full_description,
            'base_price' => $profile->base_price,
            'country' => $profile->country,
            'state' => $profile->state,
            'city_name' => $profile->city_name,
            'postal_code' => $profile->postal_code,
            'address' => $profile->address,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'travels_to_other_cities' => (bool) $profile->travels_to_other_cities,
            'listing_completion' => (int) ($profile->profile_completion ?? 0),
            'listing_completed' => (bool) $profile->profile_completed,
            'status' => $status,
            'is_active' => $isActive,
            'selected_plan_code' => $isActive ? 'starter' : null,
            'plan_selected_at' => $isActive ? now() : null,
            'activated_at' => $isActive ? now() : null,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ]);
    }

    private function ensureUniqueListingSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (DB::table('mariachi_listings')->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function copyListingMedia(int $profileId, int $listingId): void
    {
        $legacyPhotos = DB::table('mariachi_photos')
            ->where('mariachi_profile_id', $profileId)
            ->orderBy('sort_order')
            ->get();

        foreach ($legacyPhotos as $photo) {
            DB::table('mariachi_listing_photos')->insert([
                'mariachi_listing_id' => $listingId,
                'path' => $photo->path,
                'title' => $photo->title,
                'sort_order' => $photo->sort_order,
                'is_featured' => (bool) $photo->is_featured,
                'created_at' => $photo->created_at,
                'updated_at' => $photo->updated_at,
            ]);
        }

        $legacyVideos = DB::table('mariachi_videos')
            ->where('mariachi_profile_id', $profileId)
            ->get();

        foreach ($legacyVideos as $video) {
            DB::table('mariachi_listing_videos')->insert([
                'mariachi_listing_id' => $listingId,
                'url' => $video->url,
                'platform' => $video->platform,
                'created_at' => $video->created_at,
                'updated_at' => $video->updated_at,
            ]);
        }

        $legacyAreas = DB::table('mariachi_service_areas')
            ->where('mariachi_profile_id', $profileId)
            ->get();

        foreach ($legacyAreas as $area) {
            DB::table('mariachi_listing_service_areas')->insert([
                'mariachi_listing_id' => $listingId,
                'city_name' => $area->city_name,
                'created_at' => $area->created_at,
                'updated_at' => $area->updated_at,
            ]);
        }
    }

    private function copyListingPivots(int $profileId, int $listingId): void
    {
        $eventTypeIds = DB::table('event_type_mariachi_profile')
            ->where('mariachi_profile_id', $profileId)
            ->pluck('event_type_id');

        foreach ($eventTypeIds as $eventTypeId) {
            DB::table('event_type_mariachi_listing')->insert([
                'event_type_id' => $eventTypeId,
                'mariachi_listing_id' => $listingId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $serviceTypeIds = DB::table('mariachi_profile_service_type')
            ->where('mariachi_profile_id', $profileId)
            ->pluck('service_type_id');

        foreach ($serviceTypeIds as $serviceTypeId) {
            DB::table('mariachi_listing_service_type')->insert([
                'mariachi_listing_id' => $listingId,
                'service_type_id' => $serviceTypeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $budgetRangeIds = DB::table('budget_range_mariachi_profile')
            ->where('mariachi_profile_id', $profileId)
            ->pluck('budget_range_id');

        foreach ($budgetRangeIds as $budgetRangeId) {
            DB::table('budget_range_mariachi_listing')->insert([
                'mariachi_listing_id' => $listingId,
                'budget_range_id' => $budgetRangeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groupSizeIds = DB::table('group_size_option_mariachi_profile')
            ->where('mariachi_profile_id', $profileId)
            ->pluck('group_size_option_id');

        foreach ($groupSizeIds as $groupSizeId) {
            DB::table('group_size_option_mariachi_listing')->insert([
                'mariachi_listing_id' => $listingId,
                'group_size_option_id' => $groupSizeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
