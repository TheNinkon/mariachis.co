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
    <link rel="stylesheet" href="assets/theme.css?v=20260311-client-auth-v9" />
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
      if (! $isClientAuthFlow) {
          $footerCities = \App\Models\MariachiProfile::query()
              ->published()
              ->selectRaw('city_name, count(*) as total')
              ->whereNotNull('city_name')
              ->where('city_name', '!=', '')
              ->groupBy('city_name')
              ->orderByDesc('total')
              ->limit(5)
              ->get();
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
                <a href="{{ route('client.dashboard') }}">Mi cuenta</a>
                <a href="{{ route('client.dashboard') }}">Solicitudes / mensajería</a>
                <a href="{{ route('client.account.favorites') }}">Lista de deseos</a>
                <a href="{{ route('client.account.recent') }}">Vistos recientemente</a>
                <a href="{{ route('client.account.profile') }}">Perfil</a>
                <a href="{{ route('client.account.security') }}">Seguridad</a>
                <form action="{{ $clientLogoutRoute }}" method="POST">
                  @csrf
                  <button type="submit">Cerrar sesión</button>
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
            <a href="/#como-funciona">Cómo funciona</a>
          </nav>
        </div>
      </footer>
    @else
      <footer class="public-clean-footer">
        <div class="public-clean-footer-inner public-clean-footer-grid layout-shell">
          <section>
            <a class="brand-logo brand-logo--footer" href="/" aria-label="mariachis.co">
              <span class="brand-logo-copy">
                <span class="brand-logo-word">
                  <img src="assets/logo-wordmark.png" alt="Mariachis.co" class="brand-logo-image" />
                </span>
                <span class="brand-logo-sub">marketplace colombiano</span>
              </span>
            </a>
            <p class="public-clean-footer-text">Marketplace para contratar mariachis en Colombia con perfiles reales, contacto directo y búsqueda por ciudad.</p>
            <div class="public-clean-footer-chips">
              <span>SEO local</span>
              <span>WhatsApp first</span>
              <span>Mobile</span>
            </div>
          </section>

          <section>
            <h3>Ciudades populares</h3>
            <ul>
              @forelse($footerCities as $city)
                <li><a href="{{ route('seo.landing.slug', ['slug' => \Illuminate\Support\Str::slug($city->city_name)]) }}">Mariachis en {{ $city->city_name }}</a></li>
              @empty
                <li><span>Sin ciudades activas todavía</span></li>
              @endforelse
            </ul>
          </section>

          <section>
            <h3>Marketplace</h3>
            <ul>
              <li><a href="/#como-funciona">Cómo funciona</a></li>
              <li><a href="/#soy-mariachi">Publica tu anuncio</a></li>
              <li><a href="/mariachis/bogota">Anuncios en tu ciudad</a></li>
              <li><a href="/blog">Blog</a></li>
              <li><a href="{{ route('static.help') }}">Centro de ayuda</a></li>
            </ul>
          </section>
        </div>
      </footer>
    @endif

    @stack('scripts')
  </body>
</html>
