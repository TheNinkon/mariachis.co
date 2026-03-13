@extends('front.layouts.marketplace')

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('body_page', 'provider-profile')

@push('head')
  <link rel="canonical" href="{{ $canonicalUrl }}" />
  <meta property="og:title" content="{{ $seoTitle }}" />
  <meta property="og:description" content="{{ $seoDescription }}" />
  <meta property="og:url" content="{{ $canonicalUrl }}" />
  @if($profile->logo_path)
    <meta property="og:image" content="{{ asset('storage/'.$profile->logo_path) }}" />
  @endif
  <script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@section('content')
  @php
    $heroImage = $profile->logo_path
      ? asset('storage/'.$profile->logo_path)
      : asset('marketplace/assets/logo-wordmark.png');
  @endphp

  <main>
    <section class="hero-split-shell hero-split-shell--flush hero-split-shell--editorial">
      <div class="hero-split-grid hero-split-grid--editorial">
        <div class="hero-split-left hero-split-left--editorial" data-reveal>
          <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-600">Perfil oficial del mariachi</p>
          <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.02em] text-slate-900 md:text-5xl">{{ $profileName }}</h1>
          <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 md:text-lg">
            {{ $profile->short_description ?: 'Perfil público del grupo con presencia activa en Mariachis.co.' }}
          </p>

          <div class="mt-5 flex flex-wrap gap-2">
            <span class="artist-seo-chip">{{ '@'.$profile->slug }}</span>
            <span class="artist-seo-chip">{{ $cityName }}</span>
            <span class="artist-seo-chip">{{ $verificationLabel }}</span>
            <span class="artist-seo-chip">{{ $profile->active_listings_count }} {{ $profile->active_listings_count === 1 ? 'anuncio activo' : 'anuncios activos' }}</span>
          </div>

          @if($profile->website || $profile->instagram || $profile->facebook || $profile->tiktok || $profile->youtube)
            <div class="artist-seo-chip-cloud mt-5">
              @if($profile->website)
                <a href="{{ $profile->website }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">Sitio web</a>
              @endif
              @if($profile->instagram)
                <a href="{{ $profile->instagram }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">Instagram</a>
              @endif
              @if($profile->facebook)
                <a href="{{ $profile->facebook }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">Facebook</a>
              @endif
              @if($profile->tiktok)
                <a href="{{ $profile->tiktok }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">TikTok</a>
              @endif
              @if($profile->youtube)
                <a href="{{ $profile->youtube }}" target="_blank" rel="noopener noreferrer" class="artist-seo-chip">YouTube</a>
              @endif
            </div>
          @endif
        </div>

        <div class="hero-split-right hero-split-right--editorial" data-reveal>
          <div class="hero-split-home-media rounded-[2rem] overflow-hidden shadow-soft bg-white">
            <img src="{{ $heroImage }}" alt="{{ $profileName }}" class="hero-split-home-media__image object-cover" />
            <div class="hero-split-home-media__veil" aria-hidden="true"></div>
          </div>
        </div>
      </div>
    </section>

    <section class="layout-shell pb-14 pt-8">
      <div class="mb-6 flex flex-wrap items-end justify-between gap-4" data-reveal>
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">Anuncios del proveedor</p>
          <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.015em] text-slate-900">Anuncios activos de {{ $profileName }}</h2>
        </div>
        <span class="text-sm font-semibold text-slate-600">{{ $profile->active_listings_count }} activos</span>
      </div>

      @if($profile->activeListings->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          @foreach($profile->activeListings as $listing)
            @php
              $featuredPhoto = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
              $photoUrl = $featuredPhoto ? asset('storage/'.$featuredPhoto->path) : $heroImage;
              $isVip = $listing->hasPremiumMarketplaceBadge();
            @endphp

            <article class="featured-promo-card featured-promo-card--listing {{ $isVip ? 'featured-promo-card--vip' : '' }}">
              <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" class="featured-promo-media">
                <img src="{{ $photoUrl }}" alt="{{ $listing->title }}" />
                @if($isVip)
                  <span class="featured-promo-ribbon">{{ $listing->marketplaceBadgeLabel() }}</span>
                @endif
                <span class="featured-promo-chip">{{ $listing->city_name ?: $cityName }}</span>
              </a>
              <div class="featured-promo-body">
                <p class="featured-promo-kicker">{{ $profileName }}</p>
                <h3 class="featured-promo-title">{{ $listing->title }}</h3>
              </div>
              <div class="featured-promo-footer">
                <p class="featured-promo-meta">{{ $listing->short_description ?: 'Anuncio activo del proveedor en Mariachis.co.' }}</p>
                <div class="featured-promo-bottom">
                  <strong>{{ $listing->base_price ? 'Desde $'.number_format((float) $listing->base_price, 0, ',', '.') : 'Precio por cotizar' }}</strong>
                  <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">Ver anuncio</a>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @else
        <div class="surface rounded-3xl p-6" data-reveal>
          <p class="text-sm text-slate-600">Este perfil está publicado, pero todavía no tiene anuncios activos visibles en este momento.</p>
        </div>
      @endif
    </section>
  </main>
@endsection
