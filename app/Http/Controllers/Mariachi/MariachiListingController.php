<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\BudgetRange;
use App\Models\CatalogSuggestion;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\ListingPayment;
use App\Models\MariachiListing;
use App\Models\MariachiListingPhoto;
use App\Models\MariachiListingVideo;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\ServiceType;
use App\Services\EntitlementsService;
use App\Services\GoogleMapsSettingsService;
use App\Services\MediaProtectionService;
use App\Services\NequiPaymentSettingsService;
use App\Services\PlanAssignmentService;
use App\Services\SubscriptionCapabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MariachiListingController extends Controller
{
    public function __construct(
        private readonly SubscriptionCapabilityService $capabilityService,
        private readonly EntitlementsService $entitlementsService,
        private readonly PlanAssignmentService $planAssignmentService,
        private readonly MediaProtectionService $mediaProtectionService,
        private readonly GoogleMapsSettingsService $googleMapsSettings,
        private readonly NequiPaymentSettingsService $nequiSettings
    ) {
    }

    public function index(): View
    {
        $profile = $this->providerProfile();
        $listings = $profile->listings()
            ->with(['mariachiProfile.activeSubscription.plan', 'photos', 'videos', 'serviceAreas', 'eventTypes:id,name', 'latestPayment'])
            ->latest('updated_at')
            ->get();

        $openDraftLimit = MariachiListing::OPEN_DRAFT_LIMIT;
        $openDraftsCount = $listings->filter(fn (MariachiListing $listing): bool => $listing->isOpenDraft())->count();
        $planSummary = $this->entitlementsService->summary($profile);
        $planIssues = $this->entitlementsService->profileAdjustmentIssues($profile);
        $listingIssues = $listings->mapWithKeys(
            fn (MariachiListing $listing): array => [$listing->id => $this->entitlementsService->listingAdjustmentIssues($listing)]
        );
        $activeCount = $listings->filter(fn (MariachiListing $listing): bool => $listing->status === MariachiListing::STATUS_ACTIVE && $listing->is_active)->count();
        $pendingReviewCount = $listings->filter(fn (MariachiListing $listing): bool => $listing->review_status === MariachiListing::REVIEW_PENDING)->count();
        $awaitingPaymentCount = $listings->filter(function (MariachiListing $listing): bool {
            return $listing->status === MariachiListing::STATUS_AWAITING_PAYMENT
                && in_array($listing->payment_status, [MariachiListing::PAYMENT_NONE, MariachiListing::PAYMENT_REJECTED], true);
        })->count();
        $pausedCount = $listings->filter(fn (MariachiListing $listing): bool => $listing->status === MariachiListing::STATUS_PAUSED)->count();

        return view('content.mariachi.listings-index', [
            'profile' => $profile,
            'listings' => $listings,
            'planSummary' => $planSummary,
            'planIssues' => $planIssues,
            'listingIssues' => $listingIssues,
            'openDraftLimit' => $openDraftLimit,
            'openDraftsCount' => $openDraftsCount,
            'canCreateListingDraft' => $openDraftsCount < $openDraftLimit,
            'activeCount' => $activeCount,
            'pendingReviewCount' => $pendingReviewCount,
            'awaitingPaymentCount' => $awaitingPaymentCount,
            'pausedCount' => $pausedCount,
        ]);
    }

    public function create(): RedirectResponse
    {
        $profile = $this->providerProfile();
        $openDraftsCount = $profile->listings()->openDrafts()->count();

        if ($openDraftsCount >= MariachiListing::OPEN_DRAFT_LIMIT) {
            return redirect()
                ->route('mariachi.listings.index')
                ->withErrors([
                    'open_drafts' => 'Has alcanzado el maximo de 5 borradores abiertos. Publica o elimina uno para crear otro.',
                ]);
        }

        $listing = $this->createDraft($profile);

        return redirect()
            ->route('mariachi.listings.edit', ['listing' => $listing->id])
            ->with('status', 'Borrador listo. Cambia el titulo, la descripcion corta y el precio base para continuar.');
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();
        $openDraftsCount = $profile->listings()->openDrafts()->count();

        if ($openDraftsCount >= MariachiListing::OPEN_DRAFT_LIMIT) {
            return redirect()
                ->route('mariachi.listings.index')
                ->withErrors([
                    'open_drafts' => 'Has alcanzado el maximo de 5 borradores abiertos. Publica o elimina uno para crear otro.',
                ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'short_description' => ['required', 'string', 'max:280'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $listing = $this->createDraft($profile, [
            'title' => $validated['title'],
            'short_description' => $validated['short_description'],
            'base_price' => $validated['base_price'] ?? null,
        ]);

        return redirect()
            ->route('mariachi.listings.edit', ['listing' => $listing->id])
            ->with('status', 'Borrador creado. Completa el anuncio, elige plan y luego envíalo a revisión.');
    }

    public function plans(MariachiListing $listing): View
    {
        $this->ensureOwned($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        return view('content.mariachi.listings-plans', [
            'listing' => $listing->loadMissing('mariachiProfile.subscriptions.plan', 'latestPayment.reviewedBy'),
            'capabilities' => $this->capabilityService->resolveCapabilities($profile, $listing),
            'planSummary' => $this->entitlementsService->summary($profile),
            'plans' => $this->availablePlans(),
            'nequi' => $this->nequiSettings->publicConfig(),
        ]);
    }

    public function selectPlan(Request $request, MariachiListing $listing): RedirectResponse|JsonResponse
    {
        $this->ensureOwned($listing);
        $this->refreshListingProgress($listing);
        $listing->refresh();

        if (! $listing->listing_completed) {
            return $this->planSelectionResponse(
                $request,
                false,
                'Completa primero la informacion del anuncio (datos, ubicacion, filtros y fotos) antes de seleccionar plan.',
                route('mariachi.listings.edit', ['listing' => $listing->id]),
                422
            );
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
            ->public()
            ->where('code', $planCode)
            ->first();
        abort_unless($planModel, 422);

        if ($listing->payment_status === MariachiListing::PAYMENT_APPROVED && $listing->selected_plan_code === $planCode) {
            return $this->planSelectionResponse(
                $request,
                true,
                'Ese plan ya esta aprobado para este anuncio.',
                route('mariachi.listings.plans', ['listing' => $listing->id])
            );
        }

        if ($listing->isPaymentPending()) {
            return $this->planSelectionResponse(
                $request,
                false,
                'Ya enviaste un comprobante. Espera la validacion del admin antes de cambiar el plan.',
                route('mariachi.listings.plans', ['listing' => $listing->id]),
                409
            );
        }

        $listing->update([
            'selected_plan_code' => $planCode,
            'plan_selected_at' => now(),
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
            'is_active' => false,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
            'deactivated_at' => now(),
        ]);

        $this->refreshListingProgress($listing->refresh());

        return $this->planSelectionResponse(
            $request,
            true,
            'Plan seleccionado ('.$selectedPlan['name'].'). Ahora paga por Nequi y sube el comprobante.',
            route('mariachi.listings.plans', ['listing' => $listing->id])
        );
    }

    public function storeNequiPayment(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->refreshListingProgress($listing);
        $listing->refresh();

        if (! $this->nequiSettings->publicConfig()['is_configured']) {
            return back()->withErrors([
                'proof_image' => 'El pago por Nequi no esta configurado en este momento. Intenta mas tarde o contacta a soporte.',
            ]);
        }

        if ($listing->isPaymentPending()) {
            return back()->withErrors([
                'proof_image' => 'Ya enviaste un comprobante. Debes esperar la validacion del admin.',
            ]);
        }

        if (! $listing->listing_completed) {
            return back()->withErrors([
                'proof_image' => 'Completa primero la informacion del anuncio antes de enviar el pago.',
            ]);
        }

        $plans = $this->availablePlans();
        $validated = $request->validate([
            'listing_id' => ['required', 'integer'],
            'plan_code' => ['required', Rule::in(array_keys($plans))],
            'amount_cop' => ['required', 'integer', 'min:0'],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'reference_text' => ['nullable', 'string', 'max:120'],
        ]);

        if ((int) $validated['listing_id'] !== (int) $listing->id) {
            abort(422);
        }

        $planCode = $validated['plan_code'];
        $selectedPlan = $plans[$planCode];
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $planModel = Plan::query()
            ->active()
            ->public()
            ->where('code', $planCode)
            ->first();
        abort_unless($planModel, 422);

        if ((int) $validated['amount_cop'] !== (int) $selectedPlan['price_cop']) {
            return back()->withErrors([
                'amount_cop' => 'El monto enviado no coincide con el valor configurado para este plan.',
            ]);
        }

        $proofPath = $request->file('proof_image')->store('listing-payment-proofs', 'public');

        $listing->payments()->create([
            'mariachi_profile_id' => $profile->id,
            'plan_code' => $planCode,
            'amount_cop' => (int) $selectedPlan['price_cop'],
            'method' => ListingPayment::METHOD_NEQUI,
            'proof_path' => $proofPath,
            'status' => ListingPayment::STATUS_PENDING,
            'reference_text' => $validated['reference_text'] ?? null,
        ]);

        $listing->update([
            'selected_plan_code' => $planCode,
            'plan_selected_at' => now(),
            'payment_status' => MariachiListing::PAYMENT_PENDING,
            'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
            'is_active' => false,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
            'deactivated_at' => now(),
        ]);

        return redirect()
            ->route('mariachi.listings.plans', ['listing' => $listing->id])
            ->with('status', 'Comprobante enviado. El equipo admin validara tu pago antes de publicar el anuncio.');
    }

    public function edit(MariachiListing $listing): View|RedirectResponse
    {
        $this->ensureOwned($listing);

        if ($listing->isPaymentPending()) {
            return redirect()
                ->route('mariachi.listings.plans', ['listing' => $listing->id])
                ->withErrors([
                    'listing' => 'Pago enviado, esperando validacion. No puedes editar este anuncio mientras revisamos el comprobante.',
                ]);
        }

        if ($listing->isPendingReview()) {
            return redirect()
                ->route('mariachi.listings.index')
                ->withErrors([
                    'listing' => 'Este anuncio ya fue enviado a revisión. Mientras el equipo lo evalúa no puedes editarlo.',
                ]);
        }

        $listing->load([
            'mariachiProfile.activeSubscription.plan.entitlements',
            'marketplaceCity:id,name',
            'photos',
            'videos',
            'serviceAreas.marketplaceZone:id,marketplace_city_id,name',
            'faqs',
            'eventTypes:id,name,slug,icon,sort_order',
            'serviceTypes:id,name,slug,icon,sort_order',
            'groupSizeOptions:id,name,slug,icon,sort_order',
            'budgetRanges:id,name,slug,icon,sort_order',
            'latestPayment.reviewedBy',
        ]);

        $profile = $listing->mariachiProfile;
        $capabilities = $profile
            ? $this->capabilityService->resolveCapabilities($profile, $listing)
            : $this->capabilityService->resolveCapabilities($this->providerProfile());
        $maxCitiesAllowed = $profile
            ? $this->capabilityService->maxCitiesForListing($profile, $listing)
            : 1;

        return view('content.mariachi.listings-edit', [
            'listing' => $listing,
            'capabilities' => $capabilities,
            'planSummary' => $profile ? $this->entitlementsService->summary($profile) : null,
            'planIssues' => $profile ? $this->entitlementsService->profileAdjustmentIssues($profile) : [],
            'listingIssues' => $this->entitlementsService->listingAdjustmentIssues($listing),
            'maxCitiesAllowed' => $maxCitiesAllowed,
            'plans' => $this->availablePlans(),
            'googleMaps' => $this->googleMapsSettings->publicConfig(),
            'eventTypes' => EventType::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'serviceTypes' => ServiceType::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'groupSizeOptions' => GroupSizeOption::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'budgetRanges' => BudgetRange::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'cities' => MarketplaceCity::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'zones' => MarketplaceZone::query()
                ->searchVisible()
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
        $this->ensureOwnerCanModifyListing($listing);

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

        [$city, $cityName] = $this->resolveListingCity($validated, $listing);
        [$zones, $zoneName] = $this->resolveListingZones($validated, $city);

        if ($city && $zones->contains(fn (MarketplaceZone $zone): bool => (int) $zone->marketplace_city_id !== (int) $city->id)) {
            return back()
                ->withInput()
                ->withErrors([
                    'zone_ids' => 'Las zonas seleccionadas deben pertenecer a la ciudad principal del anuncio.',
                ]);
        }

        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxZonesCovered = $this->entitlementsService->maxZonesCovered($profile, $listing);
        if ($zones->count() > $maxZonesCovered) {
            return back()
                ->withInput()
                ->withErrors([
                    'zone_ids' => 'Tu plan permite hasta '.$maxZonesCovered.' zona(s) por anuncio.',
                ]);
        }

        $shouldResetReview = $this->shouldResetReviewAfterFormChange($listing, $validated, $city, $cityName, $zones, $zoneName);
        $reviewResetPayload = $shouldResetReview ? $this->reviewResetPayloadOnOwnerChange($listing) : [];

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
        ] + $reviewResetPayload);

        $listing->eventTypes()->sync($validated['event_type_ids'] ?? []);
        $listing->serviceTypes()->sync($validated['service_type_ids'] ?? []);
        $listing->groupSizeOptions()->sync($validated['group_size_option_ids'] ?? []);
        $listing->budgetRanges()->sync($validated['budget_range_ids'] ?? []);

        $this->syncListingZones($listing, $zones);

        $this->syncListingFaqs($listing, $validated);

        $this->storeCatalogSuggestions($validated, $request, $city, $cityName, $zoneName, $zones->isNotEmpty());

        $listing->ensureSlug();
        $this->refreshListingProgress($listing);

        return back()->with('status', $this->ownerMutationStatusMessage('Anuncio actualizado.', $reviewResetPayload !== []));
    }

    public function autosave(Request $request, MariachiListing $listing): JsonResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);

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

        $travelsToOtherCities = array_key_exists('travels_to_other_cities', $validated)
            ? $request->boolean('travels_to_other_cities')
            : $listing->travels_to_other_cities;
        $shouldResetReview = $this->shouldResetReviewAfterFormChange($listing, $validated, $city, $cityName, $zones, $zoneName);
        $reviewResetPayload = $shouldResetReview ? $this->reviewResetPayloadOnOwnerChange($listing) : [];

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
        ] + $reviewResetPayload);

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

        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxZonesCovered = $this->entitlementsService->maxZonesCovered($profile, $listing);
        if ($zones->count() > $maxZonesCovered) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu plan permite hasta '.$maxZonesCovered.' zona(s) por anuncio.',
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
            'message' => $this->ownerMutationStatusMessage('Borrador guardado', $reviewResetPayload !== []),
            'saved_at' => now()->toIso8601String(),
            'listing_completion' => (int) $listing->listing_completion,
            'listing_completed' => (bool) $listing->listing_completed,
            'status' => (string) $listing->status,
            'review_status' => (string) $listing->review_status,
        ]);
    }

    public function pause(MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);

        if (! $listing->canOwnerPause()) {
            throw ValidationException::withMessages([
                'listing' => 'Solo puedes pausar anuncios ya publicados, aprobados y con plan activo.',
            ]);
        }

        $listing->update([
            'status' => MariachiListing::STATUS_PAUSED,
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        return back()->with('status', 'Anuncio pausado. El tiempo de tu plan sigue corriendo normalmente.');
    }

    public function resume(MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);

        if (! $listing->canOwnerResume()) {
            throw ValidationException::withMessages([
                'listing' => 'Solo puedes reanudar anuncios aprobados que hoy estén pausados.',
            ]);
        }

        $listing->update([
            'status' => MariachiListing::STATUS_ACTIVE,
            'is_active' => true,
            'activated_at' => $listing->activated_at ?? now(),
            'deactivated_at' => null,
        ]);

        return back()->with('status', 'Anuncio reanudado. El plan no se reinicia; continúa con el tiempo restante.');
    }

    public function uploadPhoto(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxPhotos = $this->capabilityService->maxPhotosPerListing($profile, $listing);
        if ($listing->photos()->count() >= $maxPhotos) {
            return back()->withErrors([
                'photo' => 'Tu plan permite hasta '.$maxPhotos.' foto(s) por anuncio.',
            ]);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('photo');
        $path = $file->store('mariachi-listing-photos', 'public');
        $sortOrder = ((int) $listing->photos()->max('sort_order')) + 1;
        $hash = $listing->image_hashing_enabled ? hash_file('sha256', $file->getRealPath()) : null;

        $photo = $listing->photos()->create([
            'path' => $path,
            'title' => null,
            'sort_order' => $sortOrder,
            'is_featured' => $listing->photos()->count() === 0,
            'image_hash' => $hash,
        ]);

        if ($photo->is_featured) {
            $listing->photos()->where('id', '!=', $photo->id)->update(['is_featured' => false]);
        }

        $this->mediaProtectionService->registerListingPhotoHash($listing, $photo, $hash);

        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);
        $this->refreshListingProgress($listing->refresh());

        return back()->with('status', $this->ownerMutationStatusMessage('Foto cargada en el anuncio.', $reviewReset));
    }

    public function deletePhoto(MariachiListing $listing, MariachiListingPhoto $photo): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
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

        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);
        $this->refreshListingProgress($listing->refresh());

        return back()->with('status', $this->ownerMutationStatusMessage('Foto eliminada del anuncio.', $reviewReset));
    }

    public function setFeaturedPhoto(MariachiListing $listing, MariachiListingPhoto $photo): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
        abort_unless($photo->mariachi_listing_id === $listing->id, 404);

        $listing->photos()->update(['is_featured' => false]);
        $photo->update(['is_featured' => true]);

        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);

        return back()->with('status', $this->ownerMutationStatusMessage('Foto destacada actualizada.', $reviewReset));
    }

    public function movePhoto(MariachiListing $listing, MariachiListingPhoto $photo, string $direction): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
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

        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);

        return back()->with('status', $this->ownerMutationStatusMessage('Orden de fotos actualizado.', $reviewReset));
    }

    public function storeVideo(Request $request, MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $maxVideos = $this->capabilityService->maxVideosPerListing($profile, $listing);
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

        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);
        $this->refreshListingProgress($listing->refresh());

        return back()->with('status', $this->ownerMutationStatusMessage('Video agregado al anuncio.', $reviewReset));
    }

    public function deleteVideo(MariachiListing $listing, MariachiListingVideo $video): RedirectResponse
    {
        $this->ensureOwned($listing);
        $this->ensureOwnerCanModifyListing($listing);
        abort_unless($video->mariachi_listing_id === $listing->id, 404);

        $video->delete();
        $reviewReset = $this->resetApprovedReviewAfterOwnerMutation($listing);
        $this->refreshListingProgress($listing->refresh());

        return back()->with('status', $this->ownerMutationStatusMessage('Video eliminado del anuncio.', $reviewReset));
    }

    public function submitForReview(MariachiListing $listing): RedirectResponse
    {
        $this->ensureOwned($listing);

        if ($listing->isPendingReview()) {
            return back()->withErrors([
                'listing' => 'Este anuncio ya esta en cola de revision.',
            ]);
        }

        $this->refreshListingProgress($listing->refresh());
        $listing->refresh();

        if (! $listing->listing_completed) {
            return back()->withErrors([
                'listing' => 'Completa primero la informacion obligatoria del anuncio antes de enviarlo a revision.',
            ]);
        }

        if (! $listing->hasEffectivePlan()) {
            return back()->withErrors([
                'listing' => 'Activa primero un plan para este perfil antes de enviar el anuncio a revision.',
            ]);
        }

        $blockers = $this->entitlementsService->publicationBlockers($listing->loadMissing('mariachiProfile', 'photos', 'videos', 'serviceAreas'));
        if ($blockers !== []) {
            return back()->withErrors([
                'listing' => 'Tu plan actual requiere ajustes antes de publicar: '.implode(' ', $blockers),
            ]);
        }

        $wasRejected = $listing->review_status === MariachiListing::REVIEW_REJECTED;

        $listing->update([
            'review_status' => MariachiListing::REVIEW_PENDING,
            'submitted_for_review_at' => now(),
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
        ]);

        return back()->with('status', $wasRejected
            ? 'Anuncio reenviado a revision. Te avisaremos cuando el equipo admin lo revise.'
            : 'Anuncio enviado a revision. Quedara bloqueado mientras el equipo admin lo evalua.');
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

        $pendingQuery = CatalogSuggestion::query()
            ->where('catalog_type', $catalogType)
            ->where('proposed_slug', $slug)
            ->where('status', CatalogSuggestion::STATUS_PENDING);

        if ($catalogType === CatalogSuggestion::TYPE_ZONE && isset($contextData['marketplace_city_id'])) {
            $pendingQuery->where('context_data->marketplace_city_id', (int) $contextData['marketplace_city_id']);
        }

        if ($pendingQuery->exists()) {
            return;
        }

        CatalogSuggestion::query()->create([
            'catalog_type' => $catalogType,
            'proposed_name' => $name,
            'proposed_slug' => $slug,
            'context_data' => $contextData === [] ? null : $contextData,
            'status' => CatalogSuggestion::STATUS_PENDING,
            'submitted_by_user_id' => $submittedByUserId,
        ]);
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
        $order = 4;

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
            ->public()
            ->with('entitlements')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($plans->isNotEmpty()) {
            return $plans
                ->mapWithKeys(fn (Plan $plan): array => [
                    $plan->code => [
                        'id' => $plan->id,
                        'code' => $plan->code,
                        'slug' => $plan->slug,
                        'name' => $plan->name,
                        'badge_text' => $plan->badge_text,
                        'is_public' => (bool) $plan->is_public,
                        'price_cop' => (int) $plan->price_cop,
                        'listing_limit' => (int) ($plan->entitlementValue('max_listings_total', $plan->listing_limit) ?? 1),
                        'included_cities' => (int) ($plan->entitlementValue('max_cities_covered', $plan->included_cities) ?? 1),
                        'max_zones_covered' => (int) ($plan->entitlementValue('max_zones_covered', max(5, $plan->included_cities * 5)) ?? 5),
                        'max_photos_per_listing' => (int) ($plan->entitlementValue('max_photos_per_listing', $plan->max_photos_per_listing) ?? 0),
                        'can_add_video' => (bool) ($plan->entitlementValue('can_add_video', $plan->max_videos_per_listing > 0) ?? false),
                        'max_videos_per_listing' => (int) ($plan->entitlementValue('max_videos_per_listing', $plan->max_videos_per_listing) ?? 0),
                        'show_whatsapp' => (bool) ($plan->entitlementValue('can_show_whatsapp', $plan->show_whatsapp) ?? false),
                        'show_phone' => (bool) ($plan->entitlementValue('can_show_phone', $plan->show_phone) ?? false),
                        'description' => $plan->description ?: 'Paquete configurable para anuncios de mariachi.',
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
                        'slug' => null,
                        'name' => (string) ($plan['name'] ?? strtoupper($code)),
                        'badge_text' => $plan['badge_text'] ?? null,
                        'is_public' => (bool) ($plan['is_public'] ?? true),
                        'price_cop' => (int) ($plan['price_cop'] ?? 0),
                        'listing_limit' => (int) ($plan['listing_limit'] ?? 1),
                        'included_cities' => (int) ($plan['included_cities'] ?? 1),
                        'max_zones_covered' => (int) (($plan['entitlements']['max_zones_covered'] ?? null) ?: max(5, (int) ($plan['included_cities'] ?? 1) * 5)),
                        'max_photos_per_listing' => (int) ($plan['max_photos_per_listing'] ?? 5),
                        'can_add_video' => (bool) (($plan['entitlements']['can_add_video'] ?? null) ?? (($plan['max_videos_per_listing'] ?? 0) > 0)),
                        'max_videos_per_listing' => (int) ($plan['max_videos_per_listing'] ?? 0),
                        'show_whatsapp' => (bool) ($plan['show_whatsapp'] ?? false),
                        'show_phone' => (bool) ($plan['show_phone'] ?? false),
                        'description' => (string) ($plan['description'] ?? 'Plan base para anuncios de mariachi.'),
                    ],
                ];
            })
            ->all();
    }

    private function ensureOwned(MariachiListing $listing): void
    {
        abort_unless($listing->mariachiProfile?->user_id === auth()->id(), 403);
    }

    private function planSelectionResponse(
        Request $request,
        bool $ok,
        string $message,
        string $redirectTo,
        int $status = 200
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'redirect_to' => $redirectTo,
            ], $status);
        }

        if ($ok) {
            return redirect()->to($redirectTo)->with('status', $message);
        }

        return redirect()->to($redirectTo)->withErrors([
            'plan_code' => $message,
        ]);
    }

    private function ensureOwnerCanModifyListing(MariachiListing $listing): void
    {
        if ($listing->isPaymentPending()) {
            throw ValidationException::withMessages([
                'listing' => 'Pago enviado, esperando validacion. No puedes editar este anuncio mientras revisamos el comprobante.',
            ]);
        }

        if ($listing->isPendingReview()) {
            throw ValidationException::withMessages([
                'listing' => 'Este anuncio esta en revision. Debes esperar la decision del equipo admin antes de editarlo.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewResetPayloadOnOwnerChange(MariachiListing $listing): array
    {
        if ($listing->review_status !== MariachiListing::REVIEW_APPROVED) {
            return [];
        }

        return [
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
        ];
    }

    private function resetApprovedReviewAfterOwnerMutation(MariachiListing $listing): bool
    {
        $payload = $this->reviewResetPayloadOnOwnerChange($listing);
        if ($payload === []) {
            return false;
        }

        $listing->update($payload);

        return true;
    }

    private function ownerMutationStatusMessage(string $baseMessage, bool $reviewReset): string
    {
        if (! $reviewReset) {
            return $baseMessage;
        }

        return $baseMessage.' El anuncio salio de publicacion y debe reenviarse a revision.';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  \Illuminate\Support\Collection<int, MarketplaceZone>  $zones
     */
    private function shouldResetReviewAfterFormChange(
        MariachiListing $listing,
        array $validated,
        ?MarketplaceCity $city,
        ?string $cityName,
        $zones,
        ?string $zoneName
    ): bool {
        if ($listing->review_status !== MariachiListing::REVIEW_APPROVED) {
            return false;
        }

        $currentPayload = [
            'title' => $this->normalizeComparableValue($listing->title),
            'short_description' => $this->normalizeComparableValue($listing->short_description),
            'description' => $this->normalizeComparableValue($listing->description),
            'base_price' => $this->normalizeComparableValue($listing->base_price),
            'state' => $this->normalizeComparableValue($listing->state),
            'marketplace_city_id' => $this->normalizeComparableValue($listing->marketplace_city_id),
            'city_name' => $this->normalizeComparableValue($listing->city_name),
            'zone_name' => $this->normalizeComparableValue($listing->zone_name),
            'postal_code' => $this->normalizeComparableValue($listing->postal_code),
            'address' => $this->normalizeComparableValue($listing->address),
            'latitude' => $this->normalizeComparableValue($listing->latitude),
            'longitude' => $this->normalizeComparableValue($listing->longitude),
            'google_place_id' => $this->normalizeComparableValue($listing->google_place_id),
            'google_location_payload' => $this->normalizeComparableValue($listing->google_location_payload),
            'travels_to_other_cities' => (bool) $listing->travels_to_other_cities,
        ];

        $nextPayload = [
            'title' => $this->normalizeComparableValue($validated['title'] ?? $listing->title),
            'short_description' => $this->normalizeComparableValue($validated['short_description'] ?? $listing->short_description),
            'description' => $this->normalizeComparableValue($validated['description'] ?? $listing->description),
            'base_price' => $this->normalizeComparableValue($validated['base_price'] ?? $listing->base_price),
            'state' => $this->normalizeComparableValue($validated['state'] ?? $listing->state),
            'marketplace_city_id' => $this->normalizeComparableValue($city?->id),
            'city_name' => $this->normalizeComparableValue($cityName),
            'zone_name' => $this->normalizeComparableValue($zoneName),
            'postal_code' => $this->normalizeComparableValue($validated['postal_code'] ?? $listing->postal_code),
            'address' => $this->normalizeComparableValue($validated['address'] ?? $listing->address),
            'latitude' => $this->normalizeComparableValue($validated['latitude'] ?? $listing->latitude),
            'longitude' => $this->normalizeComparableValue($validated['longitude'] ?? $listing->longitude),
            'google_place_id' => $this->normalizeComparableValue($validated['google_place_id'] ?? $listing->google_place_id),
            'google_location_payload' => $this->normalizeComparableValue(
                array_key_exists('google_location_payload', $validated)
                    ? $this->decodeGooglePayload($validated['google_location_payload'] ?? null)
                    : $listing->google_location_payload
            ),
            'travels_to_other_cities' => array_key_exists('travels_to_other_cities', $validated)
                ? (bool) $validated['travels_to_other_cities']
                : (bool) $listing->travels_to_other_cities,
        ];

        if ($currentPayload !== $nextPayload) {
            return true;
        }

        if ($this->normalizedIdList($listing->eventTypes()->pluck('event_types.id')->all()) !== $this->normalizedIdList($validated['event_type_ids'] ?? [])) {
            return true;
        }

        if ($this->normalizedIdList($listing->serviceTypes()->pluck('service_types.id')->all()) !== $this->normalizedIdList($validated['service_type_ids'] ?? [])) {
            return true;
        }

        if ($this->normalizedIdList($listing->groupSizeOptions()->pluck('group_size_options.id')->all()) !== $this->normalizedIdList($validated['group_size_option_ids'] ?? [])) {
            return true;
        }

        if ($this->normalizedIdList($listing->budgetRanges()->pluck('budget_ranges.id')->all()) !== $this->normalizedIdList($validated['budget_range_ids'] ?? [])) {
            return true;
        }

        if ($this->normalizedIdList($listing->serviceAreas()->pluck('marketplace_zone_id')->filter()->all()) !== $this->normalizedIdList($zones->pluck('id')->all())) {
            return true;
        }

        return $this->normalizedFaqRowsFromModel($listing) !== $this->normalizedFaqRowsFromValidated($validated, $listing);
    }

    private function normalizeComparableValue(mixed $value): mixed
    {
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function normalizedIdList(array $ids): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => (int) $id, $ids))));
        sort($normalized);

        return $normalized;
    }

    /**
     * @return array<int, array{question:string,answer:string}>
     */
    private function normalizedFaqRowsFromModel(MariachiListing $listing): array
    {
        return $listing->faqs()
            ->orderBy('sort_order')
            ->get(['question', 'answer'])
            ->map(fn ($faq): array => [
                'question' => trim((string) $faq->question),
                'answer' => trim((string) $faq->answer),
            ])
            ->filter(fn (array $faq): bool => $faq['question'] !== '' && $faq['answer'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{question:string,answer:string}>
     */
    private function normalizedFaqRowsFromValidated(array $validated, MariachiListing $listing): array
    {
        $questions = (array) ($validated['faq_question'] ?? $listing->faqs()->orderBy('sort_order')->pluck('question')->all());
        $answers = (array) ($validated['faq_answer'] ?? $listing->faqs()->orderBy('sort_order')->pluck('answer')->all());

        $rows = [];

        foreach ($questions as $index => $question) {
            $row = [
                'question' => trim((string) $question),
                'answer' => trim((string) ($answers[$index] ?? '')),
            ];

            if ($row['question'] === '' || $row['answer'] === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = $user->mariachiProfile()->firstOrCreate([], [
            'city_name' => null,
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
        $plan = Plan::query()->active()->public()->where('code', $defaultPlanCode)->first()
            ?? Plan::query()->active()->public()->orderBy('sort_order')->first()
            ?? Plan::query()->active()->orderBy('sort_order')->first();

        if (! $plan) {
            return;
        }

        $this->planAssignmentService->assignToProfile($profile, $plan, null, 'auto_default');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createDraft(MariachiProfile $profile, array $attributes = []): MariachiListing
    {
        $listing = $profile->listings()->create(array_merge([
            'title' => 'Nuevo anuncio',
            'short_description' => 'Completa la informacion del anuncio',
            'base_price' => null,
            'country' => $this->defaultCountryName(),
            'status' => MariachiListing::STATUS_DRAFT,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'payment_status' => MariachiListing::PAYMENT_NONE,
            'is_active' => false,
            'selected_plan_code' => null,
        ], $attributes));

        $listing->ensureSlug();
        $this->refreshListingProgress($listing);

        return $listing->refresh();
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
            'faqs' => $listing->renderedFaqRows()->isNotEmpty(),
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
