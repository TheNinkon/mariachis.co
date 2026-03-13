@extends('front.layouts.marketplace')

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@section('body_page', 'public-collection')
@section('body_class', 'font-sans text-slate-900 antialiased')

@section('content')
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
@endsection

@push('scripts')
  <script src="js/public-listing-collections.js?v=20260311-listing-collections-v1"></script>
@endpush
