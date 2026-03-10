<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogOptionController extends Controller
{
    public function index(string $catalog): View
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        $items = $modelClass::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('content.admin.catalog-options-index', [
            'catalog' => $catalog,
            'meta' => $meta,
            'items' => $items,
        ]);
    }

    public function create(string $catalog): View
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        return view('content.admin.catalog-options-form', [
            'catalog' => $catalog,
            'meta' => $meta,
            'item' => new $modelClass(),
            'formAction' => route('admin.catalog-options.store', ['catalog' => $catalog]),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear opción',
            'pageTitle' => 'Nueva opción de '.$meta['title'],
            'iconOptions' => $this->iconOptions(),
        ]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        $validated = $this->validateCatalogOption($request, $meta);

        $item = new $modelClass();
        $this->fillCatalogOption($item, $validated, $request, $meta);
        $item->save();

        return redirect()
            ->route('admin.catalog-options.index', ['catalog' => $catalog])
            ->with('status', $meta['singular'].' creado correctamente.');
    }

    public function edit(string $catalog, int $id): View
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        $item = $modelClass::query()->findOrFail($id);

        return view('content.admin.catalog-options-form', [
            'catalog' => $catalog,
            'meta' => $meta,
            'item' => $item,
            'formAction' => route('admin.catalog-options.update', ['catalog' => $catalog, 'id' => $item->id]),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar '.$meta['singular'],
            'iconOptions' => $this->iconOptions(),
        ]);
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        $item = $modelClass::query()->findOrFail($id);
        $validated = $this->validateCatalogOption($request, $meta, $item->id);

        $this->fillCatalogOption($item, $validated, $request, $meta);
        $item->save();

        return redirect()
            ->route('admin.catalog-options.index', ['catalog' => $catalog])
            ->with('status', $meta['singular'].' actualizado.');
    }

    public function toggleStatus(string $catalog, int $id): RedirectResponse
    {
        $meta = $this->resolveCatalog($catalog);
        $modelClass = $meta['model'];

        $item = $modelClass::query()->findOrFail($id);
        $item->update(['is_active' => ! (bool) $item->is_active]);

        return back()->with('status', $meta['singular'].' '.($item->is_active ? 'activado' : 'desactivado').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCatalogOption(Request $request, array $meta, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique($meta['table'], 'slug')->ignore($ignoreId),
            ],
            'icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  object  $item
     * @param  array<string, mixed>  $meta
     */
    private function fillCatalogOption(object $item, array $validated, Request $request, array $meta): void
    {
        $name = trim((string) $validated['name']);
        $baseSlug = Str::slug((string) ($validated['slug'] ?: $name));

        if ($baseSlug === '') {
            $baseSlug = Str::slug((string) $meta['slug_fallback']);
        }

        $slug = $this->resolveUniqueSlug(
            $meta['table'],
            $baseSlug,
            $item->id ?? null
        );

        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null || (int) $sortOrder < 0) {
            $maxSort = (int) $meta['model']::query()->max('sort_order');
            $sortOrder = $maxSort + 1;
        }

        $item->fill([
            'name' => $name,
            'slug' => $slug,
            'icon' => trim((string) ($validated['icon'] ?? '')) ?: $meta['default_icon'],
            'sort_order' => (int) $sortOrder,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured'),
        ]);
    }

    private function resolveUniqueSlug(string $table, string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'catalogo';
        $candidate = $slug;
        $counter = 2;

        while (
            app('db')->table($table)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @return array<string, string>
     */
    private function iconOptions(): array
    {
        return [
            'confetti' => 'Confeti',
            'sparkles' => 'Destacado',
            'rings' => 'Bodas',
            'cake' => 'Cumpleaños',
            'briefcase' => 'Corporativo',
            'party' => 'Fiesta',
            'music-note' => 'Música',
            'church' => 'Ceremonia',
            'flower' => 'Homenaje',
            'home' => 'Domicilio',
            'clock' => 'Horas',
            'gift' => 'Sorpresa',
            'settings' => 'Personalizado',
            'microphone' => 'Show',
            'edit' => 'Editar',
            'users-3' => '3 integrantes',
            'users-4' => '4 integrantes',
            'users-5' => '5 integrantes',
            'users-7' => '7 integrantes',
            'users-group' => 'Grupo completo',
            'users' => 'Personas',
            'wallet' => 'Económico',
            'coins' => 'Presupuesto',
            'diamond' => 'Premium',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCatalog(string $catalog): array
    {
        $catalogs = [
            'event-types' => [
                'title' => 'Tipos de evento',
                'singular' => 'tipo de evento',
                'description' => 'Opciones oficiales para clasificar los anuncios por ocasión.',
                'model' => EventType::class,
                'table' => 'event_types',
                'default_icon' => 'confetti',
                'slug_fallback' => 'tipo-evento',
            ],
            'service-types' => [
                'title' => 'Tipos de servicio',
                'singular' => 'tipo de servicio',
                'description' => 'Opciones oficiales para describir cómo se presta el servicio.',
                'model' => ServiceType::class,
                'table' => 'service_types',
                'default_icon' => 'settings',
                'slug_fallback' => 'tipo-servicio',
            ],
            'group-sizes' => [
                'title' => 'Tamaños de grupo',
                'singular' => 'tamaño de grupo',
                'description' => 'Opciones oficiales para número de integrantes del mariachi.',
                'model' => GroupSizeOption::class,
                'table' => 'group_size_options',
                'default_icon' => 'users',
                'slug_fallback' => 'tamano-grupo',
            ],
            'budget-ranges' => [
                'title' => 'Presupuestos',
                'singular' => 'presupuesto',
                'description' => 'Rangos oficiales para segmentar por nivel de inversión.',
                'model' => BudgetRange::class,
                'table' => 'budget_ranges',
                'default_icon' => 'coins',
                'slug_fallback' => 'presupuesto',
            ],
        ];

        abort_unless(isset($catalogs[$catalog]), 404);

        return $catalogs[$catalog];
    }
}
