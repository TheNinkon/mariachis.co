<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MariachiListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicListingCollectionController extends Controller
{
    public function wishlist(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isClient()) {
            return redirect()->route('client.account.favorites');
        }

        return view('front.public-listing-collection', [
            'pageTitle' => 'Lista de deseos | Mariachis.co',
            'pageDescription' => 'Tus mariachis guardados en este navegador para volver a compararlos cuando quieras.',
            'collectionType' => 'favorites',
            'headline' => 'Lista de deseos',
            'eyebrow' => 'Guardado en este navegador',
            'intro' => 'Guarda los anuncios que más te gusten y vuelve a ellos cuando quieras comparar precios, fotos y estilo.',
            'emptyTitle' => 'Todavía no guardas anuncios',
            'emptyBody' => 'Cuando pulses "Guardar" en un anuncio, aparecerá aquí automáticamente.',
            'emptyCtaLabel' => 'Explorar mariachis',
            'emptyCtaUrl' => route('home'),
            'resolveUrl' => route('public.listings.resolve'),
            'clearLabel' => 'Vaciar lista',
        ]);
    }

    public function recentlyViewed(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isClient()) {
            return redirect()->route('client.account.recent');
        }

        return view('front.public-listing-collection', [
            'pageTitle' => 'Vistos recientemente | Mariachis.co',
            'pageDescription' => 'Recupera los últimos anuncios de mariachis que revisaste en este navegador.',
            'collectionType' => 'recents',
            'headline' => 'Vistos recientemente',
            'eyebrow' => 'Historial local',
            'intro' => 'Retoma tu búsqueda donde la dejaste. Aquí verás los anuncios que revisaste hace poco en este dispositivo.',
            'emptyTitle' => 'Aún no tienes anuncios recientes',
            'emptyBody' => 'Cuando abras perfiles públicos, los iremos guardando aquí para que no pierdas el hilo.',
            'emptyCtaLabel' => 'Ver anuncios activos',
            'emptyCtaUrl' => route('home'),
            'resolveUrl' => route('public.listings.resolve'),
            'clearLabel' => 'Borrar historial',
        ]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $ids = $this->normalizeIntegerValues($request->query('ids'));
        $slugs = $this->normalizeStringValues($request->query('slugs'));

        if ($ids->isEmpty() && $slugs->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $query = MariachiListing::query()
            ->with([
                'mariachiProfile.user:id,name,first_name,last_name',
                'photos',
                'eventTypes:id,name',
            ])
            ->published();

        $query->where(function ($builder) use ($ids, $slugs): void {
            if ($ids->isNotEmpty()) {
                $builder->whereIn('id', $ids->all());
            }

            if ($slugs->isNotEmpty()) {
                $method = $ids->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                $builder->{$method}('slug', $slugs->all());
            }
        });

        $listings = $query->get();
        $byId = $listings->keyBy(fn (MariachiListing $listing): string => (string) $listing->id);
        $bySlug = $listings->keyBy(fn (MariachiListing $listing): string => (string) $listing->slug);

        $ordered = collect()
            ->concat($ids->map(fn (int $id): ?MariachiListing => $byId->get((string) $id)))
            ->concat($slugs->map(fn (string $slug): ?MariachiListing => $bySlug->get($slug)))
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn (MariachiListing $listing): array => $this->serializeListing($listing));

        return response()->json(['data' => $ordered]);
    }

    private function normalizeIntegerValues(mixed $value): Collection
    {
        return $this->normalizeStringValues($value)
            ->map(fn (string $item): int => (int) $item)
            ->filter(fn (int $item): bool => $item > 0)
            ->values();
    }

    private function normalizeStringValues(mixed $value): Collection
    {
        $items = collect();

        if (is_array($value)) {
            $items = collect($value);
        } elseif (is_string($value)) {
            $items = collect(explode(',', $value));
        }

        return $items
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values();
    }

    private function serializeListing(MariachiListing $listing): array
    {
        $photo = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
        $name = $listing->business_name ?: $listing->user?->display_name ?: $listing->title ?: 'Mariachi disponible';
        $eventLabels = $listing->eventTypes->pluck('name')->take(2)->values();

        return [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'title' => $name,
            'city' => $listing->city_name ?: 'Colombia',
            'state' => $listing->state ?: '',
            'description' => $listing->short_description ?: 'Perfil activo para serenatas, bodas y celebraciones privadas.',
            'price_label' => $listing->base_price ? 'Desde $'.number_format((float) $listing->base_price, 0, ',', '.') : 'Cotización directa',
            'image_url' => $photo ? asset('storage/'.$photo->path) : asset('marketplace/assets/logo-wordmark.png'),
            'detail_url' => route('mariachi.public.show', ['slug' => $listing->slug]),
            'event_labels' => $eventLabels->all(),
            'favorite_key' => 'listing-'.$listing->id,
            'completion' => (int) $listing->profile_completion,
        ];
    }
}
