@extends('front.layouts.marketplace')

@section('title', 'Mariachis.co | Marketplace de mariachis en Colombia')
@section('meta_description', 'Encuentra mariachis por ciudad, compara perfiles y contacta por WhatsApp o llamada.')
@section('body_page', 'home')

@push('styles')
  <style>
    .home-featured-card {
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 1.7rem;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(246, 250, 248, 0.98) 100%);
      box-shadow: 0 28px 56px -40px rgba(15, 23, 42, 0.3);
      overflow: hidden;
    }

    .home-featured-card .featured-promo-media {
      height: 18.9rem;
      overflow: hidden;
      border-radius: 0;
      box-shadow: none;
      cursor: pointer;
    }

    .home-featured-card .featured-promo-media::after {
      background:
        linear-gradient(180deg, rgba(15, 23, 42, 0.02) 0%, rgba(15, 23, 42, 0.1) 34%, rgba(15, 23, 42, 0.46) 100%);
    }

    .home-featured-card__gallery {
      position: relative;
      width: 100%;
      height: 100%;
    }

    .home-featured-card__slide {
      position: absolute;
      inset: 0;
      opacity: 0;
      transform: scale(1.02);
      transition:
        opacity 0.35s ease,
        transform 0.45s ease;
    }

    .home-featured-card__slide.is-active {
      opacity: 1;
      transform: scale(1);
    }

    .home-featured-card__slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .home-featured-card__nav {
      position: absolute;
      inset: 0;
      z-index: 6;
      pointer-events: none;
    }

    .home-featured-card__arrow {
      position: absolute;
      top: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2.35rem;
      height: 2.35rem;
      border: 0;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.94);
      color: #0f172a;
      box-shadow: 0 18px 28px -22px rgba(15, 23, 42, 0.68);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-50%) scale(0.9);
      transition:
        opacity 0.22s ease,
        transform 0.22s ease;
    }

    .home-featured-card__arrow--prev {
      left: 0.8rem;
    }

    .home-featured-card__arrow--next {
      right: 0.8rem;
    }

    .home-featured-card:hover .home-featured-card__arrow,
    .home-featured-card:focus-within .home-featured-card__arrow {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(-50%) scale(1);
    }

    .home-featured-card__arrow:hover {
      background: #ffffff;
    }

    .home-featured-card__arrow svg {
      width: 1rem;
      height: 1rem;
      stroke: currentColor;
      stroke-width: 2.35;
      fill: none;
    }

    .home-featured-card .featured-promo-chip {
      top: auto;
      bottom: 0.9rem;
      left: 0.9rem;
      padding: 0.45rem 0.82rem;
      letter-spacing: 0.09em;
      color: #0f172a;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 14px 24px -18px rgba(15, 23, 42, 0.5);
    }

    .home-featured-card .featured-favorite-btn {
      background: rgba(255, 255, 255, 0.94);
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 999px;
      box-shadow: 0 14px 22px -18px rgba(15, 23, 42, 0.46);
    }

    .home-featured-card__body {
      padding: 1.15rem 1.2rem 1.45rem;
    }

    .home-featured-card__provider {
      margin: 0;
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #0b5d43;
    }

    .home-featured-card .featured-promo-title {
      margin-top: 0.2rem;
      -webkit-line-clamp: 2;
      font-size: 1.36rem;
      line-height: 1.1;
      letter-spacing: -0.02em;
    }

    .home-featured-card__title-link {
      color: inherit;
      text-decoration: none;
    }

    .home-featured-card__rating {
      display: inline-flex;
      align-items: center;
      gap: 0.42rem;
      margin-top: 0.55rem;
      color: #475569;
      font-size: 0.9rem;
      font-weight: 600;
      letter-spacing: -0.01em;
    }

    .home-featured-card__rating-star {
      color: #d97706;
      font-size: 1rem;
      line-height: 1;
      text-shadow: 0 8px 18px rgba(217, 119, 6, 0.18);
    }

    .home-featured-card__rating-score {
      color: #0f172a;
      font-weight: 700;
    }

    .home-featured-card__rating-count {
      color: #64748b;
      font-weight: 500;
    }

    .home-featured-card__location {
      margin-top: 0.55rem;
      font-size: 0.9rem;
      color: #64748b;
    }

    .home-featured-card__signals {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
      margin-top: 0.7rem;
    }

    .home-featured-card__signal {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.35rem 0.72rem;
      border: 1px solid rgba(0, 104, 71, 0.08);
      background: #eff6f3;
      color: #0b5d43;
      font-size: 0.72rem;
      font-weight: 700;
      line-height: 1;
    }

    .home-featured-card__price-row {
      display: flex;
      align-items: center;
      margin-top: 0.72rem;
      padding-top: 0.78rem;
      border-top: 1px solid rgba(148, 163, 184, 0.16);
    }

    .home-featured-card__price-inline {
      display: inline-flex;
      align-items: center;
      gap: 0.52rem;
      color: #0f172a;
      text-decoration: none;
    }

    .home-featured-card__price-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.9rem;
      height: 1.9rem;
      border-radius: 999px;
      background: linear-gradient(180deg, #eff6f3 0%, #e2efe9 100%);
      color: #0b5d43;
      box-shadow: inset 0 0 0 1px rgba(11, 93, 67, 0.08);
    }

    .home-featured-card__price-icon svg {
      width: 1rem;
      height: 1rem;
      stroke: currentColor;
      stroke-width: 1.9;
      fill: none;
    }

    .home-featured-card__price-copy {
      display: inline-flex;
      align-items: baseline;
      gap: 0.32rem;
      flex-wrap: wrap;
    }

    .home-featured-card__price-prefix {
      font-size: 0.92rem;
      font-weight: 600;
      color: #475569;
    }

    .home-featured-card__price-amount {
      font-size: 1.34rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      color: #0f172a;
    }

    @media (max-width: 768px) {
      .home-featured-card .featured-promo-media {
        height: 16.2rem;
      }

      .home-featured-card .featured-promo-title {
        font-size: 1.18rem;
      }

      .home-featured-card__price-row {
        align-items: center;
      }

      .home-featured-card__arrow {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(-50%) scale(1);
      }
    }
  </style>
