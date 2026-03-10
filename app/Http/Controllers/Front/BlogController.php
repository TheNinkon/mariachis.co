<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
            ])
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(9);

        return view('front.blog-index', [
            'posts' => $posts,
            'seoTitle' => 'Blog y recursos para contratar mariachis',
            'seoDescription' => 'Consejos, guias y recursos locales para encontrar mariachis por ciudad, zona y tipo de evento en Colombia.',
            'h1' => 'Blog y recursos del marketplace',
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,slug,blog_city_id',
                'zones.city:id,name,slug',
            ])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $seoTitle = $post->title.' | Blog Mariachis';
        $seoDescription = (string) Str::limit(
            strip_tags($post->excerpt ?: $post->content ?: 'Guia y recursos para contratar mariachis en Colombia.'),
            160
        );

        $about = collect()
            ->merge($post->cities->pluck('name'))
            ->merge($post->zones->pluck('name'))
            ->merge($post->eventTypes->pluck('name'))
            ->when($post->cities->isEmpty() && $post->city_name, fn ($items) => $items->push($post->city_name))
            ->when($post->zones->isEmpty() && $post->zone_name, fn ($items) => $items->push($post->zone_name))
            ->when($post->eventTypes->isEmpty() && $post->eventType?->name, fn ($items) => $items->push($post->eventType->name))
            ->filter()
            ->unique()
            ->values();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $seoDescription,
            'datePublished' => optional($post->published_at)->toIso8601String() ?: optional($post->created_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'image' => $post->featured_image ? asset('storage/'.$post->featured_image) : null,
            'url' => route('blog.show', ['slug' => $post->slug]),
            'about' => $about->all(),
        ];

        $eventTypeIds = $post->eventTypes->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $cityIds = $post->cities->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $zoneIds = $post->zones->pluck('id')->map(fn (mixed $id): int => (int) $id);

        $relatedPostsQuery = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
            ])
            ->published()
            ->where('id', '!=', $post->id);

        if ($eventTypeIds->isNotEmpty() || $cityIds->isNotEmpty() || $zoneIds->isNotEmpty()) {
            $relatedPostsQuery->where(function (Builder $query) use ($eventTypeIds, $cityIds, $zoneIds): void {
                if ($eventTypeIds->isNotEmpty()) {
                    $query->orWhereHas('eventTypes', function (Builder $eventTypeQuery) use ($eventTypeIds): void {
                        $eventTypeQuery->whereIn('event_types.id', $eventTypeIds->all());
                    });
                }

                if ($cityIds->isNotEmpty()) {
                    $query->orWhereHas('cities', function (Builder $cityQuery) use ($cityIds): void {
                        $cityQuery->whereIn('blog_cities.id', $cityIds->all());
                    });
                }

                if ($zoneIds->isNotEmpty()) {
                    $query->orWhereHas('zones', function (Builder $zoneQuery) use ($zoneIds): void {
                        $zoneQuery->whereIn('blog_zones.id', $zoneIds->all());
                    });
                }
            });
        }

        $relatedPosts = $relatedPostsQuery
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'city_name', 'event_type_id', 'published_at']);

        return view('front.blog-show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'h1' => $post->title,
            'schemaJson' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
