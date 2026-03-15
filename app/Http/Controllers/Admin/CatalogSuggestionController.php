<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogSuggestion;
use App\Models\EventType;
use App\Models\MarketplaceCity;
use App\Models\MarketplaceZone;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogSuggestionController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', CatalogSuggestion::STATUS_PENDING);
        $validStatuses = [
            CatalogSuggestion::STATUS_PENDING,
            CatalogSuggestion::STATUS_APPROVED,
            CatalogSuggestion::STATUS_REJECTED,
            'all',
        ];

        if (! in_array($status, $validStatuses, true)) {
            $status = CatalogSuggestion::STATUS_PENDING;
        }

        $suggestions = CatalogSuggestion::query()
            ->with([
                'submittedBy:id,name,first_name,last_name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('content.admin.catalog-suggestions-index', [
            'suggestions' => $suggestions,
            'selectedStatus' => $status,
            'citiesById' => MarketplaceCity::query()->pluck('name', 'id'),
            'statuses' => [
                CatalogSuggestion::STATUS_PENDING => 'Pendientes',
                CatalogSuggestion::STATUS_APPROVED => 'Aprobadas',
                CatalogSuggestion::STATUS_REJECTED => 'Rechazadas',
                'all' => 'Todas',
            ],
        ]);
    }

    public function approve(Request $request, CatalogSuggestion $catalogSuggestion): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($catalogSuggestion, $validated, $request): void {
            $resolved = $this->resolveSuggestionTarget($catalogSuggestion);

            $context = (array) ($catalogSuggestion->context_data ?? []);
            $context['resolved_model'] = $resolved['model'];
            $context['resolved_id'] = $resolved['id'];

            $catalogSuggestion->update([
                'status' => CatalogSuggestion::STATUS_APPROVED,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'context_data' => $context,
                'reviewed_by_user_id' => $request->user()?->id,
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('status', 'Sugerencia aprobada y convertida en opción oficial.');
    }

    public function reject(Request $request, CatalogSuggestion $catalogSuggestion): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:2000'],
        ]);

        $catalogSuggestion->update([
            'status' => CatalogSuggestion::STATUS_REJECTED,
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by_user_id' => $request->user()?->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Sugerencia rechazada.');
    }

    /**
     * @return array{model:string,id:int}
     */
    private function resolveSuggestionTarget(CatalogSuggestion $suggestion): array
    {
        $name = trim((string) $suggestion->proposed_name);
        $slug = Str::slug((string) ($suggestion->proposed_slug ?: $name));

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages([
                'suggestion' => 'La sugerencia no tiene nombre válido para aprobar.',
            ]);
        }

        return match ($suggestion->catalog_type) {
            CatalogSuggestion::TYPE_EVENT => $this->resolveEventType($name, $slug),
            CatalogSuggestion::TYPE_SERVICE => $this->resolveServiceType($name, $slug),
            CatalogSuggestion::TYPE_CITY => $this->resolveCity($name, $slug),
            CatalogSuggestion::TYPE_ZONE => $this->resolveZone($name, $slug, (array) ($suggestion->context_data ?? [])),
            default => throw ValidationException::withMessages([
                'suggestion' => 'Tipo de sugerencia no soportado.',
            ]),
        };
    }

    /**
     * @return array{model:string,id:int}
     */
    private function resolveEventType(string $name, string $slug): array
    {
        $existing = EventType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhere('slug', $slug)
            ->first();

        if ($existing) {
            return ['model' => EventType::class, 'id' => (int) $existing->id];
        }

        $eventType = EventType::query()->create([
            'name' => $name,
            'slug' => $this->resolveUniqueSlug('event_types', $slug),
            'icon' => 'confetti',
            'sort_order' => ((int) EventType::query()->max('sort_order')) + 1,
            'is_featured' => false,
            'is_active' => true,
            'is_visible_in_home' => false,
            'home_priority' => 999,
            'min_active_listings_required' => null,
            'home_clicks_count' => 0,
        ]);

        return ['model' => EventType::class, 'id' => (int) $eventType->id];
    }

    /**
     * @return array{model:string,id:int}
     */
    private function resolveServiceType(string $name, string $slug): array
    {
        $existing = ServiceType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhere('slug', $slug)
            ->first();

        if ($existing) {
            return ['model' => ServiceType::class, 'id' => (int) $existing->id];
        }

        $serviceType = ServiceType::query()->create([
            'name' => $name,
            'slug' => $this->resolveUniqueSlug('service_types', $slug),
            'icon' => 'settings',
            'sort_order' => ((int) ServiceType::query()->max('sort_order')) + 1,
            'is_featured' => false,
            'is_active' => true,
        ]);

        return ['model' => ServiceType::class, 'id' => (int) $serviceType->id];
    }

    /**
     * @return array{model:string,id:int}
     */
    private function resolveCity(string $name, string $slug): array
    {
        $existing = MarketplaceCity::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orWhere('slug', $slug)
            ->first();

        if ($existing) {
            return ['model' => MarketplaceCity::class, 'id' => (int) $existing->id];
        }

        $city = MarketplaceCity::query()->create([
            'name' => $name,
            'slug' => $this->resolveUniqueSlug('marketplace_cities', $slug),
            'sort_order' => ((int) MarketplaceCity::query()->max('sort_order')) + 1,
            'is_featured' => false,
            'is_active' => true,
            'show_in_search' => true,
        ]);

        return ['model' => MarketplaceCity::class, 'id' => (int) $city->id];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{model:string,id:int}
     */
    private function resolveZone(string $name, string $slug, array $context): array
    {
        $cityId = (int) ($context['marketplace_city_id'] ?? 0);

        if ($cityId <= 0) {
            throw ValidationException::withMessages([
                'suggestion' => 'La sugerencia de zona no tiene ciudad asociada.',
            ]);
        }

        $city = MarketplaceCity::query()->find($cityId);
        if (! $city) {
            throw ValidationException::withMessages([
                'suggestion' => 'La ciudad asociada a la sugerencia no existe.',
            ]);
        }

        $existing = MarketplaceZone::query()
            ->where('marketplace_city_id', $city->id)
            ->where(function ($query) use ($name, $slug): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            return ['model' => MarketplaceZone::class, 'id' => (int) $existing->id];
        }

        $zone = MarketplaceZone::query()->create([
            'marketplace_city_id' => $city->id,
            'name' => $name,
            'slug' => $this->resolveUniqueSlugByCity($city->id, $slug),
            'sort_order' => ((int) MarketplaceZone::query()
                ->where('marketplace_city_id', $city->id)
                ->max('sort_order')) + 1,
            'is_active' => true,
            'show_in_search' => true,
        ]);

        return ['model' => MarketplaceZone::class, 'id' => (int) $zone->id];
    }

    private function resolveUniqueSlug(string $table, string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (
            DB::table($table)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function resolveUniqueSlugByCity(int $cityId, string $baseSlug, ?int $ignoreId = null): string
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
