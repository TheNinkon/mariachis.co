<!DOCTYPE html>
<html lang="es">
  <head>
    @php
      $seo = $seo ?? app(\App\Services\Seo\SeoResolver::class)->resolve(request(), null, [
        'title' => trim((string) $__env->yieldContent('title')),
        'description' => trim((string) $__env->yieldContent('meta_description')),
      ]);
    @endphp
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @include('front.partials.seo-meta', ['seo' => $seo])
    <base href="{{ asset('marketplace') }}/" />
    <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/theme.css?v=20260313-footer-v2" />
    @stack('head')
  </head>
  <body data-page="@yield('page_id', 'client-auth')">
    @php
      $pageId = trim((string) $__env->yieldContent('page_id', 'client-auth'));
      $isClientAuthFlow = $pageId === 'client-auth';
      $authUser = auth()->user();
      $isClientAuth = $authUser && $authUser->role === \App\Models\User::ROLE_CLIENT;
      $clientLogoutRoute = \Illuminate\Support\Facades\Route::has('client.logout') ? route('client.logout') : url('/auth/logout');
      $initials = 'C';
      if ($isClientAuth) {
          $first = trim((string) ($authUser->first_name ?? ''));
          $last = trim((string) ($authUser->last_name ?? ''));
          $parts = array_filter([$first, $last], fn ($value) => $value !== '');
          if (! empty($parts)) {
              $initials = collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
              $initials = mb_substr($initials, 0, 2);
          } else {
              $name = trim((string) ($authUser->name ?? 'Cliente'));
              $initials = mb_strtoupper(mb_substr($name, 0, 1));
          }
      }

      $footerCities = collect();
      $footerEvents = collect();
      $footerZones = collect();
      $footerResources = collect();
      $footerSocialLinks = collect();
      $footerDescription = '';
      $footerSiteName = 'Mariachis.co';
      $footerPrimaryCityUrl = route('home');
      if (! $isClientAuthFlow) {
          $seoSettings = app(\App\Services\Seo\SeoSettingsService::class);
          $footerSiteName = $seoSettings->siteName();
          $footerDescription = $seoSettings->defaultMetaDescription();
          $footerCities = \App\Models\MariachiProfile::query()
              ->published()
              ->selectRaw('city_name, count(*) as total')
              ->whereNotNull('city_name')
              ->where('city_name', '!=', '')
              ->groupBy('city_name')
              ->orderByDesc('total')
              ->limit(5)
              ->get();

          $footerPrimaryCity = $footerCities->first();
          if ($footerPrimaryCity && filled($footerPrimaryCity->city_name)) {
              $footerPrimaryCityUrl = route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($footerPrimaryCity->city_name)]);
          }

          $preferredEventSlugs = ['bodas', 'cumpleanos', 'aniversarios', 'serenatas', 'corporativos'];
          $footerEvents = \App\Models\EventType::query()
              ->active()
              ->select(['id', 'name', 'slug', 'sort_order'])
              ->withCount([
                  'mariachiListings as published_listings_count' => fn ($query) => $query->published(),
              ])
              ->get()
              ->filter(fn (\App\Models\EventType $eventType): bool => (int) $eventType->published_listings_count > 0)
              ->sortBy(function (\App\Models\EventType $eventType) use ($preferredEventSlugs): string {
                  $slug = (string) ($eventType->slug ?: \Illuminate\Support\Str::slug($eventType->name));
                  $priority = array_search($slug, $preferredEventSlugs, true);
                  $priority = $priority === false ? 99 : $priority;

                  return str_pad((string) $priority, 2, '0', STR_PAD_LEFT)
                      .'|'.str_pad((string) max(0, 9999 - (int) $eventType->published_listings_count), 4, '0', STR_PAD_LEFT)
                      .'|'.mb_strtolower((string) $eventType->name);
              })
              ->take(5)
              ->values();

          $preferredZoneSlugs = ['chapinero', 'usaquen', 'suba', 'kennedy'];
          $footerZones = \App\Models\MarketplaceZone::query()
              ->with('city:id,name,slug')
              ->searchVisible()
              ->select(['id', 'marketplace_city_id', 'name', 'slug', 'sort_order'])
              ->withCount([
                  'serviceAreas as published_listings_count' => fn ($query) => $query->whereHas('listing', fn ($listingQuery) => $listingQuery->published()),
              ])
              ->get()
              ->filter(fn (\App\Models\MarketplaceZone $zone): bool => (int) $zone->published_listings_count > 0 && filled($zone->city?->slug))
              ->sortBy(function (\App\Models\MarketplaceZone $zone) use ($preferredZoneSlugs): string {
                  $slug = (string) ($zone->slug ?: \Illuminate\Support\Str::slug($zone->name));
                  $priority = array_search($slug, $preferredZoneSlugs, true);
                  $priority = $priority === false ? 99 : $priority;

                  return str_pad((string) $priority, 2, '0', STR_PAD_LEFT)
                      .'|'.str_pad((string) max(0, 9999 - (int) $zone->published_listings_count), 4, '0', STR_PAD_LEFT)
                      .'|'.mb_strtolower((string) $zone->name);
              })
              ->take(5)
              ->values();

          $footerResources = collect([
              ['label' => 'Publica tu anuncio', 'url' => route('mariachi.register')],
              ['label' => 'Anuncios en tu ciudad', 'url' => $footerPrimaryCityUrl],
              ['label' => 'Blog', 'url' => route('blog.index')],
          ]);

          $footerSocialLinks = collect([
              ['label' => 'Facebook', 'url' => config('variables.facebookUrl'), 'icon' => 'facebook'],
              ['label' => 'Instagram', 'url' => config('variables.instagramUrl'), 'icon' => 'instagram'],
              ['label' => 'TikTok', 'url' => config('variables.tiktokUrl'), 'icon' => 'tiktok'],
              ['label' => 'YouTube', 'url' => config('variables.youtubeUrl'), 'icon' => 'youtube'],
          ])->filter(fn (array $link): bool => filled($link['url']))->values();
      }
    @endphp
    <header class="public-clean-header">
      <div class="public-clean-header-inner layout-shell">
        <a class="brand-logo brand-logo--header" href="/" aria-label="mariachis.co">
          <span class="brand-logo-copy">
            <span class="brand-logo-word">
              <img src="assets/logo-wordmark.png" alt="Mariachis.co" class="brand-logo-image" />
            </span>
          </span>
        </a>

        <nav class="public-clean-nav" aria-label="Navegación principal">
          @if($isClientAuth && ! $isClientAuthFlow)
            <a class="public-clean-inbox-btn" href="{{ route('client.dashboard') }}" aria-label="Solicitudes y mensajería">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 8.25v8.25a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 16.5V8.25m19.5 0L15.75 3.75h-7.5L2.25 8.25m19.5 0v.75A2.25 2.25 0 0119.5 11.25h-3.621a2.25 2.25 0 00-2.122 1.5l-.27.81a2.25 2.25 0 01-2.122 1.5H8.636a2.25 2.25 0 01-2.122-1.5l-.27-.81a2.25 2.25 0 00-2.122-1.5H2.25A2.25 2.25 0 010 9V8.25" />
              </svg>
            </a>

            <details class="public-clean-account">
              <summary>
                <span class="public-clean-avatar">{{ $initials }}</span>
                <span class="public-clean-account-name">{{ $authUser->first_name ?: 'Mi cuenta' }}</span>
              </summary>
              <div class="public-clean-account-menu">
                <a href="{{ route('client.dashboard') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 15 0" />
                  </svg>
                  <span>Mi cuenta</span>
                </a>
                <a href="{{ route('client.dashboard') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 4.5h5.25M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v12l-3.75-2.25L12 18l-3.75-2.25L4.5 18V6a2.25 2.25 0 0 1 2.25-2.25Z" />
                  </svg>
                  <span>Solicitudes / mensajería</span>
                </a>
                <a href="{{ route('client.account.favorites') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25s-6.75-4.35-8.625-8.4A4.875 4.875 0 0 1 12 6.375a4.875 4.875 0 0 1 8.625 5.475C18.75 15.9 12 20.25 12 20.25Z" />
                  </svg>
                  <span>Lista de deseos</span>
                </a>
                <a href="{{ route('client.account.recent') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 2.25" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-2.64-6.36" />
                  </svg>
                  <span>Vistos recientemente</span>
                </a>
                <a href="{{ route('client.account.profile') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 21a5.25 5.25 0 1 1 10.5 0" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A2.25 2.25 0 0 1 6 3h12a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V5.25Z" />
                  </svg>
                  <span>Perfil</span>
                </a>
                <a href="{{ route('client.account.security') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.5 4.5 0 1 0-9 0V10.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 10.5h13.5v8.25a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V10.5Z" />
                  </svg>
                  <span>Seguridad</span>
                </a>
                <form action="{{ $clientLogoutRoute }}" method="POST">
                  @csrf
                  <button type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 12h9m0 0-3-3m3 3-3 3" />
                    </svg>
                    <span>Cerrar sesión</span>
                  </button>
                </form>
              </div>
            </details>
          @elseif(! $isClientAuthFlow)
            <a href="{{ route('client.login') }}" class="public-clean-login-link">Iniciar sesión</a>
          @endif
        </nav>
      </div>
    </header>

    @yield('content')

    @if($isClientAuthFlow)
      <footer class="public-clean-footer public-clean-footer--auth">
        <div class="public-clean-footer-inner public-clean-footer-inner--auth layout-shell">
          <span>&copy; {{ now()->year }} Mariachis.co</span>
          <nav class="public-clean-footer-inline" aria-label="Enlaces legales">
            <a href="{{ route('static.terms') }}">Términos y condiciones</a>
            <a href="{{ route('static.privacy') }}">Privacidad y cookies</a>
          </nav>
        </div>
      </footer>
    @else
      <footer class="public-clean-footer public-clean-footer--marketplace">
        <div class="public-clean-footer-inner layout-shell">
          <div class="public-clean-footer-top{{ $footerSocialLinks->isEmpty() ? ' public-clean-footer-top--solo' : '' }}">
            <section class="public-clean-footer-brand">
              <a class="brand-logo brand-logo--footer" href="/" aria-label="mariachis.co">
                <span class="brand-logo-copy">
                  <span class="brand-logo-word">
                    <img src="assets/logo-wordmark.png" alt="{{ $footerSiteName }}" class="brand-logo-image" />
                  </span>
                  <span class="brand-logo-sub">marketplace colombiano</span>
                </span>
              </a>
              <p class="public-clean-footer-text">{{ $footerDescription }}</p>
            </section>

            @if($footerSocialLinks->isNotEmpty())
              <section class="public-clean-footer-socials" aria-label="Redes sociales">
                <span class="public-clean-footer-eyebrow">Síguenos</span>
                <div class="public-clean-footer-social-list">
                  @foreach($footerSocialLinks as $socialLink)
                    <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" class="public-clean-footer-social" aria-label="{{ $socialLink['label'] }}">
                      <span class="public-clean-footer-social__icon" aria-hidden="true">
                        @switch($socialLink['icon'])
                          @case('facebook')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                              <path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v3H6v4h3v4h4v-4h3.2l.8-4H13V9c0-.6.4-1 1-1Z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            @break
                          @case('instagram')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                              <rect x="3.5" y="3.5" width="17" height="17" rx="5" />
                              <circle cx="12" cy="12" r="4" />
                              <circle cx="17.5" cy="6.5" r="0.75" fill="currentColor" stroke="none" />
                            </svg>
                            @break
                          @case('tiktok')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                              <path d="M14 4c.8 2 2.3 3.5 4 4v3a7.1 7.1 0 0 1-4-1.2V15a5 5 0 1 1-5-5h1.2v3H9a2 2 0 1 0 2 2V4h3Z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            @break
                          @case('youtube')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                              <path d="M21 12s0-3.3-.4-4.8a2.5 2.5 0 0 0-1.8-1.8C17.3 5 12 5 12 5s-5.3 0-6.8.4a2.5 2.5 0 0 0-1.8 1.8C3 8.7 3 12 3 12s0 3.3.4 4.8a2.5 2.5 0 0 0 1.8 1.8C6.7 19 12 19 12 19s5.3 0 6.8-.4a2.5 2.5 0 0 0 1.8-1.8C21 15.3 21 12 21 12Z" stroke-linecap="round" stroke-linejoin="round" />
                              <path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none" />
                            </svg>
                            @break
                        @endswitch
                      </span>
                      <span>{{ $socialLink['label'] }}</span>
                    </a>
                  @endforeach
                </div>
              </section>
            @endif
          </div>

          <div class="public-clean-footer-divider" role="presentation"></div>

          <div class="public-clean-footer-middle">
            <section class="public-clean-footer-column">
              <h3>Ciudades populares</h3>
              <ul>
                @forelse($footerCities as $city)
                  <li><a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($city->city_name)]) }}">Mariachis en {{ $city->city_name }}</a></li>
                @empty
                  <li><span>Sin ciudades activas todavía</span></li>
                @endforelse
              </ul>
            </section>

            <section class="public-clean-footer-column">
              <h3>Eventos destacados</h3>
              <ul>
                @forelse($footerEvents as $eventType)
                  <li><a href="{{ route('seo.landing.slug', ['slug' => $eventType->slug ?: \Illuminate\Support\Str::slug($eventType->name)]) }}">Mariachis para {{ \Illuminate\Support\Str::lower($eventType->name) }}</a></li>
                @empty
                  <li><span>Próximamente más eventos destacados</span></li>
                @endforelse
              </ul>
            </section>

            <section class="public-clean-footer-column">
              <h3>Zonas destacadas</h3>
              <ul>
                @forelse($footerZones as $zone)
                  <li><a href="{{ route('seo.landing.city-category', ['citySlug' => $zone->city->slug, 'scopeSlug' => $zone->slug]) }}">{{ $zone->name }}, {{ $zone->city->name }}</a></li>
                @empty
                  <li><span>Próximamente más zonas destacadas</span></li>
                @endforelse
              </ul>
            </section>

            <section class="public-clean-footer-column">
              <h3>Recursos</h3>
              <ul>
                @foreach($footerResources as $resource)
                  <li><a href="{{ $resource['url'] }}">{{ $resource['label'] }}</a></li>
                @endforeach
              </ul>
            </section>
          </div>

          <div class="public-clean-footer-divider" role="presentation"></div>

          <div class="public-clean-footer-bottom">
            <span>&copy; {{ now()->year }} {{ $footerSiteName }}</span>
            <nav class="public-clean-footer-inline" aria-label="Enlaces legales">
              <a href="{{ route('static.terms') }}">Términos y condiciones</a>
              <a href="{{ route('static.privacy') }}">Privacidad y cookies</a>
            </nav>
          </div>
        </div>
      </footer>
    @endif

    @stack('scripts')
  </body>
</html>