@endpush

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-card-gallery]').forEach(function (gallery) {
        const slides = Array.from(gallery.querySelectorAll('[data-card-gallery-slide]'));
        const prevButton = gallery.querySelector('[data-card-gallery-prev]');
        const nextButton = gallery.querySelector('[data-card-gallery-next]');

        if (slides.length < 2) {
          prevButton?.remove();
          nextButton?.remove();
          return;
        }

        let activeIndex = 0;
        let autoplay = null;
        const detailUrl = gallery.closest('[data-card-url]')?.getAttribute('data-card-url') || '';

        const render = function () {
          slides.forEach(function (slide, index) {
            const isActive = index === activeIndex;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
          });
        };

        const stopAutoplay = function () {
          if (autoplay) {
            window.clearInterval(autoplay);
            autoplay = null;
          }
        };

        const startAutoplay = function () {
          stopAutoplay();
          autoplay = window.setInterval(function () {
            activeIndex = (activeIndex + 1) % slides.length;
            render();
          }, 1500);
        };

        const openListing = function () {
          if (!detailUrl) {
            return;
          }

          window.location.href = detailUrl;
        };

        const goNext = function () {
          activeIndex = (activeIndex + 1) % slides.length;
          render();
        };

        const goPrev = function () {
          activeIndex = (activeIndex - 1 + slides.length) % slides.length;
          render();
        };

        gallery.addEventListener('mouseenter', function () {
          goNext();
          startAutoplay();
        });

        gallery.addEventListener('mouseleave', function () {
          stopAutoplay();
          activeIndex = 0;
          render();
        });

        prevButton?.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          stopAutoplay();
          goPrev();
        });

        nextButton?.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (activeIndex >= slides.length - 1) {
            openListing();
            return;
          }

          stopAutoplay();
          goNext();
        });

        render();
      });
    });
  </script>
@endpush

