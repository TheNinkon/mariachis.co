<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}" />
    <base href="{{ asset('marketplace') }}/" />
    <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/theme.css?v=20260311-public-collections-v1" />
  </head>
  <body data-page="public-collection" class="font-sans text-slate-900 antialiased">
    <div data-component="site-header"></div>

    <main class="public-collection-page">
      <section class="layout-shell py-8 md:py-12">
        <div
          class="public-collection-hero"
          data-public-collection
          data-collection-kind="{{ $collectionType }}"
          data-resolve-url="{{ $resolveUrl }}"
          data-empty-title="{{ $emptyTitle }}"
          data-empty-body="{{ $emptyBody }}"
          data-empty-cta-label="{{ $emptyCtaLabel }}"
          data-empty-cta-url="{{ $emptyCtaUrl }}"
        >
          <div class="public-collection-copy">
            <p class="public-collection-eyebrow">{{ $eyebrow }}</p>
            <h1 class="public-collection-title">{{ $headline }}</h1>
            <p class="public-collection-intro">{{ $intro }}</p>
          </div>
          <div class="public-collection-toolbar">
            <div class="public-collection-toolbar__meta">
              <span class="public-collection-pill" data-public-collection-count>0 anuncios</span>
              <span class="public-collection-note">Disponible sin iniciar sesión en este navegador.</span>
            </div>
            <button type="button" class="public-collection-clear hidden" data-public-collection-clear>{{ $clearLabel }}</button>
          </div>
          <div class="public-collection-grid" data-public-collection-grid></div>
          <div class="public-collection-empty hidden" data-public-collection-empty>
            <h2 data-public-collection-empty-title>{{ $emptyTitle }}</h2>
            <p data-public-collection-empty-body>{{ $emptyBody }}</p>
            <a href="{{ $emptyCtaUrl }}" class="public-collection-empty__cta" data-public-collection-empty-cta>{{ $emptyCtaLabel }}</a>
          </div>
        </div>
      </section>
    </main>

    <div data-component="site-footer"></div>

    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260311-brand-green-v2"></script>
    <script src="js/public-listing-collections.js?v=20260311-listing-collections-v1"></script>
  </body>
</html>
