<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Seo\SeoResolver;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request, SeoResolver $seoResolver): View
    {
        $baseQuery = BlogPost::query()
            ->with([
                'eventTypes:id,name',
                'cities:id,name',
                'zones:id,name,blog_city_id',
            ])
            ->published()
            ->latest('published_at')
            ->latest('id');

        $heroPosts = (clone $baseQuery)
            ->take(3)
            ->get();

        $posts = (clone $baseQuery)
            ->paginate(9);

        return view('front.blog-index', [
            'seo' => $seoResolver->resolve($request, 'blog_index', [
                'page_key' => 'blog_index',
                'title' => 'Blog y recursos para contratar mariachis',
                'description' => 'Consejos, guias y recursos locales para encontrar mariachis por ciudad, zona y tipo de evento en Colombia.',
            ]),
            'posts' => $posts,
            'heroPosts' => $heroPosts,
            'seoTitle' => 'Blog y recursos para contratar mariachis',
            'seoDescription' => 'Consejos, guias y recursos locales para encontrar mariachis por ciudad, zona y tipo de evento en Colombia.',
            'h1' => 'Blog y recursos del marketplace',
        ]);
    }

    public function show(Request $request, string $slug, SeoResolver $seoResolver): View
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
            'seo' => $seoResolver->resolve($request, 'blog_post', [
                'title' => $post->meta_title ?: $seoTitle,
                'description' => $post->meta_description ?: $seoDescription,
                'canonical' => $post->canonical_override ?: route('blog.show', ['slug' => $post->slug]),
                'robots' => $post->robots ?: 'index,follow',
                'og_image' => $post->og_image ?: $post->featured_image,
                'og_type' => 'article',
                'jsonld' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'h1' => $post->title,
            'schemaJson' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
