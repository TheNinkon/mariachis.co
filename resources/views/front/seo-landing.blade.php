@extends('front.layouts.marketplace')

@section('title', $seoTitle . ' | Mariachis.co')
@section('meta_description', $seoDescription)
@section('body_page', 'city')
@section('body_class', 'font-sans text-slate-900 antialiased city-viator')

@section('content')
    @php
      $isCountry = $mode === 'country';
      $isZone = $mode === 'zone';
      $isCity = in_array($mode, ['city', 'city_category', 'zone'], true);
      $countryLabel = $countryName ?: 'Colombia';
      $countrySlugValue = $countrySlug ?: \Illuminate\Support\Str::slug($countryLabel);
      $countryLandingUrl = route('seo.landing.slug', ['slug' => $countrySlugValue]);
      $citySlugValue = $citySlug ?: ($cityName ? \Illuminate\Support\Str::slug($cityName) : null);

      $heroContextLabel = $isZone
        ? 'Zona local'
        : ($isCountry ? 'Pais' : ($isCity ? 'Ciudad' : 'Categoria'));

      $heroContextName = $isZone
        ? ($zoneName ? $zoneName.', '.$cityName : $cityName)
        : ($cityName ?: ($eventType?->name ?: $countryLabel));

      $scopeLabel = $zoneName ?: ($cityName ?: ($eventType?->name ?: $countryLabel));

      $currentPath = request()->url();
      $resultsDisplay = $totalResults >= 600 ? '600+' : number_format($totalResults, 0, ',', '.');
      $activeFilterCount = collect([
        $selectedFilters['event'] ?? null,
        $selectedFilters['city'] ?? null,
        $selectedFilters['service'] ?? null,
        $selectedFilters['budget'] ?? null,
      ])->filter()->count();
      $showCityFilter = ! $isCity && ! $isZone && $filterOptions['cities']->isNotEmpty();
    @endphp

    <main class="city-hero">
      <section class="city-results-shell city-results-shell--viator pt-4 pb-10 md:pt-5">
        <nav class="city-breadcrumbs" aria-label="Migas de pan" data-reveal>
          <a href="{{ route('home') }}">Inicio</a>
          <span class="city-breadcrumb-sep">/</span>
          <a href="{{ $countryLandingUrl }}">Mariachis en {{ $countryLabel }}</a>
          @if($cityName && $citySlugValue)
            <span class="city-breadcrumb-sep">/</span>
            <a href="{{ route('seo.landing.slug', ['slug' => $citySlugValue]) }}">Mariachis en {{ $cityName }}</a>
          @endif
          @if($zoneName)
            <span class="city-breadcrumb-sep">/</span>
            <span>{{ $zoneName }}</span>
          @elseif($eventType)
            <span class="city-breadcrumb-sep">/</span>
            <span>{{ $eventType->name }}</span>
          @endif
        </nav>

        <div class="city-results-intro" data-reveal>
          <div>
            <h1>{{ $h1 }}</h1>
            @if(!empty($subtitle))
              <p class="city-results-subtitle">{{ $subtitle }}</p>
            @endif
          </div>
        </div>

        <div class="city-filters-sentinel" data-city-filters-sentinel aria-hidden="true"></div>
        <div class="city-sticky-filters" data-reveal data-city-sticky-filters>
          <form method="GET" action="{{ $currentPath }}" class="city-controls-form">
            <div class="city-control">
              <label for="city-filter-event">Categoria</label>
              <select id="city-filter-event" name="event">
                <option value="">Todas</option>
                @foreach($filterOptions['events']->take(30) as $eventOption)
                  <option value="{{ $eventOption['slug'] }}" @selected($selectedFilters['event'] === $eventOption['slug'])>
                    {{ $eventOption['name'] }} ({{ $eventOption['count'] }})
                  </option>
                @endforeach
              </select>
            </div>

            @if($showCityFilter)
              <div class="city-control">
                <label for="city-filter-city">Ciudad</label>
                <select id="city-filter-city" name="city">
                  <option value="">Todas</option>
                  @foreach($filterOptions['cities']->take(30) as $cityOption)
                    <option value="{{ $cityOption['slug'] }}" @selected($selectedFilters['city'] === $cityOption['slug'])>
                      {{ $cityOption['name'] }} ({{ $cityOption['count'] }})
                    </option>
                  @endforeach
                </select>
              </div>
            @endif

            <div class="city-control">
              <label for="city-filter-service">Tipo</label>
              <select id="city-filter-service" name="service">
                <option value="">Todos</option>
                @foreach($filterOptions['services']->take(30) as $serviceOption)
                  <option value="{{ $serviceOption['slug'] }}" @selected($selectedFilters['service'] === $serviceOption['slug'])>
                    {{ $serviceOption['name'] }} ({{ $serviceOption['count'] }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="city-control">
              <label for="city-filter-budget">Precio</label>
              <select id="city-filter-budget" name="budget">
                <option value="">Todos</option>
                @foreach($filterOptions['budgets']->take(30) as $budgetOption)
                  <option value="{{ $budgetOption['slug'] }}" @selected($selectedFilters['budget'] === $budgetOption['slug'])>
                    {{ $budgetOption['name'] }} ({{ $budgetOption['count'] }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="city-controls-actions">
              <button type="submit" class="city-controls-submit">Aplicar</button>
              <a href="{{ $currentPath }}" class="city-reset-link">Limpiar</a>
            </div>
          </form>
        </div>

        <div class="city-controls-footer" data-reveal>
          <div class="city-controls-footer__meta">
            <p class="city-results-count">{{ $resultsDisplay }} resultados</p>
            <p class="city-results-note">Los ingresos pueden influir en este orden de clasificacion.</p>
          </div>

          <form method="GET" action="{{ $currentPath }}" class="city-sort-form">
            @if($selectedFilters['event'])
              <input type="hidden" name="event" value="{{ $selectedFilters['event'] }}">
            @endif
            @if($selectedFilters['city'])
              <input type="hidden" name="city" value="{{ $selectedFilters['city'] }}">
            @endif
            @if($selectedFilters['service'])
              <input type="hidden" name="service" value="{{ $selectedFilters['service'] }}">
            @endif
            @if($selectedFilters['budget'])
              <input type="hidden" name="budget" value="{{ $selectedFilters['budget'] }}">
            @endif

            <label for="city-sort">Ordenar por:</label>
            <select id="city-sort" name="sort" onchange="this.form.submit()">
              @foreach($sortOptions as $sortValue => $sortLabel)
                <option value="{{ $sortValue }}" @selected($selectedSort === $sortValue)>{{ $sortLabel }}</option>
              @endforeach
            </select>
          </form>
        </div>

        <div data-results-panel class="city-results-panel city-results-panel--viator" data-view-mode="gallery" data-reveal>
          <div data-results-cards>
            <div class="city-results-grid city-results-grid--viator" data-results-grid>
              @forelse($profiles as $profile)
                @php
                  $profileName = $profile->business_name ?: $profile->user?->display_name;
                  $photo = $profile->photos->firstWhere('is_featured', true) ?? $profile->photos->first();
                  $photoUrl = $photo ? asset('storage/'.$photo->path) : asset('marketplace/assets/logo-wordmark.png');
                  $profileUrl = route('mariachi.public.show', ['slug' => $profile->slug]);
                  $coverage = $profile->serviceAreas->pluck('city_name')->take(2)->join(' · ');
                  $priceLabel = $profile->base_price
                    ? 'Desde $'.number_format((float) $profile->base_price, 0, ',', '.')
                    : 'Cotizacion directa';
                  $isVip = $profile->hasPremiumMarketplaceBadge();
                @endphp

                <article
                  class="city-result-card city-result-card--viator {{ $isVip ? 'city-result-card--vip' : '' }}"
                  data-favorite-id="city-{{ $profile->id }}"
                  data-compare-id="{{ $profile->id }}"
                  data-card-url="{{ $profileUrl }}"
                  role="link"
                  tabindex="0"
                  aria-label="Abrir anuncio de {{ $profileName }}"
                >
                  <a href="{{ $profileUrl }}" class="city-result-media city-result-media--viator">
                    <img src="{{ $photoUrl }}" alt="{{ $profileName }}" class="h-full w-full object-cover" />
                    @if($isVip)
                      <span class="city-result-ribbon">{{ $profile->marketplaceBadgeLabel() }}</span>
                    @endif
                    <span class="city-result-badge">{{ $profile->city_name ?: 'Colombia' }}</span>
                  </a>

                  <button data-favorite="city-{{ $profile->id }}" class="favorite-btn city-result-favorite" aria-label="Guardar en favoritos" aria-pressed="false">
                    <svg data-fav-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                  </button>

                  <div class="city-result-main city-result-main--viator">
                    <div class="city-result-topline">
                      <span class="city-result-chip">Disponible</span>
                      <span class="city-result-rating">{{ $profile->profile_completion }}% perfil completo</span>
                    </div>

                    <h3><a href="{{ $profileUrl }}" class="hover:underline">{{ $profileName }}</a></h3>
                    <p class="city-result-description">{{ $profile->short_description ?: 'Mariachi disponible para serenatas, bodas y eventos privados.' }}</p>
                    <p class="city-result-coverage">{{ $coverage ?: ($profile->city_name ?: 'Cobertura nacional') }}</p>

                    <div class="city-result-meta">
                      @forelse($profile->eventTypes->take(2) as $eventTypeItem)
                        <span>{{ $eventTypeItem->name }}</span>
                      @empty
                        <span>Eventos varios</span>
                      @endforelse
                    </div>

                    <div class="city-result-bottom city-result-bottom--viator">
                      <div class="city-result-price-wrap">
                        <span class="city-result-price-label">Precio base</span>
                        <p class="city-result-price">{{ $priceLabel }}</p>
                      </div>
                    </div>
                  </div>
                </article>
              @empty
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                  No encontramos anuncios en este momento para {{ $heroContextName ?: 'esta zona' }}.
                </div>
              @endforelse
            </div>

            @if($profiles->hasPages())
              <div class="mt-6 border-t border-slate-200 pt-4">
                {{ $profiles->onEachSide(1)->links() }}
              </div>
            @endif
          </div>
        </div>
      </section>

      @if($isCountry)
        <section class="layout-shell pb-10" data-reveal>
          <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
            <div class="mb-4">
              <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Exploracion nacional</p>
              <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Ciudades destacadas en {{ $countryLabel }}</h2>
              <p class="mt-1 text-sm text-slate-600">Enlaces internos por ciudad para facilitar navegacion y cobertura SEO local.</p>
            </div>

            @if($featuredCountryCities->isNotEmpty())
              <div class="artist-seo-chip-cloud">
                @foreach($featuredCountryCities as $city)
                  <a href="{{ route('seo.landing.slug', ['slug' => $city['slug']]) }}" class="artist-seo-chip">Mariachis en {{ $city['name'] }} ({{ $city['count'] }})</a>
                @endforeach
              </div>
            @else
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                Aun no hay suficientes ciudades publicadas para destacar.
              </div>
            @endif
          </div>
        </section>

        <section class="layout-shell pb-10" data-reveal>
          <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
            <div class="mb-4">
              <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Cobertura por ciudad</p>
              <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Nuestras ciudades en {{ $countryLabel }}</h2>
              <p class="mt-1 text-sm text-slate-600">Indice alfabetico generado automaticamente desde los anuncios activos.</p>
            </div>

            @if($countryCitiesByLetter->isNotEmpty())
              <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($countryCitiesByLetter as $letter => $cities)
                  <article class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.12em] text-brand-700">{{ $letter }}</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                      @foreach($cities as $city)
                        <a href="{{ route('seo.landing.slug', ['slug' => $city['slug']]) }}" class="artist-seo-chip">{{ $city['name'] }}</a>
                      @endforeach
                    </div>
                  </article>
                @endforeach
              </div>
            @else
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                No hay ciudades disponibles para construir el indice.
              </div>
            @endif
          </div>
        </section>
      @elseif($nearbyZones->isNotEmpty() && $citySlugValue)
        <section class="layout-shell pb-10" data-reveal>
          <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Exploracion local</p>
                <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Otras zonas cercanas</h2>
              </div>
              @if($citySlugValue)
                <a href="{{ route('seo.landing.slug', ['slug' => $citySlugValue]) }}" class="text-sm font-bold text-brand-700 hover:text-brand-600">Ver toda {{ $cityName }}</a>
              @endif
            </div>

            <div class="artist-seo-chip-cloud">
              @foreach($nearbyZones as $zone)
                <a href="{{ route('seo.landing.city-category', ['citySlug' => $citySlugValue, 'scopeSlug' => $zone['slug']]) }}" class="artist-seo-chip">{{ $zone['name'] }} ({{ $zone['count'] }})</a>
              @endforeach
            </div>
          </div>
        </section>
      @endif

      <section class="layout-shell pb-10" data-reveal>
        <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
          <div class="mb-4">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Confianza local</p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Opiniones en {{ $scopeLabel }}</h2>
            <p class="mt-1 text-sm text-slate-600">Resenas publicadas despues de moderacion, con respuesta del proveedor cuando aplica.</p>
          </div>

          @if($cityReviews->isNotEmpty())
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
              @foreach($cityReviews as $review)
                @php
                  $reviewMariachiName = $review->mariachiListing?->title
                    ?: $review->mariachiProfile?->business_name
                    ?: $review->mariachiProfile?->user?->display_name;
                  $defaultReviewListing = $review->mariachiProfile?->resolveDefaultListing();
                  $reviewProfileUrl = $review->mariachiListing?->slug
                    ? route('mariachi.public.show', ['slug' => $review->mariachiListing->slug])
                    : ($defaultReviewListing?->isApprovedForMarketplace() && $defaultReviewListing?->slug
                      ? route('mariachi.public.show', ['slug' => $defaultReviewListing->slug])
                      : null);
                @endphp
                <article class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <p class="text-sm font-extrabold text-slate-900">{{ $review->clientUser?->display_name ?: 'Cliente' }}</p>
                      <p class="text-xs text-slate-500">{{ $review->created_at->format('Y-m-d') }}</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">{{ $review->rating }}/5 · {{ str_repeat('★', $review->rating) }}</span>
                  </div>

                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700">
                      {{ $review->verification_label }}
                    </span>
                    @if($review->event_type)
                      <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $review->event_type }}</span>
                    @endif
                    @if($review->event_date)
                      <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600">Evento: {{ $review->event_date->format('Y-m-d') }}</span>
                    @endif
                  </div>

                  @if($review->title)
                    <p class="mt-2 text-sm font-bold text-slate-800">{{ $review->title }}</p>
                  @endif
                  <p class="mt-2 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($review->comment, 180) }}</p>

                  @if($review->photos->isNotEmpty())
                    <div class="mt-2 grid grid-cols-3 gap-2">
                      @foreach($review->photos->take(3) as $photo)
                        <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-md border border-slate-200">
                          <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto de resena" class="h-16 w-full object-cover" loading="lazy" />
                        </a>
                      @endforeach
                    </div>
                  @endif

                  <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">{{ $reviewMariachiName ?: 'Mariachi' }}</p>
                    @if($reviewProfileUrl)
                      <a href="{{ $reviewProfileUrl }}" class="text-xs font-bold text-brand-700 hover:text-brand-600">Abrir perfil</a>
                    @endif
                  </div>

                  @if($review->mariachi_reply && $review->mariachi_reply_visible)
                    <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                      <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500">Respuesta del mariachi</p>
                      <p class="mt-1 text-xs text-slate-700">{{ \Illuminate\Support\Str::limit($review->mariachi_reply, 120) }}</p>
                    </div>
                  @endif
                </article>
              @endforeach
            </div>
          @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-sm font-bold text-slate-900">Aun no hay opiniones publicas en este contexto</p>
              <p class="mt-1 text-sm text-slate-600">Las reseñas apareceran aqui cuando sean aprobadas por moderacion.</p>
            </div>
          @endif
        </div>
      </section>

      <section class="layout-shell pb-10" data-reveal data-listing-recents-shell data-resolve-url="{{ route('public.listings.resolve') }}" data-current-listing-id="0" data-account-url="{{ route('public.collections.recents') }}" data-has-server-items="{{ $recentViews->isNotEmpty() ? 'true' : 'false' }}">
        <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
          <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Personalizacion</p>
              <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Vistos recientemente</h2>
            </div>
            <a href="{{ route('public.collections.recents') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Abrir historial</a>
          </div>

          @if($recentViews->isNotEmpty())
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
              @foreach($recentViews as $recentView)
                @php
                  $recentListing = $recentView->mariachiListing ?: $recentView->mariachiProfile?->resolveDefaultListing();
                  $recentName = $recentListing?->business_name ?: $recentListing?->user?->display_name;
                  $recentPhoto = $recentListing?->photos?->firstWhere('is_featured', true) ?? $recentListing?->photos?->first();
                  $recentPhotoUrl = $recentPhoto ? asset('storage/'.$recentPhoto->path) : asset('marketplace/assets/logo-wordmark.png');
                  $recentEvents = $recentListing?->eventTypes?->pluck('name')->take(2)->join(' · ');
                @endphp
                @if($recentListing?->slug)
                  <article class="featured-promo-card featured-promo-card--listing is-clickable-card">
                    <a class="featured-promo-media" href="{{ route('mariachi.public.show', ['slug' => $recentListing->slug]) }}">
                      <img src="{{ $recentPhotoUrl }}" alt="{{ $recentName }}" loading="lazy" />
                      <span class="featured-promo-chip">{{ $recentListing->city_name }}</span>
                      <span class="featured-promo-score">{{ $recentListing->profile_completion }}%</span>
                    </a>
                    <div class="featured-promo-body">
                      <p class="featured-promo-kicker">{{ $recentEvents ?: 'Disponible para eventos' }}</p>
                      <h3 class="featured-promo-title">{{ $recentListing->short_description ?: 'Perfil listo para cotizar y comparar.' }}</h3>
                    </div>
                    <div class="featured-promo-footer">
                      <p class="featured-promo-artist">{{ $recentName }}</p>
                      <div class="featured-promo-bottom">
                        <strong>{{ $recentListing->base_price ? 'Desde $'.number_format((float) $recentListing->base_price, 0, ',', '.') : 'Cotizacion directa' }}</strong>
                        <a href="{{ route('mariachi.public.show', ['slug' => $recentListing->slug]) }}">Ver anuncio</a>
                      </div>
                    </div>
                  </article>
                  @endif
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
        </div>
      </section>

      <section class="layout-shell pb-10" data-reveal>
        <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
          <div class="mb-4">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Ayuda rapida</p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Preguntas frecuentes</h2>
          </div>

          <div data-accordion class="space-y-3">
            @foreach($faqItems as $index => $faq)
              <div data-accordion-item class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <button data-accordion-trigger aria-expanded="false" aria-controls="city-faq-{{ $index }}" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-bold text-slate-900" type="button">
                  {{ $faq['question'] }}
                  <span data-accordion-icon>+</span>
                </button>
                <div id="city-faq-{{ $index }}" class="hidden border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                  {{ $faq['answer'] }}
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>

      <section class="layout-shell pb-10" data-reveal>
        <div class="artist-seo-hub">
          <div class="artist-seo-hub__head">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Visibilidad orgánica</p>
              <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.01em] text-slate-900 md:text-4xl">Busquedas populares con datos reales</h2>
              <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600">Bloque reutilizado del home para conectar ciudades, eventos y presupuesto con enlaces internos.</p>
            </div>
          </div>

          <div class="artist-seo-tabs" data-tabs>
            <div class="artist-seo-tabs__nav" role="tablist" aria-label="Busquedas populares">
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-cities">Ciudades populares</button>
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-events">Eventos frecuentes</button>
              <button type="button" class="artist-seo-tab tab-idle" data-tab-target="seo-price">Por presupuesto</button>
            </div>

            <div data-tab-panel="seo-cities" class="artist-seo-tabs__panel">
              <div class="artist-seo-chip-cloud">
                @forelse($popularCities as $city)
                  <a href="{{ route('seo.landing.slug', ['slug' => $city['slug']]) }}" class="artist-seo-chip">Mariachis en {{ $city['name'] }}</a>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavia</span>
                @endforelse
              </div>
            </div>

            <div data-tab-panel="seo-events" class="artist-seo-tabs__panel hidden">
              <div class="artist-seo-chip-cloud">
                @forelse($popularEvents->where('active_profiles_count', '>', 0) as $event)
                  <a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($event->name)]) }}" class="artist-seo-chip">{{ $event->name }} ({{ $event->active_profiles_count }})</a>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavia</span>
                @endforelse
              </div>
            </div>

            <div data-tab-panel="seo-price" class="artist-seo-tabs__panel hidden">
              <div class="artist-seo-chip-cloud">
                @forelse($popularBudgetRanges->where('active_profiles_count', '>', 0) as $range)
                  <span class="artist-seo-chip">{{ $range->name }} ({{ $range->active_profiles_count }})</span>
                @empty
                  <span class="artist-seo-chip opacity-70">Sin datos suficientes todavia</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="layout-shell pb-14" data-reveal>
        <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
          <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Contenido editorial</p>
              <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Blog filtrado por {{ $scopeLabel }}</h2>
              <p class="mt-1 text-sm text-slate-600">Articulos relacionados con ciudad, zona o tipo de evento para reforzar SEO long tail.</p>
            </div>
            <a href="{{ route('blog.index') }}" class="text-sm font-bold text-brand-700 hover:text-brand-600">Ver blog completo</a>
          </div>

          @if($relatedBlogPosts->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              @foreach($relatedBlogPosts as $post)
                @php
                  $cityLabel = $post->primary_city_name ?: ($cityName ?: 'Colombia');
                  $zoneLabel = $post->primary_zone_name;
                  $eventLabel = $post->eventTypes->pluck('name')->take(2)->join(' · ') ?: ($post->primary_event_type_name ?: 'Blog');
                @endphp
                <article class="featured-promo-card">
                  <a href="{{ route('blog.show', ['slug' => $post->slug]) }}" class="featured-promo-media">
                    <img src="{{ $post->featured_image ? asset('storage/'.$post->featured_image) : asset('marketplace/assets/logo-wordmark.png') }}" alt="{{ $post->title }}" />
                    <span class="featured-promo-chip">{{ $cityLabel }}</span>
                  </a>
                  <div class="featured-promo-body">
                    <p class="featured-promo-kicker">{{ $eventLabel }}{{ $zoneLabel ? ' · '.$zoneLabel : '' }}</p>
                    <h3 class="featured-promo-title">{{ $post->title }}</h3>
                  </div>
                  <div class="featured-promo-footer">
                    <p class="featured-promo-meta">{{ $post->excerpt ?: 'Recurso local para planear y contratar mariachis con mejor contexto.' }}</p>
                    <div class="featured-promo-bottom">
                      <strong>Articulo</strong>
                      <a href="{{ route('blog.show', ['slug' => $post->slug]) }}">Leer</a>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
              <p class="text-sm font-bold text-slate-900">Aun no hay articulos para este contexto</p>
              <p class="mt-1 text-sm text-slate-600">Cuando publiquemos contenidos locales como "Los mejores mariachis para bodas en {{ $scopeLabel }}", apareceran aqui.</p>
            </div>
          @endif
        </div>
      </section>
    </main>

@endsection

@push('scripts')
  <script src="js/public-listing-collections.js?v=20260311-listing-collections-v1"></script>
@endpush
