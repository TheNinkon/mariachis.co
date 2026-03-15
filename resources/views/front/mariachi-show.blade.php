<!DOCTYPE html>
<html lang="es">
  <head>
    @php
      $seo = $seo ?? app(\App\Services\Seo\SeoResolver::class)->resolve(request(), 'listing', [
        'title' => $seoTitle ?? '',
        'description' => $seoDescription ?? '',
        'jsonld' => $schemaJson ?? null,
        'og_image' => isset($featuredPhoto) && $featuredPhoto ? $featuredPhoto->path : null,
      ]);
    @endphp
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @include('front.partials.seo-meta', ['seo' => $seo])
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <base href="{{ asset('marketplace') }}/" />
    <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/theme.css?v=20260315-listing-v22" />
  </head>
  <body data-page="listing" class="has-mobile-cta font-sans text-slate-900 antialiased">
    <div data-component="site-header"></div>

    @php
      $resolveVideoThumb = static function (string $url): ?string {
        if (preg_match('/embed\/([^?&"\'#\/]+)/', $url, $matches) === 1 && ! empty($matches[1])) {
          return 'https://i.ytimg.com/vi/'.$matches[1].'/hqdefault.jpg';
        }

        return null;
      };
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
          'thumb' => $mainPhotoUrl,
          'title' => $mainPhoto->title ?: $h1,
        ]);
      }

      foreach ($secondaryPhotos as $photo) {
        $photoGalleryItems->push([
          'type' => 'image',
          'src' => asset('storage/'.$photo->path),
          'thumb' => asset('storage/'.$photo->path),
          'title' => $photo->title ?: $h1,
        ]);
      }

      if ($photoGalleryItems->isEmpty()) {
        $photoGalleryItems->push([
          'type' => 'image',
          'src' => $mainPhotoUrl,
          'thumb' => $mainPhotoUrl,
          'title' => $h1,
        ]);
      }

      $videoGalleryItems = $youtubeEmbeds
        ->values()
        ->map(fn (string $url, int $index): array => [
          'type' => 'video',
          'src' => $url,
          'thumb' => $resolveVideoThumb($url),
          'title' => 'Video '.($index + 1).' de '.$h1,
        ]);

      $galleryItems = $photoGalleryItems
        ->concat($videoGalleryItems)
        ->filter()
        ->unique(fn (array $item): string => $item['type'].'|'.$item['src'])
        ->values();
      $heroGalleryItems = $galleryItems->take(4)->values();
      $hasGalleryOverflow = $galleryItems->count() > $heroGalleryItems->count();
      $heroRailItems = $heroGalleryItems->slice(1)->take(3)->values();
      $heroRailOverflowPreview = $hasGalleryOverflow
        ? ($galleryItems->get($heroGalleryItems->count()) ?? $heroGalleryItems->last())
        : null;
      $primaryGalleryItem = $heroGalleryItems->first();
      $galleryPhotosCount = $photoGalleryItems->count();
      $galleryVideosCount = $videoGalleryItems->count();
      $basePriceLabel = $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Cotizar';
      $providerProfile = $profile->mariachiProfile;
      $responsibleName = $profile->responsible_name ?: $profile->user?->display_name ?: $h1;
      $heroSummary = $profile->short_description ?: 'Servicio activo para serenatas, celebraciones y eventos privados.';
      $providerPublicProfileUrl = $providerProfile?->slug
        ? route('mariachi.provider.public.show', ['handle' => $providerProfile->slug])
        : null;
      $verifiedProfileLabel = $isVerifiedProfile ? 'Perfil verificado' : null;
      $shortDescriptionNormalized = (string) \Illuminate\Support\Str::of($profile->short_description ?: '')->squish();
      $descriptionParagraphs = collect(preg_split('/\r\n|\r|\n/', (string) ($profile->full_description ?: '')))
        ->map(fn ($paragraph): string => (string) \Illuminate\Support\Str::of((string) $paragraph)->squish())
        ->filter()
        ->reject(fn (string $paragraph): bool => $shortDescriptionNormalized !== '' && mb_strtolower($paragraph) === mb_strtolower($shortDescriptionNormalized))
        ->values();
      if ($descriptionParagraphs->isEmpty()) {
        $descriptionParagraphs = collect([
          'Este mariachi tiene su perfil activo y disponible para cotizaciones y eventos en su ciudad principal.',
        ]);
      }
      $coveragePreview = $coverageAreas->take(5)->values();
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
      $favoriteKey = 'listing-'.$profile->id;
      $favoriteStoreUrl = auth()->user()?->role === \App\Models\User::ROLE_CLIENT
        ? route('client.favorites.store', ['slug' => $profile->slug])
        : null;
      $favoriteDestroyUrl = auth()->user()?->role === \App\Models\User::ROLE_CLIENT
        ? route('client.favorites.destroy', ['slug' => $profile->slug])
        : null;
      $shareEmailSubject = 'Mira este mariachi en Mariachis.co';
      $shareEmailBody = 'He encontrado este mariachi en Mariachis.co y he pensado que te podria interesar: '.$h1."\r\n\r\n".'Echale un vistazo: '.url()->current();
      $shareEmailHref = 'mailto:?subject='.rawurlencode($shareEmailSubject).'&body='.rawurlencode($shareEmailBody);
      $currentListingPayload = [
        'id' => $profile->id,
        'slug' => $profile->slug,
        'city' => $profile->city_name ?: 'Colombia',
        'title' => $h1,
        'image_url' => $mainPhotoUrl,
        'price_label' => $basePriceLabel,
      ];

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
            <div class="listing-shell-title-row">
              <h1 class="listing-shell-title">{{ $h1 }}</h1>
              @if($verifiedProfileLabel)
                <span class="listing-verified-badge" aria-label="{{ $verifiedProfileLabel }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 2.25 2.25L15 9.75" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6l.75 2.25 2.25.75v5.16c0 3.124-1.697 6-4.43 7.502L12 20.25l-1.57-.828A8.486 8.486 0 0 1 6 11.91V6.75L8.25 6 9 3.75Z" />
                  </svg>
                  <span>{{ $verifiedProfileLabel }}</span>
                </span>
              @endif
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
              <article class="listing-showcase listing-showcase--viator" data-gallery-experience="viator">
                <script type="application/json" data-gallery-slides>@json($galleryItems->values()->all())</script>
                <div class="listing-showcase__grid listing-showcase__grid--viator {{ $heroGalleryItems->count() === 1 ? 'listing-showcase__grid--single' : '' }}" data-gallery-hero-count="{{ $heroGalleryItems->count() }}">
                  @if($heroRailItems->isNotEmpty() || $hasGalleryOverflow)
                    <div class="listing-showcase__rail listing-showcase__rail--viator" data-listing-gallery-rail data-count="{{ $heroRailItems->count() + ($hasGalleryOverflow ? 1 : 0) }}" aria-label="Miniaturas del anuncio">
                      @foreach($heroRailItems as $index => $media)
                        <button
                          data-gallery-item
                          data-gallery-index="{{ $index + 1 }}"
                          data-type="{{ $media['type'] }}"
                          data-src="{{ $media['src'] }}"
                          data-thumb="{{ $media['thumb'] }}"
                          data-title="{{ $media['title'] }}"
                          class="listing-showcase__thumb listing-showcase__thumb--viator"
                          type="button"
                          aria-label="{{ $media['type'] === 'video' ? 'Ver video' : 'Ver foto' }} {{ $index + 2 }}"
                        >
                          @if($media['thumb'])
                            <img src="{{ $media['thumb'] }}" alt="{{ $media['title'] }}" loading="lazy" class="h-full w-full object-cover" />
                          @else
                            <span class="listing-showcase__thumb-fallback">{{ $media['type'] === 'video' ? 'Video' : 'Foto' }}</span>
                          @endif
                          <span class="listing-showcase__shade"></span>
                          @if($media['type'] === 'video')
                            <span class="listing-showcase__badge">Video</span>
                          @endif
                        </button>
                      @endforeach

                      @if($hasGalleryOverflow && $heroRailOverflowPreview)
                        <button
                          type="button"
                          class="listing-showcase__thumb listing-showcase__thumb--viator listing-showcase__thumb--more"
                          data-open-gallery-overflow
                          aria-label="Ver mas fotos y videos"
                        >
                          @if($heroRailOverflowPreview['thumb'])
                            <img src="{{ $heroRailOverflowPreview['thumb'] }}" alt="Ver mas del anuncio" loading="lazy" class="h-full w-full object-cover" />
                          @else
                            <span class="listing-showcase__thumb-fallback">Ver mas</span>
                          @endif
                          <span class="listing-showcase__shade"></span>
                          <span class="listing-showcase__more-badge">+{{ $galleryItems->count() - $heroGalleryItems->count() }}</span>
                          <span class="listing-showcase__more-label">Ver más</span>
                        </button>
                      @endif
                    </div>
                  @endif

                  <div class="listing-showcase__viewer">
                    <div class="listing-showcase__frame" data-gallery-inline-stage>
                      <div class="listing-showcase__stage-media" data-gallery-inline-media>
                        @if($primaryGalleryItem && $primaryGalleryItem['type'] === 'video')
                          <div class="listing-showcase__video">
                            <iframe src="{{ $primaryGalleryItem['src'] }}" title="{{ $primaryGalleryItem['title'] }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                          </div>
                        @elseif($primaryGalleryItem)
                          <img src="{{ $primaryGalleryItem['src'] }}" alt="{{ $primaryGalleryItem['title'] }}" class="listing-showcase__stage-image" />
                        @endif
                      </div>

                      <div class="listing-showcase__floating listing-showcase__floating--top">
                        <div class="listing-showcase__meta">
                          <span>{{ $galleryPhotosCount }} foto(s)</span>
                          @if($galleryVideosCount > 0)
                            <span>{{ $galleryVideosCount }} video(s)</span>
                          @endif
                        </div>

                        <div class="listing-showcase__overlay-actions">
                          <div class="listing-share-box" data-share-box>
                            <button type="button" class="listing-hero-action-btn listing-hero-action-btn--overlay" data-share-toggle aria-expanded="false">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12v6.75m9-6.75V4.5m0 0L12 9m4.5-4.5L21 9M3 15.75V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-2.25" />
                              </svg>
                              <span>Compartir</span>
                              <svg xmlns="http://www.w3.org/2000/svg" class="listing-hero-action-btn__chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                              </svg>
                            </button>

                            <div class="listing-share-dropdown hidden" data-share-menu>
                              <button type="button" class="listing-share-option" data-share-copy>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 6.364 6.364l-4.242 4.243a4.5 4.5 0 0 1-6.364-6.364m-2.122-2.122a4.5 4.5 0 0 1 6.364-6.364l1.757 1.757" />
                                </svg>
                                <span>Copiar enlace</span>
                              </button>
                              <a href="{{ $shareEmailHref }}" class="listing-share-option" data-share-email>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9m19.5 0A2.25 2.25 0 0 0 19.5 5.25h-15A2.25 2.25 0 0 0 2.25 7.5m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615A2.25 2.25 0 0 1 2.25 7.743V7.5" />
                                </svg>
                                <span>Dirección de correo electrónico</span>
                              </a>
                            </div>

                            <p data-share-status class="listing-share-status hidden">Enlace copiado</p>
                          </div>

                          <button
                            type="button"
                            class="listing-hero-action-btn listing-hero-action-btn--overlay listing-hero-action-btn--favorite listing-hero-action-btn--icon-only {{ $isFavorited ? 'is-active' : '' }}"
                            data-listing-favorite="{{ $favoriteKey }}"
                            data-initial-favorited="{{ $isFavorited ? 'true' : 'false' }}"
                            @if($favoriteStoreUrl)
                              data-sync-store-url="{{ $favoriteStoreUrl }}"
                              data-sync-destroy-url="{{ $favoriteDestroyUrl }}"
                            @endif
                            aria-label="{{ $isFavorited ? 'Quitar de favoritos' : 'Guardar en favoritos' }}"
                            aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true" data-listing-favorite-icon>
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            <span class="listing-visually-hidden" data-listing-favorite-label>{{ $isFavorited ? 'Guardado' : 'Añadir a la lista de deseos' }}</span>
                          </button>
                        </div>
                      </div>

                      <button type="button" class="listing-showcase__stage-nav listing-showcase__stage-nav--prev" data-gallery-inline-prev aria-label="Elemento anterior">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                      </button>
                      <button type="button" class="listing-showcase__stage-nav listing-showcase__stage-nav--next" data-gallery-inline-next aria-label="Elemento siguiente">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                      </button>

                      <button type="button" class="listing-showcase__more-overlay hidden" data-gallery-inline-more>
                        Ver más
                      </button>

                      <div class="listing-showcase__floating listing-showcase__floating--bottom">
                        <span class="listing-showcase__inline-counter" data-gallery-inline-counter>1 / {{ $heroGalleryItems->count() }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </section>
          </div>

          <aside class="listing-page-aside md:col-span-4 md:row-span-2 md:row-start-1">
            <div class="listing-right-rail">
              <article class="listing-budget-card listing-budget-card--premium">
                <div class="listing-budget-head">
                  <div class="listing-budget-lead">
                    <div class="listing-budget-lead__copy">
                      <h2 class="listing-budget-title">{{ $basePriceLabel }}</h2>
                      <p class="listing-budget-summary">{{ $profile->base_price ? 'Precio desde' : 'Cotización personalizada' }}</p>
                    </div>
                    @if($providerPublicProfileUrl)
                      <a href="{{ $providerPublicProfileUrl }}" target="_blank" rel="noopener noreferrer" class="listing-budget-avatar-link" aria-label="Abrir perfil público del mariachi en una nueva pestaña">
                        <img src="{{ $mainPhotoUrl }}" alt="{{ $h1 }}" class="listing-budget-avatar" />
                      </a>
                    @else
                      <img src="{{ $mainPhotoUrl }}" alt="{{ $h1 }}" class="listing-budget-avatar" />
                    @endif
                  </div>

                  <div class="listing-budget-contact">
                    <div class="min-w-0 flex-1">
                      <div class="listing-budget-contact-top">
                        <p class="listing-budget-contact-name">{{ $responsibleName }}</p>
                        @if($providerPublicProfileUrl)
                          <a href="{{ $providerPublicProfileUrl }}" target="_blank" rel="noopener noreferrer" class="listing-budget-contact-link">
                            <span>Ver perfil</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M17 7H9m8 0v8" />
                            </svg>
                          </a>
                        @endif
                      </div>
                      <p class="listing-budget-contact-copy">{{ $heroSummary }}</p>
                    </div>
                  </div>
                </div>

                <div class="listing-budget-cta-stack mt-4">
                  @if($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="listing-budget-btn listing-budget-btn--whatsapp" data-listing-whatsapp-link>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 8.01 0C3.673 0 .145 3.528.145 7.864c0 1.386.362 2.74 1.05 3.936L0 16l4.32-1.133a7.82 7.82 0 0 0 3.69.94h.003c4.336 0 7.864-3.528 7.864-7.864a7.8 7.8 0 0 0-2.276-5.617Zm-5.59 12.18h-.002a6.5 6.5 0 0 1-3.314-.908l-.237-.14-2.564.673.685-2.502-.155-.257a6.52 6.52 0 0 1-1.002-3.482c0-3.6 2.93-6.53 6.53-6.53 1.744 0 3.385.678 4.618 1.911A6.48 6.48 0 0 1 14.5 7.89c0 3.6-2.93 6.53-6.49 6.53Zm3.58-4.89c-.196-.098-1.16-.573-1.34-.638-.18-.065-.311-.098-.442.098-.13.196-.507.638-.622.769-.114.13-.229.147-.425.049-.196-.098-.828-.305-1.577-.973-.582-.52-.975-1.162-1.09-1.358-.114-.196-.012-.302.086-.4.09-.09.196-.229.294-.344.098-.114.13-.196.196-.327.065-.13.033-.245-.016-.344-.049-.098-.442-1.064-.605-1.456-.159-.381-.32-.33-.442-.336a7.63 7.63 0 0 0-.377-.007.72.72 0 0 0-.523.245c-.18.196-.687.67-.687 1.635s.703 1.897.801 2.028c.098.13 1.385 2.114 3.356 2.964.469.202.836.323 1.122.413.472.15.902.129 1.242.078.379-.056 1.16-.474 1.324-.932.163-.458.163-.85.114-.932-.049-.082-.18-.131-.376-.229Z"/>
                      </svg>
                      <span>WhatsApp</span>
                    </a>
                  @endif

                  <button type="button" class="listing-budget-btn listing-budget-btn--quote" data-open-lead-modal>
                    Quiero más información
                  </button>

                  @if($phoneUrl)
                    <a href="{{ $phoneUrl }}" class="listing-budget-btn listing-budget-btn--secondary" aria-label="Llamar al mariachi">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5A2.25 2.25 0 0 1 4.5 2.25h3A2.25 2.25 0 0 1 9.75 4.5v2.04a2.25 2.25 0 0 1-.659 1.591l-1.2 1.2a16.45 16.45 0 0 0 6.774 6.774l1.2-1.2a2.25 2.25 0 0 1 1.591-.659H19.5a2.25 2.25 0 0 1 2.25 2.25v3A2.25 2.25 0 0 1 19.5 21.75h-.75C9.775 21.75 2.25 14.225 2.25 5.25V4.5Z" />
                      </svg>
                      <span>Llamar</span>
                    </a>
                  @endif
                </div>
                <div class="listing-budget-note">
                  Te respondemos con más detalles según disponibilidad, ciudad y fecha de tu evento.
                </div>

              </article>
            </div>
          </aside>

          <div class="listing-page-content md:col-span-8">
            <div class="listing-anchor-sentinel" data-listing-anchor-sentinel aria-hidden="true"></div>
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

              <div data-readmore class="listing-readmore listing-readmore--editorial">
                <div data-readmore-content class="listing-readmore-content">
                  <article class="listing-about-lead">
                    <p class="listing-about-eyebrow">Resumen del anuncio</p>
                    <p class="listing-about-lead-copy">{{ $profile->short_description ?: 'Este mariachi tiene su perfil activo y disponible para cotizaciones.' }}</p>
                  </article>

                  <div class="listing-about-prose">
                    @foreach($descriptionParagraphs as $paragraph)
                      <p>{{ $paragraph }}</p>
                    @endforeach
                  </div>

                  <div class="listing-about-grid">
                    <article class="listing-about-card">
                      <p class="listing-about-card__eyebrow">Ideal para</p>
                      @if($profile->eventTypes->isNotEmpty())
                        <div class="listing-about-chip-cloud">
                          @foreach($profile->eventTypes as $eventType)
                            <span class="listing-detail-chip">{{ $eventType->name }}</span>
                          @endforeach
                        </div>
                      @else
                        <p class="listing-about-card__copy">Se agregaran pronto los tipos de evento para este anuncio.</p>
                      @endif
                    </article>

                    <article class="listing-about-card">
                      <p class="listing-about-card__eyebrow">Cobertura principal</p>
                      <p class="listing-about-card__copy">
                        {{ $profile->address ? 'Referencia: '.$profile->address.'. ' : '' }}
                        Cobertura principal en {{ $profile->city_name ?: 'su ciudad principal' }}.
                      </p>
                      @if($coveragePreview->isNotEmpty())
                        <div class="listing-about-chip-cloud">
                          @foreach($coveragePreview as $area)
                            <span class="listing-detail-chip">{{ $area }}</span>
                          @endforeach
                        </div>
                      @endif
                    </article>
                  </div>
                </div>

                <button type="button" data-readmore-toggle class="listing-readmore-toggle">Leer mas</button>
              </div>
            </section>

            <section id="detalles" class="listing-flow-section" data-reveal>
              <h2>Detalles del anuncio</h2>

              <div class="listing-detail-grid">
                <article class="listing-detail-card">
                  <div class="listing-detail-card__head">
                    <span class="listing-detail-card__icon">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.75A1.75 1.75 0 0 1 5.75 6h12.5A1.75 1.75 0 0 1 20 7.75v8.5A1.75 1.75 0 0 1 18.25 18H5.75A1.75 1.75 0 0 1 4 16.25v-8.5ZM7.5 9.5h9M7.5 12h9M7.5 14.5h5" />
                      </svg>
                    </span>
                    <div>
                      <p class="listing-detail-card__eyebrow">Tipo de servicio</p>
                      <p class="listing-detail-card__title">Como suele presentarse</p>
                    </div>
                  </div>
                  @if($serviceTypeNames->isNotEmpty())
                    <div class="listing-detail-chip-cloud">
                      @foreach($serviceTypeNames as $serviceTypeName)
                        <span class="listing-detail-chip">{{ $serviceTypeName }}</span>
                      @endforeach
                    </div>
                  @else
                    <p class="listing-detail-card__copy">Sin especificar por ahora.</p>
                  @endif
                </article>

                <article class="listing-detail-card">
                  <div class="listing-detail-card__head">
                    <span class="listing-detail-card__icon">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75A2.25 2.25 0 0 0 20.25 16.5v-.39a4.5 4.5 0 0 0-2.565-4.055M15.75 18.75v-.75a4.5 4.5 0 0 0-9 0v.75m9 0H8.25m7.5 0h.75m-8.25 0H7.5m9-10.5a3 3 0 1 0-6 0 3 3 0 0 0 6 0Zm-9 1.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                      </svg>
                    </span>
                    <div>
                      <p class="listing-detail-card__eyebrow">Tamaño del grupo</p>
                      <p class="listing-detail-card__title">Formatos disponibles</p>
                    </div>
                  </div>
                  @if($groupSizeNames->isNotEmpty())
                    <div class="listing-detail-chip-cloud">
                      @foreach($groupSizeNames as $groupSizeName)
                        <span class="listing-detail-chip">{{ $groupSizeName }}</span>
                      @endforeach
                    </div>
                  @else
                    <p class="listing-detail-card__copy">Sin especificar por ahora.</p>
                  @endif
                </article>

                <article class="listing-detail-card">
                  <div class="listing-detail-card__head">
                    <span class="listing-detail-card__icon">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m4.5-9H9.75a2.25 2.25 0 1 0 0 4.5h4.5a2.25 2.25 0 1 1 0 4.5H6" />
                      </svg>
                    </span>
                    <div>
                      <p class="listing-detail-card__eyebrow">Rango de presupuesto</p>
                      <p class="listing-detail-card__title">Niveles de contratación</p>
                    </div>
                  </div>
                  @if($budgetNames->isNotEmpty())
                    <div class="listing-detail-chip-cloud">
                      @foreach($budgetNames as $budgetName)
                        <span class="listing-detail-chip">{{ $budgetName }}</span>
                      @endforeach
                    </div>
                  @else
                    <p class="listing-detail-card__copy">Sin especificar por ahora.</p>
                  @endif
                </article>

                <article class="listing-detail-card listing-detail-card--price">
                  <div class="listing-detail-card__head">
                    <span class="listing-detail-card__icon">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25c-1.657 0-3 .84-3 1.875S10.343 12 12 12s3 .84 3 1.875S13.657 15.75 12 15.75m0-7.5c1.32 0 2.44.533 2.84 1.274M12 8.25V6m0 9.75V18m-2.84-6.476C8.76 12.264 8.25 12.812 8.25 13.5c0 1.035 1.343 1.875 3 1.875m0-9.75c-1.32 0-2.44.533-2.84 1.274" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                      </svg>
                    </span>
                    <div>
                      <p class="listing-detail-card__eyebrow">Precio desde</p>
                      <p class="listing-detail-card__title">Referencia para cotizar</p>
                    </div>
                  </div>
                  <p class="listing-detail-price">{{ $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Se cotiza' }}</p>
                  <p class="listing-detail-price-note">Este valor sirve como punto de partida. El total final puede variar según ciudad, duración y formato del evento.</p>
                </article>
              </div>

              @if($socialLinks->isNotEmpty())
                <div class="listing-detail-socials">
                  <p class="listing-detail-socials__label">Canales y redes</p>
                  <div class="artist-seo-chip-cloud">
                    @foreach($socialLinks as $link)
                      @php
                        $socialSlug = \Illuminate\Support\Str::slug($link['label']);
                      @endphp
                      <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">
                        <span class="artist-seo-chip__icon" aria-hidden="true">
                          @if($socialSlug === 'sitio-web')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M12 3.75c2.51 2.295 4 5.41 4 8.25s-1.49 5.955-4 8.25m0-16.5c-2.51 2.295-4 5.41-4 8.25s1.49 5.955 4 8.25M4.5 7.5h15m-15 9h15M4.75 12a7.25 7.25 0 1 1 14.5 0 7.25 7.25 0 0 1-14.5 0Z" />
                            </svg>
                          @elseif($socialSlug === 'instagram')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                              <path d="M7.75 3h8.5A4.75 4.75 0 0 1 21 7.75v8.5A4.75 4.75 0 0 1 16.25 21h-8.5A4.75 4.75 0 0 1 3 16.25v-8.5A4.75 4.75 0 0 1 7.75 3Zm0 1.5A3.25 3.25 0 0 0 4.5 7.75v8.5A3.25 3.25 0 0 0 7.75 19.5h8.5a3.25 3.25 0 0 0 3.25-3.25v-8.5A3.25 3.25 0 0 0 16.25 4.5h-8.5ZM12 7.25A4.75 4.75 0 1 1 7.25 12 4.756 4.756 0 0 1 12 7.25Zm0 1.5A3.25 3.25 0 1 0 15.25 12 3.254 3.254 0 0 0 12 8.75Zm4.875-2.125a1.125 1.125 0 1 1-1.125 1.125 1.125 1.125 0 0 1 1.125-1.125Z" />
                            </svg>
                          @elseif($socialSlug === 'facebook')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                              <path d="M13.5 21v-7h2.359L16.25 11.1H13.5V9.249c0-.84.247-1.412 1.451-1.412h1.4V5.27A18.8 18.8 0 0 0 14.31 5c-2.02 0-3.402 1.232-3.402 3.495V11.1H8.75V14h2.158v7H13.5Z" />
                            </svg>
                          @elseif($socialSlug === 'tiktok')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                              <path d="M14.53 3c.33 2.26 1.67 3.91 3.97 4.22v2.46a6.76 6.76 0 0 1-3.93-1.3v5.92a5.33 5.33 0 1 1-5.33-5.34c.32 0 .63.03.94.09v2.62a2.67 2.67 0 1 0 1.73 2.5V3h2.62Z" />
                            </svg>
                          @elseif($socialSlug === 'youtube')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                              <path d="M21.8 8.001a2.98 2.98 0 0 0-2.096-2.108C17.84 5.4 12 5.4 12 5.4s-5.84 0-7.704.493A2.98 2.98 0 0 0 2.2 8c-.5 1.87-.5 4-.5 4s0 2.13.5 4a2.98 2.98 0 0 0 2.096 2.108C6.16 18.6 12 18.6 12 18.6s5.84 0 7.704-.493A2.98 2.98 0 0 0 21.8 16c.5-1.87.5-4 .5-4s0-2.13-.5-3.999ZM10.2 14.57V9.43L14.73 12 10.2 14.57Z" />
                            </svg>
                          @endif
                        </span>
                        <span>{{ $link['label'] }}</span>
                      </a>
                    @endforeach
                  </div>
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

              @php
                $renderedFaqs = $profile->renderedFaqRows();
              @endphp
              <div data-accordion class="mt-4 space-y-3">
                @foreach($renderedFaqs as $index => $faq)
                  <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200">
                    <button data-accordion-trigger aria-expanded="false" aria-controls="faq-listing-{{ $index + 1 }}" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                      {{ $faq['question'] }}
                      <span data-accordion-icon>+</span>
                    </button>
                    <div id="faq-listing-{{ $index + 1 }}" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                      {{ $faq['answer'] }}
                    </div>
                  </div>
                @endforeach
              </div>
            </section>
          </div>
        </div>

        <section class="listing-suggest listing-flow-section" data-reveal>
              <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2>Otros clientes tambien vieron</h2>
                  <p class="mt-1 text-sm text-slate-600">Más opciones activas en {{ $profile->city_name ?: 'esta ciudad' }} con estilo y presupuesto similares.</p>
                </div>
                <a href="{{ $cityLandingUrl }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Ver toda la ciudad</a>
              </div>

              @if($relatedProfiles->isNotEmpty())
                <div class="featured-carousel-shell mt-4" data-featured-carousel-shell>
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
                        $isVip = $related->hasPremiumMarketplaceBadge();
                      @endphp
                      <article class="featured-promo-card featured-promo-card--listing is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}">
                        <a class="featured-promo-media" href="{{ route('mariachi.public.show', ['slug' => $related->slug]) }}">
                          <img src="{{ $relatedPhotoUrl }}" alt="{{ $relatedName }}" loading="lazy" />
                          @if($isVip)
                            <span class="featured-promo-ribbon">{{ $related->marketplaceBadgeLabel() }}</span>
                          @endif
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

        @if($seoHelpfulLinkGroups->isNotEmpty())
          <section class="listing-seo-explorer listing-flow-section" data-reveal>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
              <div>
                <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Categorías</p>
                <h2 class="listing-seo-explorer__title">Sigue explorando en {{ $listingCityLabel }}</h2>
                <p class="mt-2 text-sm text-slate-600">Compara zonas, ocasiones y anuncios parecidos sin salirte de esta búsqueda.</p>
              </div>
            </div>

            <div class="categories-explorer" data-tabs>
              <div class="categories-explorer-nav" role="tablist" aria-label="Explorar enlaces relacionados del anuncio">
                @foreach($seoHelpfulLinkGroups as $group)
                  <button type="button" class="categories-explorer-tab tab-idle" data-tab-target="listing-seo-{{ $group['key'] }}">{{ $group['label'] }}</button>
                @endforeach
              </div>

              @foreach($seoHelpfulLinkGroups as $group)
                <div class="categories-explorer-panel {{ $loop->first ? '' : 'hidden' }}" data-tab-panel="listing-seo-{{ $group['key'] }}">
                  <div class="categories-chip-cloud">
                    @foreach($group['items'] as $link)
                      <a href="{{ $link['url'] }}" class="category-cloud-link">
                        <span aria-hidden="true">
                          @if($group['key'] === 'location')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.35 6-10a6 6 0 1 0-12 0c0 5.65 6 10 6 10Z" />
                              <circle cx="12" cy="11" r="2.25" />
                            </svg>
                          @elseif($group['key'] === 'event')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v3m8-3v3M4 9h16M5.25 5.75h13.5A1.75 1.75 0 0 1 20.5 7.5v10.75A1.75 1.75 0 0 1 18.75 20H5.25A1.75 1.75 0 0 1 3.5 18.25V7.5a1.75 1.75 0 0 1 1.75-1.75Z" />
                            </svg>
                          @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5H19.5V10.5M19 5L11 13M10.5 19.5H4.5V13.5" />
                            </svg>
                          @endif
                        </span>
                        <span>{{ $link['label'] }}</span>
                      </a>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          </section>
        @endif

        <section class="listing-recent-shell" data-reveal data-listing-recents-shell data-resolve-url="{{ route('public.listings.resolve') }}" data-current-listing-id="{{ $profile->id }}" data-account-url="{{ route('public.collections.recents') }}" data-has-server-items="{{ $recentlyViewedListings->isNotEmpty() ? 'true' : 'false' }}">
              <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2>Vistos recientemente</h2>
                  <p class="mt-1 text-sm text-slate-600">Recupera los anuncios que estabas comparando para no perder el hilo.</p>
                </div>
                <a href="{{ route('public.collections.recents') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Abrir historial</a>
              </div>

              @if($recentlyViewedListings->isNotEmpty())
                <div class="featured-carousel-shell mt-4" data-featured-carousel-shell data-recent-carousel-wrap>
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--left" data-featured-scroll="left" aria-label="Desplazar vistos recientemente a la izquierda">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--right" data-featured-scroll="right" aria-label="Desplazar vistos recientemente a la derecha">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <div data-featured-carousel class="featured-carousel-track" data-recent-track>
                    @foreach($recentlyViewedListings as $recentListing)
                      @php
                        $recentPhoto = $recentListing->photos->firstWhere('is_featured', true) ?? $recentListing->photos->first();
                        $recentPhotoUrl = $recentPhoto ? asset('storage/'.$recentPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
                        $recentName = $recentListing->business_name ?: $recentListing->user?->display_name;
                        $isVip = $recentListing->hasPremiumMarketplaceBadge();
                      @endphp
                      <article class="featured-promo-card featured-promo-card--listing is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}">
                        <a class="featured-promo-media" href="{{ route('mariachi.public.show', ['slug' => $recentListing->slug]) }}">
                          <img src="{{ $recentPhotoUrl }}" alt="{{ $recentName }}" loading="lazy" />
                          @if($isVip)
                            <span class="featured-promo-ribbon">{{ $recentListing->marketplaceBadgeLabel() }}</span>
                          @endif
                          <span class="featured-promo-chip">{{ $recentListing->city_name }}</span>
                          <span class="featured-promo-score">{{ $recentListing->profile_completion }}%</span>
                        </a>
                        <div class="featured-promo-body">
                          <p class="featured-promo-kicker">{{ $recentListing->eventTypes->pluck('name')->take(2)->join(' · ') ?: 'Disponible para eventos' }}</p>
                          <h3 class="featured-promo-title">{{ $recentListing->short_description ?: 'Perfil verificado y listo para cotizar.' }}</h3>
                        </div>
                        <div class="featured-promo-footer">
                          <p class="featured-promo-artist">{{ $recentName }}</p>
                          <div class="featured-promo-bottom">
                            <strong>{{ $recentListing->base_price ? 'Desde $'.number_format((float) $recentListing->base_price, 0, ',', '.') : 'Cotizacion directa' }}</strong>
                            <a href="{{ route('mariachi.public.show', ['slug' => $recentListing->slug]) }}">Ver anuncio</a>
                          </div>
                        </div>
                      </article>
                    @endforeach
                  </div>
                </div>
                <article class="listing-opinion-empty mt-4 hidden" data-listing-recents-empty>
                  <p class="text-sm font-bold text-slate-900">Aun no hay historial reciente en este navegador</p>
                  <p class="mt-1 text-sm text-slate-600">Cuando visites otros anuncios activos, apareceran aqui automaticamente.</p>
                </article>
              @else
                <div class="featured-carousel-shell mt-4 hidden" data-featured-carousel-shell data-recent-carousel-wrap>
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--left" data-featured-scroll="left" aria-label="Desplazar vistos recientemente a la izquierda">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                  </button>
                  <button type="button" class="featured-carousel-btn featured-carousel-btn--right" data-featured-scroll="right" aria-label="Desplazar vistos recientemente a la derecha">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>

                  <div data-featured-carousel class="featured-carousel-track" data-recent-track></div>
                </div>
                <article class="listing-opinion-empty mt-4" data-listing-recents-empty>
                  <p class="text-sm font-bold text-slate-900">Aun no hay historial reciente en este navegador</p>
                  <p class="mt-1 text-sm text-slate-600">Cuando visites otros anuncios activos, apareceran aqui automaticamente.</p>
                </article>
              @endif
        </section>
      </section>
    </main>

    <div class="mobile-cta md:hidden">
      <button type="button" class="mobile-cta-btn mobile-cta-btn--wa" data-open-lead-modal>Más info</button>
      @if($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mobile-cta-btn">WhatsApp</a>
      @endif
      @if($phoneUrl)
        <a href="{{ $phoneUrl }}" class="mobile-cta-btn">Llamar</a>
      @endif
    </div>

    <div class="gallery-modal hidden" data-listing-gallery-modal aria-hidden="true">
      <div class="gallery-modal__card gallery-modal__card--listing" role="dialog" aria-modal="true" aria-label="Galería del anuncio">
        <div class="gallery-modal__top">
          <div class="gallery-modal__meta">
            @if($galleryVideosCount > 0)
              <div class="gallery-modal__filters" data-gallery-modal-filters>
                <button type="button" class="gallery-modal__filter is-active" data-gallery-filter="all">Todo ({{ $galleryItems->count() }})</button>
                <button type="button" class="gallery-modal__filter" data-gallery-filter="image">Fotos del mariachi ({{ $galleryPhotosCount }})</button>
                <button type="button" class="gallery-modal__filter" data-gallery-filter="video">Videos del mariachi ({{ $galleryVideosCount }})</button>
              </div>
            @endif
            <span data-gallery-counter class="gallery-modal__counter"></span>
          </div>
          <button type="button" data-gallery-close class="gallery-modal__close" aria-label="Cerrar galería">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="gallery-modal__stage-shell">
          <button type="button" data-gallery-prev class="gallery-modal__nav gallery-modal__nav--prev" aria-label="Anterior">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div data-gallery-stage class="gallery-modal__stage"></div>
          <button type="button" data-gallery-next class="gallery-modal__nav gallery-modal__nav--next" aria-label="Siguiente">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <div data-gallery-thumbs class="gallery-modal__thumbs" aria-label="Miniaturas de la galería"></div>
      </div>
    </div>

    <div class="lead-modal hidden" data-lead-modal aria-hidden="true">
      <div class="lead-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lead-modal-title">
        <div class="lead-modal__head">
          <div>
            <p class="lead-modal__eyebrow">Contacto directo</p>
            <h2 id="lead-modal-title" class="lead-modal__title">Quiero más información</h2>
            <p class="lead-modal__copy">Déjanos tus datos y te ayudamos a validar disponibilidad y detalles del servicio.</p>
          </div>
          <button type="button" class="lead-modal__close" data-lead-modal-close aria-label="Cerrar formulario">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form action="{{ route('listing.info-requests.store', ['slug' => $profile->slug]) }}" method="POST" class="lead-modal__form" data-lead-form>
          @csrf
          <div class="lead-modal__grid">
            <label class="lead-modal__field">
              <span>Nombre</span>
              <input type="text" name="name" value="{{ $quoteDefaults['contact_name'] }}" placeholder="Tu nombre" required />
            </label>
            <label class="lead-modal__field">
              <span>Correo</span>
              <input type="email" name="email" value="{{ $quoteDefaults['contact_email'] }}" placeholder="tucorreo@ejemplo.com" required />
            </label>
            <label class="lead-modal__field">
              <span>Teléfono</span>
              <input type="text" name="phone" value="{{ $quoteDefaults['contact_phone'] }}" placeholder="300 123 4567" required />
            </label>
            <label class="lead-modal__field">
              <span>Fecha del evento</span>
              <input type="date" name="event_date" required />
            </label>
          </div>

          <label class="lead-modal__field">
            <span>Ciudad del evento</span>
            <input type="text" name="event_city" value="{{ $quoteDefaults['event_city'] }}" placeholder="Bogotá" />
          </label>

          <label class="lead-modal__field">
            <span>Mensaje</span>
            <textarea name="message" rows="4" placeholder="Cuéntanos qué tipo de evento tienes, horario y lo que necesitas." required></textarea>
          </label>

          <p class="lead-modal__error hidden" data-lead-error></p>
          <p class="lead-modal__success hidden" data-lead-success></p>

          <div class="lead-modal__actions">
            <button type="button" class="lead-modal__btn lead-modal__btn--ghost" data-lead-modal-close>Cancelar</button>
            <button type="submit" class="lead-modal__btn lead-modal__btn--primary" data-lead-submit>Enviar solicitud</button>
          </div>
        </form>
      </div>
    </div>

    <script type="application/json" data-current-listing>@json($currentListingPayload)</script>
    <div data-component="site-footer"></div>

    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260315-html-sitemap-v1"></script>
    <script src="js/listing-gallery.js?v=20260314-listing-v6"></script>
    <script src="js/listing-lead-modal.js?v=20260311-listing-v1"></script>
    <script src="js/listing-favorites.js?v=20260313-listing-v2"></script>
    <script src="js/public-listing-collections.js?v=20260311-listing-collections-v1"></script>
  </body>
</html>
