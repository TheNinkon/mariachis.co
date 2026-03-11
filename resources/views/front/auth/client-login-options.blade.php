@extends('front.layouts.public-clean')

@section('title', 'Elige cómo entrar | Mariachis.co')
@section('meta_description', 'Selecciona si quieres recibir un enlace de acceso o usar tu contraseña.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.login.email'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <span class="client-auth-step">Paso 2 de 3</span>
          <h1 class="client-auth-subtitle">¿Cómo quieres entrar?</h1>
          <p class="client-auth-copy">
            {{ $canUsePassword ? 'Este correo ya tiene una cuenta cliente activa.' : 'Si este correo aún no tiene cuenta, la crearemos cuando confirmes el enlace.' }}
          </p>
        </div>

        <div class="client-auth-chip" title="{{ $email }}">
          <span>Correo</span>
          <strong>{{ $email }}</strong>
        </div>

        <div class="client-auth-choice-grid">
          <form action="{{ route('client.login.magic.send') }}" method="POST" class="client-auth-choice-card">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />
            <div class="client-auth-choice-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4m0 10v4M4.93 4.93l2.83 2.83m8.48 8.48 2.83 2.83M3 12h4m10 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83" />
              </svg>
            </div>
            <div class="client-auth-choice-copy">
              <strong>Recibir un enlace seguro</strong>
              <p>{{ $canUsePassword ? 'Te enviamos un acceso de un solo uso a tu correo.' : 'Te enviamos un enlace para entrar y dejar tu cuenta lista.' }}</p>
            </div>
            <button type="submit" class="client-auth-btn">Enviar enlace</button>
          </form>

          <div class="client-auth-choice-card">
            <div class="client-auth-choice-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V8.25a4.5 4.5 0 1 0-9 0v2.25m-1.5 0h12a1.5 1.5 0 0 1 1.5 1.5v6A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18v-6A1.5 1.5 0 0 1 6 10.5Z" />
              </svg>
            </div>
            <div class="client-auth-choice-copy">
              <strong>{{ $canUsePassword ? 'Usar contraseña' : 'Primero confirma tu correo' }}</strong>
              <p>{{ $canUsePassword ? 'Accede con la contraseña que ya usas en tu cuenta.' : 'La contraseña aparece cuando el correo ya tiene una cuenta activa.' }}</p>
            </div>
            @if($canUsePassword)
              <a href="{{ route('client.login.password') }}" class="client-auth-btn secondary client-auth-btn--linkish">Continuar con contraseña</a>
            @else
              <button type="button" class="client-auth-btn secondary" disabled aria-disabled="true">Aún no disponible</button>
            @endif
          </div>
        </div>

        <div class="client-auth-inline-links">
          <a href="{{ route('client.login.email') }}" class="client-auth-link">Cambiar correo</a>
          <a href="{{ route('client.password.request') }}" class="client-auth-link">Olvidé mi contraseña</a>
        </div>
      </section>
    </div>
  </main>
@endsection
