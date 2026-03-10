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
        Schema::table('event_types', function (Blueprint $table): void {
            $table->string('slug', 160)->nullable()->after('name');
            $table->string('icon', 80)->nullable()->after('slug');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('icon');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });

        Schema::table('service_types', function (Blueprint $table): void {
            $table->string('slug', 160)->nullable()->after('name');
            $table->string('icon', 80)->nullable()->after('slug');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('icon');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });

        Schema::table('group_size_options', function (Blueprint $table): void {
            $table->string('slug', 160)->nullable()->after('name');
            $table->string('icon', 80)->nullable()->after('slug');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });

        Schema::table('budget_ranges', function (Blueprint $table): void {
            $table->string('slug', 160)->nullable()->after('name');
            $table->string('icon', 80)->nullable()->after('slug');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('icon');
            $table->boolean('is_featured')->default(false)->after('sort_order');
        });

        Schema::create('marketplace_cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_in_search')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'show_in_search'], 'marketplace_city_search_idx');
            $table->index(['is_active', 'sort_order'], 'marketplace_city_order_idx');
        });

        Schema::create('marketplace_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_city_id')->constrained('marketplace_cities')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 160);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('show_in_search')->default(true);
            $table->timestamps();

            $table->unique(['marketplace_city_id', 'slug'], 'marketplace_zone_city_slug_unique');
            $table->index(['marketplace_city_id', 'is_active'], 'marketplace_zone_city_active_idx');
            $table->index(['is_active', 'show_in_search'], 'marketplace_zone_search_idx');
        });

        Schema::create('catalog_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->string('catalog_type', 60);
            $table->string('proposed_name', 160);
            $table->string('proposed_slug', 180)->nullable();
            $table->json('context_data')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['catalog_type', 'status'], 'catalog_suggestions_type_status_idx');
            $table->index(['status', 'created_at'], 'catalog_suggestions_status_created_idx');
        });

        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->foreignId('marketplace_city_id')
                ->nullable()
                ->after('city_name')
                ->constrained('marketplace_cities')
                ->nullOnDelete();
            $table->index(['marketplace_city_id', 'is_active'], 'mariachi_listing_market_city_idx');
        });

        Schema::table('mariachi_listing_service_areas', function (Blueprint $table): void {
            $table->foreignId('marketplace_zone_id')
                ->nullable()
                ->after('mariachi_listing_id')
                ->constrained('marketplace_zones')
                ->nullOnDelete();
            $table->index(['marketplace_zone_id', 'mariachi_listing_id'], 'ml_service_area_zone_idx');
        });

        $this->backfillCatalogs();
        $this->backfillMarketplaceLocations();

        Schema::table('event_types', function (Blueprint $table): void {
            $table->unique('slug', 'event_types_slug_unique');
        });

        Schema::table('service_types', function (Blueprint $table): void {
            $table->unique('slug', 'service_types_slug_unique');
        });

        Schema::table('group_size_options', function (Blueprint $table): void {
            $table->unique('slug', 'group_size_options_slug_unique');
        });

        Schema::table('budget_ranges', function (Blueprint $table): void {
            $table->unique('slug', 'budget_ranges_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('budget_ranges', function (Blueprint $table): void {
            $table->dropUnique('budget_ranges_slug_unique');
            $table->dropColumn(['slug', 'icon', 'sort_order', 'is_featured']);
        });

        Schema::table('group_size_options', function (Blueprint $table): void {
            $table->dropUnique('group_size_options_slug_unique');
            $table->dropColumn(['slug', 'icon', 'is_featured']);
        });

        Schema::table('service_types', function (Blueprint $table): void {
            $table->dropUnique('service_types_slug_unique');
            $table->dropColumn(['slug', 'icon', 'sort_order', 'is_featured']);
        });

        Schema::table('event_types', function (Blueprint $table): void {
            $table->dropUnique('event_types_slug_unique');
            $table->dropColumn(['slug', 'icon', 'sort_order', 'is_featured']);
        });

        Schema::table('mariachi_listing_service_areas', function (Blueprint $table): void {
            $table->dropIndex('ml_service_area_zone_idx');
            $table->dropConstrainedForeignId('marketplace_zone_id');
        });

        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->dropIndex('mariachi_listing_market_city_idx');
            $table->dropConstrainedForeignId('marketplace_city_id');
        });

        Schema::dropIfExists('catalog_suggestions');
        Schema::dropIfExists('marketplace_zones');
        Schema::dropIfExists('marketplace_cities');
    }

    private function backfillCatalogs(): void
    {
        $this->populateCatalogFields(
            table: 'event_types',
            defaultIcon: 'confetti',
            tableIconMap: [
                'aniversarios' => 'sparkles',
                'bodas' => 'rings',
                'cumpleanos' => 'cake',
                'eventos-corporativos' => 'briefcase',
                'fiestas-privadas' => 'party',
                'serenatas' => 'music-note',
                'bautizos' => 'church',
                'funerales' => 'flower',
            ]
        );

        $this->populateCatalogFields(
            table: 'service_types',
            defaultIcon: 'settings',
            tableIconMap: [
                'a-domicilio' => 'home',
                'mariachi-por-horas' => 'clock',
                'serenata-sorpresa' => 'gift',
                'servicio-personalizado' => 'edit',
                'show-completo' => 'microphone',
            ]
        );

        $this->populateCatalogFields(
            table: 'group_size_options',
            defaultIcon: 'users',
            tableIconMap: [
                '3-integrantes' => 'users-3',
                '4-integrantes' => 'users-4',
                '5-integrantes' => 'users-5',
                '7-integrantes' => 'users-7',
                'mariachi-completo' => 'users-group',
            ],
            preserveSortOrder: true
        );

        $this->populateCatalogFields(
            table: 'budget_ranges',
            defaultIcon: 'coins',
            tableIconMap: [
                'economico' => 'wallet',
                'estandar' => 'coins',
                'premium' => 'diamond',
            ]
        );
    }

    /**
     * @param  array<string, string>  $tableIconMap
     */
    private function populateCatalogFields(string $table, string $defaultIcon, array $tableIconMap, bool $preserveSortOrder = false): void
    {
        $rows = DB::table($table)
            ->select(['id', 'name', 'sort_order'])
            ->orderBy('id')
            ->get();

        $usedSlugs = [];
        $nextSort = 1;

        foreach ($rows as $row) {
            $name = $this->normalizeText($row->name);
            if (! $name) {
                continue;
            }

            $baseSlug = Str::slug($name);
            $slug = $this->uniqueSlug($baseSlug !== '' ? $baseSlug : 'catalogo', $usedSlugs);

            $sortOrder = $preserveSortOrder
                ? max(1, (int) ($row->sort_order ?? $nextSort))
                : $nextSort;

            DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'name' => $name,
                    'slug' => $slug,
                    'icon' => $tableIconMap[$slug] ?? $defaultIcon,
                    'sort_order' => $sortOrder,
                    'updated_at' => now(),
                ]);

            $nextSort++;
        }
    }

    private function backfillMarketplaceLocations(): void
    {
        $cityRows = DB::table('mariachi_listings')
            ->selectRaw('LOWER(TRIM(city_name)) as city_key, MAX(city_name) as display_name, COUNT(*) as total')
            ->whereNotNull('city_name')
            ->whereRaw("TRIM(city_name) != ''")
            ->groupBy('city_key')
            ->orderByDesc('total')
            ->orderBy('display_name')
            ->get();

        $cityMap = [];
        $usedCitySlugs = [];
        $sortOrder = 1;

        foreach ($cityRows as $row) {
            $cityName = $this->normalizeText($row->display_name);
            if (! $cityName) {
                continue;
            }

            $cityKey = mb_strtolower($cityName);
            $citySlug = $this->uniqueSlug(Str::slug($cityName), $usedCitySlugs);

            $cityId = (int) DB::table('marketplace_cities')->insertGetId([
                'name' => $cityName,
                'slug' => $citySlug,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'is_featured' => $sortOrder <= 12,
                'show_in_search' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cityMap[$cityKey] = [
                'id' => $cityId,
                'name' => $cityName,
            ];

            $sortOrder++;
        }

        DB::table('mariachi_listings')
            ->select(['id', 'city_name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$cityMap, &$usedCitySlugs, &$sortOrder): void {
                foreach ($rows as $row) {
                    $cityName = $this->normalizeText($row->city_name);
                    if (! $cityName) {
                        continue;
                    }

                    $cityKey = mb_strtolower($cityName);
                    $city = $cityMap[$cityKey] ?? null;

                    if (! $city) {
                        $citySlug = $this->uniqueSlug(Str::slug($cityName), $usedCitySlugs);
                        $cityId = (int) DB::table('marketplace_cities')->insertGetId([
                            'name' => $cityName,
                            'slug' => $citySlug,
                            'is_active' => true,
                            'sort_order' => $sortOrder,
                            'is_featured' => false,
                            'show_in_search' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $city = ['id' => $cityId, 'name' => $cityName];
                        $cityMap[$cityKey] = $city;
                        $sortOrder++;
                    }

                    DB::table('mariachi_listings')
                        ->where('id', $row->id)
                        ->update([
                            'marketplace_city_id' => $city['id'],
                            'city_name' => $city['name'],
                            'updated_at' => now(),
                        ]);
                }
            });

        $zoneMap = [];
        $zoneSortOrders = [];
        $usedZoneSlugsByCity = [];

        DB::table('mariachi_listing_service_areas as service_areas')
            ->join('mariachi_listings as listings', 'listings.id', '=', 'service_areas.mariachi_listing_id')
            ->select([
                'service_areas.id as service_area_id',
                'service_areas.city_name as zone_name',
                'listings.marketplace_city_id as city_id',
            ])
            ->orderBy('service_areas.id')
            ->chunk(200, function ($rows) use (&$zoneMap, &$zoneSortOrders, &$usedZoneSlugsByCity): void {
                foreach ($rows as $row) {
                    $cityId = (int) ($row->city_id ?? 0);
                    $zoneName = $this->normalizeText($row->zone_name);

                    if ($cityId <= 0 || ! $zoneName) {
                        continue;
                    }

                    $zoneKey = $cityId.'::'.mb_strtolower($zoneName);
                    $zone = $zoneMap[$zoneKey] ?? null;

                    if (! $zone) {
                        $baseSlug = Str::slug($zoneName);
                        if ($baseSlug === '') {
                            $baseSlug = 'zona';
                        }

                        $used = $usedZoneSlugsByCity[$cityId] ?? [];
                        $slug = $this->uniqueSlug($baseSlug, $used);
                        $usedZoneSlugsByCity[$cityId] = $used;

                        $zoneSortOrders[$cityId] = ($zoneSortOrders[$cityId] ?? 0) + 1;

                        $zoneId = (int) DB::table('marketplace_zones')->insertGetId([
                            'marketplace_city_id' => $cityId,
                            'name' => $zoneName,
                            'slug' => $slug,
                            'is_active' => true,
                            'sort_order' => $zoneSortOrders[$cityId],
                            'show_in_search' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $zone = [
                            'id' => $zoneId,
                            'name' => $zoneName,
                        ];
                        $zoneMap[$zoneKey] = $zone;
                    }

                    DB::table('mariachi_listing_service_areas')
                        ->where('id', $row->service_area_id)
                        ->update([
                            'marketplace_zone_id' => $zone['id'],
                            'city_name' => $zone['name'],
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    /**
     * @param  array<int, string>  $usedSlugs
     */
    private function uniqueSlug(string $baseSlug, array &$usedSlugs): string
    {
        $base = trim($baseSlug) !== '' ? $baseSlug : 'item';
        $slug = $base;
        $counter = 2;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        $usedSlugs[] = $slug;

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
};
