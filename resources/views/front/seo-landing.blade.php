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
  <body data-page="city" class="font-sans text-slate-900 antialiased city-viator">
    <style>
      body.city-viator .city-results-shell--viator {
        width: 100%;
        max-width: none;
        margin: 0;
        padding-inline: clamp(0.9rem, 2.1vw, 2rem);
      }

      body.city-viator .city-results-intro {
        display: grid;
        gap: 0.7rem;
        margin-bottom: 1rem;
      }

      body.city-viator .city-results-intro h1 {
        font-size: clamp(1.55rem, 2.4vw, 2.2rem);
        line-height: 1.18;
        font-weight: 800;
        color: #0f172a;
      }

      body.city-viator .city-results-kicker {
        font-size: 0.73rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
      }

      body.city-viator .city-results-subtitle {
        max-width: 76ch;
        font-size: 0.92rem;
        line-height: 1.45;
        color: #334155;
      }

      body.city-viator .city-breadcrumbs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.42rem;
        margin-bottom: 0.7rem;
        font-size: 0.74rem;
        font-weight: 600;
        color: #64748b;
      }

      body.city-viator .city-breadcrumbs a {
        color: #475569;
        text-decoration: none;
      }

      body.city-viator .city-breadcrumbs a:hover {
        color: #0f172a;
        text-decoration: underline;
      }

      body.city-viator .city-breadcrumb-sep {
        color: #94a3b8;
        font-size: 0.68rem;
      }

      body.city-viator .city-results-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
      }

      body.city-viator .city-results-stats span {
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 999px;
        background: #ffffff;
        padding: 0.34rem 0.66rem;
        font-size: 0.71rem;
        font-weight: 700;
        color: #334155;
      }

      body.city-viator .city-results-panel[data-view-mode="gallery"] .city-results-grid--viator {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.9rem;
      }

      body.city-viator .city-result-card--viator {
        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: #fff;
        box-shadow: 0 14px 30px -26px rgba(15, 23, 42, 0.55);
        padding: 0;
        will-change: transform, box-shadow;
        transition:
          transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
          box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
      }

      @media (hover: hover) {
        body.city-viator .city-result-card--viator:hover {
          transform: translate3d(0, -6px, 0) scale(1.01);
          box-shadow: 0 24px 46px -24px rgba(15, 23, 42, 0.45);
        }
      }

      body.city-viator .city-result-card--viator:active {
        transform: translate3d(0, -2px, 0) scale(1.005);
      }

      body.city-viator .city-result-media--viator {
        height: auto;
        min-height: 0;
        aspect-ratio: 16 / 10;
        border-radius: 0;
      }

      body.city-viator .city-result-favorite {
        position: absolute;
        top: 0.6rem;
        right: 0.6rem;
        z-index: 5;
        border-color: rgba(15, 23, 42, 0.12);
        backdrop-filter: blur(3px);
        background: rgba(255, 255, 255, 0.92);
      }

      body.city-viator .city-result-main--viator {
        display: grid;
        gap: 0.52rem;
        padding: 0.78rem 0.82rem 0.82rem;
      }

      body.city-viator .city-result-main--viator h3 a {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 0.98rem;
        font-weight: 800;
        line-height: 1.3;
        color: #0f172a;
      }

      body.city-viator .city-result-topline {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
      }

      body.city-viator .city-result-chip {
        border-radius: 999px;
        background: #ecfdf5;
        color: #065f46;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.2rem 0.45rem;
      }

      body.city-viator .city-result-topline .city-result-rating {
        font-size: 0.71rem;
        font-weight: 700;
        color: #64748b;
      }

      body.city-viator .city-result-main--viator .city-result-description {
        font-size: 0.82rem;
        line-height: 1.42;
        color: #334155;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      body.city-viator .city-result-coverage {
        font-size: 0.74rem;
        color: #475569;
      }

      body.city-viator .city-result-bottom--viator {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 0.55rem;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        padding-top: 0.54rem;
      }

      body.city-viator .city-result-price-wrap {
        display: grid;
      }

      body.city-viator .city-result-price-label {
        font-size: 0.64rem;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #64748b;
      }

      body.city-viator .city-result-bottom--viator .city-result-price {
        margin: 0;
        font-size: 1rem;
        font-weight: 900;
        color: #0f172a;
      }

      body.city-viator .city-result-cta--wide {
        width: auto;
        margin-top: 0;
        white-space: nowrap;
      }

      body.city-viator .city-controls-form {
        display: grid;
        gap: 0.58rem;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 0.95rem;
        background: #ffffff;
        padding: 0.7rem;
        margin-bottom: 0.72rem;
      }

      body.city-viator .city-control {
        display: grid;
        gap: 0.24rem;
      }

      body.city-viator .city-control label {
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
      }

      body.city-viator .city-control select {
        width: 100%;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 0.65rem;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.56rem 0.62rem;
      }

      body.city-viator .city-control select:focus {
        outline: none;
        border-color: #0f766e;
        box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.12);
      }

      body.city-viator .city-controls-actions {
        display: flex;
        gap: 0.45rem;
        align-items: end;
      }

      body.city-viator .city-controls-submit {
        border: 1px solid #115e59;
        border-radius: 0.62rem;
        background: #115e59;
        color: #ffffff;
        font-size: 0.8rem;
        font-weight: 800;
        padding: 0.55rem 0.8rem;
      }

      body.city-viator .city-controls-submit:hover {
        background: #0f766e;
        border-color: #0f766e;
      }

      body.city-viator .city-reset-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 0.62rem;
        background: #ffffff;
        color: #334155;
        font-size: 0.79rem;
        font-weight: 700;
        padding: 0.55rem 0.75rem;
        text-decoration: none;
      }

      body.city-viator .city-reset-link:hover {
        border-color: rgba(15, 23, 42, 0.26);
      }

      body.city-viator .city-controls-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.55rem;
        margin-bottom: 0.8rem;
      }

      body.city-viator .city-results-count {
        font-size: 0.78rem;
        font-weight: 700;
        color: #334155;
      }

      body.city-viator .city-results-note {
        font-size: 0.74rem;
        color: #64748b;
      }

      @media (min-width: 640px) {
        body.city-viator .city-results-panel[data-view-mode="gallery"] .city-results-grid--viator {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
      }

      @media (min-width: 900px) {
        body.city-viator .city-controls-form {
          grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
          align-items: end;
        }

        body.city-viator .city-controls-actions {
          justify-content: flex-end;
        }
      }

      @media (min-width: 1024px) {
        body.city-viator .city-results-panel[data-view-mode="gallery"] .city-results-grid--viator {
          grid-template-columns: repeat(4, minmax(0, 1fr));
        }
      }
    </style>
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
        $selectedFilters['service'] ?? null,
        $selectedFilters['budget'] ?? null,
      ])->filter()->count();
      $hasClientAuth = auth()->user()?->role === \App\Models\User::ROLE_CLIENT;
    @endphp

    <div data-component="site-header"></div>

    <main class="city-hero">
      <section class="city-results-shell city-results-shell--viator pt-6 pb-10 md:pt-8">
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

        <form method="GET" action="{{ $currentPath }}" class="city-controls-form" data-reveal>
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

          <div class="city-control">
            <label for="city-sort">Ordenar por</label>
            <select id="city-sort" name="sort">
              @foreach($sortOptions as $sortValue => $sortLabel)
                <option value="{{ $sortValue }}" @selected($selectedSort === $sortValue)>{{ $sortLabel }}</option>
              @endforeach
            </select>
          </div>

          <div class="city-controls-actions">
            <button type="submit" class="city-controls-submit">Aplicar</button>
            <a href="{{ $currentPath }}" class="city-reset-link">Limpiar</a>
          </div>
        </form>

        <div class="city-controls-footer" data-reveal>
          <p class="city-results-count">{{ $resultsDisplay }} resultados</p>
          <p class="city-results-note">Los ingresos pueden influir en este orden de clasificacion.</p>
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
                @endphp

                <article
                  class="city-result-card city-result-card--viator"
                  data-favorite-id="city-{{ $profile->id }}"
                  data-compare-id="{{ $profile->id }}"
                  data-card-url="{{ $profileUrl }}"
                  role="link"
                  tabindex="0"
                  aria-label="Abrir anuncio de {{ $profileName }}"
                >
                  <a href="{{ $profileUrl }}" class="city-result-media city-result-media--viator">
                    <img src="{{ $photoUrl }}" alt="{{ $profileName }}" class="h-full w-full object-cover" />
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
      @else
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

            @if($nearbyZones->isNotEmpty() && $citySlugValue)
              <div class="artist-seo-chip-cloud">
                @foreach($nearbyZones as $zone)
                  <a href="{{ route('seo.landing.city-category', ['citySlug' => $citySlugValue, 'scopeSlug' => $zone['slug']]) }}" class="artist-seo-chip">{{ $zone['name'] }} ({{ $zone['count'] }})</a>
                @endforeach
              </div>
            @else
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                Todavia no tenemos suficientes zonas relacionadas para mostrar en este contexto.
              </div>
            @endif
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

      <section class="layout-shell pb-10" data-reveal>
        <div class="surface rounded-2xl border border-slate-200 px-4 py-5 md:px-6">
          <div class="mb-4">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-brand-600">Personalizacion</p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-900 md:text-3xl">Vistos recientemente</h2>
          </div>

          @if($recentViews->isNotEmpty())
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
              @foreach($recentViews as $recentView)
                @php
                  $recentProfile = $recentView->mariachiListing ?: $recentView->mariachiProfile?->resolveDefaultListing() ?: $recentView->mariachiProfile;
                  $recentName = $recentProfile?->business_name ?: $recentProfile?->user?->display_name;
                  $recentPhoto = $recentProfile?->photos?->firstWhere('is_featured', true) ?? $recentProfile?->photos?->first();
                @endphp
                <article class="rounded-xl border border-slate-200 bg-white p-3">
                  @if($recentPhoto)
                    <img src="{{ asset('storage/'.$recentPhoto->path) }}" alt="{{ $recentName }}" class="h-32 w-full rounded-lg object-cover" />
                  @endif
                  <a href="{{ $recentProfile?->slug ? route('mariachi.public.show', ['slug' => $recentProfile->slug]) : '#' }}" class="mt-2 block text-sm font-extrabold text-slate-900 hover:underline">{{ $recentName }}</a>
                  <p class="mt-1 text-xs text-slate-500">Ultima visita: {{ $recentView->last_viewed_at?->format('Y-m-d H:i') }}</p>
                </article>
              @endforeach
            </div>
          @elseif($hasClientAuth)
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">Aun no tienes vistas recientes para {{ $isCountry ? 'este pais' : 'esta ciudad/zona' }}.</div>
          @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
              Inicia sesion como cliente para ver tu historial de anuncios consultados.
            </div>
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

    <div data-component="site-footer"></div>

    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260311-brand-green-v1"></script>
  </body>
</html>
