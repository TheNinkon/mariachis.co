<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceCityController extends Controller
{
    public function index(): View
    {
        $cities = MarketplaceCity::query()
            ->withCount(['zones', 'listings'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        return view('content.admin.marketplace-cities-index', [
            'cities' => $cities,
        ]);
    }

    public function create(): View
    {
        return view('content.admin.marketplace-cities-form', [
            'city' => new MarketplaceCity(),
            'formAction' => route('admin.marketplace-cities.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear ciudad',
            'pageTitle' => 'Nueva ciudad',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCity($request);

        $city = new MarketplaceCity();
        $this->fillCity($city, $validated, $request);
        $city->save();

        return redirect()->route('admin.marketplace-cities.index')->with('status', 'Ciudad creada correctamente.');
    }

    public function edit(MarketplaceCity $marketplaceCity): View
    {
        return view('content.admin.marketplace-cities-form', [
            'city' => $marketplaceCity,
            'formAction' => route('admin.marketplace-cities.update', $marketplaceCity),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar ciudad',
        ]);
    }

    public function update(Request $request, MarketplaceCity $marketplaceCity): RedirectResponse
    {
        $validated = $this->validateCity($request, $marketplaceCity->id);

        $this->fillCity($marketplaceCity, $validated, $request);
        $marketplaceCity->save();

        return redirect()->route('admin.marketplace-cities.index')->with('status', 'Ciudad actualizada.');
    }

    public function toggleStatus(MarketplaceCity $marketplaceCity): RedirectResponse
    {
        $marketplaceCity->update(['is_active' => ! $marketplaceCity->is_active]);

        return back()->with('status', 'Ciudad '.($marketplaceCity->is_active ? 'activada' : 'desactivada').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCity(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('marketplace_cities', 'name')->ignore($ignoreId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('marketplace_cities', 'slug')->ignore($ignoreId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'show_in_search' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillCity(MarketplaceCity $city, array $validated, Request $request): void
    {
        $name = trim((string) $validated['name']);
        $baseSlug = Str::slug((string) ($validated['slug'] ?: $name));

        if ($baseSlug === '') {
            $baseSlug = 'ciudad';
        }

        $slug = $this->resolveUniqueSlug($baseSlug, $city->id);

        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null || (int) $sortOrder < 0) {
            $sortOrder = ((int) MarketplaceCity::query()->max('sort_order')) + 1;
        }

        $city->fill([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => (int) $sortOrder,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured'),
            'show_in_search' => $request->boolean('show_in_search', true),
        ]);
    }

    private function resolveUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (
            MarketplaceCity::query()
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
