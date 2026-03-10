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
        Schema::create('blog_cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('blog_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_city_id')->constrained('blog_cities')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->timestamps();

            $table->unique(['blog_city_id', 'slug'], 'blog_zone_city_slug_unique');
            $table->index('name');
        });

        Schema::create('blog_city_blog_post', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_city_id')->constrained('blog_cities')->cascadeOnDelete();

            $table->unique(['blog_post_id', 'blog_city_id'], 'blog_post_city_unique');
        });

        Schema::create('blog_post_blog_zone', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_zone_id')->constrained('blog_zones')->cascadeOnDelete();

            $table->unique(['blog_post_id', 'blog_zone_id'], 'blog_post_zone_unique');
        });

        Schema::create('blog_post_event_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('event_type_id')->constrained('event_types')->cascadeOnDelete();

            $table->unique(['blog_post_id', 'event_type_id'], 'blog_post_event_type_unique');
        });

        $this->backfillLocationCatalogFromListings();
        $this->backfillPostRelationsFromLegacyColumns();
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_event_type');
        Schema::dropIfExists('blog_post_blog_zone');
        Schema::dropIfExists('blog_city_blog_post');
        Schema::dropIfExists('blog_zones');
        Schema::dropIfExists('blog_cities');
    }

    private function backfillLocationCatalogFromListings(): void
    {
        $listingCities = DB::table('mariachi_listings')
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->pluck('city_name');

        foreach ($listingCities as $cityName) {
            $this->ensureCityId($cityName);
        }

        $zoneRows = DB::table('mariachi_listing_service_areas as service_areas')
            ->join('mariachi_listings as listings', 'listings.id', '=', 'service_areas.mariachi_listing_id')
            ->whereNotNull('listings.city_name')
            ->where('listings.city_name', '!=', '')
            ->whereNotNull('service_areas.city_name')
            ->where('service_areas.city_name', '!=', '')
            ->select([
                'listings.city_name as city_name',
                'service_areas.city_name as zone_name',
            ])
            ->get();

        foreach ($zoneRows as $row) {
            $cityId = $this->ensureCityId($row->city_name);
            if (! $cityId) {
                continue;
            }

            $this->ensureZoneId($cityId, $row->zone_name);
        }
    }

    private function backfillPostRelationsFromLegacyColumns(): void
    {
        $posts = DB::table('blog_posts')
            ->select(['id', 'city_name', 'zone_name', 'event_type_id'])
            ->orderBy('id')
            ->get();

        foreach ($posts as $post) {
            $cityId = $this->ensureCityId($post->city_name);
            if ($cityId) {
                $this->attachPostCity((int) $post->id, $cityId);
            }

            $zoneName = $this->normalizeName($post->zone_name);
            if ($zoneName) {
                $zoneCityId = $cityId;

                if (! $zoneCityId) {
                    $zoneCityId = DB::table('blog_zones')
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($zoneName)])
                        ->value('blog_city_id');
                }

                if (! $zoneCityId) {
                    $zoneCityId = $this->ensureCityId('Colombia');
                }

                if ($zoneCityId) {
                    $zoneId = $this->ensureZoneId((int) $zoneCityId, $zoneName);
                    if ($zoneId) {
                        $this->attachPostZone((int) $post->id, $zoneId);
                    }

                    if (! $cityId) {
                        $this->attachPostCity((int) $post->id, (int) $zoneCityId);
                    }
                }
            }

            if ($post->event_type_id) {
                $this->attachPostEventType((int) $post->id, (int) $post->event_type_id);
            }
        }
    }

    private function ensureCityId(mixed $rawName): ?int
    {
        $name = $this->normalizeName($rawName);
        if (! $name) {
            return null;
        }

        $slug = Str::slug($name);
        if ($slug === '') {
            return null;
        }

        $existingId = DB::table('blog_cities')->where('slug', $slug)->value('id');
        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('blog_cities')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureZoneId(int $cityId, mixed $rawName): ?int
    {
        $name = $this->normalizeName($rawName);
        if (! $name) {
            return null;
        }

        $slug = Str::slug($name);
        if ($slug === '') {
            return null;
        }

        $existingId = DB::table('blog_zones')
            ->where('blog_city_id', $cityId)
            ->where('slug', $slug)
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('blog_zones')->insertGetId([
            'blog_city_id' => $cityId,
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function attachPostCity(int $postId, int $cityId): void
    {
        DB::table('blog_city_blog_post')->updateOrInsert([
            'blog_post_id' => $postId,
            'blog_city_id' => $cityId,
        ]);
    }

    private function attachPostZone(int $postId, int $zoneId): void
    {
        DB::table('blog_post_blog_zone')->updateOrInsert([
            'blog_post_id' => $postId,
            'blog_zone_id' => $zoneId,
        ]);
    }

    private function attachPostEventType(int $postId, int $eventTypeId): void
    {
        DB::table('blog_post_event_type')->updateOrInsert([
            'blog_post_id' => $postId,
            'event_type_id' => $eventTypeId,
        ]);
    }

    private function normalizeName(mixed $rawValue): ?string
    {
        if (! is_string($rawValue)) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/', ' ', $rawValue));

        return $value !== '' ? $value : null;
    }
};
