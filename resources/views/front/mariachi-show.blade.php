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
    <link rel="stylesheet" href="assets/theme.css?v=20260310-05" />
    <script type="application/ld+json">{!! $schemaJson !!}</script>
  </head>
  <body data-page="listing" class="has-mobile-cta font-sans text-slate-900 antialiased">
    <div data-component="site-header"></div>

    @php
      $mainPhoto = $featuredPhoto;
      $mainPhotoUrl = $mainPhoto ? asset('storage/'.$mainPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
      $cityLandingUrl = route('seo.landing.slug', ['slug' => $citySlug]);
      $defaultCountrySlug = collect(config('seo.country_pages', ['colombia' => 'Colombia']))->keys()->first() ?: 'colombia';
      $marketplaceLandingUrl = route('seo.landing.slug', ['slug' => $defaultCountrySlug]);
      $serviceTypeNames = $profile->serviceTypes->pluck('name');
      $groupSizeNames = $profile->groupSizeOptions->pluck('name');
      $budgetNames = $profile->budgetRanges->pluck('name');
      $photoGalleryItems = collect();
      if ($mainPhoto) {
        $photoGalleryItems->push([
          'type' => 'image',
          'src' => $mainPhotoUrl,
          'title' => $mainPhoto->title ?: $h1,
        ]);
      }

      foreach ($secondaryPhotos as $photo) {
        $photoGalleryItems->push([
          'type' => 'image',
          'src' => asset('storage/'.$photo->path),
          'title' => $photo->title ?: $h1,
        ]);
      }

      if ($photoGalleryItems->isEmpty()) {
        $photoGalleryItems->push([
          'type' => 'image',
          'src' => $mainPhotoUrl,
          'title' => $h1,
        ]);
      }

      $videoGalleryItems = $youtubeEmbeds
        ->values()
        ->map(fn (string $url, int $index): array => [
          'type' => 'video',
          'src' => $url,
          'title' => 'Video '.($index + 1).' de '.$h1,
        ]);

      $galleryItems = $photoGalleryItems->concat($videoGalleryItems)->values();
      $basePriceLabel = $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Cotizar';
      $responsibleName = $profile->responsible_name ?: $profile->user?->display_name ?: $h1;
      $heroSummary = $profile->short_description ?: 'Servicio activo para serenatas, celebraciones y eventos privados.';
      $locationLabelOverrides = [
        'bogota' => 'Bogotá',
        'medellin' => 'Medellín',
        'barranquilla' => 'Barranquilla',
        'cartagena' => 'Cartagena',
        'bucaramanga' => 'Bucaramanga',
        'pereira' => 'Pereira',
        'manizales' => 'Manizales',
        'cali' => 'Cali',
        'colombia' => 'Colombia',
      ];
      $listingCityLabel = (string) \Illuminate\Support\Str::of($profile->city_name ?: 'Colombia')->squish();
      $listingCityLabel = $locationLabelOverrides[\Illuminate\Support\Str::slug($listingCityLabel)]
        ?? (string) \Illuminate\Support\Str::of(mb_strtolower($listingCityLabel))->title();
      $listingContextLabel = 'Mariachi en '.$listingCityLabel;
      $listingCategoryItems = $profile->eventTypes->pluck('name')->filter()->take(2)->values();
      if ($listingCategoryItems->isEmpty()) {
        $listingCategoryItems = $serviceTypeNames->filter()->take(2)->values();
      }
      if ($listingCategoryItems->isEmpty()) {
        $listingCategoryItems = collect(['Evento por cotizar']);
      }
      $listingRatingLabel = $reviewsTotal > 0 ? number_format($averageRating, 1) : 'Sin rating';
      $listingOpinionsLabel = $reviewsTotal === 1 ? '1 opinión' : $reviewsTotal.' opiniones';
      $listingMetaItems = collect([
        ['label' => $listingRatingLabel, 'kind' => 'rating'],
        ['label' => $listingOpinionsLabel, 'kind' => 'reviews'],
      ])
        ->merge($listingCategoryItems->map(fn (string $label): array => [
          'label' => $label,
          'kind' => 'category',
        ]))
        ->push([
          'label' => $listingCityLabel,
          'kind' => 'city',
        ])
        ->values();
      $heroGalleryItems = $photoGalleryItems->take(4)->values();
      $heroGalleryMainItem = $heroGalleryItems->first() ?: $galleryItems->first();
      $heroGallerySideItems = $heroGalleryItems->slice(1)->values();
      $heroGalleryVisibleCount = $heroGalleryItems->count();
      $hiddenGalleryItems = $galleryItems->slice($heroGalleryVisibleCount)->values();
      $heroGalleryMoreCount = max(0, $galleryItems->count() - $heroGalleryVisibleCount);

      $reviewVerificationMap = [
        'basic' => 'Opinion basica',
        'manual_validated' => 'Validada manualmente',
        'evidence_attached' => 'Con foto/prueba',
      ];
    @endphp

    <main>
      <section class="layout-shell py-6">
        <nav aria-label="Breadcrumb" class="listing-breadcrumbs" data-reveal>
          <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
              <a itemprop="item" href="{{ route('home') }}"><span itemprop="name">Inicio</span></a>
              <meta itemprop="position" content="1" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
              <a itemprop="item" href="{{ $marketplaceLandingUrl }}"><span itemprop="name">Mariachis</span></a>
              <meta itemprop="position" content="2" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
              <a itemprop="item" href="{{ $cityLandingUrl }}"><span itemprop="name">{{ $listingCityLabel }}</span></a>
              <meta itemprop="position" content="3" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
              <span itemprop="name">{{ $h1 }}</span>
              <meta itemprop="position" content="4" />
            </li>
          </ol>
        </nav>

        <header class="listing-shell-head" data-reveal>
          <div class="listing-shell-head__main">
            <p class="listing-shell-context">{{ $listingContextLabel }}</p>
            <div class="listing-shell-title-row">
              <h1 class="listing-shell-title">{{ $h1 }}</h1>

              <div class="listing-shell-actions" data-share-box>
                <button type="button" data-share-native class="listing-hero-action-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12v6.75m9-6.75V4.5m0 0L12 9m4.5-4.5L21 9M3 15.75V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-2.25" />
                  </svg>
                  <span>Compartir</span>
                </button>

                @if(auth()->user()?->role === \App\Models\User::ROLE_CLIENT)
                  @if($isFavorited)
                    <form action="{{ route('client.favorites.destroy', ['slug' => $profile->slug]) }}" method="POST">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="listing-hero-action-btn listing-hero-action-btn--favorite is-active" aria-label="Quitar de favoritos" aria-pressed="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                          <path d="M12.001 4.529a5.998 5.998 0 0 1 8.484 8.484l-7.778 7.779a1 1 0 0 1-1.414 0l-7.778-7.779a5.998 5.998 0 1 1 8.484-8.484Z"/>
                        </svg>
                        <span>Añadido a favoritos</span>
                      </button>
                    </form>
                  @else
                    <form action="{{ route('client.favorites.store', ['slug' => $profile->slug]) }}" method="POST">
                      @csrf
                      <button type="submit" class="listing-hero-action-btn listing-hero-action-btn--favorite" aria-label="Guardar en favoritos" aria-pressed="false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span>Agregar a favoritos</span>
                      </button>
                    </form>
                  @endif
                @else
                  <a href="{{ route('client.login') }}" class="listing-hero-action-btn listing-hero-action-btn--favorite">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span>Agregar a favoritos</span>
                  </a>
                @endif

                <p data-share-status class="listing-share-status hidden">Enlace copiado</p>
              </div>
            </div>

            <div class="listing-shell-meta" aria-label="Resumen del anuncio">
              @foreach($listingMetaItems as $metaItem)
                <span class="listing-shell-meta__item {{ $metaItem['kind'] === 'rating' ? 'listing-shell-meta__item--rating' : '' }}">
                  @if($metaItem['kind'] === 'rating')
                    <span class="listing-shell-meta__icon" aria-hidden="true">⭐</span>
                  @endif
                  <span>{{ $metaItem['label'] }}</span>
                </span>
              @endforeach
            </div>
          </div>
        </header>

        <div class="listing-page-grid mt-5 grid gap-6 md:grid-cols-12">
          <div class="listing-page-hero md:col-span-8">
            <section data-reveal>
              <article class="listing-showcase">
                <div class="listing-showcase__grid {{ $heroGallerySideItems->isEmpty() ? 'listing-showcase__grid--single' : '' }}">
                  <button
                    data-gallery-item
                    data-type="{{ $heroGalleryMainItem['type'] }}"
                    data-src="{{ $heroGalleryMainItem['src'] }}"
                    data-title="{{ $heroGalleryMainItem['title'] }}"
                    class="listing-showcase__main"
                    type="button"
                  >
                    <img src="{{ $heroGalleryMainItem['src'] }}" alt="{{ $heroGalleryMainItem['title'] }}" class="h-full w-full object-cover" />
                    <span class="listing-showcase__shade"></span>
                    <span class="listing-showcase__panel">
                      <span class="listing-showcase__meta">
                        <span>{{ $photoGalleryItems->count() }} foto(s)</span>
                        @if($videoGalleryItems->isNotEmpty())
                          <span>{{ $videoGalleryItems->count() }} video(s)</span>
                        @endif
                      </span>
                      <span class="listing-showcase__cta">Ver galería completa</span>
                    </span>
                  </button>

                  @if($heroGallerySideItems->isNotEmpty())
                    <div class="listing-showcase__rail" data-count="{{ $heroGallerySideItems->count() }}">
                      @foreach($heroGallerySideItems as $media)
                        <button
                          data-gallery-item
                          data-type="{{ $media['type'] }}"
                          data-src="{{ $media['src'] }}"
                          data-title="{{ $media['title'] }}"
                          class="listing-showcase__thumb"
                          type="button"
                        >
                          <img src="{{ $media['src'] }}" alt="{{ $media['title'] }}" class="h-full w-full object-cover" />
                          <span class="listing-showcase__shade"></span>
                          <span class="listing-showcase__caption">
                            @if($loop->last && $heroGalleryMoreCount > 0)
                              +{{ $heroGalleryMoreCount }} más
                            @else
                              {{ $media['type'] === 'video' ? 'Ver video' : 'Ver foto' }}
                            @endif
                          </span>
                        </button>
                      @endforeach
                    </div>
                  @endif
                </div>

                <div class="listing-stage-sources" aria-hidden="true">
                  @foreach($hiddenGalleryItems as $galleryItem)
                    <button
                      data-gallery-item
                      data-type="{{ $galleryItem['type'] }}"
                      data-src="{{ $galleryItem['src'] }}"
                      data-title="{{ $galleryItem['title'] }}"
                      type="button"
                      tabindex="-1"
                      class="hidden"
                    ></button>
                  @endforeach
                </div>
              </article>
            </section>
          </div>

          <aside class="listing-page-aside md:col-span-4 md:row-span-2 md:row-start-1" data-reveal>
            <div class="listing-right-rail">
              <article class="listing-budget-card listing-budget-card--premium">
                <div class="listing-budget-head">
                  <div class="listing-budget-lead">
                    <div class="listing-budget-lead__copy">
                      <h2 class="listing-budget-title">{{ $basePriceLabel }}</h2>
                      <p class="listing-budget-summary">{{ $profile->base_price ? 'Precio desde' : 'Cotización personalizada' }}</p>
                    </div>
                    <img src="{{ $mainPhotoUrl }}" alt="{{ $h1 }}" class="listing-budget-avatar" />
                  </div>

                  <div class="listing-budget-contact">
                    <div class="min-w-0 flex-1">
                      <p class="listing-budget-contact-name">{{ $responsibleName }}</p>
                      <p class="listing-budget-contact-copy">{{ $heroSummary }}</p>
                    </div>
                  </div>
                </div>

                @if($errors->has('quote') || $errors->has('event_notes'))
                  <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {{ $errors->first('quote') ?: $errors->first('event_notes') }}
                  </div>
                @endif

                <div class="listing-budget-cta-stack mt-4">
                  @if($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="listing-budget-btn listing-budget-btn--whatsapp">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 8.01 0C3.673 0 .145 3.528.145 7.864c0 1.386.362 2.74 1.05 3.936L0 16l4.32-1.133a7.82 7.82 0 0 0 3.69.94h.003c4.336 0 7.864-3.528 7.864-7.864a7.8 7.8 0 0 0-2.276-5.617Zm-5.59 12.18h-.002a6.5 6.5 0 0 1-3.314-.908l-.237-.14-2.564.673.685-2.502-.155-.257a6.52 6.52 0 0 1-1.002-3.482c0-3.6 2.93-6.53 6.53-6.53 1.744 0 3.385.678 4.618 1.911A6.48 6.48 0 0 1 14.5 7.89c0 3.6-2.93 6.53-6.49 6.53Zm3.58-4.89c-.196-.098-1.16-.573-1.34-.638-.18-.065-.311-.098-.442.098-.13.196-.507.638-.622.769-.114.13-.229.147-.425.049-.196-.098-.828-.305-1.577-.973-.582-.52-.975-1.162-1.09-1.358-.114-.196-.012-.302.086-.4.09-.09.196-.229.294-.344.098-.114.13-.196.196-.327.065-.13.033-.245-.016-.344-.049-.098-.442-1.064-.605-1.456-.159-.381-.32-.33-.442-.336a7.63 7.63 0 0 0-.377-.007.72.72 0 0 0-.523.245c-.18.196-.687.67-.687 1.635s.703 1.897.801 2.028c.098.13 1.385 2.114 3.356 2.964.469.202.836.323 1.122.413.472.15.902.129 1.242.078.379-.056 1.16-.474 1.324-.932.163-.458.163-.85.114-.932-.049-.082-.18-.131-.376-.229Z"/>
                      </svg>
                      <span>WhatsApp</span>
                    </a>
                  @else
                    <span class="listing-budget-btn listing-budget-btn--whatsapp is-disabled">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 8.01 0C3.673 0 .145 3.528.145 7.864c0 1.386.362 2.74 1.05 3.936L0 16l4.32-1.133a7.82 7.82 0 0 0 3.69.94h.003c4.336 0 7.864-3.528 7.864-7.864a7.8 7.8 0 0 0-2.276-5.617Zm-5.59 12.18h-.002a6.5 6.5 0 0 1-3.314-.908l-.237-.14-2.564.673.685-2.502-.155-.257a6.52 6.52 0 0 1-1.002-3.482c0-3.6 2.93-6.53 6.53-6.53 1.744 0 3.385.678 4.618 1.911A6.48 6.48 0 0 1 14.5 7.89c0 3.6-2.93 6.53-6.49 6.53Zm3.58-4.89c-.196-.098-1.16-.573-1.34-.638-.18-.065-.311-.098-.442.098-.13.196-.507.638-.622.769-.114.13-.229.147-.425.049-.196-.098-.828-.305-1.577-.973-.582-.52-.975-1.162-1.09-1.358-.114-.196-.012-.302.086-.4.09-.09.196-.229.294-.344.098-.114.13-.196.196-.327.065-.13.033-.245-.016-.344-.049-.098-.442-1.064-.605-1.456-.159-.381-.32-.33-.442-.336a7.63 7.63 0 0 0-.377-.007.72.72 0 0 0-.523.245c-.18.196-.687.67-.687 1.635s.703 1.897.801 2.028c.098.13 1.385 2.114 3.356 2.964.469.202.836.323 1.122.413.472.15.902.129 1.242.078.379-.056 1.16-.474 1.324-.932.163-.458.163-.85.114-.932-.049-.082-.18-.131-.376-.229Z"/>
                      </svg>
                      <span>WhatsApp</span>
                    </span>
                  @endif

                  @if(auth()->user()?->role === \App\Models\User::ROLE_CLIENT)
                    <a href="#solicitar" class="listing-budget-btn listing-budget-btn--quote">Solicitud de presupuesto</a>
                  @elseif(auth()->check())
                    <span class="listing-budget-btn listing-budget-btn--quote is-disabled">Solicitud de presupuesto</span>
                  @else
                    <a href="{{ route('client.login') }}" class="listing-budget-btn listing-budget-btn--quote">Solicitud de presupuesto</a>
                  @endif

                  @if($phoneUrl)
                    <a href="{{ $phoneUrl }}" class="listing-budget-btn listing-budget-btn--secondary" aria-label="Llamar al mariachi">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5A2.25 2.25 0 0 1 4.5 2.25h3A2.25 2.25 0 0 1 9.75 4.5v2.04a2.25 2.25 0 0 1-.659 1.591l-1.2 1.2a16.45 16.45 0 0 0 6.774 6.774l1.2-1.2a2.25 2.25 0 0 1 1.591-.659H19.5a2.25 2.25 0 0 1 2.25 2.25v3A2.25 2.25 0 0 1 19.5 21.75h-.75C9.775 21.75 2.25 14.225 2.25 5.25V4.5Z" />
                      </svg>
                      <span>Llamar</span>
                    </a>
                  @else
                    <span class="listing-budget-btn listing-budget-btn--secondary is-disabled" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5A2.25 2.25 0 0 1 4.5 2.25h3A2.25 2.25 0 0 1 9.75 4.5v2.04a2.25 2.25 0 0 1-.659 1.591l-1.2 1.2a16.45 16.45 0 0 0 6.774 6.774l1.2-1.2a2.25 2.25 0 0 1 1.591-.659H19.5a2.25 2.25 0 0 1 2.25 2.25v3A2.25 2.25 0 0 1 19.5 21.75h-.75C9.775 21.75 2.25 14.225 2.25 5.25V4.5Z" />
                      </svg>
                      <span>Llamar</span>
                    </span>
                  @endif
                </div>

                @if(auth()->user()?->role === \App\Models\User::ROLE_CLIENT)
                  <form id="solicitar" action="{{ route('quote.request.store', ['slug' => $profile->slug]) }}" method="POST" class="listing-budget-form">
                    @csrf
                    <div>
                      <p class="listing-budget-form-title">Cuéntanos tu evento</p>
                      <p class="listing-budget-form-copy">Recibe una respuesta más clara si incluyes fecha, ciudad y formato del servicio.</p>
                    </div>
                    <label class="listing-budget-field">
                      <span>Teléfono de contacto</span>
                      <input type="text" name="contact_phone" value="{{ old('contact_phone', $quoteDefaults['contact_phone']) }}" placeholder="300 123 4567" />
                    </label>
                    <label class="listing-budget-field">
                      <span>Fecha del evento</span>
                      <input type="date" name="event_date" value="{{ old('event_date') }}" />
                    </label>
                    <label class="listing-budget-field">
                      <span>Ciudad del evento</span>
                      <input type="text" name="event_city" value="{{ old('event_city', $quoteDefaults['event_city']) }}" placeholder="Bogotá" />
                    </label>
                    <label class="listing-budget-field">
                      <span>Detalles</span>
                      <textarea name="event_notes" rows="4" placeholder="Cuéntanos fecha, tipo de evento y lo que necesitas..." required>{{ old('event_notes') }}</textarea>
                    </label>
                    <button type="submit" class="listing-budget-btn listing-budget-btn--quote">Enviar solicitud</button>
                  </form>
                @elseif(auth()->check())
                  <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Para solicitar presupuesto desde esta vista, usa una cuenta de cliente.
                  </div>
                @endif

              </article>
            </div>
          </aside>

          <div class="listing-page-content space-y-6 md:col-span-8">
            <div class="listing-anchor-shell" data-reveal data-listing-anchor-shell>
              <nav class="listing-anchor-nav" data-listing-anchor-nav>
                <a href="#info" class="is-active">Informacion</a>
                <a href="#detalles">Detalles</a>
                <a href="#opiniones">Opiniones</a>
                <a href="#mapa">Mapa</a>
                <a href="#faqs">FAQs</a>
              </nav>
            </div>

            <section id="info" class="listing-flow-section" data-reveal>
              <h2>Informacion</h2>

              <div data-readmore class="listing-readmore">
                <div data-readmore-content class="listing-readmore-content text-sm leading-relaxed text-slate-700">
                  <p>{{ $profile->short_description ?: 'Este mariachi tiene su perfil activo y disponible para cotizaciones.' }}</p>

                  <p class="mt-3">{{ $profile->full_description ?: 'La descripcion completa estara disponible cuando el equipo termine de cargar toda la informacion del anuncio.' }}</p>

                  <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                      <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Tipos de evento</p>
                      @if($profile->eventTypes->isNotEmpty())
                        <ul class="mt-2 space-y-1.5">
                          @foreach($profile->eventTypes as $eventType)
                            <li>• {{ $eventType->name }}</li>
                          @endforeach
                        </ul>
                      @else
                        <p class="mt-2 text-sm text-slate-500">Se agregaran pronto los tipos de evento.</p>
                      @endif
                    </div>

                    <div>
                      <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Cobertura</p>
                      @if($coverageAreas->isNotEmpty())
                        <ul class="mt-2 space-y-1.5">
                          @foreach($coverageAreas->take(5) as $area)
                            <li>• {{ $area }}</li>
                          @endforeach
                        </ul>
                      @else
                        <p class="mt-2 text-sm text-slate-500">Por ahora trabaja principalmente en {{ $profile->city_name ?: 'su ciudad principal' }}.</p>
                      @endif
                    </div>
                  </div>
                </div>

                <button type="button" data-readmore-toggle class="listing-readmore-toggle">Leer mas</button>
              </div>
            </section>

            <section id="detalles" class="listing-flow-section" data-reveal>
              <h2>Detalles del anuncio</h2>

              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <article class="listing-opinion-card">
                  <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Tipo de servicio</p>
                  <p class="mt-2 text-sm text-slate-700">{{ $serviceTypeNames->isNotEmpty() ? $serviceTypeNames->join(', ') : 'Sin especificar por ahora' }}</p>
                </article>
                <article class="listing-opinion-card">
                  <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Tamano del grupo</p>
                  <p class="mt-2 text-sm text-slate-700">{{ $groupSizeNames->isNotEmpty() ? $groupSizeNames->join(', ') : 'Sin especificar por ahora' }}</p>
                </article>
                <article class="listing-opinion-card">
                  <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Rango de presupuesto</p>
                  <p class="mt-2 text-sm text-slate-700">{{ $budgetNames->isNotEmpty() ? $budgetNames->join(', ') : 'Sin especificar por ahora' }}</p>
                </article>
                <article class="listing-opinion-card">
                  <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Precio desde</p>
                  <p class="mt-2 text-sm text-slate-700">{{ $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Se cotiza segun el evento' }}</p>
                </article>
              </div>

              @if($socialLinks->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                  @foreach($socialLinks as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">{{ $link['label'] }}</a>
                  @endforeach
                </div>
              @endif
            </section>

            <section id="opiniones" class="listing-flow-section" data-reveal>
              <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                  <h2>Opiniones</h2>
                  <p class="mt-1 text-sm text-slate-600">Experiencias reales de clientes que conversaron con este mariachi.</p>
                </div>
                @if($reviewsTotal > 0)
                  <p class="text-sm font-semibold text-slate-700">{{ number_format($averageRating, 1) }} / 5 · {{ $reviewsTotal }} reseña(s)</p>
                @endif
              </div>

              @if($reviewsTotal === 0)
                <article class="listing-opinion-empty mt-4">
                  <p class="text-sm font-bold text-slate-900">Este anuncio aun no tiene opiniones publicas</p>
                  <p class="mt-1 text-sm text-slate-600">Cuando una reseña sea aprobada por moderacion, aparecera aqui.</p>
                </article>
              @else
                <div class="mt-4 grid gap-4 lg:grid-cols-12">
                  <aside class="lg:col-span-4 space-y-4">
                    <article class="listing-opinion-card">
                      <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Resumen de puntuacion</p>
                      <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($averageRating, 1) }}</p>
                      <p class="text-sm text-slate-500">Basado en {{ $reviewsTotal }} reseña(s)</p>
                      <div class="mt-3 space-y-2">
                        @foreach([5, 4, 3, 2, 1] as $rating)
                          @php
                            $count = (int) ($ratingDistribution[$rating] ?? 0);
                            $percentage = $reviewsTotal > 0 ? round(($count / $reviewsTotal) * 100) : 0;
                          @endphp
                          <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span class="w-14 font-semibold">{{ $rating }} estrellas</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                              <div class="h-full rounded-full bg-amber-400" style="width: {{ $percentage }}%;"></div>
                            </div>
                            <span class="w-7 text-right font-semibold">{{ $count }}</span>
                          </div>
                        @endforeach
                      </div>
                    </article>

                    @if($reviewPhotoGallery->isNotEmpty())
                      <article class="listing-opinion-card">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Fotos adjuntas</p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                          @foreach($reviewPhotoGallery->take(9) as $photo)
                            <a href="{{ $photo['src'] }}" target="_blank" rel="noopener noreferrer" class="overflow-hidden rounded-lg border border-slate-200">
                              <img src="{{ $photo['src'] }}" alt="Foto de reseña" class="h-20 w-full object-cover" />
                            </a>
                          @endforeach
                        </div>
                      </article>
                    @endif
                  </aside>

                  <div class="lg:col-span-8 space-y-3">
                    @foreach($publicReviews as $review)
                      @php
                        $reviewClientName = $review->clientUser?->display_name ?: 'Cliente';
                        $verificationLabel = $reviewVerificationMap[$review->verification_status] ?? $review->verification_status;
                      @endphp
                      <article class="listing-opinion-card">
                        <div class="listing-opinion-head">
                          <div>
                            <p class="text-sm font-extrabold text-slate-900">{{ $reviewClientName }}</p>
                            <p class="text-xs text-slate-500">{{ $review->created_at->format('Y-m-d') }}</p>
                          </div>
                          <div class="text-right">
                            <p class="text-sm font-black text-amber-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                            <p class="text-[11px] font-semibold text-slate-500">{{ $verificationLabel }}</p>
                          </div>
                        </div>

                        @if($review->title)
                          <p class="mt-2 text-sm font-bold text-slate-800">{{ $review->title }}</p>
                        @endif
                        <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $review->comment }}</p>

                        @if($review->event_type || $review->event_date)
                          <p class="mt-2 text-xs font-semibold text-slate-500">
                            {{ $review->event_type ?: 'Evento sin tipo' }}
                            @if($review->event_date)
                              · {{ $review->event_date->format('Y-m-d') }}
                            @endif
                          </p>
                        @endif

                        @if($review->photos->isNotEmpty())
                          <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach($review->photos as $photo)
                              <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" rel="noopener noreferrer" class="overflow-hidden rounded-lg border border-slate-200">
                                <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto de reseña" class="h-20 w-full object-cover" />
                              </a>
                            @endforeach
                          </div>
                        @endif

                        @if($review->mariachi_reply && $review->mariachi_reply_visible)
                          <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Respuesta del mariachi</p>
                            <p class="mt-2 text-sm text-slate-700">{{ $review->mariachi_reply }}</p>
                          </div>
                        @endif
                      </article>
                    @endforeach
                  </div>
                </div>
              @endif
            </section>

            <section class="listing-suggest listing-flow-section" data-reveal>
              <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2>Mariachis que podrian gustarte</h2>
                  <p class="mt-1 text-sm text-slate-600">Descubre otros grupos activos en {{ $profile->city_name ?: 'esta ciudad' }}.</p>
                </div>
                <a href="{{ $cityLandingUrl }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Ver todos</a>
              </div>

              @if($relatedProfiles->isNotEmpty())
                <div class="featured-carousel-shell mt-4">
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--left" data-featured-scroll="left" aria-label="Desplazar sugerencias a la izquierda">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--right" data-featured-scroll="right" aria-label="Desplazar sugerencias a la derecha">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <div data-featured-carousel class="featured-carousel-track">
                    @foreach($relatedProfiles as $related)
                      @php
                        $relatedPhoto = $related->photos->firstWhere('is_featured', true) ?? $related->photos->first();
                        $relatedPhotoUrl = $relatedPhoto ? asset('storage/'.$relatedPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
                        $relatedName = $related->business_name ?: $related->user?->display_name;
                      @endphp
                      <article class="featured-promo-card">
                        <a class="featured-promo-media" href="{{ route('mariachi.public.show', ['slug' => $related->slug]) }}">
                          <img src="{{ $relatedPhotoUrl }}" alt="{{ $relatedName }}" />
                          <span class="featured-promo-chip">{{ $related->city_name }}</span>
                          <span class="featured-promo-score">{{ $related->profile_completion }}%</span>
                        </a>
                        <div class="featured-promo-body">
                          <p class="featured-promo-kicker">{{ $related->eventTypes->pluck('name')->take(2)->join(' · ') ?: 'Disponible para eventos' }}</p>
                          <h3 class="featured-promo-title">{{ $related->short_description ?: 'Perfil verificado y listo para cotizar.' }}</h3>
                        </div>
                        <div class="featured-promo-footer">
                          <p class="featured-promo-artist">{{ $relatedName }}</p>
                          <div class="featured-promo-bottom">
                            <strong>{{ $related->base_price ? 'Desde $'.number_format((float) $related->base_price, 0, ',', '.') : 'Cotizacion directa' }}</strong>
                            <a href="{{ route('mariachi.public.show', ['slug' => $related->slug]) }}">Ver anuncio</a>
                          </div>
                        </div>
                      </article>
                    @endforeach
                  </div>
                </div>
              @else
                <article class="listing-opinion-empty mt-4">
                  <p class="text-sm font-bold text-slate-900">Pronto veras mas opciones en esta ciudad</p>
                  <p class="mt-1 text-sm text-slate-600">Cuando se publiquen nuevos perfiles, apareceran aqui automaticamente.</p>
                </article>
              @endif
            </section>

            <section id="mapa" class="listing-flow-section" data-reveal>
              <h2>Ubicacion</h2>
              <div class="listing-map-shell mt-4">
                <iframe title="Ubicacion del mariachi" loading="lazy" src="{{ $mapEmbedUrl }}"></iframe>
              </div>
              <p class="mt-3 text-sm text-slate-600">
                {{ $profile->address ? 'Referencia: '.$profile->address.'. ' : '' }}
                Cobertura principal en {{ $profile->city_name ?: 'su ciudad principal' }}.
              </p>
            </section>

            <section id="faqs" class="listing-flow-section" data-reveal>
              <h2>Preguntas frecuentes</h2>

              <div data-accordion class="mt-4 space-y-3">
                @if($profile->faqs->isNotEmpty())
                  @foreach($profile->faqs->where('is_visible', true)->values() as $index => $faq)
                    <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200">
                      <button data-accordion-trigger aria-expanded="false" aria-controls="faq-listing-{{ $index + 1 }}" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                        {{ $faq->question }}
                        <span data-accordion-icon>+</span>
                      </button>
                      <div id="faq-listing-{{ $index + 1 }}" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                        {{ $faq->answer }}
                      </div>
                    </div>
                  @endforeach
                @else
                  <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200">
                    <button data-accordion-trigger aria-expanded="false" aria-controls="faq-listing-1" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                      Cuanto cuesta una serenata con {{ $h1 }}?
                      <span data-accordion-icon>+</span>
                    </button>
                    <div id="faq-listing-1" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                      {{ $profile->base_price ? 'El precio base inicia desde $'.number_format((float) $profile->base_price, 0, ',', '.').' COP.' : 'El valor se define segun fecha, ciudad y tipo de evento.' }}
                    </div>
                  </div>

                  <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200">
                    <button data-accordion-trigger aria-expanded="false" aria-controls="faq-listing-2" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                      Que zonas cubre este mariachi?
                      <span data-accordion-icon>+</span>
                    </button>
                    <div id="faq-listing-2" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                      @if($coverageAreas->isNotEmpty())
                        {{ $coverageAreas->join(', ') }}.
                      @else
                        Actualmente opera en {{ $profile->city_name ?: 'su ciudad principal' }}.
                      @endif
                    </div>
                  </div>

                  <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200">
                    <button data-accordion-trigger aria-expanded="false" aria-controls="faq-listing-3" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                      Como puedo solicitar una cotizacion?
                      <span data-accordion-icon>+</span>
                    </button>
                    <div id="faq-listing-3" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                      @if($whatsappUrl || $phoneUrl)
                        Puedes contactar por los botones de esta ficha para validar disponibilidad o usar el formulario de cotizacion.
                      @else
                        Usa el formulario de solicitud de presupuesto para contactar al mariachi segun su plan actual.
                      @endif
                    </div>
                  </div>
                @endif
              </div>
            </section>
          </div>
        </div>
      </section>
    </main>

    <div class="mobile-cta md:hidden">
      @if(auth()->user()?->role === \App\Models\User::ROLE_CLIENT)
        <a href="#solicitar" class="mobile-cta-btn mobile-cta-btn--wa">Cotizar</a>
      @else
        <a href="{{ route('client.login') }}" class="mobile-cta-btn mobile-cta-btn--wa">Cotizar</a>
      @endif
      @if($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mobile-cta-btn">WhatsApp</a>
      @else
        <span class="mobile-cta-btn opacity-70">WhatsApp</span>
      @endif
      @if($phoneUrl)
        <a href="{{ $phoneUrl }}" class="mobile-cta-btn">Llamar</a>
      @else
        <span class="mobile-cta-btn opacity-70">Llamar</span>
      @endif
    </div>

    <div data-component="site-footer"></div>

    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260310-03"></script>
  </body>
</html>
