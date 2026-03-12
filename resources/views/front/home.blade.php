@extends('front.layouts.marketplace')

@section('title', 'Mariachis.co | Marketplace de mariachis en Colombia')
@section('meta_description', 'Encuentra mariachis por ciudad, compara perfiles y contacta por WhatsApp o llamada.')
@section('body_page', 'home')

@section('content')
    <main>
      <section class="hero-split-shell hero-split-shell--flush hero-split-shell--home">
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
              <img src="img/2.webp" alt="Mariachis en vivo durante un evento" class="hero-split-home-media__image" />
              <div class="hero-split-home-media__veil" aria-hidden="true"></div>
            </div>
          </div>
        </div>
        <div class="hero-home-search-bridge">
          <div class="mx-auto w-full max-w-7xl px-4 md:px-8">
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

      <section id="categorias" class="layout-shell py-14">
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
              @forelse($eventTypes as $item)
                <a href="{{ route('seo.landing.slug', ['slug' => $item->slug ?: \Illuminate\Support\Str::slug($item->name)]) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="servicio">
            <div class="categories-chip-cloud">
              @forelse($serviceTypes as $item)
                <a href="{{ route('seo.landing.slug', ['slug' => $countryLandingSlug]) }}?service={{ urlencode($item->slug ?: \Illuminate\Support\Str::slug($item->name)) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="grupo">
            <div class="categories-chip-cloud">
              @forelse($groupSizeOptions as $item)
                <a href="{{ route('seo.landing.slug', ['slug' => $countryLandingSlug]) }}?group={{ urlencode($item->slug ?: \Illuminate\Support\Str::slug($item->name)) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>

          <div class="categories-explorer-panel hidden" data-tab-panel="presupuesto">
            <div class="categories-chip-cloud">
              @forelse($budgetRanges as $item)
                <a href="{{ route('seo.landing.slug', ['slug' => $countryLandingSlug]) }}?budget={{ urlencode($item->slug ?: \Illuminate\Support\Str::slug($item->name)) }}" class="category-cloud-link"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></a>
              @empty
                <span class="category-cloud-link opacity-70"><span>⏳</span><span>Próximamente</span></span>
              @endforelse
            </div>
          </div>
        </div>
      </section>

      <section class="layout-shell py-14">
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
              @endphp

              <article
                data-filter-card="home-featured"
                data-card-tags="{{ $tags }}"
                data-favorite-id="home-{{ $profile->id }}"
                data-card-url="{{ $detailUrl }}"
                role="link"
                tabindex="0"
                aria-label="Abrir anuncio de {{ $name }}"
                class="featured-promo-card featured-promo-card--listing is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}"
              >
                <a href="{{ $detailUrl }}" class="featured-promo-media">
                  <img src="{{ $photoUrl }}" alt="{{ $name }}" />
                  @if($isVip)
                    <span class="featured-promo-ribbon">{{ $profile->marketplaceBadgeLabel() }}</span>
                  @endif
                  <span class="featured-promo-chip">Perfil completo</span>
                  <span class="featured-promo-score">{{ $profile->profile_completion }}%</span>
                </a>
                <button data-favorite="home-{{ $profile->id }}" class="favorite-btn featured-favorite-btn" aria-label="Guardar en favoritos" aria-pressed="false">
                  <svg data-fav-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </button>
                <div class="featured-promo-body">
                  <p class="featured-promo-kicker">{{ $city }}</p>
                  <h3 class="featured-promo-title">{{ $profile->short_description ?: 'Perfil de mariachi disponible para eventos en tu ciudad.' }}</h3>
                </div>
                <div class="featured-promo-footer">
                  <p class="featured-promo-artist">{{ $name }}</p>
                  <p class="featured-promo-meta">{{ $profile->state ?: $profile->country ?: 'Colombia' }}</p>
                  <div class="featured-promo-bottom">
                    <strong>{{ $profile->base_price ? 'Desde $'.number_format((float) $profile->base_price, 0, ',', '.') : 'Precio por cotizar' }}</strong>
                  </div>
                </div>
              </article>
            @empty
              <article class="featured-promo-card">
                <div class="featured-promo-body">
                  <p class="featured-promo-kicker">Sin anuncios aún</p>
                  <h3 class="featured-promo-title">Aún no hay perfiles completos para mostrar en destacados.</h3>
                </div>
                <div class="featured-promo-footer">
                  <p class="featured-promo-meta">Cuando el primer mariachi complete su anuncio, aparecerá automáticamente aquí.</p>
                </div>
              </article>
            @endforelse
          </div>
        </div>
      </section>

      <section id="ciudades" class="layout-shell pt-10 pb-10">
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

      <section class="layout-shell pb-14">
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
                      @endphp
                      <article
                        data-card-url="{{ $detailUrl }}"
                        role="link"
                        tabindex="0"
                        aria-label="Abrir anuncio de {{ $name }}"
                        class="featured-promo-card featured-promo-card--listing is-clickable-card {{ $isVip ? 'featured-promo-card--vip' : '' }}"
                      >
                        <a href="{{ $detailUrl }}" class="featured-promo-media">
                          <img src="{{ $photoUrl }}" alt="{{ $name }}" />
                          @if($isVip)
                            <span class="featured-promo-ribbon">{{ $profile->marketplaceBadgeLabel() }}</span>
                          @endif
                          <span class="featured-promo-chip">{{ $city['city'] }}</span>
                          <span class="featured-promo-score">{{ $profile->profile_completion }}%</span>
                        </a>
                        <div class="featured-promo-body">
                          <p class="featured-promo-kicker">{{ $profile->state ?: 'Colombia' }}</p>
                          <h3 class="featured-promo-title">{{ $profile->short_description ?: 'Perfil disponible para cotización.' }}</h3>
                        </div>
                        <div class="featured-promo-footer">
                          <p class="featured-promo-artist">{{ $name }}</p>
                          <div class="featured-promo-bottom">
                            <strong>{{ $profile->base_price ? 'Desde $'.number_format((float) $profile->base_price, 0, ',', '.') : 'Precio por cotizar' }}</strong>
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

      <section class="layout-shell py-14" id="como-funciona">
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
        <div class="layout-shell home-artist-cta__inner grid md:grid-cols-12">
          <div class="home-artist-cta__copy relative z-10 md:col-span-8" data-reveal>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-200">CTA para artistas</p>
            <h2 class="home-artist-cta__title mt-3">Convierte tu grupo en una marca visible en tu ciudad</h2>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-200">Completa tu perfil y aparecerás automáticamente en los bloques públicos cuando el anuncio esté listo.</p>
          </div>
          <div class="home-artist-cta__action relative z-10 flex items-center md:col-span-4 md:justify-end" data-reveal>
            <a href="{{ route('mariachi.register') }}" target="_blank" rel="noopener" class="home-artist-cta__btn inline-flex rounded-xl bg-brand-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-600">Quiero publicar mi anuncio</a>
          </div>
        </div>
      </section>

      <section class="layout-shell py-14">
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

      <section class="layout-shell home-trustpilot-shell pb-16">
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
