<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCity;
use App\Models\BlogPost;
use App\Models\BlogZone;
use App\Models\EventType;
use App\Models\MariachiListing;
use App\Models\MariachiListingServiceArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->with([
                'author:id,first_name,last_name,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
                'eventTypes:id,name',
            ])
            ->latest('updated_at')
            ->paginate(15);

        return view('content.admin.blog-posts-index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        [$cities, $zones] = $this->resolveLocationOptions();

        return view('content.admin.blog-posts-form', [
            'post' => new BlogPost(),
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'cities' => $cities,
            'zones' => $zones,
            'statuses' => $this->statuses(),
            'formAction' => route('admin.blog-posts.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Publicar entrada',
            'pageTitle' => 'Nueva entrada',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request);

        $post = new BlogPost();
        $this->fillPost($post, $validated);
        $post->author_id = $request->user()?->id;
        $post->save();

        $this->syncPostRelations($post, $validated);

        return redirect()->route('admin.blog-posts.index')->with('status', 'Entrada creada correctamente.');
    }

    public function edit(BlogPost $blogPost): View
    {
        [$cities, $zones] = $this->resolveLocationOptions();
        $blogPost->loadMissing([
            'cities:id,name',
            'zones:id,name,blog_city_id',
            'eventTypes:id,name',
        ]);

        return view('content.admin.blog-posts-form', [
            'post' => $blogPost,
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'cities' => $cities,
            'zones' => $zones,
            'statuses' => $this->statuses(),
            'formAction' => route('admin.blog-posts.update', $blogPost),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar entrada',
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $this->validatePost($request, $blogPost->id);

        $this->fillPost($blogPost, $validated);
        $blogPost->save();

        $this->syncPostRelations($blogPost, $validated);

        return redirect()->route('admin.blog-posts.index')->with('status', 'Entrada actualizada correctamente.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }
        if ($blogPost->og_image) {
            Storage::disk('public')->delete($blogPost->og_image);
        }

        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('status', 'Entrada eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePost(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('blog_posts', 'slug')->ignore($ignoreId),
            ],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:600'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', 'image', 'max:5120'],
            'clear_og_image' => ['nullable', 'boolean'],
            'robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'canonical_override' => ['nullable', 'url:http,https', 'max:2048'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['integer', 'exists:blog_cities,id'],
            'zone_ids' => ['nullable', 'array'],
            'zone_ids.*' => ['integer', 'exists:blog_zones,id'],
            'event_type_ids' => ['nullable', 'array'],
            'event_type_ids.*' => ['integer', 'exists:event_types,id'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $cityIds = $this->normalizeIds($request->input('city_ids', []));
            $zoneIds = $this->normalizeIds($request->input('zone_ids', []));

            if ($zoneIds->isNotEmpty() && $cityIds->isEmpty()) {
                $validator->errors()->add('zone_ids', 'Selecciona al menos una ciudad para poder asignar zonas.');

                return;
            }

            if ($zoneIds->isNotEmpty()) {
                $validZoneIds = BlogZone::query()
                    ->whereIn('id', $zoneIds)
                    ->whereIn('blog_city_id', $cityIds)
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();

                $invalidCount = $zoneIds->diff($validZoneIds)->count();
                if ($invalidCount > 0) {
                    $validator->errors()->add('zone_ids', 'Algunas zonas no pertenecen a las ciudades seleccionadas.');
                }
            }
        });

        return $validator->validate();
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function fillPost(BlogPost $post, array $validated): void
    {
        $baseSlug = Str::slug((string) ($validated['slug'] ?: $validated['title']));
        if ($baseSlug === '') {
            $baseSlug = 'entrada-blog';
        }
        $slug = $this->resolveUniqueSlug($baseSlug, $post->id);

        if (! empty($validated['featured_image']) && $validated['featured_image']->isValid()) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $post->featured_image = $validated['featured_image']->store('blog-posts', 'public');
        }

        if (! empty($validated['og_image']) && $validated['og_image']->isValid()) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }

            $post->og_image = $validated['og_image']->store('blog-posts/seo', 'public');
        } elseif (! empty($validated['clear_og_image'])) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }

            $post->og_image = null;
        }

        $cityIds = $this->normalizeIds($validated['city_ids'] ?? []);
        $zoneIds = $this->normalizeIds($validated['zone_ids'] ?? []);
        $eventTypeIds = $this->normalizeIds($validated['event_type_ids'] ?? []);

        $primaryCityName = $cityIds->isNotEmpty()
            ? BlogCity::query()->whereIn('id', $cityIds)->orderBy('name')->value('name')
            : null;
        $primaryZoneName = $zoneIds->isNotEmpty()
            ? BlogZone::query()->whereIn('id', $zoneIds)->orderBy('name')->value('name')
            : null;
        $primaryEventTypeId = $eventTypeIds->isNotEmpty()
            ? (int) $eventTypeIds->first()
            : null;

        $post->fill([
            'title' => $validated['title'],
            'slug' => $slug,
            'meta_title' => $validated['meta_title'] ?: null,
            'excerpt' => $validated['excerpt'] ?: null,
            'meta_description' => $validated['meta_description'] ?: null,
            'content' => $validated['content'] ?: null,
            'robots' => $validated['robots'] ?: null,
            'canonical_override' => $validated['canonical_override'] ?: null,
            'status' => $validated['status'],
            // Legacy fallback fields: keep first relation for old consumers.
            'city_name' => $primaryCityName,
            'zone_name' => $primaryZoneName,
            'event_type_id' => $primaryEventTypeId,
            'published_at' => $validated['status'] === BlogPost::STATUS_PUBLISHED
                ? ($post->published_at ?: now())
                : null,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function syncPostRelations(BlogPost $post, array $validated): void
    {
        $cityIds = $this->normalizeIds($validated['city_ids'] ?? []);
        $zoneIds = $this->normalizeIds($validated['zone_ids'] ?? []);
        $eventTypeIds = $this->normalizeIds($validated['event_type_ids'] ?? []);

        $post->cities()->sync($cityIds->all());
        $post->zones()->sync($zoneIds->all());
        $post->eventTypes()->sync($eventTypeIds->all());
    }

    /**
     * @return array{0:Collection<int, BlogCity>,1:Collection<int, BlogZone>}
     */
    private function resolveLocationOptions(): array
    {
        $this->syncLocationCatalogFromListings();

        $cities = BlogCity::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $zones = BlogZone::query()
            ->orderBy('name')
            ->get(['id', 'blog_city_id', 'name', 'slug']);

        return [$cities, $zones];
    }

    private function syncLocationCatalogFromListings(): void
    {
        $listingCities = MariachiListing::query()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->pluck('city_name');

        foreach ($listingCities as $cityName) {
            $normalizedCity = $this->normalizeName((string) $cityName);
            if (! $normalizedCity) {
                continue;
            }

            $citySlug = Str::slug($normalizedCity);
            if ($citySlug === '') {
                continue;
            }

            BlogCity::query()->firstOrCreate(
                ['slug' => $citySlug],
                ['name' => $normalizedCity]
            );
        }

        $zoneRows = MariachiListingServiceArea::query()
            ->join('mariachi_listings as listings', 'listings.id', '=', 'mariachi_listing_service_areas.mariachi_listing_id')
            ->whereNotNull('listings.city_name')
            ->where('listings.city_name', '!=', '')
            ->whereNotNull('mariachi_listing_service_areas.city_name')
            ->where('mariachi_listing_service_areas.city_name', '!=', '')
            ->select([
                'listings.city_name as city_name',
                'mariachi_listing_service_areas.city_name as zone_name',
            ])
            ->distinct()
            ->get();

        foreach ($zoneRows as $row) {
            $normalizedCity = $this->normalizeName((string) $row->city_name);
            $normalizedZone = $this->normalizeName((string) $row->zone_name);

            if (! $normalizedCity || ! $normalizedZone) {
                continue;
            }

            $citySlug = Str::slug($normalizedCity);
            $zoneSlug = Str::slug($normalizedZone);

            if ($citySlug === '' || $zoneSlug === '') {
                continue;
            }

            $city = BlogCity::query()->firstOrCreate(
                ['slug' => $citySlug],
                ['name' => $normalizedCity]
            );

            BlogZone::query()->firstOrCreate(
                [
                    'blog_city_id' => $city->id,
                    'slug' => $zoneSlug,
                ],
                ['name' => $normalizedZone]
            );
        }
    }

    private function resolveUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (
            BlogPost::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            BlogPost::STATUS_DRAFT => 'Borrador',
            BlogPost::STATUS_PUBLISHED => 'Publicado',
        ];
    }

    /**
     * @param  mixed  $value
     */
    private function normalizeName(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', $value));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  mixed  $ids
     * @return Collection<int, int>
     */
    private function normalizeIds(mixed $ids): Collection
    {
        return collect(is_array($ids) ? $ids : [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }
}
