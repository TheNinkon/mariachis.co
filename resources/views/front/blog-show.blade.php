<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}" />
    <base href="{{ asset('marketplace') }}/" />
    <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/theme.css?v=20260311-brand-green-v1" />
    <script type="application/ld+json">{!! $schemaJson !!}</script>
  </head>
  <body data-page="listing" class="font-sans text-slate-900 antialiased">
    <div data-component="site-header"></div>

    <main>
      <section class="layout-shell layout-shell--narrow py-10">
        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">← Volver al blog</a>

        <article class="listing-flow-section mt-4">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">Blog</p>
          <h1 class="mt-2 text-4xl font-extrabold tracking-[-0.015em] text-slate-900">{{ $h1 }}</h1>

          <div class="mt-3 flex flex-wrap gap-2">
            <span class="artist-seo-chip">{{ optional($post->published_at)->format('d/m/Y') ?: optional($post->created_at)->format('d/m/Y') }}</span>
            @if($post->cities->isNotEmpty())
              @foreach($post->cities as $city)
                <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($city->name)]) }}" class="artist-seo-chip">{{ $city->name }}</a>
              @endforeach
            @elseif($post->city_name)
              <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($post->city_name)]) }}" class="artist-seo-chip">{{ $post->city_name }}</a>
            @endif
            @if($post->zones->isNotEmpty())
              @foreach($post->zones as $zone)
                @php
                  $zoneCitySlug = \Illuminate\Support\Str::slug($zone->city?->name ?: $post->primary_city_name);
                  $zoneSlug = \Illuminate\Support\Str::slug($zone->name);
                @endphp
                @if($zoneCitySlug && $zoneSlug)
                  <a href="{{ route('seo.landing.city-category', ['citySlug' => $zoneCitySlug, 'scopeSlug' => $zoneSlug]) }}" class="artist-seo-chip">{{ $zone->name }}</a>
                @endif
              @endforeach
            @elseif($post->zone_name && $post->city_name)
              <a href="{{ route('seo.landing.city-category', ['citySlug' => \Illuminate\Support\Str::slug($post->city_name), 'scopeSlug' => \Illuminate\Support\Str::slug($post->zone_name)]) }}" class="artist-seo-chip">{{ $post->zone_name }}</a>
            @endif
            @if($post->eventTypes->isNotEmpty())
              @foreach($post->eventTypes as $eventType)
                <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($eventType->name)]) }}" class="artist-seo-chip">{{ $eventType->name }}</a>
              @endforeach
            @elseif($post->eventType)
              <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($post->eventType->name)]) }}" class="artist-seo-chip">{{ $post->eventType->name }}</a>
            @endif
          </div>

          <div class="listing-stage mt-5">
            <div class="listing-stage-media">
              <div class="listing-cover listing-stage-main overflow-hidden rounded-xl">
                <img src="{{ $post->featured_image ? asset('storage/'.$post->featured_image) : asset('marketplace/assets/logo-wordmark.png') }}" alt="{{ $post->title }}" class="h-full w-full object-cover" />
              </div>
            </div>
          </div>

          @if($post->excerpt)
            <p class="mt-5 text-lg font-semibold text-slate-700">{{ $post->excerpt }}</p>
          @endif

          <div class="prose prose-slate mt-6 max-w-none text-slate-700">{!! $post->content ?: '<p>Este artículo aún no tiene contenido extendido.</p>' !!}</div>
        </article>
      </section>

      <section class="layout-shell pb-14">
        <div class="mb-5 flex items-end justify-between gap-3">
          <h2 class="text-2xl font-extrabold text-slate-900">Recursos relacionados</h2>
          <a href="{{ route('blog.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Ver todos</a>
        </div>

        @if($relatedPosts->isNotEmpty())
          <div class="grid gap-4 md:grid-cols-3">
            @foreach($relatedPosts as $related)
              <article class="featured-promo-card">
                <a href="{{ route('blog.show', ['slug' => $related->slug]) }}" class="featured-promo-media">
                  <img src="{{ $related->featured_image ? asset('storage/'.$related->featured_image) : asset('marketplace/assets/logo-wordmark.png') }}" alt="{{ $related->title }}" />
                  <span class="featured-promo-chip">{{ $related->primary_city_name ?: 'Colombia' }}</span>
                </a>
                <div class="featured-promo-body">
                  <h3 class="featured-promo-title">{{ $related->title }}</h3>
                </div>
                <div class="featured-promo-footer">
                  <p class="featured-promo-meta">{{ $related->excerpt ?: 'Artículo relacionado con esta búsqueda.' }}</p>
                </div>
              </article>
            @endforeach
          </div>
        @else
          <div class="surface rounded-3xl p-6">
            <p class="text-sm text-slate-600">Aún no hay recursos relacionados para este artículo.</p>
          </div>
        @endif
      </section>
    </main>

    <div data-component="site-footer"></div>
    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260311-brand-green-v1"></script>
  </body>
</html>
