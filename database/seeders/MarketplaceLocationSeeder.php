<?php

namespace Database\Seeders;

use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketplaceLocationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('marketplace_cities') || ! Schema::hasTable('marketplace_zones')) {
            return;
        }

        $this->syncCities();
    }

    /**
     * @return array<string, MarketplaceCity>
     */
    private function syncCities(): array
    {
        $cityNames = collect();

        if (Schema::hasTable('mariachi_listings')) {
            $cityNames = $cityNames->merge(
                DB::table('mariachi_listings')
                    ->whereNotNull('city_name')
                    ->whereRaw("TRIM(city_name) != ''")
                    ->pluck('city_name')
            );
        }

        if (Schema::hasTable('mariachi_profiles')) {
            $cityNames = $cityNames->merge(
                DB::table('mariachi_profiles')
                    ->whereNotNull('city_name')
                    ->whereRaw("TRIM(city_name) != ''")
                    ->pluck('city_name')
            );
        }

        $cityNames = $cityNames
            ->map(fn (mixed $name): ?string => $this->normalizeText($name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values();

        $map = [];
        $usedSlugs = MarketplaceCity::query()->pluck('slug')->filter()->values()->all();

        foreach ($cityNames as $index => $cityName) {
            $existing = MarketplaceCity::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)])
                ->first();

            if (! $existing) {
                $baseSlug = Str::slug($cityName);
                if ($baseSlug === '') {
                    $baseSlug = 'ciudad';
                }

                $slug = $this->resolveUniqueSlug($baseSlug, $usedSlugs);

                $existing = MarketplaceCity::query()->create([
                    'name' => $cityName,
                    'slug' => $slug,
                    'is_active' => true,
                    'sort_order' => ((int) MarketplaceCity::query()->max('sort_order')) + 1 + $index,
                    'is_featured' => false,
                    'show_in_search' => true,
                ]);
            }

            $map[mb_strtolower($cityName)] = $existing;
        }

        return $map;
    }

    /**
     * @param  array<string, MarketplaceCity>  $cityMap
     */
    private function syncZones(array $cityMap): void
    {
        $zoneRows = collect();

        if (Schema::hasTable('mariachi_listing_service_areas') && Schema::hasTable('mariachi_listings')) {
            $zoneRows = $zoneRows->merge(
                DB::table('mariachi_listing_service_areas as areas')
                    ->join('mariachi_listings as listings', 'listings.id', '=', 'areas.mariachi_listing_id')
                    ->whereNotNull('listings.city_name')
                    ->whereRaw("TRIM(listings.city_name) != ''")
                    ->whereNotNull('areas.city_name')
                    ->whereRaw("TRIM(areas.city_name) != ''")
                    ->select(['listings.city_name as city_name', 'areas.city_name as zone_name'])
                    ->get()
            );
        }

        if (Schema::hasTable('mariachi_service_areas') && Schema::hasTable('mariachi_profiles')) {
            $zoneRows = $zoneRows->merge(
                DB::table('mariachi_service_areas as areas')
                    ->join('mariachi_profiles as profiles', 'profiles.id', '=', 'areas.mariachi_profile_id')
                    ->whereNotNull('profiles.city_name')
                    ->whereRaw("TRIM(profiles.city_name) != ''")
                    ->whereNotNull('areas.city_name')
                    ->whereRaw("TRIM(areas.city_name) != ''")
                    ->select(['profiles.city_name as city_name', 'areas.city_name as zone_name'])
                    ->get()
            );
        }

        $usedByCity = [];

        foreach ($zoneRows as $row) {
            $cityName = $this->normalizeText($row->city_name ?? null);
            $zoneName = $this->normalizeText($row->zone_name ?? null);

            if (! $cityName || ! $zoneName) {
                continue;
            }

            $city = $cityMap[mb_strtolower($cityName)] ?? null;
            if (! $city) {
                continue;
            }

            $existing = MarketplaceZone::query()
                ->where('marketplace_city_id', $city->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($zoneName)])
                ->first();

            if ($existing) {
                continue;
            }

            $baseSlug = Str::slug($zoneName);
            if ($baseSlug === '') {
                $baseSlug = 'zona';
            }

            $usedByCity[$city->id] = $usedByCity[$city->id] ?? MarketplaceZone::query()
                ->where('marketplace_city_id', $city->id)
                ->pluck('slug')
                ->filter()
                ->values()
                ->all();

            $slug = $this->resolveUniqueSlug($baseSlug, $usedByCity[$city->id]);

            MarketplaceZone::query()->create([
                'marketplace_city_id' => $city->id,
                'name' => $zoneName,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => ((int) MarketplaceZone::query()->where('marketplace_city_id', $city->id)->max('sort_order')) + 1,
                'show_in_search' => true,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $used
     */
    private function resolveUniqueSlug(string $baseSlug, array &$used): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (in_array($slug, $used, true)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $used[] = $slug;

        return $slug;
    }

    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', $value));

        return $normalized !== '' ? $normalized : null;
    }
}
