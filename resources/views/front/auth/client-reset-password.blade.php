@extends('front.layouts.public-clean')

@section('title', 'Crear contraseña | Mariachis.co')
@section('meta_description', 'Define tu contraseña para completar tu acceso de cliente.')
@section('page_id', 'client-auth')

@php
  $submitLabel = ($isAccountCreationFlow ?? false) ? 'Crear contraseña' : 'Actualizar contraseña';
  $title = ($isAccountCreationFlow ?? false) ? 'Crea tu contraseña' : 'Actualiza tu contraseña';
  $lead = ($isAccountCreationFlow ?? false)
      ? 'Ya casi está. Define tu contraseña para terminar de preparar tu acceso.'
      : 'Elige una nueva contraseña para volver a entrar a tu cuenta.';
@endphp

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.password.request', ['email' => $request->email]), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow client-auth-stage--flow-centered client-auth-stage--email-options">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <h1 class="client-auth-subtitle">{{ $title }}</h1>
          <p class="client-auth-copy client-auth-copy--centered">{{ $lead }}</p>
        </div>

        <form action="{{ route('client.password.update') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
          @csrf
          <input type="hidden" name="token" value="{{ $request->route('token') }}" />
          <input type="hidden" name="email" value="{{ old('email', $request->email) }}" />

          <div>
            <div class="client-auth-password-wrap">
              <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
                placeholder="Nueva contraseña"
                class="client-auth-input client-auth-input--centered client-auth-input--password"
                data-password-setup-input
              />
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
            @error('password')<p class="client-auth-error">{{ $message }}</p>@enderror
          </div>

          <ul class="client-auth-password-rules" data-password-rules>
            <li data-rule="length">Debe tener al menos 12 caracteres</li>
            <li data-rule="upper">Debe tener al menos 1 letra mayúscula</li>
            <li data-rule="number">Debe tener al menos 1 número</li>
          </ul>

          <div class="client-auth-actions">
            <button type="submit" class="client-auth-btn" data-password-submit disabled>{{ $submitLabel }}</button>
          </div>
        </form>
      </section>
    </div>
  </main>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const input = document.querySelector('[data-password-setup-input]');
      const submit = document.querySelector('[data-password-submit]');
      const toggle = document.querySelector('[data-password-toggle]');

      if (!input || !submit || !toggle) {
        return;
      }

      const openIcon = toggle.querySelector('[data-password-eye-open]');
      const closedIcon = toggle.querySelector('[data-password-eye-closed]');
      const checks = {
        length: function (value) {
          return value.length >= 12;
        },
        upper: function (value) {
          return /[A-Z]/.test(value);
        },
        number: function (value) {
          return /\d/.test(value);
        }
      };

      const renderRules = function () {
        const value = input.value || '';
        let valid = true;

        Object.keys(checks).forEach(function (rule) {
          const item = document.querySelector(`[data-rule="${rule}"]`);
          const passed = checks[rule](value);

          if (!passed) {
            valid = false;
          }

          if (item) {
            item.classList.toggle('is-valid', passed);
          }
        });

        submit.disabled = !valid;
      };

      toggle.addEventListener('click', function () {
        const nextType = input.type === 'password' ? 'text' : 'password';
        const showing = nextType === 'text';

        input.type = nextType;
        toggle.setAttribute('aria-pressed', showing ? 'true' : 'false');
        toggle.setAttribute('aria-label', showing ? 'Ocultar contraseña' : 'Mostrar contraseña');
        openIcon.classList.toggle('is-hidden', showing);
        closedIcon.classList.toggle('is-hidden', !showing);
      });

      input.addEventListener('input', renderRules);
      renderRules();
    });
  </script>
@endpush
