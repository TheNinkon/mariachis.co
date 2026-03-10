<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $seoTitle }} | Mariachis.co</title>
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
    <link rel="stylesheet" href="assets/theme.css?v=20260309-11" />
  </head>
  <body data-page="home" class="font-sans text-slate-900 antialiased">
    <div data-component="site-header"></div>

    <main>
      <section class="hero-home-immersive relative border-b border-slate-200">
        <div class="hero-home-immersive__backdrop" aria-hidden="true"></div>
        <div class="layout-shell py-14">
          <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Comunidad</p>
          <h1 class="hero-home-immersive__title mt-3">{{ $h1 }}</h1>
          <p class="hero-home-immersive__lead">{{ $seoDescription }}</p>
        </div>
      </section>

      <section class="layout-shell py-12">
        @if($posts->isNotEmpty())
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($posts as $post)
              <article class="featured-promo-card">
                @php
                  $cityLabel = $post->primary_city_name ?: 'Colombia';
                  $zoneLabel = $post->primary_zone_name;
                  $eventLabel = $post->eventTypes->pluck('name')->take(2)->join(' · ') ?: ($post->primary_event_type_name ?: 'Recurso');
                @endphp
                <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="featured-promo-media">
                  <img src="{{ $post->featured_image ? asset('storage/'.$post->featured_image) : asset('marketplace/assets/logo-wordmark.png') }}" alt="{{ $post->title }}" />
                  <span class="featured-promo-chip">{{ $cityLabel }}</span>
                  <span class="featured-promo-score">{{ optional($post->published_at)->format('d/m/Y') ?: optional($post->created_at)->format('d/m/Y') }}</span>
                </a>
                <div class="featured-promo-body">
                  <p class="featured-promo-kicker">{{ $eventLabel }}{{ $zoneLabel ? ' · '.$zoneLabel : '' }}</p>
                  <h2 class="featured-promo-title">{{ $post->title }}</h2>
                </div>
                <div class="featured-promo-footer">
                  <p class="featured-promo-meta">{{ $post->excerpt ?: 'Contenido editorial para posicionamiento SEO local y ayuda al cliente.' }}</p>
                  <div class="featured-promo-bottom">
                    <strong>Leer artículo</strong>
                    <a href="{{ route('blog.show', ['slug' => $post->slug]) }}">Ver más</a>
                  </div>
                </div>
              </article>
            @endforeach
          </div>

          <div class="mt-8">
            {{ $posts->links() }}
          </div>
        @else
          <div class="surface rounded-3xl p-8">
            <h2 class="text-2xl font-bold text-slate-900">Aún no hay publicaciones públicas</h2>
            <p class="mt-2 text-sm text-slate-600">Cuando el equipo publique artículos desde el panel admin, aparecerán aquí automáticamente.</p>
          </div>
        @endif
      </section>
    </main>

    <div data-component="site-footer"></div>
    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260310-02"></script>
  </body>
</html>
