@extends('front.layouts.public-clean')

@section('title', 'Ingresa tu contraseña | Mariachis.co')
@section('meta_description', 'Introduce tu contraseña para entrar a tu cuenta de cliente.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.login.email.options'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <span class="client-auth-step">Paso 3 de 3</span>
          <h1 class="client-auth-subtitle">Usa tu contraseña</h1>
          <p class="client-auth-copy">Entra con la clave asociada a tu cuenta de cliente.</p>
        </div>

        <div class="client-auth-chip" title="{{ $email }}">
          <span>Correo</span>
          <strong>{{ $email }}</strong>
        </div>

        <form action="{{ route('client.login.attempt') }}" method="POST" class="client-auth-form">
          @csrf
          <input type="hidden" name="email" value="{{ $email }}" />

          <div>
            <label for="password" class="client-auth-label">Contraseña</label>
            <div class="client-auth-password-wrap">
              <input id="password" name="password" type="password" autocomplete="current-password" required class="client-auth-input client-auth-input--password" />
              <button type="button" class="client-auth-password-toggle" data-password-toggle aria-label="Mostrar contraseña" aria-pressed="false">
                <svg data-password-eye-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12C3.73 7.94 7.52 5 12 5c4.48 0 8.27 2.94 9.54 7-1.27 4.06-5.06 7-9.54 7-4.48 0-8.27-2.94-9.54-7Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.25A3.25 3.25 0 1 0 12 8.75a3.25 3.25 0 0 0 0 6.5Z" />
                </svg>
                <svg data-password-eye-closed class="is-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 5c4.48 0 8.27 2.94 9.54 7a11.01 11.01 0 0 1-4.04 5.27M6.24 6.24A10.99 10.99 0 0 0 2.46 12c1.27 4.06 5.06 7 9.54 7 1.61 0 3.14-.38 4.49-1.07" />
                </svg>
              </button>
            </div>
            @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
          </div>

          <div class="client-auth-row">
            <label class="client-auth-check"><input type="checkbox" name="remember" /> Mantener mi sesión</label>
            <a href="{{ route('client.password.request') }}" class="client-auth-link">Olvidé mi contraseña</a>
          </div>

          <div class="client-auth-actions">
            <button type="submit" class="client-auth-btn">Entrar</button>
          </div>
        </form>
      </section>
    </div>
  </main>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggle = document.querySelector('[data-password-toggle]');
      const input = document.querySelector('#password');

      if (!toggle || !input) {
        return;
      }

      const openIcon = toggle.querySelector('[data-password-eye-open]');
      const closedIcon = toggle.querySelector('[data-password-eye-closed]');

      toggle.addEventListener('click', function () {
        const nextType = input.type === 'password' ? 'text' : 'password';
        const showing = nextType === 'text';

        input.type = nextType;
        toggle.setAttribute('aria-pressed', showing ? 'true' : 'false');
        toggle.setAttribute('aria-label', showing ? 'Ocultar contraseña' : 'Mostrar contraseña');
        openIcon.classList.toggle('is-hidden', showing);
        closedIcon.classList.toggle('is-hidden', !showing);
      });
    });
  </script>
@endpush
