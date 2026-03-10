<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceZoneController extends Controller
{
    public function index(Request $request): View
    {
        $cityId = (int) $request->query('city_id', 0);

        $zones = MarketplaceZone::query()
            ->with(['city:id,name'])
            ->withCount('serviceAreas')
            ->when($cityId > 0, fn ($query) => $query->where('marketplace_city_id', $cityId))
            ->orderBy('marketplace_city_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        return view('content.admin.marketplace-zones-index', [
            'zones' => $zones,
            'cities' => MarketplaceCity::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'selectedCityId' => $cityId,
        ]);
    }

    public function create(): View
    {
        return view('content.admin.marketplace-zones-form', [
            'zone' => new MarketplaceZone(),
            'cities' => MarketplaceCity::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'formAction' => route('admin.marketplace-zones.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear zona',
            'pageTitle' => 'Nueva zona / barrio',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateZone($request);

        $zone = new MarketplaceZone();
        $this->fillZone($zone, $validated, $request);
        $zone->save();

        return redirect()->route('admin.marketplace-zones.index')->with('status', 'Zona creada correctamente.');
    }

    public function edit(MarketplaceZone $marketplaceZone): View
    {
        return view('content.admin.marketplace-zones-form', [
            'zone' => $marketplaceZone,
            'cities' => MarketplaceCity::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'formAction' => route('admin.marketplace-zones.update', $marketplaceZone),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar zona / barrio',
        ]);
    }

    public function update(Request $request, MarketplaceZone $marketplaceZone): RedirectResponse
    {
        $validated = $this->validateZone($request, $marketplaceZone->id);

        $this->fillZone($marketplaceZone, $validated, $request);
        $marketplaceZone->save();

        return redirect()->route('admin.marketplace-zones.index')->with('status', 'Zona actualizada.');
    }

    public function toggleStatus(MarketplaceZone $marketplaceZone): RedirectResponse
    {
        $marketplaceZone->update(['is_active' => ! $marketplaceZone->is_active]);

        return back()->with('status', 'Zona '.($marketplaceZone->is_active ? 'activada' : 'desactivada').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateZone(Request $request, ?int $ignoreId = null): array
    {
        $cityId = (int) $request->input('marketplace_city_id');

        return $request->validate([
            'marketplace_city_id' => ['required', 'integer', 'exists:marketplace_cities,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('marketplace_zones', 'slug')
                    ->where(fn ($query) => $query->where('marketplace_city_id', $cityId))
                    ->ignore($ignoreId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_search' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillZone(MarketplaceZone $zone, array $validated, Request $request): void
    {
        $cityId = (int) $validated['marketplace_city_id'];
        $name = trim((string) $validated['name']);
        $baseSlug = Str::slug((string) ($validated['slug'] ?: $name));

        if ($baseSlug === '') {
            $baseSlug = 'zona';
        }

        $slug = $this->resolveUniqueSlug($cityId, $baseSlug, $zone->id);

        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null || (int) $sortOrder < 0) {
            $sortOrder = ((int) MarketplaceZone::query()
                ->where('marketplace_city_id', $cityId)
                ->max('sort_order')) + 1;
        }

        $zone->fill([
            'marketplace_city_id' => $cityId,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => (int) $sortOrder,
            'is_active' => $request->boolean('is_active', true),
            'show_in_search' => $request->boolean('show_in_search', true),
        ]);
    }

    private function resolveUniqueSlug(int $cityId, string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (
            MarketplaceZone::query()
                ->where('marketplace_city_id', $cityId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