@section('content')
    <main>
      <section class="hero-split-shell hero-split-shell--flush hero-split-shell--home">
        <div class="layout-shell--wide">
          <div class="hero-split-grid-shell">
            <div class="hero-split-grid hero-split-grid--home">
              <div class="hero-split-left hero-split-left--home" data-reveal>
                <h1 class="hero-home-immersive__title">
                  <span class="hero-home-immersive__title-main">Encuentra mariachis para</span>
                  <span class="hero-home-immersive__title-accent">bodas, serenatas y eventos</span>
                </h1>
                <p class="hero-home-immersive__lead">Más de {{ number_format($publishedProfilesCount ?? 0) }} mariachis para tu celebración.</p>
              </div>

              <div class="hero-split-right hero-split-right--home" data-reveal>
                <div class="hero-split-home-media">
                  <img src="{{ asset('marketplace/img/home-hero-tight.webp') }}" alt="Mariachis en vivo durante un evento" class="hero-split-home-media__image" />
                  <div class="hero-split-home-media__veil" aria-hidden="true"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="hero-home-search-bridge">
          <div class="layout-shell--wide">
            @include('front.partials.search-form', [
              'eventTypes' => $eventTypes,
              'serviceTypes' => $serviceTypes,
              'groupSizeOptions' => $groupSizeOptions,
              'budgetRanges' => $budgetRanges,
              'searchCityOptions' => $searchCityOptions,
              'countryLandingSlug' => $countryLandingSlug,
            ])
          </div>
        </div>
      </section>

      <section id="categorias" class="layout-shell--wide py-14">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4" data-reveal>
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Categorías</p>
            <h2 class="mt-2 text-4xl font-extrabold tracking-[-0.015em] text-slate-900">Explora por evento, servicio, tamaño y presupuesto</h2>
          </div>
        </div>

        <div class="categories-explorer" data-tabs data-reveal>
          <div class="categories-explorer-nav" role="tablist" aria-label="Explorar categorías de mariachis">
            <button type="button" class="categories-explorer-tab tab-idle" data-tab-target="evento">Tipo de evento</button>
            <button type="button" class="categories-explorer-tab tab-idle" data-tab-target="servicio">Tipo de servicio</button>
            <button type="button" class="categories-explorer-tab tab-idle" data-tab-target="grupo">Tamaño del grupo</button>
            <button type="button" class="categories-explorer-tab tab-idle" data-tab-target="presupuesto">Presupuesto</button>
          </div>

          <div class="categories-explorer-panel" data-tab-panel="evento">
            <div class="categories-chip-cloud">
              @forelse($homeEventTypes as $item)
                <a href="{{ route('home.event-category.redirect', ['eventType' => $item->slug ?: \Illuminate\Support\Str::slug($item->name)]) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="servicio">
            <div class="categories-chip-cloud">
              @forelse($homeServiceTypes as $item)
                <a href="{{ route('home.service-category.redirect', ['serviceType' => $item->slug ?: \Illuminate\Support\Str::slug($item->name)]) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="grupo">
            <div class="categories-chip-cloud">
              @forelse($homeGroupSizeOptions as $item)
                <a href="{{ route('home.group-size-category.redirect', ['groupSizeOption' => $item->slug ?: \Illuminate\Support\Str::slug($item->name)]) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="presupuesto">
            <div class="categories-chip-cloud">
              @forelse($homeBudgetRanges as $item)
                <a href="{{ route('home.budget-category.redirect', ['budgetRange' => $item->slug ?: \Illuminate\Support\Str::slug($item->name)]) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>
        </div>
      </section>

      <section class="layout-shell--wide py-14">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4" data-reveal>
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Anuncios destacados</p>
            <h2 class="mt-2 text-4xl font-extrabold tracking-[-0.015em] text-slate-900">Elige por ocasión, no solo por precio</h2>
          </div>
          <p class="text-sm font-semibold text-slate-600">Mostrando <span data-filter-count="home-featured">{{ $featuredProfiles->count() }}</span> resultados</p>
        </div>

        <div class="mb-6 flex flex-wrap gap-2 overflow-x-auto pb-1" data-filter-wrap="home-featured" data-reveal>
          <button data-filter-chip="all" class="filter-chip is-active rounded-full px-4 py-2 text-sm font-bold">Todos</button>
          @foreach($featuredTags as $tag)
            <button data-filter-chip="{{ $tag['slug'] }}" class="filter-chip rounded-full px-4 py-2 text-sm font-bold">{{ $tag['label'] }}</button>
          @endforeach
          <button data-filter-chip="favoritos" class="filter-chip rounded-full px-4 py-2 text-sm font-bold">Favoritos</button>
        </div>

        <div class="featured-carousel-shell" data-featured-carousel-shell data-reveal>
          <div class="featured-carousel-track" data-featured-carousel>
            @forelse($featuredProfiles as $profile)
              @php
                $tags = collect()
                  ->merge($profile->eventTypes->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::slug($name)))
                  ->merge($profile->serviceTypes->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::slug($name)))
                  ->merge($profile->budgetRanges->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::slug($name)))
                  ->filter()
                  ->unique()
                  ->implode(',');

                $featuredPhoto = $profile->photos->firstWhere('is_featured', true) ?? $profile->photos->first();
                $photoUrl = $featuredPhoto ? asset('storage/'.$featuredPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
                $name = $profile->business_name ?: $profile->user?->display_name;
                $city = $profile->city_name ?: 'Ciudad no definida';
                $detailUrl = $profile->slug ? route('mariachi.public.show', ['slug' => $profile->slug]) : route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($city)]);
                $isVip = $profile->hasPremiumMarketplaceBadge();
                $title = $profile->title ?: ($profile->short_description ?: 'Mariachi disponible para tu evento en '.$city.'.');
                $showProviderLine = ! \Illuminate\Support\Str::contains(mb_strtolower($title), mb_strtolower((string) $name));
                $previewPhotos = $profile->photos
                  ->sortByDesc(fn ($photo) => $photo->is_featured ? 1 : 0)
                  ->take(3)
                  ->map(fn ($photo) => asset('storage/'.$photo->path))
                  ->values();

                if ($previewPhotos->isEmpty()) {
                  $previewPhotos = collect([asset('marketplace/assets/logo-wordmark.png')]);
                }
                $signals = collect()
                  ->merge($profile->eventTypes->pluck('name'))
                  ->merge($profile->serviceTypes->pluck('name'))
                  ->filter()
                  ->unique()
                  ->take(2);
                $reviewsCount = (int) ($profile->public_reviews_count ?? 0);
                $ratingValue = $reviewsCount > 0 ? (float) ($profile->public_rating_avg ?? 0) : 0;
                $ratingLabel = number_format($ratingValue, 1);
              @endphp

              <article
                data-filter-card="home-featured"
                data-card-tags="{{ $tags }}"
                data-favorite-id="listing-{{ $profile->id }}"
                data-card-url="{{ $detailUrl }}"
                role="link"
                tabindex="0"
                aria-label="Abrir anuncio de {{ $title }}"
                class="featured-promo-card featured-promo-card--listing home-featured-card is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}"
              >
                <div class="featured-promo-media" data-card-gallery>
                  <div class="home-featured-card__gallery">
                    @foreach ($previewPhotos as $index => $previewPhoto)
                      <div class="home-featured-card__slide {{ $index === 0 ? 'is-active' : '' }}" data-card-gallery-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                        <img src="{{ $previewPhoto }}" alt="{{ $name }}" />
                      </div>
                    @endforeach
                  </div>
                  @if($isVip)
                    <span class="featured-promo-ribbon">{{ $profile->marketplaceBadgeLabel() }}</span>
                  @endif
                  <span class="featured-promo-chip">{{ $city }}</span>
                  @if ($previewPhotos->count() > 1)
                    <div class="home-featured-card__nav" aria-hidden="true">
                      <button type="button" class="home-featured-card__arrow home-featured-card__arrow--prev" data-card-gallery-prev aria-label="Ver foto anterior">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                      </button>
                      <button type="button" class="home-featured-card__arrow home-featured-card__arrow--next" data-card-gallery-next aria-label="Ver foto siguiente">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                      </button>
                    </div>
                  @endif
                </div>
                <button data-favorite="listing-{{ $profile->id }}" class="favorite-btn featured-favorite-btn" aria-label="Guardar en favoritos" aria-pressed="false">
                  <svg data-fav-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </button>
                <div class="featured-promo-body home-featured-card__body">
                  @if ($showProviderLine)
                    <p class="home-featured-card__provider">{{ $name }}</p>
                  @endif
                  <h3 class="featured-promo-title">
                    <a href="{{ $detailUrl }}" class="home-featured-card__title-link">{{ $title }}</a>
                  </h3>
                  <div class="home-featured-card__rating" aria-label="{{ $ratingLabel }} de 5 con {{ $reviewsCount }} opiniones">
                    <span class="home-featured-card__rating-star" aria-hidden="true">★</span>
                    <span class="home-featured-card__rating-score">{{ $ratingLabel }}</span>
                    <span class="home-featured-card__rating-count">({{ $reviewsCount }})</span>
                  </div>
                  @if ($signals->isNotEmpty())
                    <div class="home-featured-card__signals">
                      @foreach ($signals as $signal)
                        <span class="home-featured-card__signal">{{ $signal }}</span>
                      @endforeach
                    </div>
                  @endif
                  <div class="home-featured-card__price-row">
                    <span class="home-featured-card__price-inline">
                      <span class="home-featured-card__price-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                          <path d="M7 7.5h9.5a2.5 2.5 0 0 1 0 5H8.5a2.5 2.5 0 0 0 0 5H18"></path>
                          <path d="M12 5v14"></path>
                        </svg>
                      </span>
                      <span class="home-featured-card__price-copy">
                        <span class="home-featured-card__price-prefix">Desde</span>
                        <span class="home-featured-card__price-amount">{{ $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Cotizar' }}</span>
                      </span>
                    </span>
                  </div>
                </div>
              </article>
            @empty
              <article class="featured-promo-card">
                <div class="featured-promo-body">
                  <p class="featured-promo-kicker">Sin anuncios aún</p>
                  <h3 class="featured-promo-title">Aún no hay anuncios destacados para mostrar.</h3>
                </div>
                <div class="featured-promo-footer">
                  <p class="featured-promo-meta">Cuando el primer mariachi publique un anuncio activo, aparecerá automáticamente aquí.</p>
                </div>
              </article>
            @endforelse
          </div>
        </div>
      </section>

      <section id="ciudades" class="layout-shell--wide pt-10 pb-10">
        <div class="mb-5 flex items-end justify-between gap-4" data-reveal>
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">Por zona</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.01em] text-slate-900 md:text-4xl">Músicos por zona de Colombia</h2>
          </div>
        </div>

        <div class="zone-carousel-wrap" data-zone-carousel-wrap data-reveal>
          <div class="zone-carousel-track" data-zone-carousel>
            @forelse($zones as $zone)
              @php
                $zoneUrl = !empty($zone['city_slug'])
                  ? route('seo.landing.city-category', ['citySlug' => $zone['city_slug'], 'scopeSlug' => $zone['slug']])
                  : route('seo.landing.slug', ['slug' => $zone['slug']]);
              @endphp
              <a href="{{ $zoneUrl }}" class="zone-card zone-card--visual" @if(!empty($zone['cover_url'])) style="--zone-cover-image: url('{{ $zone['cover_url'] }}');" @endif>
                <div class="zone-card__content">
                  <h3>{{ $zone['name'] }}</h3>
                  <p>{{ $zone['count'] }} {{ $zone['count'] === 1 ? 'mariachi' : 'mariachis' }}</p>
                  @if(!empty($zone['city_name']))
                    <span class="zone-card__meta">Zona en {{ $zone['city_name'] }}</span>
                  @endif
                </div>
              </a>
            @empty
              <article class="zone-card zone-card--visual">
                <div class="zone-card__content">
                <h3>Sin zonas publicadas aún</h3>
                <p>Cuando se complete la cobertura de los perfiles aparecerán aquí.</p>
                </div>
              </article>
              <article class="zone-card zone-card--visual">
                <div class="zone-card__content">
                <h3>Bloque preparado</h3>
                <p>La home se actualiza automáticamente con datos reales.</p>
                </div>
              </article>
            @endforelse
          </div>
        </div>
      </section>

      <section class="layout-shell--wide pb-14">
        <div class="home-city-showcase p-4 md:p-6" data-home-city-showcase data-reveal>
          <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Por ciudad</p>
              <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.01em] text-slate-900 md:text-4xl">Mira anuncios destacados por zona</h2>
            </div>
          </div>

          @if($cityShowcase->isNotEmpty())
            @php $cityTabs = $cityShowcase->take(6); @endphp
            <div class="home-city-tabs" role="tablist" aria-label="Ciudades destacadas" style="--home-city-tab-count: {{ max($cityTabs->count(), 1) }};">
              @foreach($cityTabs as $index => $city)
                <button type="button" class="home-city-tab {{ $index === 0 ? 'is-active' : '' }}" data-city-showcase-tab="{{ $city['slug'] }}" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="home-city-panel-{{ $city['slug'] }}">
                  <span class="home-city-tab-thumb"><img src="{{ asset('marketplace/img/6.jpeg') }}" alt="{{ $city['city'] }}" /></span>
                  <span>{{ $city['city'] }}</span>
                </button>
              @endforeach
            </div>

            <div class="home-city-panels">
              @foreach($cityTabs as $index => $city)
                <div id="home-city-panel-{{ $city['slug'] }}" class="home-city-panel {{ $index === 0 ? 'is-active' : '' }}" data-city-showcase-panel="{{ $city['slug'] }}" role="tabpanel">
                  <div class="home-city-cards">
                    @foreach($city['profiles'] as $profile)
                      @php
                        $featuredPhoto = $profile->photos->firstWhere('is_featured', true) ?? $profile->photos->first();
                        $photoUrl = $featuredPhoto ? asset('storage/'.$featuredPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
                        $name = $profile->business_name ?: $profile->user?->display_name;
                        $detailUrl = $profile->slug ? route('mariachi.public.show', ['slug' => $profile->slug]) : route('seo.landing.slug', ['slug' => $city['slug']]);
                        $isVip = $profile->hasPremiumMarketplaceBadge();
                        $title = $profile->title ?: ($profile->short_description ?: 'Mariachi disponible para cotización en '.$city['city'].'.');
                        $showProviderLine = ! \Illuminate\Support\Str::contains(mb_strtolower($title), mb_strtolower((string) $name));
                        $previewPhotos = $profile->photos
                          ->sortByDesc(fn ($photo) => $photo->is_featured ? 1 : 0)
                          ->take(3)
                          ->map(fn ($photo) => asset('storage/'.$photo->path))
                          ->values();

                        if ($previewPhotos->isEmpty()) {
                          $previewPhotos = collect([asset('marketplace/assets/logo-wordmark.png')]);
                        }
                        $signals = collect()
                          ->merge($profile->eventTypes->pluck('name'))
                          ->merge($profile->serviceTypes->pluck('name'))
                          ->filter()
                          ->unique()
                          ->take(2);
                        $reviewsCount = (int) ($profile->public_reviews_count ?? 0);
                        $ratingValue = $reviewsCount > 0 ? (float) ($profile->public_rating_avg ?? 0) : 0;
                        $ratingLabel = number_format($ratingValue, 1);
                      @endphp
                      <article
                        data-card-url="{{ $detailUrl }}"
                        role="link"
                        tabindex="0"
                        aria-label="Abrir anuncio de {{ $title }}"
                        class="featured-promo-card featured-promo-card--listing home-featured-card is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}"
                      >
                        <div class="featured-promo-media" data-card-gallery>
                          <div class="home-featured-card__gallery">
                            @foreach ($previewPhotos as $index => $previewPhoto)
                              <div class="home-featured-card__slide {{ $index === 0 ? 'is-active' : '' }}" data-card-gallery-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                                <img src="{{ $previewPhoto }}" alt="{{ $name }}" />
                              </div>
                            @endforeach
                          </div>
                          @if($isVip)
                            <span class="featured-promo-ribbon">{{ $profile->marketplaceBadgeLabel() }}</span>
                          @endif
                          <span class="featured-promo-chip">{{ $city['city'] }}</span>
                          @if ($previewPhotos->count() > 1)
                            <div class="home-featured-card__nav" aria-hidden="true">
                              <button type="button" class="home-featured-card__arrow home-featured-card__arrow--prev" data-card-gallery-prev aria-label="Ver foto anterior">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                  <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                              <button type="button" class="home-featured-card__arrow home-featured-card__arrow--next" data-card-gallery-next aria-label="Ver foto siguiente">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                  <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </button>
                            </div>
                          @endif
                        </div>
                        <div class="featured-promo-body home-featured-card__body">
                          @if ($showProviderLine)
                            <p class="home-featured-card__provider">{{ $name }}</p>
                          @endif
                          <h3 class="featured-promo-title">
                            <a href="{{ $detailUrl }}" class="home-featured-card__title-link">{{ $title }}</a>
                          </h3>
                          <div class="home-featured-card__rating" aria-label="{{ $ratingLabel }} de 5 con {{ $reviewsCount }} opiniones">
                            <span class="home-featured-card__rating-star" aria-hidden="true">★</span>
                            <span class="home-featured-card__rating-score">{{ $ratingLabel }}</span>
                            <span class="home-featured-card__rating-count">({{ $reviewsCount }})</span>
                          </div>
                          @if ($signals->isNotEmpty())
                            <div class="home-featured-card__signals">
                              @foreach ($signals as $signal)
                                <span class="home-featured-card__signal">{{ $signal }}</span>
                              @endforeach
                            </div>
                          @endif
                          <div class="home-featured-card__price-row">
                            <span class="home-featured-card__price-inline">
                              <span class="home-featured-card__price-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                  <path d="M7 7.5h9.5a2.5 2.5 0 0 1 0 5H8.5a2.5 2.5 0 0 0 0 5H18"></path>
                                  <path d="M12 5v14"></path>
                                </svg>
                              </span>
                              <span class="home-featured-card__price-copy">
                                <span class="home-featured-card__price-prefix">Desde</span>
                                <span class="home-featured-card__price-amount">{{ $profile->base_price ? '$'.number_format((float) $profile->base_price, 0, ',', '.') : 'Cotizar' }}</span>
                              </span>
                            </span>
                          </div>
                        </div>
                      </article>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="home-city-panels">
              <div class="home-city-panel is-active">
                <div class="surface rounded-3xl p-6 md:p-8">
                  <h3 class="text-xl font-bold text-slate-900">Sin ciudades publicadas todavía</h3>
                  <p class="mt-2 text-sm text-slate-600">Este bloque se llenará automáticamente cuando existan anuncios completos con ciudad definida.</p>
                </div>
              </div>
            </div>
          @endif
        </div>
      </section>

      <section class="layout-shell--wide py-14" id="como-funciona">
        <div class="blog-preview" data-reveal>
          <div class="blog-preview__head">
            <div>
              <p class="blog-preview__eyebrow">Comunidad</p>
              <h2 class="blog-preview__title">Blog y recursos</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Ver blog completo</a>
          </div>
          @if($latestBlogPosts->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-3">
              @foreach($latestBlogPosts as $post)
                <article class="featured-promo-card">
                  @php
                    $eventLabel = $post->eventTypes->pluck('name')->take(2)->join(' · ');
                  @endphp
                  <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="featured-promo-media">
                    <img src="{{ $post->featured_image ? asset('storage/'.$post->featured_image) : asset('marketplace/assets/logo-wordmark.png') }}" alt="{{ $post->title }}" />
                    <span class="featured-promo-chip">{{ $post->primary_city_name ?: 'Colombia' }}</span>
                  </a>
                  <div class="featured-promo-body">
                    <p class="featured-promo-kicker">{{ $eventLabel ?: ($post->primary_event_type_name ?: 'Blog') }}</p>
                    <h3 class="featured-promo-title">{{ $post->title }}</h3>
                  </div>
                  <div class="featured-promo-footer">
                    <p class="featured-promo-meta">{{ $post->excerpt ?: 'Entrada publicada para alimentar SEO y recursos del marketplace.' }}</p>
                    <div class="featured-promo-bottom">
                      <strong>Artículo</strong>
                      <a href="{{ route('blog.show', ['slug' => $post->slug]) }}">Leer</a>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          @else
            <div class="surface rounded-3xl p-6 md:p-8">
              <h3 class="text-xl font-bold text-slate-900">Aún no hay publicaciones</h3>
              <p class="mt-2 text-sm text-slate-600">Cuando el equipo publique los primeros artículos, se mostrarán aquí automáticamente.</p>
            </div>
          @endif
        </div>
      </section>

      <section id="soy-mariachi" class="home-artist-cta relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_15%,rgba(244,63,94,0.32),transparent_35%),radial-gradient(circle_at_10%_80%,rgba(251,191,36,0.26),transparent_30%)]"></div>
        <div class="layout-shell--wide home-artist-cta__inner grid md:grid-cols-12">
          <div class="home-artist-cta__copy relative z-10 md:col-span-8" data-reveal>
            <h2 class="home-artist-cta__title">Convierte tu grupo en una marca visible en tu ciudad</h2>
          </div>
          <div class="home-artist-cta__action relative z-10 flex items-center md:col-span-4 md:justify-end" data-reveal>
            <a href="{{ route('mariachi.register') }}" target="_blank" rel="noopener" class="home-artist-cta__btn">
              <span>Publicar mi anuncio</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m13 5 7 7-7 7" />
              </svg>
            </a>
          </div>
        </div>
      </section>

      <section class="layout-shell--wide py-14">
        <div class="artist-seo-hub" data-reveal>
          <div class="artist-seo-hub__head">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Visibilidad orgánica</p>
              <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.01em] text-slate-900 md:text-4xl">Búsquedas populares con datos reales</h2>
              <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600">Este bloque se alimenta solo con ciudades y categorías que tengan perfiles publicados.</p>
            </div>
          </div>

          <div class="artist-seo-tabs" data-tabs>
            <div class="artist-seo-tabs__nav" role="tablist" aria-label="Búsquedas populares">
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-cities">Ciudades populares</button>
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-events">Eventos frecuentes</button>
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-price">Por presupuesto</button>
            </div>

            <div data-tab-panel="seo-cities" class="artist-seo-tabs__panel">
              <div class="artist-seo-chip-cloud">
                @forelse($popularCities as $city)
                  <a href="{{ route('seo.landing.slug', ['slug' => $city['slug']]) }}" class="artist-seo-chip">Mariachis en {{ $city['name'] }}</a>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavía</span>
                @endforelse
              </div>
            </div>

            <div data-tab-panel="seo-events" class="artist-seo-tabs__panel hidden">
              <div class="artist-seo-chip-cloud">
                @forelse($popularEvents->where('active_profiles_count', '>', 0) as $event)
                  <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($event->name)]) }}" class="artist-seo-chip">{{ $event->name }} ({{ $event->active_profiles_count }})</a>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavía</span>
                @endforelse
              </div>
            </div>

            <div data-tab-panel="seo-price" class="artist-seo-tabs__panel hidden">
              <div class="artist-seo-chip-cloud">
                @forelse($popularBudgetRanges->where('active_profiles_count', '>', 0) as $range)
                  <span class="artist-seo-chip">{{ $range->name }} ({{ $range->active_profiles_count }})</span>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavía</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="layout-shell--wide home-trustpilot-shell pb-16">
        @php
          $trustpilotReviewCount = (int) ($trustpilot['review_count'] ?? 0);
          $trustpilotScore = (float) ($trustpilot['trust_score'] ?? 0);
          $trustpilotReviews = collect($trustpilotReviews ?? [])->values();
        @endphp
        <div @class(['trustpilot-home', 'trustpilot-home--with-reviews' => $trustpilotReviews->isNotEmpty()]) data-reveal>
          <div class="trustpilot-home__summary" aria-label="Resumen de Trustpilot">
            <div class="trustpilot-home__brand">
              <span class="trustpilot-home__brand-mark" aria-hidden="true">★</span>
              <span class="trustpilot-home__brand-name">Trustpilot</span>
            </div>

            <div class="trustpilot-home__score">
              <strong>{{ number_format($trustpilotScore, 1) }}</strong>
              <span>de 5</span>
            </div>

            <div class="trustpilot-home__stars" aria-hidden="true">
              @for ($i = 0; $i < 5; $i++)
                <span class="trustpilot-home__star {{ $i < (int) floor($trustpilotScore) ? 'is-filled' : '' }}">★</span>
              @endfor
            </div>

            <p class="trustpilot-home__reviews">
              {{ number_format($trustpilotReviewCount) }} opinion(es) públicas por ahora
            </p>

            <div class="trustpilot-home__actions">
              <a href="{{ $trustpilot['profile_url'] }}" target="_blank" rel="noopener" class="trustpilot-home__primary">
                Ver perfil en Trustpilot
              </a>
            </div>
          </div>

          @if($trustpilotReviews->isNotEmpty())
            <div class="trustpilot-home__showcase" aria-label="Opiniones recientes de Trustpilot">
              @foreach($trustpilotReviews as $review)
              <article class="trustpilot-home__quote">
                @if(filled($review['published_label'] ?? ''))
                  <p class="trustpilot-home__quote-time">{{ $review['published_label'] }}</p>
                @endif
                <div class="trustpilot-home__quote-stars" aria-hidden="true">
                  @for ($i = 0; $i < 5; $i++)
                    <span class="trustpilot-home__quote-star {{ $i < (int) ($review['stars'] ?? 0) ? 'is-filled' : '' }}">★</span>
                  @endfor
                </div>
                @if(filled($review['title'] ?? ''))
                  <h3 class="trustpilot-home__quote-title">{{ $review['title'] }}</h3>
                @endif
                @if(filled($review['excerpt'] ?? ''))
                  <p class="trustpilot-home__quote-body">{{ \Illuminate\Support\Str::limit($review['excerpt'], 150) }}</p>
                @endif
                @if(filled($review['author'] ?? ''))
                  <p class="trustpilot-home__quote-author">{{ $review['author'] }}</p>
                @endif
              </article>
              @endforeach
            </div>
          @endif
        </div>
      </section>
    </main>
@endsection
