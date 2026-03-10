<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\BudgetRange;
use App\Models\CatalogSuggestion;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiListing;
use App\Models\MariachiListingPhoto;
use App\Models\MariachiListingVideo;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\ServiceType;
use App\Services\MediaProtectionService;
use App\Services\GoogleMapsSettingsService;
use App\Services\SubscriptionCapabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiListingController extends Controller
{
    public function __construct(
        private readonly SubscriptionCapabilityService $capabilityService,
        private readonly MediaProtectionService $mediaProtectionService,
        private readonly GoogleMapsSettingsService $googleMapsSettings
    ) {
    }

    public function index(): View
    {
        $profile = $this->providerProfile();
        $listings = $profile->listings()
            ->with(['photos', 'eventTypes:id,name'])
            ->latest('updated_at')
            ->get();

        $capabilities = $this->capabilityService->resolveCapabilities($profile);
        $limit = $capabilities['listing_limit'];
        $used = $listings->count();

        return view('content.mariachi.listings-index', [
            'profile' => $profile,
            'listings' => $listings,
            'capabilities' => $capabilities,
            'listingLimit' => $limit,
            'listingsUsed' => $used,
            'listingsRemaining' => max(0, $limit - $used),
        ]);
    }

    public function create(): View
    {
        $profile = $this->providerProfile();
        $capabilities = $this->capabilityService->resolveCapabilities($profile);
        $listingLimit = $capabilities['listing_limit'];
        $listingsUsed = $profile->listings()->count();

        return view('content.mariachi.listings-create', [
            'profile' => $profile,
            'capabilities' => $capabilities,
            'canCreate' => $listingsUsed < $listingLimit,
            'listingLimit' => $listingLimit,
            'listingsUsed' => $listingsUsed,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();
        $listingLimit = $this->capabilityService->listingLimit($profile);
        $listingsUsed = $profile->listings()->count();

        if ($listingsUsed >= $listingLimit) {
            return redirect()
                ->route('mariachi.listings.index')
                ->withErrors([
                    'listing_limit' => 'Alcanzaste el limite de anuncios de tu plan actual.',
                ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'short_description' => ['required', 'string', 'max:280'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $listing = $profile->listings()->create([
            'title' => $validated['title'],
            'short_description' => $validated['short_description'],
            'base_price' => $validated['base_price'] ?? null,
            'country' => $this->defaultCountryName(),
            'status' => MariachiListing::STATUS_DRAFT,
            'is_active' => false,
            'selected_plan_code' => null,
        ]);

        $listing->ensureSlug();
        $this->refreshListingProgress($listing);

        return redirect()
            ->route('mariachi.listings.edit', ['listing' => $listing->id])
            ->with('status', 'Borrador creado. Completa el anuncio y al final elige plan y paga para activarlo.');
    }

    public function plans(MariachiListing $listing): View
    {
        $this->ensureOwned($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        return view('content.mariachi.listings-plans', [
            'listing' => $listing->loadMissing('mariachiProfile.subscriptions.plan'),
            'capabilities' => $this->capabilityService->resolveCapabilities($profile),
            'plans' => $this->availablePlans(),
        ]);
    }

    public function selectPlan(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->refreshListingProgress($listing);
        $listing->refresh();

        if (! $listing->listing_completed) {
            return redirect()
                ->route('mariachi.listings.edit', ['listing' => $listing->id])
                ->withErrors([
                    'plan_code' => 'Completa primero la informacion del anuncio (datos, ubicacion, filtros y fotos) antes de seleccionar plan.',
                ]);
        }

        $plans = $this->availablePlans();

        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($plans))],
        ]);

        $planCode = $validated['plan_code'];
        $selectedPlan = $plans[$planCode];

        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $planModel = Plan::query()
            ->active()
            ->where('code', $planCode)
            ->first();
        abort_unless($planModel, 422);

        DB::transaction(function () use ($profile, $planModel, $listing): void {
            $profile->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update([
                    'status' => Subscription::STATUS_REPLACED,
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            $profile->subscriptions()->create([
                'plan_id' => $planModel->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'renews_at' => now()->addMonth(),
                'base_amount_cop' => $planModel->price_cop,
                'extra_city_amount_cop' => (int) config('monetization.additional_city_price_cop', 9900),
                'metadata' => [
                    'source' => 'manual_selection',
                    'currency' => 'COP',
                ],
            ]);

            $profile->update([
                'subscription_plan_code' => $planModel->code,
                'subscription_listing_limit' => $planModel->listing_limit,
                'subscription_active' => true,
                'default_mariachi_listing_id' => $profile->default_mariachi_listing_id ?: $listing->id,
            ]);

            $listing->update([
                'selected_plan_code' => $planModel->code,
                'plan_selected_at' => now(),
                'status' => MariachiListing::STATUS_ACTIVE,
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ]);
        });

        $this->refreshListingProgress($listing->refresh());

        return redirect()
            ->route('mariachi.listings.edit', ['listing' => $listing->id])
            ->with('status', 'Plan seleccionado ('.$selectedPlan['name'].'). El anuncio quedo activo.');
    }

    public function edit(MariachiListing $listing): View
    {
        $this->ensureOwned($listing);

        $listing->load([
            'mariachiProfile.activeSubscription.plan',
            'marketplaceCity:id,name',
            'photos',
            'videos',
            'serviceAreas.marketplaceZone:id,marketplace_city_id,name',
            'faqs',
            'eventTypes:id,name,slug,icon,sort_order',
            'serviceTypes:id,name,slug,icon,sort_order',
            'groupSizeOptions:id,name,slug,icon,sort_order',
            'budgetRanges:id,name,slug,icon,sort_order',
        ]);

        $profile = $listing->mariachiProfile;
        $capabilities = $profile
            ? $this->capabilityService->resolveCapabilities($profile)
            : $this->capabilityService->resolveCapabilities($this->providerProfile());
        $maxCitiesAllowed = $profile
            ? $this->capabilityService->maxCitiesForListing($profile, $listing)
            : 1;

        return view('content.mariachi.listings-edit', [
            'listing' => $listing,
            'capabilities' => $capabilities,
            'maxCitiesAllowed' => $maxCitiesAllowed,
            'plans' => $this->availablePlans(),
            'googleMaps' => $this->googleMapsSettings->publicConfig(),
            'eventTypes' => EventType::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'serviceTypes' => ServiceType::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'groupSizeOptions' => GroupSizeOption::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'budgetRanges' => BudgetRange::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'cities' => MarketplaceCity::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'zones' => MarketplaceZone::query()
                ->active()
                ->with('city:id,name')
                ->orderBy('marketplace_city_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'marketplace_city_id', 'name']),
        ]);
    }

    public function update(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'short_description' => ['required', 'string', 'max:280'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'city_name' => ['nullable', 'string', 'max:120'],
            'zone_name' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'marketplace_city_id' => [
                'nullable',
                'integer',
                Rule::exists('marketplace_cities', 'id')->where('is_active', true),
            ],
            'primary_marketplace_zone_id' => ['nullable', 'integer', Rule::exists('marketplace_zones', 'id')],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:191'],
            'google_location_payload' => ['nullable', 'string', 'max:20000'],
            'travels_to_other_cities' => ['nullable', 'boolean'],
            'zone_ids' => ['nullable', 'array'],
            'zone_ids.*' => ['integer', Rule::exists('marketplace_zones', 'id')],
            'status' => ['nullable', Rule::in([
                MariachiListing::STATUS_DRAFT,
                MariachiListing::STATUS_ACTIVE,
                MariachiListing::STATUS_PAUSED,
            ])],
            'event_type_ids' => ['nullable', 'array'],
            'event_type_ids.*' => ['integer', Rule::exists('event_types', 'id')],
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', Rule::exists('service_types', 'id')],
            'group_size_option_ids' => ['nullable', 'array'],
            'group_size_option_ids.*' => ['integer', Rule::exists('group_size_options', 'id')],
            'budget_range_ids' => ['nullable', 'array'],
            'budget_range_ids.*' => ['integer', Rule::exists('budget_ranges', 'id')],
            'faq_question' => ['nullable', 'array', 'max:10'],
            'faq_question.*' => ['nullable', 'string', 'max:240'],
            'faq_answer' => ['nullable', 'array', 'max:10'],
            'faq_answer.*' => ['nullable', 'string', 'max:2000'],
            'suggest_event_type' => ['nullable', 'string', 'max:120'],
            'suggest_service_type' => ['nullable', 'string', 'max:120'],
            'suggest_zone' => ['nullable', 'string', 'max:120'],
        ]);

        $status = $validated['status'] ?? $listing->status;
        if ($status === MariachiListing::STATUS_ACTIVE && blank($listing->selected_plan_code)) {
            return back()
                ->withInput()
                ->withErrors([
                    'status' => 'Para activar el anuncio primero debes elegir y pagar un plan en el paso final.',
                ]);
        }

        [$city, $cityName] = $this->resolveListingCity($validated, $listing);
        [$zones, $zoneName] = $this->resolveListingZones($validated, $city);

        if ($city && $zones->contains(fn (MarketplaceZone $zone): bool => (int) $zone->marketplace_city_id !== (int) $city->id)) {
            return back()
                ->withInput()
                ->withErrors([
                    'zone_ids' => 'Las zonas seleccionadas deben pertenecer a la ciudad principal del anuncio.',
                ]);
        }

        $listing->update([
            'title' => $validated['title'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'] ?? null,
            'country' => $this->defaultCountryName(),
            'state' => $validated['state'] ?? null,
            'marketplace_city_id' => $city?->id,
            'city_name' => $cityName,
            'zone_name' => $zoneName,
            'postal_code' => $validated['postal_code'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'google_place_id' => $validated['google_place_id'] ?? null,
            'google_location_payload' => $this->decodeGooglePayload($validated['google_location_payload'] ?? null),
            'travels_to_other_cities' => $request->boolean('travels_to_other_cities'),
            'status' => $status,
            'is_active' => $status === MariachiListing::STATUS_ACTIVE,
            'activated_at' => $status === MariachiListing::STATUS_ACTIVE ? ($listing->activated_at ?? now()) : $listing->activated_at,
            'deactivated_at' => $status === MariachiListing::STATUS_ACTIVE ? null : now(),
        ]);

        $listing->eventTypes()->sync($validated['event_type_ids'] ?? []);
        $listing->serviceTypes()->sync($validated['service_type_ids'] ?? []);
        $listing->groupSizeOptions()->sync($validated['group_size_option_ids'] ?? []);
        $listing->budgetRanges()->sync($validated['budget_range_ids'] ?? []);

        $this->syncListingZones($listing, $zones);

        $this->syncListingFaqs($listing, $validated);

        $this->storeCatalogSuggestions($validated, $request, $city, $cityName, $zoneName, $zones->isNotEmpty());

        $listing->ensureSlug();
        $this->refreshListingProgress($listing);

        return back()->with('status', 'Anuncio actualizado.');
    }

    public function autosave(Request $request, MariachiListing $listing): JsonResponse
    {
        $this->ensureOwned($listing);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'short_description' => ['nullable', 'string', 'max:280'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'city_name' => ['nullable', 'string', 'max:120'],
            'zone_name' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'marketplace_city_id' => [
                'nullable',
                'integer',
                Rule::exists('marketplace_cities', 'id')->where('is_active', true),
            ],
            'primary_marketplace_zone_id' => ['nullable', 'integer', Rule::exists('marketplace_zones', 'id')],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:191'],
            'google_location_payload' => ['nullable', 'string', 'max:20000'],
            'travels_to_other_cities' => ['nullable', 'boolean'],
            'zone_ids' => ['nullable', 'array'],
            'zone_ids.*' => ['integer', Rule::exists('marketplace_zones', 'id')],
            'status' => ['nullable', Rule::in([
                MariachiListing::STATUS_DRAFT,
                MariachiListing::STATUS_AWAITING_PLAN,
                MariachiListing::STATUS_ACTIVE,
                MariachiListing::STATUS_PAUSED,
            ])],
            'event_type_ids' => ['nullable', 'array'],
            'event_type_ids.*' => ['integer', Rule::exists('event_types', 'id')],
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', Rule::exists('service_types', 'id')],
            'group_size_option_ids' => ['nullable', 'array'],
            'group_size_option_ids.*' => ['integer', Rule::exists('group_size_options', 'id')],
            'budget_range_ids' => ['nullable', 'array'],
            'budget_range_ids.*' => ['integer', Rule::exists('budget_ranges', 'id')],
            'faq_question' => ['nullable', 'array', 'max:10'],
            'faq_question.*' => ['nullable', 'string', 'max:240'],
            'faq_answer' => ['nullable', 'array', 'max:10'],
            'faq_answer.*' => ['nullable', 'string', 'max:2000'],
            'suggest_event_type' => ['nullable', 'string', 'max:120'],
            'suggest_service_type' => ['nullable', 'string', 'max:120'],
            'suggest_zone' => ['nullable', 'string', 'max:120'],
            'autosave_sync' => ['nullable', 'boolean'],
        ]);

        $syncAll = $request->boolean('autosave_sync');
        [$city, $cityName] = $this->resolveListingCity($validated, $listing);
        [$zones, $zoneName] = $this->resolveListingZones($validated, $city);

        $currentStatus = array_key_exists('status', $validated) ? $validated['status'] : $listing->status;
        if ($currentStatus === MariachiListing::STATUS_ACTIVE && blank($listing->selected_plan_code)) {
            $currentStatus = MariachiListing::STATUS_AWAITING_PLAN;
        }

        $travelsToOtherCities = array_key_exists('travels_to_other_cities', $validated)
            ? $request->boolean('travels_to_other_cities')
            : $listing->travels_to_other_cities;

        $listing->update([
            'title' => $this->validatedValue($validated, 'title', $listing->title),
            'short_description' => $this->validatedValue($validated, 'short_description', $listing->short_description),
            'description' => $this->validatedValue($validated, 'description', $listing->description),
            'base_price' => $this->validatedValue($validated, 'base_price', $listing->base_price),
            'country' => $this->defaultCountryName(),
            'state' => $this->validatedValue($validated, 'state', $listing->state),
            'marketplace_city_id' => $city?->id,
            'city_name' => $cityName,
            'zone_name' => $zoneName,
            'postal_code' => $this->validatedValue($validated, 'postal_code', $listing->postal_code),
            'address' => $this->validatedValue($validated, 'address', $listing->address),
            'latitude' => $this->validatedValue($validated, 'latitude', $listing->latitude),
            'longitude' => $this->validatedValue($validated, 'longitude', $listing->longitude),
            'google_place_id' => $this->validatedValue($validated, 'google_place_id', $listing->google_place_id),
            'google_location_payload' => array_key_exists('google_location_payload', $validated)
                ? $this->decodeGooglePayload($validated['google_location_payload'] ?? null)
                : $listing->google_location_payload,
            'travels_to_other_cities' => $travelsToOtherCities,
            'status' => $currentStatus,
            'is_active' => $currentStatus === MariachiListing::STATUS_ACTIVE,
            'activated_at' => $currentStatus === MariachiListing::STATUS_ACTIVE ? ($listing->activated_at ?? now()) : $listing->activated_at,
            'deactivated_at' => $currentStatus === MariachiListing::STATUS_ACTIVE ? null : ($listing->deactivated_at ?? now()),
        ]);

        if ($syncAll || array_key_exists('event_type_ids', $validated)) {
            $listing->eventTypes()->sync($validated['event_type_ids'] ?? []);
        }
        if ($syncAll || array_key_exists('service_type_ids', $validated)) {
            $listing->serviceTypes()->sync($validated['service_type_ids'] ?? []);
        }
        if ($syncAll || array_key_exists('group_size_option_ids', $validated)) {
            $listing->groupSizeOptions()->sync($validated['group_size_option_ids'] ?? []);
        }
        if ($syncAll || array_key_exists('budget_range_ids', $validated)) {
            $listing->budgetRanges()->sync($validated['budget_range_ids'] ?? []);
        }

        if ($city && $zones->contains(fn (MarketplaceZone $zone): bool => (int) $zone->marketplace_city_id !== (int) $city->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'Las zonas seleccionadas deben pertenecer a la ciudad principal del anuncio.',
            ], 422);
        }

        if ($syncAll || array_key_exists('zone_ids', $validated) || array_key_exists('primary_marketplace_zone_id', $validated)) {
            $this->syncListingZones($listing, $zones);
        }

        if ($syncAll || array_key_exists('faq_question', $validated) || array_key_exists('faq_answer', $validated)) {
            $this->syncListingFaqs($listing, $validated);
        }

        $this->storeCatalogSuggestions($validated, $request, $city, $cityName, $zoneName, $zones->isNotEmpty());

        $listing->ensureSlug();
        $this->refreshListingProgress($listing->refresh());

        return response()->json([
            'ok' => true,
            'message' => 'Borrador guardado',
            'saved_at' => now()->toIso8601String(),
            'listing_completion' => (int) $listing->listing_completion,
            'listing_completed' => (bool) $listing->listing_completed,
            'status' => (string) $listing->status,
        ]);
    }

    public function uploadPhoto(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxPhotos = $this->capabilityService->maxPhotosPerListing($profile);
        if ($listing->photos()->count() >= $maxPhotos) {
            return back()->withErrors([
                'photo' => 'Tu plan permite hasta '.$maxPhotos.' foto(s) por anuncio.',
            ]);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $file = $request->file('photo');
        $path = $file->store('mariachi-listing-photos', 'public');
        $sortOrder = ((int) $listing->photos()->max('sort_order')) + 1;
        $hash = $listing->image_hashing_enabled ? hash_file('sha256', $file->getRealPath()) : null;

        $photo = $listing->photos()->create([
            'path' => $path,
            'title' => $validated['title'] ?? null,
            'sort_order' => $sortOrder,
            'is_featured' => $listing->photos()->count() === 0,
            'image_hash' => $hash,
        ]);

        if ($photo->is_featured) {
            $listing->photos()->where('id', '!=', $photo->id)->update(['is_featured' => false]);
        }

        $this->mediaProtectionService->registerListingPhotoHash($listing, $photo, $hash);

        $this->refreshListingProgress($listing);

        return back()->with('status', 'Foto cargada en el anuncio.');
    }

    public function deletePhoto(MariachiListing $listing, MariachiListingPhoto $photo): RedirectResponse
    {
        $this->ensureOwned($listing);
        abort_unless($photo->mariachi_listing_id === $listing->id, 404);

        Storage::disk('public')->delete($photo->path);
        $wasFeatured = $photo->is_featured;
        $photo->delete();

        $remaining = $listing->photos()->orderBy('sort_order')->get();
        foreach ($remaining as $index => $item) {
            $item->update(['sort_order' => $index + 1]);
        }

        if ($wasFeatured) {
            $first = $listing->photos()->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_featured' => true]);
            }
        }

        $this->refreshListingProgress($listing);

        return back()->with('status', 'Foto eliminada del anuncio.');
    }

    public function setFeaturedPhoto(MariachiListing $listing, MariachiListingPhoto $photo): RedirectResponse
    {
        $this->ensureOwned($listing);
        abort_unless($photo->mariachi_listing_id === $listing->id, 404);

        $listing->photos()->update(['is_featured' => false]);
        $photo->update(['is_featured' => true]);

        return back()->with('status', 'Foto destacada actualizada.');
    }

    public function movePhoto(MariachiListing $listing, MariachiListingPhoto $photo, string $direction): RedirectResponse
    {
        $this->ensureOwned($listing);
        abort_unless($photo->mariachi_listing_id === $listing->id, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $target = $direction === 'up'
            ? $listing->photos()->where('sort_order', '<', $photo->sort_order)->orderByDesc('sort_order')->first()
            : $listing->photos()->where('sort_order', '>', $photo->sort_order)->orderBy('sort_order')->first();

        if (! $target) {
            return back();
        }

        $currentOrder = $photo->sort_order;
        $photo->update(['sort_order' => $target->sort_order]);
        $target->update(['sort_order' => $currentOrder]);

        return back()->with('status', 'Orden de fotos actualizado.');
    }

    public function storeVideo(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxVideos = $this->capabilityService->maxVideosPerListing($profile);
        if ($listing->videos()->count() >= $maxVideos) {
            return back()->withErrors([
                'url' => $maxVideos > 0
                    ? 'Tu plan permite hasta '.$maxVideos.' video(s) por anuncio.'
                    : 'Tu plan actual no incluye videos en los anuncios.',
            ]);
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:255'],
        ]);

        $platform = Str::contains($validated['url'], ['youtube.com', 'youtu.be']) ? 'youtube' : 'external';
        $listing->videos()->create([
            'url' => $validated['url'],
            'platform' => $platform,
        ]);

        $this->refreshListingProgress($listing);

        return back()->with('status', 'Video agregado al anuncio.');
    }

    public function deleteVideo(MariachiListing $listing, MariachiListingVideo $video): RedirectResponse
    {
        $this->ensureOwned($listing);
        abort_unless($video->mariachi_listing_id === $listing->id, 404);

        $video->delete();
        $this->refreshListingProgress($listing);

        return back()->with('status', 'Video eliminado del anuncio.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function storeCatalogSuggestions(
        array $validated,
        Request $request,
        ?MarketplaceCity $city,
        ?string $cityName,
        ?string $zoneName,
        bool $hasMatchedZone
    ): void {
        $submittedByUserId = $request->user()?->id;

        $this->createCatalogSuggestion(
            CatalogSuggestion::TYPE_EVENT,
            $validated['suggest_event_type'] ?? null,
            $submittedByUserId,
            [],
            fn (string $name): bool => EventType::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists()
        );

        $this->createCatalogSuggestion(
            CatalogSuggestion::TYPE_SERVICE,
            $validated['suggest_service_type'] ?? null,
            $submittedByUserId,
            [],
            fn (string $name): bool => ServiceType::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists()
        );

        $this->createCatalogSuggestion(
            CatalogSuggestion::TYPE_CITY,
            $city ? null : $cityName,
            $submittedByUserId,
            [],
            fn (string $name): bool => MarketplaceCity::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists()
        );

        $this->createCatalogSuggestion(
            CatalogSuggestion::TYPE_ZONE,
            $validated['suggest_zone'] ?? ($hasMatchedZone ? null : $zoneName),
            $submittedByUserId,
            $city ? ['marketplace_city_id' => $city->id] : [],
            fn (string $name): bool => $city
                ? MarketplaceZone::query()
                    ->where('marketplace_city_id', $city->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->exists()
                : true
        );
    }

    /**
     * @param  mixed  $rawName
     * @param  array<string, mixed>  $contextData
     */
    private function createCatalogSuggestion(
        string $catalogType,
        mixed $rawName,
        ?int $submittedByUserId,
        array $contextData,
        callable $existsCallback
    ): void {
        $name = trim((string) $rawName);
        if ($name === '') {
            return;
        }

        if ($existsCallback($name)) {
            return;
        }

        $slug = Str::slug($name);
        if ($slug === '') {
            return;
        }

        CatalogSuggestion::query()->firstOrCreate(
            [
                'catalog_type' => $catalogType,
                'proposed_slug' => $slug,
                'status' => CatalogSuggestion::STATUS_PENDING,
            ],
            [
                'proposed_name' => $name,
                'context_data' => $contextData === [] ? null : $contextData,
                'submitted_by_user_id' => $submittedByUserId,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validatedValue(array $validated, string $key, mixed $fallback): mixed
    {
        return array_key_exists($key, $validated) ? $validated[$key] : $fallback;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0:?MarketplaceCity,1:?string}
     */
    private function resolveListingCity(array $validated, ?MariachiListing $listing = null): array
    {
        $city = null;

        if (filled($validated['marketplace_city_id'] ?? null)) {
            $city = MarketplaceCity::query()->active()->find((int) $validated['marketplace_city_id']);
        }

        if (! $city && filled($validated['primary_marketplace_zone_id'] ?? null)) {
            $city = MarketplaceZone::query()
                ->with('city:id,name,is_active')
                ->find((int) $validated['primary_marketplace_zone_id'])
                ?->city;
        }

        $cityName = trim((string) ($validated['city_name'] ?? ''));
        if (! $city && $cityName !== '') {
            $normalizedCityName = $this->normalizeLocationName($cityName);
            $city = MarketplaceCity::query()
                ->active()
                ->get(['id', 'name'])
                ->first(fn (MarketplaceCity $candidate): bool => $this->normalizeLocationName($candidate->name) === $normalizedCityName);
        }

        if ($city) {
            return [$city, $city->name];
        }

        if ($cityName !== '') {
            return [null, $cityName];
        }

        if ($listing && filled($listing->city_name)) {
            return [$listing->marketplaceCity, $listing->city_name];
        }

        return [null, null];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0:\Illuminate\Support\Collection<int, MarketplaceZone>,1:?string}
     */
    private function resolveListingZones(array $validated, ?MarketplaceCity $city): array
    {
        $selectedZoneIds = collect($validated['zone_ids'] ?? [])
            ->push($validated['primary_marketplace_zone_id'] ?? null)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $zones = MarketplaceZone::query()
            ->whereIn('id', $selectedZoneIds)
            ->get(['id', 'marketplace_city_id', 'name']);

        if ($city) {
            $zones = $zones
                ->filter(fn (MarketplaceZone $zone): bool => (int) $zone->marketplace_city_id === (int) $city->id)
                ->values();
        }

        $zoneName = trim((string) ($validated['zone_name'] ?? ''));
        if ($zoneName === '' && $zones->isNotEmpty()) {
            $zoneName = (string) $zones->first()->name;
        }

        return [$zones, $zoneName !== '' ? $zoneName : null];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncListingFaqs(MariachiListing $listing, array $validated): void
    {
        $listing->faqs()->delete();
        $questions = (array) ($validated['faq_question'] ?? []);
        $answers = (array) ($validated['faq_answer'] ?? []);
        $order = 1;

        foreach ($questions as $index => $question) {
            $q = trim((string) $question);
            $a = trim((string) ($answers[$index] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }

            $listing->faqs()->create([
                'question' => $q,
                'answer' => $a,
                'sort_order' => $order,
                'is_visible' => true,
            ]);
            $order++;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MarketplaceZone>  $zones
     */
    private function syncListingZones(MariachiListing $listing, $zones): void
    {
        $listing->serviceAreas()->delete();

        foreach ($zones as $zone) {
            $listing->serviceAreas()->create([
                'marketplace_zone_id' => $zone->id,
                'city_name' => $zone->name,
            ]);
        }
    }

    private function decodeGooglePayload(mixed $payload): ?array
    {
        if (! is_string($payload) || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeLocationName(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->squish();
    }

    private function defaultCountryName(): string
    {
        return $this->googleMapsSettings->publicConfig()['default_country_name'];
    }

    private function availablePlans(): array
    {
        $plans = Plan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($plans->isNotEmpty()) {
            return $plans
                ->mapWithKeys(fn (Plan $plan): array => [
                    $plan->code => [
                        'id' => $plan->id,
                        'code' => $plan->code,
                        'name' => $plan->name,
                        'price_cop' => (int) $plan->price_cop,
                        'listing_limit' => (int) $plan->listing_limit,
                        'included_cities' => (int) $plan->included_cities,
                        'max_photos_per_listing' => (int) $plan->max_photos_per_listing,
                        'max_videos_per_listing' => (int) $plan->max_videos_per_listing,
                        'show_whatsapp' => (bool) $plan->show_whatsapp,
                        'show_phone' => (bool) $plan->show_phone,
                        'description' => 'Hasta '.((int) $plan->listing_limit).' anuncio(s), '.((int) $plan->included_cities).' ciudad(es) y prioridad '.((int) $plan->priority_level).'.',
                    ],
                ])
                ->all();
        }

        return collect((array) config('monetization.plans', []))
            ->mapWithKeys(function (array $plan, string $code): array {
                return [
                    $code => [
                        'id' => null,
                        'code' => $code,
                        'name' => (string) ($plan['name'] ?? strtoupper($code)),
                        'price_cop' => (int) ($plan['price_cop'] ?? 0),
                        'listing_limit' => (int) ($plan['listing_limit'] ?? 1),
                        'included_cities' => (int) ($plan['included_cities'] ?? 1),
                        'max_photos_per_listing' => (int) ($plan['max_photos_per_listing'] ?? 5),
                        'max_videos_per_listing' => (int) ($plan['max_videos_per_listing'] ?? 0),
                        'show_whatsapp' => (bool) ($plan['show_whatsapp'] ?? false),
                        'show_phone' => (bool) ($plan['show_phone'] ?? false),
                        'description' => 'Plan base para anuncios de mariachi.',
                    ],
                ];
            })
            ->all();
    }

    private function ensureOwned(MariachiListing $listing): void
    {
        abort_unless($listing->mariachiProfile?->user_id === auth()->id(), 403);
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = $user->mariachiProfile()->firstOrCreate([], [
            'city_name' => 'Pendiente',
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);

        $this->ensureDefaultSubscription($profile);

        return $profile;
    }

    private function ensureDefaultSubscription(MariachiProfile $profile): void
    {
        $active = $profile->activeSubscription()->first();
        if ($active) {
            return;
        }

        $defaultPlanCode = $profile->subscription_plan_code ?: 'basic';
        $plan = Plan::query()->active()->where('code', $defaultPlanCode)->first()
            ?? Plan::query()->active()->orderBy('sort_order')->first();

        if (! $plan) {
            return;
        }

        $profile->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'renews_at' => now()->addMonth(),
            'base_amount_cop' => $plan->price_cop,
            'extra_city_amount_cop' => (int) config('monetization.additional_city_price_cop', 9900),
            'metadata' => ['source' => 'auto_default'],
        ]);

        $profile->update([
            'subscription_plan_code' => $plan->code,
            'subscription_listing_limit' => $plan->listing_limit,
            'subscription_active' => true,
        ]);
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
            'faqs' => $listing->faqs()->count() > 0,
        ];

        $completed = count(array_filter($checks));
        $total = count($checks);
        $completion = (int) round(($completed / max($total, 1)) * 100);

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
}
