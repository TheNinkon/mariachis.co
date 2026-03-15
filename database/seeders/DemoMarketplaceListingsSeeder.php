<?php

namespace Database\Seeders;

use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\ListingPayment;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\ProfileVerificationPayment;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\PlanAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoMarketplaceListingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensurePrerequisites();

        $profiles = $this->demoProfiles();
        if ($profiles->isEmpty()) {
            return;
        }

        $plans = Plan::query()->get()->keyBy('code');
        $cityMap = MarketplaceCity::query()
            ->get()
            ->keyBy(fn (MarketplaceCity $city): string => $this->cityLookupKey((string) $city->name));
        $zonesByCity = MarketplaceZone::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_city_id');

        $eventTypes = EventType::query()->orderBy('name')->pluck('id')->all();
        $serviceTypes = ServiceType::query()->orderBy('name')->pluck('id')->all();
        $groupSizes = GroupSizeOption::query()->orderBy('sort_order')->pluck('id')->all();
        $budgetRanges = BudgetRange::query()->orderBy('sort_order')->pluck('id')->all();
        $imagePool = $this->imagePool();
        $reviewerId = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->orderByRaw("case when role = 'admin' then 0 else 1 end")
            ->value('id');

        if ($imagePool === []) {
            return;
        }

        foreach ($profiles as $index => $profile) {
            $profilePlanCode = $this->planCodeForIndex($index);
            $plan = $plans->get($profilePlanCode);
            if (! $plan) {
                continue;
            }

            $durationMonths = $this->durationForIndex($index);
            $verifiedProfile = $this->shouldVerifyProfile($index, $profilePlanCode);
            $activatedAt = CarbonImmutable::now()->subDays(18 + ($index * 2));
            $expiresAt = $activatedAt->addMonthsNoOverflow($durationMonths);
            $listingSlug = $this->listingSlug($profile);
            $cityName = (string) ($profile->city_name ?: 'Bogota');
            $city = $cityMap->get($this->cityLookupKey($cityName));
            $cityZones = collect($zonesByCity->get($city?->id, []));
            $travelToOtherCities = $profilePlanCode !== 'basic' && $index % 2 === 0;

            $listing = MariachiListing::query()->firstOrNew(['slug' => $listingSlug]);
            $listing->fill([
                'mariachi_profile_id' => $profile->id,
                'title' => $this->listingTitle($profile, $index),
                'short_description' => $this->shortDescription($profile, $profilePlanCode),
                'description' => $this->fullDescription($profile, $profilePlanCode, $verifiedProfile),
                'base_price' => $this->basePrice($profile, $profilePlanCode),
                'country' => $profile->country ?: 'Colombia',
                'state' => $profile->state,
                'city_name' => $cityName,
                'zone_name' => $cityZones->first()?->name,
                'marketplace_city_id' => $city?->id,
                'postal_code' => $profile->postal_code,
                'address' => $profile->address ?: ($cityName.' Centro'),
                'latitude' => $profile->latitude,
                'longitude' => $profile->longitude,
                'travels_to_other_cities' => $travelToOtherCities,
                'selected_plan_code' => $profilePlanCode,
                'plan_duration_months' => $durationMonths,
                'plan_selected_at' => $activatedAt,
                'submitted_for_review_at' => $activatedAt->subDays(2),
                'reviewed_at' => $activatedAt->subDay(),
                'reviewed_by_user_id' => $reviewerId,
                'review_status' => MariachiListing::REVIEW_APPROVED,
                'payment_status' => MariachiListing::PAYMENT_APPROVED,
                'status' => MariachiListing::STATUS_ACTIVE,
                'is_active' => true,
                'activated_at' => $activatedAt,
                'plan_expires_at' => $expiresAt,
                'deactivated_at' => null,
                'rejection_reason' => null,
                'watermark_enabled' => false,
                'image_hashing_enabled' => true,
                'has_duplicate_images' => false,
            ]);
            $listing->save();

            $eventTypeIds = $this->takeIds(
                $profile->eventTypes->pluck('id')->all(),
                $eventTypes,
                $this->eventLimitForPlan($profilePlanCode)
            );
            $serviceTypeIds = $this->takeIds(
                $profile->serviceTypes->pluck('id')->all(),
                $serviceTypes,
                $this->serviceLimitForPlan($profilePlanCode)
            );
            $groupSizeIds = $this->takeIds(
                $profile->groupSizeOptions->pluck('id')->all(),
                $groupSizes,
                $this->groupLimitForPlan($profilePlanCode)
            );
            $budgetRangeIds = $this->takeIds(
                $profile->budgetRanges->pluck('id')->all(),
                $budgetRanges,
                $this->budgetLimitForPlan($profilePlanCode)
            );

            $listing->eventTypes()->sync($eventTypeIds);
            $listing->serviceTypes()->sync($serviceTypeIds);
            $listing->groupSizeOptions()->sync($groupSizeIds);
            $listing->budgetRanges()->sync($budgetRangeIds);

            $selectedImages = $this->selectImages($imagePool, $index, $this->photoCountForPlan($profilePlanCode));
            $listingPhotoPaths = $this->syncListingPhotos($listing, $selectedImages);
            $proofPath = $this->storeProofImage(
                directory: 'demo/payment-proofs/listings',
                identifier: $listingSlug,
                sourcePath: $selectedImages[0] ?? null
            );

            $this->syncListingVideos($listing, $profilePlanCode, $profile, $index);
            $this->syncListingFaqs($listing, $profile, $cityName);
            $this->syncServiceAreas($listing, $travelToOtherCities, $cityZones, $cityName);
            $this->refreshListingProgress($listing->fresh());

            $subscription = app(PlanAssignmentService::class)->assignToProfile(
                $profile,
                $plan,
                $listing->fresh(),
                'demo_marketplace_seeder',
                [
                    'demo_seed' => true,
                    'seed_version' => 1,
                ],
                true,
                $durationMonths,
                ((int) $plan->price_cop) * $durationMonths
            );

            $subscription->update([
                'starts_at' => $activatedAt,
                'renews_at' => $expiresAt,
                'ends_at' => $expiresAt,
                'base_amount_cop' => ((int) $plan->price_cop) * $durationMonths,
                'metadata' => array_merge((array) $subscription->metadata, [
                    'demo_seed' => true,
                    'seed_listing_slug' => $listingSlug,
                ]),
            ]);

            $listing->update([
                'selected_plan_code' => $profilePlanCode,
                'plan_duration_months' => $durationMonths,
                'plan_selected_at' => $activatedAt,
                'payment_status' => MariachiListing::PAYMENT_APPROVED,
                'review_status' => MariachiListing::REVIEW_APPROVED,
                'submitted_for_review_at' => $activatedAt->subDays(2),
                'reviewed_at' => $activatedAt->subDay(),
                'reviewed_by_user_id' => $reviewerId,
                'status' => MariachiListing::STATUS_ACTIVE,
                'is_active' => true,
                'activated_at' => $activatedAt,
                'plan_expires_at' => $expiresAt,
                'deactivated_at' => null,
            ]);

            ListingPayment::query()->updateOrCreate(
                [
                    'mariachi_listing_id' => $listing->id,
                    'reference_text' => 'DEMO-LST-'.Str::upper($listingSlug),
                ],
                [
                    'mariachi_profile_id' => $profile->id,
                    'plan_code' => $profilePlanCode,
                    'duration_months' => $durationMonths,
                    'amount_cop' => ((int) $plan->price_cop) * $durationMonths,
                    'method' => ListingPayment::METHOD_NEQUI,
                    'proof_path' => $proofPath ?: ($listingPhotoPaths[0] ?? 'demo/payment-proofs/listings/default.jpg'),
                    'status' => ListingPayment::STATUS_APPROVED,
                    'reviewed_by' => $reviewerId,
                    'reviewed_at' => $activatedAt->subDay(),
                    'rejection_reason' => null,
                ]
            );

            $profile->update([
                'subscription_plan_code' => $profilePlanCode,
                'subscription_listing_limit' => $plan->listing_limit,
                'subscription_active' => true,
                'default_mariachi_listing_id' => $listing->id,
                'verification_status' => $verifiedProfile ? 'verified' : 'unverified',
                'verification_notes' => $verifiedProfile ? 'Perfil verificado de demostracion.' : null,
                'verification_expires_at' => $verifiedProfile ? $activatedAt->addMonthsNoOverflow($this->verificationDurationForPlan($profilePlanCode)) : null,
            ]);

            $this->syncVerificationPayment(
                profile: $profile->fresh(),
                reviewerId: $reviewerId,
                verifiedProfile: $verifiedProfile,
                sourcePath: $selectedImages[1] ?? ($selectedImages[0] ?? null),
                activatedAt: $activatedAt,
                planCode: $profilePlanCode
            );
        }
    }

    private function ensurePrerequisites(): void
    {
        if (Plan::query()->count() === 0) {
            $this->call(PlanSeeder::class);
        }

        if (EventType::query()->count() === 0) {
            $this->call(EventTypeSeeder::class);
        }

        if (ServiceType::query()->count() === 0) {
            $this->call(ServiceTypeSeeder::class);
        }

        if (GroupSizeOption::query()->count() === 0) {
            $this->call(GroupSizeOptionSeeder::class);
        }

        if (BudgetRange::query()->count() === 0) {
            $this->call(BudgetRangeSeeder::class);
        }

        if (! User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])->exists()) {
            $this->call([
                AdminUserSeeder::class,
                StaffUserSeeder::class,
            ]);
        }

        if (! MariachiProfile::query()->whereHas('user', function ($query): void {
            $query->where('role', User::ROLE_MARIACHI);
        })->exists()) {
            $this->call(RichMariachiProfilesSeeder::class);
        }

        if (MarketplaceCity::query()->count() === 0) {
            $this->call(MarketplaceLocationSeeder::class);
        }

        $this->call(MarketplaceZoneSeederBogotaMedellin::class);
    }

    /**
     * @return Collection<int, MariachiProfile>
     */
    private function demoProfiles(): Collection
    {
        $profiles = MariachiProfile::query()
            ->with([
                'user:id,email,role,status',
                'eventTypes:id',
                'serviceTypes:id',
                'groupSizeOptions:id',
                'budgetRanges:id',
            ])
            ->whereHas('user', function ($query): void {
                $query->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE)
                    ->where('email', 'like', '%@mariachis.co');
            })
            ->orderBy('city_name')
            ->orderBy('id')
            ->limit(15)
            ->get();

        if ($profiles->isNotEmpty()) {
            return $profiles;
        }

        return MariachiProfile::query()
            ->with([
                'user:id,email,role,status',
                'eventTypes:id',
                'serviceTypes:id',
                'groupSizeOptions:id',
                'budgetRanges:id',
            ])
            ->whereHas('user', function ($query): void {
                $query->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE);
            })
            ->orderBy('city_name')
            ->orderBy('id')
            ->limit(15)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function imagePool(): array
    {
        $candidates = [];

        foreach ([base_path('Mariachis.img'), public_path('marketplace/img')] as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            $files = collect(File::files($directory))
                ->filter(fn (\SplFileInfo $file): bool => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
                ->sortBy(fn (\SplFileInfo $file): string => $file->getFilename())
                ->map(fn (\SplFileInfo $file): string => $file->getRealPath() ?: $file->getPathname())
                ->values()
                ->all();

            if ($files !== []) {
                $candidates = array_merge($candidates, $files);
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<int, string>  $images
     * @return array<int, string>
     */
    private function selectImages(array $images, int $offset, int $count): array
    {
        $selected = [];
        $total = count($images);

        if ($total === 0) {
            return $selected;
        }

        for ($index = 0; $index < $count; $index++) {
            $selected[] = $images[($offset * 3 + $index) % $total];
        }

        return array_values(array_unique($selected));
    }

    private function listingSlug(MariachiProfile $profile): string
    {
        $base = $profile->slug ?: ('mariachi-'.$profile->id);

        return Str::slug($base.' demo');
    }

    private function listingTitle(MariachiProfile $profile, int $index): string
    {
        $themes = [
            'serenatas romanticas',
            'bodas y eventos elegantes',
            'cumpleanos y serenatas sorpresa',
            'eventos corporativos',
            'quinceaneras y aniversarios',
        ];

        $theme = $themes[$index % count($themes)];

        return trim(($profile->business_name ?: 'Mariachi').' | '.$theme.' en '.($profile->city_name ?: 'Colombia'));
    }

    private function shortDescription(MariachiProfile $profile, string $planCode): string
    {
        $planLabel = match ($planCode) {
            'premium' => 'premium',
            'pro' => 'pro',
            default => 'basico',
        };

        return Str::limit(
            ($profile->business_name ?: 'Mariachi').' ofrece paquete '.$planLabel.' para bodas, serenatas y eventos en '.($profile->city_name ?: 'Colombia').'.',
            250,
            ''
        );
    }

    private function fullDescription(MariachiProfile $profile, string $planCode, bool $verifiedProfile): string
    {
        $verificationCopy = $verifiedProfile
            ? 'Este perfil incluye verificacion activa para pruebas del marketplace.'
            : 'Este perfil permanece sin verificacion activa para validar la experiencia base.';

        $planCopy = match ($planCode) {
            'premium' => 'Incluye prioridad alta, contacto directo y galeria amplia.',
            'pro' => 'Incluye contacto directo, video y mejor prioridad en listados.',
            default => 'Incluye configuracion esencial para validar la publicacion de anuncios.',
        };

        return trim(
            ($profile->full_description ?: $profile->short_description ?: 'Mariachi de demostracion para pruebas internas.')
            .' '.$planCopy
            .' '.$verificationCopy
        );
    }

    private function basePrice(MariachiProfile $profile, string $planCode): int
    {
        $base = (int) round((float) ($profile->base_price ?: 320000));

        return match ($planCode) {
            'premium' => max($base + 140000, 520000),
            'pro' => max($base + 60000, 380000),
            default => max($base, 250000),
        };
    }

    private function planCodeForIndex(int $index): string
    {
        return ['basic', 'pro', 'premium'][$index % 3];
    }

    private function durationForIndex(int $index): int
    {
        return [1, 3, 12][$index % 3];
    }

    private function shouldVerifyProfile(int $index, string $planCode): bool
    {
        if ($planCode === 'basic') {
            return false;
        }

        return $planCode === 'premium' || $index % 2 === 1;
    }

    private function verificationDurationForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 12,
            'pro' => 3,
            default => 1,
        };
    }

    private function photoCountForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 8,
            'pro' => 6,
            default => 4,
        };
    }

    private function eventLimitForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 4,
            'pro' => 3,
            default => 2,
        };
    }

    private function serviceLimitForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 3,
            'pro' => 2,
            default => 1,
        };
    }

    private function groupLimitForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 3,
            'pro' => 2,
            default => 1,
        };
    }

    private function budgetLimitForPlan(string $planCode): int
    {
        return match ($planCode) {
            'premium' => 3,
            'pro' => 2,
            default => 1,
        };
    }

    /**
     * @param  array<int, int>  $preferred
     * @param  array<int, int>  $fallback
     * @return array<int, int>
     */
    private function takeIds(array $preferred, array $fallback, int $limit): array
    {
        $resolved = collect($preferred)
            ->filter()
            ->merge($fallback)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        return $resolved;
    }

    /**
     * @param  array<int, string>  $sourceImages
     * @return array<int, string>
     */
    private function syncListingPhotos(MariachiListing $listing, array $sourceImages): array
    {
        $disk = Storage::disk('public');

        foreach ($listing->photos as $photo) {
            $disk->delete($photo->path);
        }

        $listing->photos()->delete();

        $storedPaths = [];

        foreach ($sourceImages as $index => $sourcePath) {
            $targetPath = $this->storeImageCopy(
                directory: 'demo/listings/'.$listing->slug,
                identifier: ($index + 1).'-'.basename($sourcePath),
                sourcePath: $sourcePath
            );

            if (! $targetPath) {
                continue;
            }

            $storedPaths[] = $targetPath;

            $listing->photos()->create([
                'path' => $targetPath,
                'title' => 'Demo '.($index + 1),
                'sort_order' => $index + 1,
                'is_featured' => $index === 0,
            ]);
        }

        return $storedPaths;
    }

    private function syncListingVideos(MariachiListing $listing, string $planCode, MariachiProfile $profile, int $index): void
    {
        $listing->videos()->delete();

        if ($planCode === 'basic') {
            return;
        }

        $fallbackUrls = [
            'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'https://www.youtube.com/watch?v=jNQXAC9IVRw',
            'https://www.youtube.com/watch?v=ScMzIvxBSi4',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];

        $urls = array_filter([
            $profile->youtube,
            $fallbackUrls[$index % count($fallbackUrls)],
        ]);

        foreach (array_slice(array_values(array_unique($urls)), 0, $planCode === 'premium' ? 2 : 1) as $url) {
            $listing->videos()->create([
                'url' => $url,
                'platform' => Str::contains($url, ['youtube.com', 'youtu.be']) ? 'youtube' : 'external',
            ]);
        }
    }

    private function syncListingFaqs(MariachiListing $listing, MariachiProfile $profile, string $cityName): void
    {
        $listing->faqs()->delete();

        $rows = [
            [
                'question' => 'Que incluye el servicio en '.$cityName.'?',
                'answer' => 'Incluye repertorio de mariachi, coordinacion previa y llegada puntual al evento.',
            ],
            [
                'question' => 'Cuanto tiempo dura la presentacion?',
                'answer' => 'La duracion se ajusta al paquete contratado y al tipo de evento del cliente.',
            ],
        ];

        foreach ($rows as $index => $row) {
            $listing->faqs()->create([
                'question' => $row['question'],
                'answer' => $row['answer'],
                'sort_order' => $index + 1,
                'is_visible' => true,
            ]);
        }
    }

    /**
     * @param  Collection<int, MarketplaceZone>  $cityZones
     */
    private function syncServiceAreas(
        MariachiListing $listing,
        bool $travelsToOtherCities,
        Collection $cityZones,
        string $cityName
    ): void {
        $listing->serviceAreas()->delete();

        $rows = $cityZones
            ->take($travelsToOtherCities ? 3 : 1)
            ->map(fn (MarketplaceZone $zone): array => [
                'marketplace_zone_id' => $zone->id,
                'city_name' => $zone->name,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            $rows = [
                ['marketplace_zone_id' => null, 'city_name' => $cityName.' Centro'],
                ['marketplace_zone_id' => null, 'city_name' => $cityName.' Norte'],
            ];
        }

        foreach ($rows as $row) {
            $listing->serviceAreas()->create($row);
        }
    }

    private function cityLookupKey(string $value): string
    {
        return mb_strtolower(trim((string) Str::ascii($value)));
    }

    private function syncVerificationPayment(
        MariachiProfile $profile,
        ?int $reviewerId,
        bool $verifiedProfile,
        ?string $sourcePath,
        CarbonImmutable $activatedAt,
        string $planCode
    ): void {
        $reference = 'DEMO-VER-'.Str::upper($profile->slug ?: ('PROFILE-'.$profile->id));

        if (! $verifiedProfile) {
            $profile->verificationPayments()
                ->where('reference_text', $reference)
                ->delete();

            return;
        }

        $durationMonths = $this->verificationDurationForPlan($planCode);
        $proofPath = $this->storeProofImage(
            directory: 'demo/payment-proofs/verifications',
            identifier: $profile->slug ?: ('profile-'.$profile->id),
            sourcePath: $sourcePath
        );

        ProfileVerificationPayment::query()->updateOrCreate(
            [
                'mariachi_profile_id' => $profile->id,
                'reference_text' => $reference,
            ],
            [
                'plan_code' => match ($durationMonths) {
                    12 => 'verification-12m',
                    3 => 'verification-3m',
                    default => 'verification-1m',
                },
                'duration_months' => $durationMonths,
                'amount_cop' => match ($durationMonths) {
                    12 => 129900,
                    3 => 49900,
                    default => 19900,
                },
                'method' => ProfileVerificationPayment::METHOD_NEQUI,
                'proof_path' => $proofPath ?: 'demo/payment-proofs/verifications/default.jpg',
                'status' => ProfileVerificationPayment::STATUS_APPROVED,
                'reviewed_by_user_id' => $reviewerId,
                'reviewed_at' => $activatedAt->subDay(),
                'starts_at' => $activatedAt,
                'ends_at' => $activatedAt->addMonthsNoOverflow($durationMonths),
                'rejection_reason' => null,
            ]
        );
    }

    private function refreshListingProgress(MariachiListing $listing): void
    {
        $checks = [
            'core' => filled($listing->title)
                && filled($listing->short_description)
                && filled($listing->description)
                && ! is_null($listing->base_price),
            'location' => filled($listing->address)
                && filled($listing->city_name)
                && filled($listing->country)
                && filled($listing->state)
                && ! is_null($listing->latitude)
                && ! is_null($listing->longitude),
            'photos' => $listing->photos()->count() > 0,
            'videos' => $listing->videos()->count() > 0,
            'events' => $listing->eventTypes()->count() > 0,
            'filters' => $listing->serviceTypes()->count() > 0
                && $listing->groupSizeOptions()->count() > 0
                && $listing->budgetRanges()->count() > 0,
            'coverage' => ! $listing->travels_to_other_cities || $listing->serviceAreas()->count() > 0,
            'faqs' => $listing->faqs()->where('is_visible', true)->count() > 0,
        ];

        $completed = count(array_filter($checks));
        $completion = (int) round(($completed / count($checks)) * 100);
        $isComplete = $checks['core']
            && $checks['location']
            && $checks['photos']
            && $checks['events']
            && $checks['filters']
            && $checks['coverage'];

        $listing->update([
            'listing_completion' => $completion,
            'listing_completed' => $isComplete,
        ]);
    }

    private function storeProofImage(string $directory, string $identifier, ?string $sourcePath): ?string
    {
        return $this->storeImageCopy($directory, $identifier.'-proof', $sourcePath);
    }

    private function storeImageCopy(string $directory, string $identifier, ?string $sourcePath): ?string
    {
        if (! $sourcePath || ! is_file($sourcePath)) {
            return null;
        }

        $disk = Storage::disk('public');
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'jpg';
        $baseName = Str::slug(pathinfo($identifier, PATHINFO_FILENAME));
        $targetPath = trim($directory, '/').'/'.$baseName.'.'.$extension;

        $disk->put($targetPath, (string) file_get_contents($sourcePath));

        return $targetPath;
    }
}
