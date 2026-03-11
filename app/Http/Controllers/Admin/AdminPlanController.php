<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Entitlements\EntitlementKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->with('entitlements')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('content.admin.plans-index', [
            'plans' => $plans,
            'totalPlans' => $plans->count(),
            'publicPlans' => $plans->where('is_public', true)->count(),
            'privatePlans' => $plans->where('is_public', false)->count(),
            'activePlans' => $plans->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('content.admin.plans-form', [
            'plan' => new Plan(),
            'formAction' => route('admin.plans.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear paquete',
            'pageTitle' => 'Nuevo paquete',
            'entitlementGroups' => EntitlementKey::groupedDefinitions(),
            'categoryLabels' => EntitlementKey::categoryLabels(),
            'entitlementValues' => EntitlementKey::defaults(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = new Plan();
        $this->fillPlan($plan, $validated, $request);

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Paquete creado correctamente.');
    }

    public function edit(Plan $plan): View
    {
        return view('content.admin.plans-form', [
            'plan' => $plan->load('entitlements'),
            'formAction' => route('admin.plans.update', $plan),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar paquete',
            'entitlementGroups' => EntitlementKey::groupedDefinitions(),
            'categoryLabels' => EntitlementKey::categoryLabels(),
            'entitlementValues' => array_replace(
                EntitlementKey::defaults(),
                $plan->entitlements->mapWithKeys(fn ($entitlement): array => [$entitlement->key => $entitlement->value])->all()
            ),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan->id);

        $this->fillPlan($plan, $validated, $request);

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Paquete actualizado.');
    }

    public function toggleStatus(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('status', 'Estado del paquete actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('plans', 'code')->ignore($ignoreId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                Rule::unique('plans', 'slug')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],
            'badge_text' => ['nullable', 'string', 'max:80'],
            'price_cop' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', 'string', 'max:40'],
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'entitlements' => ['nullable', 'array'],
        ];

        foreach (EntitlementKey::definitions() as $key => $definition) {
            $rules['entitlements.'.$key] = match ($definition['type']) {
                'boolean' => ['nullable', 'boolean'],
                'integer' => ['nullable', 'integer', 'min:0'],
                default => ['nullable', 'string', 'max:5000'],
            };
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillPlan(Plan $plan, array $validated, Request $request): void
    {
        $entitlements = $this->resolveEntitlementValues($request, $validated);
        $slug = $this->resolveUniqueSlug(
            Str::slug((string) ($validated['slug'] ?: $validated['name'] ?: $validated['code'])),
            $plan->id
        );

        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null) {
            $sortOrder = ((int) Plan::query()->max('sort_order')) + 1;
        }

        $plan->fill([
            'code' => trim((string) $validated['code']),
            'slug' => $slug,
            'name' => trim((string) $validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'badge_text' => filled($validated['badge_text'] ?? null) ? trim((string) $validated['badge_text']) : null,
            'price_cop' => (int) $validated['price_cop'],
            'billing_cycle' => trim((string) $validated['billing_cycle']),
            'is_public' => $request->boolean('is_public', true),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $sortOrder,
            'listing_limit' => (int) $entitlements[EntitlementKey::MAX_LISTINGS_TOTAL],
            'included_cities' => (int) $entitlements[EntitlementKey::MAX_CITIES_COVERED],
            'max_photos_per_listing' => (int) $entitlements[EntitlementKey::MAX_PHOTOS_PER_LISTING],
            'max_videos_per_listing' => (bool) $entitlements[EntitlementKey::CAN_ADD_VIDEO]
                ? (int) $entitlements[EntitlementKey::MAX_VIDEOS_PER_LISTING]
                : 0,
            'show_whatsapp' => (bool) $entitlements[EntitlementKey::CAN_SHOW_WHATSAPP],
            'show_phone' => (bool) $entitlements[EntitlementKey::CAN_SHOW_PHONE],
            'priority_level' => (int) $entitlements[EntitlementKey::PRIORITY_LEVEL],
            'allows_verification' => (bool) $entitlements[EntitlementKey::CAN_REQUEST_VERIFICATION],
            'allows_featured_city' => (bool) $entitlements[EntitlementKey::CAN_FEATURED_CITY],
            'allows_featured_home' => (bool) $entitlements[EntitlementKey::CAN_FEATURED_HOME],
            'has_premium_badge' => (bool) $entitlements[EntitlementKey::HAS_PREMIUM_BADGE],
            'has_advanced_stats' => (bool) $entitlements[EntitlementKey::HAS_ADVANCED_STATS],
        ]);

        $plan->save();

        foreach ($entitlements as $key => $value) {
            $plan->entitlements()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'value_type' => EntitlementKey::typeFor($key),
                    'metadata' => null,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolveEntitlementValues(Request $request, array $validated): array
    {
        $values = [];

        foreach (EntitlementKey::definitions() as $key => $definition) {
            $raw = $definition['type'] === 'boolean'
                ? $request->boolean('entitlements.'.$key)
                : data_get($validated, 'entitlements.'.$key, EntitlementKey::defaultFor($key));

            $values[$key] = EntitlementKey::normalize(
                $key,
                $raw === null || $raw === '' ? EntitlementKey::defaultFor($key) : $raw
            );
        }

        if (! $values[EntitlementKey::CAN_ADD_VIDEO]) {
            $values[EntitlementKey::MAX_VIDEOS_PER_LISTING] = 0;
        }

        return $values;
    }

    private function resolveUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'paquete';
        $candidate = $slug;
        $counter = 2;

        while (
            Plan::query()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
