@extends('front.layouts.marketplace')

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('body_page', 'provider-profile')
@section('body_class', 'font-sans text-slate-900 antialiased bg-slate-50')

@push('styles')
  <style>
    .provider-public-shell {
      padding: 2.25rem 0 4.5rem;
    }

    .provider-profile-card,
    .provider-profile-header-card,
    .provider-profile-nav-card,
    .provider-profile-empty-card {
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 1.5rem;
      background: #fff;
      box-shadow: 0 26px 56px -42px rgba(15, 23, 42, 0.34);
    }

    .provider-profile-header-card {
      overflow: hidden;
    }

    .provider-profile-header-banner {
      position: relative;
      height: 250px;
      overflow: hidden;
      background:
        radial-gradient(circle at 14% 20%, rgba(255, 255, 255, 0.42), transparent 24%),
        radial-gradient(circle at 82% 16%, rgba(59, 130, 246, 0.16), transparent 24%),
        radial-gradient(circle at 68% 72%, rgba(15, 118, 110, 0.12), transparent 26%),
        linear-gradient(135deg, #dbece4 0%, #f8fafc 48%, #dbeafe 100%);
    }

    .provider-profile-header-banner::after {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.32), transparent 40%),
        linear-gradient(180deg, rgba(15, 23, 42, 0.06) 0%, rgba(15, 23, 42, 0.48) 100%);
    }

    .provider-profile-header-banner img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .provider-profile-header {
      position: relative;
      z-index: 2;
      margin-top: -2.2rem;
      padding: 0 2rem 2rem;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1.5rem;
    }

    .provider-profile-header__identity {
      display: flex;
      align-items: flex-end;
      gap: 1.35rem;
      min-width: 0;
    }

    .provider-profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 1.6rem;
      border: 5px solid #fff;
      background: linear-gradient(135deg, #1d976c 0%, #155e75 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      box-shadow: 0 24px 38px -28px rgba(15, 23, 42, 0.6);
    }

    .provider-profile-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .provider-profile-avatar span {
      color: #fff;
      font-size: 2.6rem;
      font-weight: 800;
      letter-spacing: -0.06em;
    }

    .provider-profile-header__copy {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
      min-width: 0;
    }

    .provider-profile-heading {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.7rem;
    }

    .provider-profile-header__copy h1 {
      margin: 0;
      font-size: clamp(2rem, 2.8vw, 2.8rem);
      line-height: 1.03;
      letter-spacing: -0.05em;
      font-weight: 800;
      color: #111827;
    }

    .provider-profile-verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      border: 1px solid rgba(15, 118, 110, 0.18);
      border-radius: 999px;
      background: rgba(15, 118, 110, 0.08);
      padding: 0.48rem 0.78rem;
      color: #0f766e;
      font-size: 0.8rem;
      font-weight: 800;
      line-height: 1;
      box-shadow: 0 18px 30px -28px rgba(15, 23, 42, 0.42);
    }

    .provider-profile-verified-badge svg {
      width: 0.92rem;
      height: 0.92rem;
      flex-shrink: 0;
    }

    .provider-profile-handle {
      display: inline-flex;
      align-items: center;
      align-self: flex-start;
      padding: 0.62rem 0.9rem;
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: rgba(255, 255, 255, 0.96);
      color: #0f766e;
      text-decoration: none;
      font-size: 0.95rem;
      font-weight: 800;
      line-height: 1;
      box-shadow: 0 14px 28px -24px rgba(15, 23, 42, 0.45);
    }

    .provider-profile-handle:hover {
      color: #115e59;
      border-color: rgba(15, 118, 110, 0.16);
    }

    .provider-profile-header__actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .provider-profile-header__summary-item {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.72rem 1rem;
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: rgba(255, 255, 255, 0.94);
      color: #334155;
      font-size: 0.9rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .provider-profile-header__summary-item svg {
      width: 1rem;
      height: 1rem;
      color: #0f766e;
      flex-shrink: 0;
    }

    .provider-profile-header__summary-item strong {
      color: #111827;
      font-weight: 800;
    }

    .provider-profile-primary-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.88rem 1.2rem;
      border-radius: 0.95rem;
      background: #0f766e;
      color: #fff;
      font-weight: 800;
      text-decoration: none;
      box-shadow: 0 22px 38px -30px rgba(15, 118, 110, 0.85);
    }

    .provider-profile-primary-btn:hover {
      color: #fff;
      background: #115e59;
    }

    .provider-profile-nav-card {
      padding: 0.55rem;
    }

    .provider-profile-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .provider-profile-nav a {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.85rem 1.1rem;
      border-radius: 0.95rem;
      color: #475569;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .provider-profile-nav a:hover,
    .provider-profile-nav a.is-active {
      background: rgba(15, 118, 110, 0.08);
      color: #0f766e;
    }

    .provider-profile-nav svg {
      width: 1rem;
      height: 1rem;
    }

    .provider-profile-section-title {
      margin: 0 0 1.35rem;
      color: #111827;
      font-size: 1.75rem;
      line-height: 1.1;
      letter-spacing: -0.04em;
      font-weight: 800;
    }

    .provider-profile-grid {
      display: grid;
      gap: 1.5rem;
      grid-template-columns: repeat(12, minmax(0, 1fr));
    }

    .provider-profile-grid > * {
      min-width: 0;
      grid-column: span 4;
    }

    .provider-profile-card {
      height: 100%;
      overflow: hidden;
    }

    .provider-profile-card--wide {
      width: 100%;
    }

    .provider-profile-card__header,
    .provider-profile-card__body,
    .provider-profile-card__footer {
      padding: 1.5rem;
    }

    .provider-profile-card__header {
      padding-bottom: 1.1rem;
    }

    .provider-profile-card__footer {
      border-top: 1px solid rgba(15, 23, 42, 0.08);
    }

    .provider-profile-card__head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
    }

    .provider-profile-card__identity {
      display: flex;
      align-items: center;
      gap: 1rem;
      min-width: 0;
    }

    .provider-profile-card__media {
      width: 52px;
      height: 52px;
      border-radius: 999px;
      overflow: hidden;
      background: #d9efe7;
      color: #0f766e;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-weight: 800;
    }

    .provider-profile-card__media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .provider-profile-card__media svg {
      width: 1.4rem;
      height: 1.4rem;
    }

    .provider-profile-card__title {
      margin: 0;
      color: #111827;
      font-size: 1.18rem;
      line-height: 1.3;
      font-weight: 800;
    }

    .provider-profile-card__client {
      margin-top: 0.28rem;
      color: #64748b;
      font-size: 0.94rem;
    }

    .provider-profile-card__action {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #fff;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      flex-shrink: 0;
    }

    .provider-profile-card__action:hover {
      color: #0f766e;
      border-color: rgba(15, 118, 110, 0.16);
    }

    .provider-profile-card__action svg {
      width: 1.05rem;
      height: 1.05rem;
    }

    .provider-profile-card__stats {
      display: flex;
      align-items: stretch;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .provider-profile-card__stat-box {
      flex: 1 1 200px;
      min-width: 0;
      border-radius: 1rem;
      padding: 0.95rem 1rem;
      background: #f8fafc;
    }

    .provider-profile-card__stat-box p {
      margin: 0 0 0.3rem;
      color: #111827;
      font-size: 1.12rem;
      font-weight: 800;
    }

    .provider-profile-card__stat-box span {
      color: #64748b;
      font-size: 0.92rem;
      font-weight: 600;
    }

    .provider-profile-card__copy {
      margin: 0;
      color: #475569;
      line-height: 1.72;
    }

    .provider-profile-card__topline,
    .provider-profile-card__progressline,
    .provider-profile-card__bottomline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .provider-profile-card__topline {
      margin-bottom: 1rem;
      color: #475569;
      font-size: 0.95rem;
    }

    .provider-profile-card__progressline {
      margin-bottom: 0.5rem;
      color: #64748b;
      font-size: 0.88rem;
      font-weight: 700;
    }

    .provider-profile-card__progress {
      width: 100%;
      height: 8px;
      border-radius: 999px;
      background: #e2e8f0;
      overflow: hidden;
      margin-bottom: 1rem;
    }

    .provider-profile-card__progress span {
      display: block;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, #0f766e 0%, #22c55e 100%);
    }

    .provider-profile-card__chips {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.45rem;
    }

    .provider-profile-card__chip {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.42rem 0.72rem;
      background: #f8fafc;
      color: #334155;
      font-size: 0.82rem;
      font-weight: 700;
      text-decoration: none;
    }

    .provider-profile-card__link {
      display: inline-flex;
      align-items: center;
      gap: 0.38rem;
      color: #475569;
      font-weight: 800;
      text-decoration: none;
    }

    .provider-profile-card__link:hover {
      color: #0f766e;
    }

    .provider-profile-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.38rem;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.22);
      padding: 0.36rem 0.72rem;
      background: #f8fafc;
      color: #475569;
      font-size: 0.78rem;
      font-weight: 800;
    }

    .provider-profile-status-badge svg {
      width: 0.9rem;
      height: 0.9rem;
      flex-shrink: 0;
    }

    .provider-profile-status-badge--verified {
      border-color: rgba(15, 118, 110, 0.18);
      background: rgba(15, 118, 110, 0.08);
      color: #0f766e;
    }

    .provider-profile-status-badge--vip {
      border-color: rgba(249, 115, 22, 0.16);
      background: rgba(249, 115, 22, 0.12);
      color: #c2410c;
    }

    .provider-profile-empty-card {
      padding: 1.75rem;
      color: #475569;
    }

    .provider-profile-anchor {
      scroll-margin-top: 6rem;
    }

    .provider-profile-panel[hidden] {
      display: none !important;
    }

    .provider-profile-link-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: 0.9rem;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .provider-profile-link-list a {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      border-radius: 1rem;
      background: #f8fafc;
      padding: 1rem 1.05rem;
      color: #0f172a;
      text-decoration: none;
      font-weight: 800;
    }

    .provider-profile-link-list a small {
      display: block;
      margin-top: 0.25rem;
      color: #64748b;
      font-weight: 600;
    }

    @media (max-width: 1199.98px) {
      .provider-profile-grid > * {
        grid-column: span 6;
      }
    }

    @media (max-width: 991.98px) {
      .provider-profile-header {
        align-items: flex-start;
        flex-direction: column;
      }

      .provider-profile-header__actions {
        justify-content: flex-start;
      }
    }

    @media (max-width: 767.98px) {
      .provider-public-shell {
        padding-top: 1.5rem;
      }

      .provider-profile-header-banner {
        height: 170px;
      }

      .provider-profile-header {
        margin-top: -1.4rem;
        padding: 0 1rem 1.2rem;
      }

      .provider-profile-header__identity {
        align-items: flex-start;
        flex-direction: column;
      }

      .provider-profile-header__actions {
        width: 100%;
      }

      .provider-profile-avatar {
        width: 96px;
        height: 96px;
      }

      .provider-profile-avatar span {
        font-size: 2rem;
      }

      .provider-profile-grid > * {
        grid-column: span 12;
      }
    }
  </style>
