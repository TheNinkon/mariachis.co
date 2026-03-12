@php
  $seoLinks = collect($cityLinks ?? [])
    ->take(6)
    ->flatMap(function (array $city) use ($eventTypes) {
      return collect($eventTypes ?? [])
        ->take(6)
        ->map(function ($event) use ($city) {
          $scopeSlug = $event->slug ?: \Illuminate\Support\Str::slug($event->name);

          return [
            'url' => route('seo.landing.city-category', ['citySlug' => $city['slug'], 'scopeSlug' => $scopeSlug]),
            'label' => 'Mariachis para ' . \Illuminate\Support\Str::lower($event->name) . ' en ' . $city['name'],
          ];
        });
    })
    ->take(24)
    ->values();
@endphp

@extends('front.layouts.marketplace')

@section('title', 'Página no encontrada | Mariachis.co')
@section('meta_description', 'La página que buscas no existe o cambió de dirección. Busca mariachis por ciudad, evento o servicio desde Mariachis.co.')
@section('body_page', 'error-404')

@section('content')
  <main class="error-404-page">
    <section class="error-404-hero-shell">
      <div class="error-404-hero">
        <img
          src="{{ asset('marketplace/img/2.webp') }}"
          alt="Mariachis en presentación en vivo"
          class="error-404-hero__image"
        />
        <div class="error-404-hero__veil"></div>

        <div class="layout-shell">
          <div class="error-404-hero__content">
            <p class="error-404-hero__eyebrow">Error 404</p>
            <h1 class="error-404-hero__title">Página no encontrada</h1>
            <p class="error-404-hero__lead">
              Por favor, comprueba que la dirección web no contiene errores o usa el buscador para encontrar mariachis por ciudad, ocasión o tipo de servicio.
            </p>
          </div>
        </div>
      </div>

      <div class="layout-shell">
        <div class="error-404-search-bridge">
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

    <section class="error-404-links-shell">
      <div class="layout-shell">
        <div class="error-404-links-head">
          <div>
            <p class="error-404-links-kicker">Búsquedas populares</p>
            <h2 class="error-404-links-title">Empieza de nuevo con una ocasión y una ciudad</h2>
          </div>
          <a href="{{ route('home') }}" class="error-404-links-home">Volver al inicio</a>
        </div>

        @if($seoLinks->isNotEmpty())
          <div class="error-404-links-grid">
            @foreach($seoLinks as $item)
              <a href="{{ $item['url'] }}" class="error-404-links-item">{{ $item['label'] }}</a>
            @endforeach
          </div>
        @elseif(collect($cityLinks ?? [])->isNotEmpty())
          <div class="error-404-links-grid">
            @foreach($cityLinks as $city)
              <a href="{{ route('seo.landing.slug', ['slug' => $city['slug']]) }}" class="error-404-links-item">
                Mariachis en {{ $city['name'] }}
              </a>
            @endforeach
          </div>
        @else
          <p class="error-404-links-empty">Aún no hay ciudades visibles en el buscador público.</p>
        @endif
      </div>
    </section>
  </main>
@endsection
