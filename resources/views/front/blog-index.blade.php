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
    <link rel="stylesheet" href="assets/theme.css?v=20260311-brand-green-v1" />
  </head>
  <body data-page="home" class="font-sans text-slate-900 antialiased">
    @php
      $heroPrimary = $heroPosts->first();
    @endphp

    <div data-component="site-header"></div>

    <main>
      <section class="hero-split-shell hero-split-shell--flush hero-split-shell--editorial">
        <div class="hero-split-grid hero-split-grid--editorial {{ $heroPrimary ? '' : 'hero-split-grid--solo' }}">
          <div class="hero-split-left hero-split-left--editorial">
            <span class="hero-blog-kicker">Comunidad editorial</span>
            <h1 class="hero-blog-title">{{ $h1 }}</h1>
          </div>

          @if($heroPrimary)
            @php
              $featuredCategory = $heroPrimary->eventTypes->pluck('name')->filter()->first()
                ?: ($heroPrimary->primary_event_type_name ?: ($heroPrimary->primary_city_name ?: 'Artículo destacado'));
              $primaryImage = $heroPrimary->featured_image
                ? asset('storage/'.$heroPrimary->featured_image)
                : asset('marketplace/assets/logo-wordmark.png');
            @endphp

            <div class="hero-split-right hero-split-right--editorial">
              <article class="hero-blog-stage">
                <img src="{{ $primaryImage }}" alt="{{ $heroPrimary->title }}" class="hero-blog-stage__image" />
                <div class="hero-blog-stage__veil" aria-hidden="true"></div>

                <a
                  href="{{ route('blog.show', ['slug' => $heroPrimary->slug]) }}"
                  class="hero-blog-stage__spotlight"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <span class="hero-blog-stage__spotlight-kicker">Bloque destacado</span>
                  <strong>{{ $featuredCategory }}</strong>
                </a>
              </article>
            </div>
          @endif
        </div>
      </section>

      <section id="articulos" class="layout-shell py-12">
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
    <script src="js/ui.js?v=20260311-brand-green-v1"></script>
  </body>
</html>
