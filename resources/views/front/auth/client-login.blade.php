@extends('front.layouts.public-clean')

@section('title', 'Iniciar sesión | Mariachis.co')
@section('meta_description', 'Accede a tu cuenta de cliente para revisar solicitudes, favoritos y conversaciones.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell">
    <div class="client-auth-frame client-auth-frame--selector">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => url('/'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--selector">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <h1 class="client-auth-title">Entrar o crear tu acceso</h1>
          <p class="client-auth-copy">Usa tu mismo correo para continuar. Si todavía no existe una cuenta, la preparamos en el proceso.</p>
        </div>

        <div class="client-auth-methods">
          <a href="{{ route('client.login.email') }}" class="client-auth-method-btn client-auth-method-btn--primary">
            <span class="client-auth-method-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.75h16A1.25 1.25 0 0 1 21.25 8v8A1.25 1.25 0 0 1 20 17.25H4A1.25 1.25 0 0 1 2.75 16V8A1.25 1.25 0 0 1 4 6.75Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m3.5 8.25 7.57 5.3a1.6 1.6 0 0 0 1.86 0l7.57-5.3" />
              </svg>
            </span>
            <span class="client-auth-method-copy">
              <strong>Continuar con correo electrónico</strong>
            </span>
          </a>

          @if(isset($socialProviders['google']))
            <a href="{{ route('client.social.redirect', ['provider' => 'google']) }}" class="client-auth-method-btn">
              <span class="client-auth-method-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M21.81 10.02h-9.19v3.96h5.27c-.23 1.27-.96 2.35-2.05 3.07v2.54h3.31c1.94-1.79 3.06-4.42 3.06-7.57 0-.67-.06-1.32-.18-1.95Z" />
                  <path d="M12.62 22c2.77 0 5.09-.92 6.79-2.49l-3.31-2.54c-.92.62-2.09.98-3.48.98-2.67 0-4.93-1.8-5.74-4.22H3.46v2.62A10.26 10.26 0 0 0 12.62 22Z" />
                  <path d="M6.88 13.73a6.16 6.16 0 0 1 0-3.46V7.65H3.46a10.27 10.27 0 0 0 0 8.7l3.42-2.62Z" />
                  <path d="M12.62 6.05c1.5 0 2.85.52 3.91 1.54l2.93-2.93A9.82 9.82 0 0 0 12.62 2 10.26 10.26 0 0 0 3.46 7.65l3.42 2.62c.81-2.42 3.07-4.22 5.74-4.22Z" />
                </svg>
              </span>
              <span class="client-auth-method-copy">
                <strong>Continuar con Google</strong>
              </span>
            </a>
          @endif

          @if(isset($socialProviders['facebook']))
            <a href="{{ route('client.social.redirect', ['provider' => 'facebook']) }}" class="client-auth-method-btn">
              <span class="client-auth-method-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M13.5 21v-8.1h2.7l.4-3.2h-3.1V7.66c0-.93.25-1.57 1.58-1.57H16.7V3.23c-.3-.04-1.3-.13-2.47-.13-2.45 0-4.13 1.5-4.13 4.26v2.37H7.33v3.2h2.77V21h3.4Z" />
                </svg>
              </span>
              <span class="client-auth-method-copy">
                <strong>Continuar con Facebook</strong>
              </span>
            </a>
          @endif
        </div>

        <p class="client-auth-legal">Al continuar aceptas nuestros <a href="{{ route('static.terms') }}" class="client-auth-link">términos de uso</a> y la <a href="{{ route('static.privacy') }}" class="client-auth-link">política de privacidad</a> de Mariachis.co.</p>
      </section>
    </div>
  </main>
@endsection