@endpush

@section('content')
  @php
    $avatarImage = $profile->shouldShowProfilePhoto() && filled($profile->logo_path)
      ? asset('storage/'.$profile->logo_path)
      : null;
    $bannerImage = $coverImage ? asset('storage/'.$coverImage) : null;
    $avatarInitials = $profile->avatarInitials();
    $featuredListing = $profile->activeListings->first();
    $joinedDate = $profile->user?->created_at;
    $joinedLabel = $joinedDate
      ? \Illuminate\Support\Str::ucfirst($joinedDate->translatedFormat('F Y'))
      : 'Reciente';
    $coverageAreas = collect([$cityName])
      ->merge($profile->serviceAreas->pluck('city_name'))
      ->filter()
      ->unique()
      ->values();
    $socialLinks = collect([
      ['label' => 'Sitio web', 'url' => $profile->website],
      ['label' => 'Instagram', 'url' => $profile->instagram],
      ['label' => 'Facebook', 'url' => $profile->facebook],
      ['label' => 'TikTok', 'url' => $profile->tiktok],
      ['label' => 'YouTube', 'url' => $profile->youtube],
    ])->filter(fn (array $link): bool => filled($link['url']))->values();
    $publicProfileLabel = '@'.$profile->slug;
    $summaryProgress = max(12, min(100, (int) ($profile->profile_completed ? 100 : ($profile->profile_completion ?? 0))));
    $verificationUiLabel = $profile->hasActiveVerification() ? 'Perfil verificado' : 'Perfil publicado';
    $activeSection = $activeSection ?? 'perfil';
    $profileSectionUrl = route('mariachi.provider.public.show', ['handle' => $profile->slug]);
    $announcementsSectionUrl = route('mariachi.provider.public.section', ['handle' => $profile->slug, 'section' => 'anuncios']);
    $coverageSectionUrl = route('mariachi.provider.public.section', ['handle' => $profile->slug, 'section' => 'cobertura']);
    $socialSectionUrl = route('mariachi.provider.public.section', ['handle' => $profile->slug, 'section' => 'redes']);
  @endphp

  <main class="provider-public-shell">
    <section class="layout-shell">
      <div class="provider-profile-header-card">
        <div class="provider-profile-header-banner">
          @if($bannerImage)
            <img src="{{ $bannerImage }}" alt="Portada de {{ $profileName }}" />
          @endif
        </div>

        <div class="provider-profile-header">
          <div class="provider-profile-header__identity">
            <div class="provider-profile-avatar">
              @if($avatarImage)
                <img src="{{ $avatarImage }}" alt="{{ $profileName }}" />
              @else
                <span>{{ $avatarInitials }}</span>
              @endif
            </div>

            <div class="provider-profile-header__copy">
              <div class="provider-profile-heading">
                <h1>{{ $profileName }}</h1>
                @if($profile->hasActiveVerification())
                  <span class="provider-profile-verified-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 2.25 2.25L15 9.75" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6l.75 2.25 2.25.75v5.16c0 3.124-1.697 6-4.43 7.502L12 20.25l-1.57-.828A8.486 8.486 0 0 1 6 11.91V6.75L8.25 6 9 3.75Z" />
                    </svg>
                    <span>Perfil verificado</span>
                  </span>
                @endif
              </div>
              <a href="{{ $canonicalUrl }}" class="provider-profile-handle">
                {{ $publicProfileLabel }}
              </a>
            </div>
          </div>

          <div class="provider-profile-header__actions">
            <span class="provider-profile-header__summary-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <rect x="3" y="6" width="18" height="15" rx="2" />
                <path d="M3 10h18" />
              </svg>
              <strong>Miembro desde</strong> {{ $joinedLabel }}
            </span>
            <span class="provider-profile-header__summary-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
                <circle cx="12" cy="10" r="2.5" />
              </svg>
              <strong>{{ $cityName }}</strong>
            </span>
            @if($featuredListing)
              <a href="{{ route('mariachi.public.show', ['slug' => $featuredListing->slug]) }}" class="provider-profile-primary-btn">
                Ver anuncio principal
              </a>
            @endif
          </div>
        </div>
      </div>
    </section>

    <section class="layout-shell mt-6">
      <div class="provider-profile-nav-card">
        <nav class="provider-profile-nav" aria-label="Navegacion del perfil" data-provider-sections-nav>
          <a href="{{ $profileSectionUrl }}" class="{{ $activeSection === 'perfil' ? 'is-active' : '' }}" data-provider-section-link data-section="perfil">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
              <path d="M4 20a8 8 0 1 1 16 0" />
            </svg>
            Perfil
          </a>
          <a href="{{ $announcementsSectionUrl }}" class="{{ $activeSection === 'anuncios' ? 'is-active' : '' }}" data-provider-section-link data-section="anuncios">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="3" y="4" width="18" height="14" rx="2" />
              <path d="M8 20h8" />
              <path d="M12 18v2" />
            </svg>
            Anuncios
          </a>
          <a href="{{ $coverageSectionUrl }}" class="{{ $activeSection === 'cobertura' ? 'is-active' : '' }}" data-provider-section-link data-section="cobertura">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
              <circle cx="12" cy="10" r="2.5" />
            </svg>
            Cobertura
          </a>
          <a href="{{ $socialSectionUrl }}" class="{{ $activeSection === 'redes' ? 'is-active' : '' }}" data-provider-section-link data-section="redes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M9 12a3 3 0 0 0 3 3h4a3 3 0 0 0 0-6h-4a3 3 0 0 0-3 3Z" />
              <path d="M15 12a3 3 0 0 0-3-3H8a3 3 0 0 0 0 6h4a3 3 0 0 0 3-3Z" />
            </svg>
            Redes
          </a>
        </nav>
      </div>
    </section>

    <div data-provider-sections>
      <section class="layout-shell mt-8 provider-profile-anchor provider-profile-panel" id="perfil" data-provider-section-panel="perfil" @if($activeSection !== 'perfil') hidden @endif>
        <h2 class="provider-profile-section-title">Resumen del perfil</h2>

        <article class="provider-profile-card provider-profile-card--wide">
          <div class="provider-profile-card__header">
            <div class="provider-profile-card__head">
              <div class="provider-profile-card__identity">
                <div class="provider-profile-card__media">
                  @if($avatarImage)
                    <img src="{{ $avatarImage }}" alt="{{ $profileName }}" />
                  @else
                    {{ $avatarInitials }}
                  @endif
                </div>
                <div class="min-w-0">
                  <h3 class="provider-profile-card__title">Perfil oficial</h3>
                  <div class="provider-profile-card__client">{{ $publicProfileLabel }}</div>
                </div>
              </div>

              <a href="{{ $canonicalUrl }}" class="provider-profile-card__action" aria-label="Abrir perfil">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M7 17 17 7" />
                  <path d="M8 7h9v9" />
                </svg>
              </a>
            </div>
          </div>

          <div class="provider-profile-card__body">
            <div class="provider-profile-card__stats">
              <div class="provider-profile-card__stat-box">
                <p>{{ $profile->active_listings_count }}</p>
                <span>Anuncio(s) activo(s)</span>
              </div>
              <div class="provider-profile-card__stat-box">
                <p>{{ $profile->public_reviews_count }}</p>
                <span>Opinion(es) visibles</span>
              </div>
              <div class="provider-profile-card__stat-box">
                <p>{{ $cityName }}</p>
                <span>Ciudad principal</span>
              </div>
            </div>

            <p class="provider-profile-card__copy">
              {{ $profile->short_description ?: 'Perfil oficial del grupo dentro de Mariachis.co con presencia publica y anuncios activos.' }}
            </p>
          </div>

          <div class="provider-profile-card__footer">
            <div class="provider-profile-card__topline">
              <p class="mb-0"><span class="font-extrabold text-slate-900">Estado:</span> {{ $verificationUiLabel }}</p>
              <span class="provider-profile-status-badge {{ $profile->hasActiveVerification() ? 'provider-profile-status-badge--verified' : '' }}">
                @if($profile->hasActiveVerification())
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 2.25 2.25L15 9.75" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6l.75 2.25 2.25.75v5.16c0 3.124-1.697 6-4.43 7.502L12 20.25l-1.57-.828A8.486 8.486 0 0 1 6 11.91V6.75L8.25 6 9 3.75Z" />
                  </svg>
                @endif
                <span>{{ $profile->hasActiveVerification() ? 'Verificado' : 'Publicado' }}</span>
              </span>
            </div>

            <div class="provider-profile-card__progressline">
              <small>Perfil publico</small>
              <small>{{ $summaryProgress }}% completado</small>
            </div>

            <div class="provider-profile-card__progress">
              <span style="width: {{ $summaryProgress }}%"></span>
            </div>

            <div class="provider-profile-card__bottomline">
              <div class="provider-profile-card__chips">
                <span class="provider-profile-card__chip">{{ $cityName }}</span>
                <span class="provider-profile-card__chip">Miembro desde {{ $joinedLabel }}</span>
              </div>

              <a href="{{ $canonicalUrl }}" class="provider-profile-card__link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                  <path d="M7 17 17 7" />
                  <path d="M8 7h9v9" />
                </svg>
                Ver perfil
              </a>
            </div>
          </div>
        </article>
      </section>

      <section class="layout-shell mt-10 provider-profile-anchor provider-profile-panel" id="anuncios" data-provider-section-panel="anuncios" @if($activeSection !== 'anuncios') hidden @endif>
        <h2 class="provider-profile-section-title">Anuncios</h2>

        @if($profile->activeListings->isNotEmpty())
          <div class="provider-profile-grid">
            @foreach($profile->activeListings as $listing)
              @php
                $featuredPhoto = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
                $cardAvatar = $featuredPhoto ? asset('storage/'.$featuredPhoto->path) : $avatarImage;
                $listingProgress = max(8, min(100, (int) $listing->listing_completion));
                $coverageSummary = $listing->zone_name ?: ($listing->service_areas_count > 0 ? $listing->service_areas_count.' zona(s)' : ($listing->city_name ?: $cityName));
                $planLabel = $listing->selected_plan_code ? \Illuminate\Support\Str::headline((string) $listing->selected_plan_code) : 'Plan por definir';
                $statusLabel = $listing->hasPremiumMarketplaceBadge() ? ($listing->marketplaceBadgeLabel() ?: 'VIP') : 'Activo';
                $eventLabels = $listing->eventTypes->pluck('name')->take(3);
              @endphp

              <article class="provider-profile-card">
                <div class="provider-profile-card__header">
                  <div class="provider-profile-card__head">
                    <div class="provider-profile-card__identity">
                      <div class="provider-profile-card__media">
                        @if($cardAvatar)
                          <img src="{{ $cardAvatar }}" alt="{{ $listing->title }}" />
                        @else
                          {{ $avatarInitials }}
                        @endif
                      </div>
                      <div class="min-w-0">
                        <h3 class="provider-profile-card__title">{{ $listing->title }}</h3>
                        <div class="provider-profile-card__client">
                          <span class="font-semibold">Ciudad:</span>
                          <span>{{ $listing->city_name ?: $cityName }}</span>
                        </div>
                      </div>
                    </div>

                    <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" class="provider-profile-card__action" aria-label="Ver anuncio">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 12s3-6 9-6 9 6 9 6-3 6-9 6-9-6-9-6Z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </a>
                  </div>
                </div>

                <div class="provider-profile-card__body">
                  <div class="provider-profile-card__stats">
                    <div class="provider-profile-card__stat-box">
                      <p>{{ $listing->base_price ? '$'.number_format((float) $listing->base_price, 0, ',', '.') : 'Por cotizar' }}</p>
                      <span>Precio base</span>
                    </div>
                    <div class="provider-profile-card__stat-box">
                      <p>{{ $coverageSummary }}</p>
                      <span>{{ $planLabel }}</span>
                    </div>
                  </div>

                  <p class="provider-profile-card__copy">
                    {{ \Illuminate\Support\Str::limit($listing->short_description ?: 'Anuncio publicado dentro del perfil oficial del grupo en Mariachis.co.', 138) }}
                  </p>
                </div>

                <div class="provider-profile-card__footer">
                  <div class="provider-profile-card__topline">
                    <p class="mb-0"><span class="font-extrabold text-slate-900">Activo desde:</span> {{ optional($listing->activated_at ?: $listing->updated_at)->format('d/m/Y') }}</p>
                    <span class="provider-profile-status-badge {{ $listing->hasPremiumMarketplaceBadge() ? 'provider-profile-status-badge--vip' : '' }}">{{ $statusLabel }}</span>
                  </div>

                  <div class="provider-profile-card__progressline">
                    <small>{{ $listing->photos_count }} foto(s) · {{ $listing->videos_count }} video(s)</small>
                    <small>{{ $listingProgress }}% completado</small>
                  </div>

                  <div class="provider-profile-card__progress">
                    <span style="width: {{ $listingProgress }}%"></span>
                  </div>

                  <div class="provider-profile-card__bottomline">
                    <div class="provider-profile-card__chips">
                      @forelse($eventLabels as $label)
                        <span class="provider-profile-card__chip">{{ $label }}</span>
                      @empty
                        <span class="provider-profile-card__chip">Evento privado</span>
                      @endforelse
                    </div>

                    <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" class="provider-profile-card__link">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                        <path d="M3 12s3-6 9-6 9 6 9 6-3 6-9 6-9-6-9-6Z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                      Ver anuncio
                    </a>
                  </div>
                </div>
              </article>
            @endforeach
          </div>
        @else
          <div class="provider-profile-empty-card">
            {{ $profileName }} aun no tiene anuncios publicados en este momento.
          </div>
        @endif
      </section>

      <section class="layout-shell mt-10 provider-profile-anchor provider-profile-panel" id="cobertura" data-provider-section-panel="cobertura" @if($activeSection !== 'cobertura') hidden @endif>
        <h2 class="provider-profile-section-title">Cobertura</h2>

        <article class="provider-profile-card provider-profile-card--wide">
          <div class="provider-profile-card__header">
            <div class="provider-profile-card__head">
              <div class="provider-profile-card__identity">
                <div class="provider-profile-card__media">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
                    <circle cx="12" cy="10" r="2.5" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <h3 class="provider-profile-card__title">Cobertura del grupo</h3>
                  <div class="provider-profile-card__client">Ciudad principal: {{ $cityName }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="provider-profile-card__body">
            <div class="provider-profile-card__stats">
              <div class="provider-profile-card__stat-box">
                <p>{{ $coverageAreas->count() }}</p>
                <span>Zona(s) visibles</span>
              </div>
              <div class="provider-profile-card__stat-box">
                <p>{{ $coverageAreas->first() ?: $cityName }}</p>
                <span>Base del grupo</span>
              </div>
            </div>

            <p class="provider-profile-card__copy">
              {{ $profileName }} aparece con base en {{ $cityName }} y puede mostrar cobertura adicional segun sus anuncios activos.
            </p>
          </div>

          <div class="provider-profile-card__footer">
            <div class="provider-profile-card__bottomline">
              <div class="provider-profile-card__chips">
                @forelse($coverageAreas->take(10) as $area)
                  <span class="provider-profile-card__chip">{{ $area }}</span>
                @empty
                  <span class="provider-profile-card__chip">{{ $cityName }}</span>
                @endforelse
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="layout-shell mt-10 provider-profile-anchor provider-profile-panel" id="redes" data-provider-section-panel="redes" @if($activeSection !== 'redes') hidden @endif>
        <h2 class="provider-profile-section-title">Redes</h2>

        <article class="provider-profile-card provider-profile-card--wide">
          <div class="provider-profile-card__header">
            <div class="provider-profile-card__head">
              <div class="provider-profile-card__identity">
                <div class="provider-profile-card__media">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M10 14a3 3 0 0 1 0-4.24l2.12-2.12a3 3 0 0 1 4.24 4.24l-1.41 1.41" />
                    <path d="M14 10a3 3 0 0 1 0 4.24l-2.12 2.12a3 3 0 1 1-4.24-4.24l1.41-1.41" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <h3 class="provider-profile-card__title">Web y redes</h3>
                  <div class="provider-profile-card__client">{{ $socialLinks->count() }} enlace(s) publicado(s)</div>
                </div>
              </div>
            </div>
          </div>

          <div class="provider-profile-card__body">
            @if($socialLinks->isNotEmpty())
              <ul class="provider-profile-link-list">
                @foreach($socialLinks as $link)
                  <li>
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                      <span>
                        {{ $link['label'] }}
                        <small>Abrir enlace</small>
                      </span>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                        <path d="M7 17 17 7" />
                        <path d="M8 7h9v9" />
                      </svg>
                    </a>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="provider-profile-card__copy">Este perfil aun no ha agregado enlaces web o redes sociales.</p>
            @endif
          </div>
        </article>
      </section>
    </div>
  </main>

  <script>
    (function () {
      const nav = document.querySelector('[data-provider-sections-nav]');
      if (!nav) {
        return;
      }

      const links = Array.from(nav.querySelectorAll('[data-provider-section-link]'));
      const panels = Array.from(document.querySelectorAll('[data-provider-section-panel]'));
      const sections = new Set(['perfil', 'anuncios', 'cobertura', 'redes']);

      const setActiveSection = (section, nextUrl) => {
        if (!sections.has(section)) {
          return;
        }

        links.forEach((link) => {
          link.classList.toggle('is-active', link.dataset.section === section);
        });

        panels.forEach((panel) => {
          panel.hidden = panel.dataset.providerSectionPanel !== section;
        });

        if (nextUrl && window.location.href !== nextUrl) {
          window.history.pushState({ section }, '', nextUrl);
        }
      };

      links.forEach((link) => {
        link.addEventListener('click', (event) => {
          const section = link.dataset.section || 'perfil';
          if (!sections.has(section)) {
            return;
          }

          event.preventDefault();
          setActiveSection(section, link.href);
        });
      });

      window.addEventListener('popstate', () => {
        const path = window.location.pathname;
        const section = path.endsWith('/anuncios')
          ? 'anuncios'
          : path.endsWith('/cobertura')
            ? 'cobertura'
            : path.endsWith('/redes')
              ? 'redes'
              : 'perfil';

        setActiveSection(section);
      });
    })();
  </script>
@endsection
