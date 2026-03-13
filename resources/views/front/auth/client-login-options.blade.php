@extends('front.layouts.public-clean')

@section('title', 'Continúa con tu acceso | Mariachis.co')
@section('meta_description', 'Elige cómo continuar con tu acceso usando tu correo electrónico.')
@section('page_id', 'client-auth')

@php
  $showConfirmation = $magicLinkSent ?? false;
  $viewErrors = $errors ?? session('errors');
  $remainingCooldownSeconds = (int) ($remainingCooldownSeconds ?? 0);
@endphp

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.login.email'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow client-auth-stage--flow-centered client-auth-stage--email-options">
        @if ($viewErrors && $viewErrors->has('auth'))
          <div class="client-auth-alert warning">{{ $viewErrors->first('auth') }}</div>
        @endif

        @if ($showConfirmation)
          <div>
            <h1 class="client-auth-subtitle">Comprueba tu correo electrónico</h1>
            <p class="client-auth-copy">Hemos enviado un enlace seguro y de un solo uso a</p>
            <p class="client-auth-confirm-email">{{ $email }}</p>
          </div>

          <form action="{{ route('client.login.magic.send') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />
            <div class="client-auth-actions">
              <button
                type="submit"
                class="client-auth-btn secondary"
                data-magic-resend-button
                data-cooldown-seconds="{{ $remainingCooldownSeconds }}"
                data-default-label="Reenviar correo electrónico"
                @disabled($remainingCooldownSeconds > 0)
              >
                Reenviar correo electrónico
              </button>
            </div>
          </form>

          <div class="client-auth-confirm-copy">
            <p class="client-auth-copy client-auth-copy--centered">
              Toca el enlace del correo para iniciar sesión o crear tu cuenta. Este enlace caduca en {{ $magicLinkTtlMinutes }} minutos.
            </p>
            <p class="client-auth-footnote client-auth-footnote--centered">
              Si no lo encuentras, revisa promociones, spam o correo no deseado.
            </p>
          </div>

          <div class="client-auth-inline-links client-auth-inline-links--centered">
            <a href="{{ route('client.login.email') }}" class="client-auth-link">Cambiar correo</a>
          </div>
        @elseif ($canUsePassword)
          <div>
            <h1 class="client-auth-subtitle">Entra o crea tu acceso con el mismo correo</h1>
            <p class="client-auth-copy">Enviaremos un enlace seguro y de un solo uso a</p>
            <p class="client-auth-confirm-email">{{ $email }}</p>
          </div>

          <form action="{{ route('client.login.magic.send') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />
            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn">Recibe un enlace</button>
            </div>
          </form>

          <div class="client-auth-divider" aria-hidden="true">
            <span>o</span>
          </div>

          <div class="client-auth-form client-auth-form--compact client-auth-form--centered">
            <div class="client-auth-actions">
              <a href="{{ route('client.login.password') }}" class="client-auth-btn secondary client-auth-btn--linkish">
                O bien, usa la contraseña
              </a>
            </div>
          </div>

          <p class="client-auth-legal client-auth-legal--centered">
            Al crear una cuenta, aceptas nuestros <a href="{{ route('static.terms') }}" class="client-auth-link">Términos y condiciones</a>, la
            <a href="{{ route('static.privacy') }}" class="client-auth-link">Política de privacidad</a> y el acuerdo con Mariachis.co.
          </p>
        @else
          <div>
            <h1 class="client-auth-subtitle">Entra o crea tu acceso con un enlace seguro</h1>
            <p class="client-auth-copy">Enviaremos un enlace seguro y de un solo uso a</p>
            <p class="client-auth-confirm-email">{{ $email }}</p>
          </div>

          <form action="{{ route('client.login.magic.send') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />
            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn">Recibe un enlace</button>
            </div>
          </form>

          @if ($showPasswordOption ?? true)
            <div class="client-auth-divider" aria-hidden="true">
              <span>o</span>
            </div>

            <div class="client-auth-form client-auth-form--compact client-auth-form--centered">
              <div class="client-auth-actions">
                <a href="{{ route('client.login.password') }}" class="client-auth-btn secondary client-auth-btn--linkish">
                  O bien, usa la contraseña
                </a>
              </div>
            </div>
          @endif

          <p class="client-auth-copy client-auth-copy--centered">
            Toca el enlace que recibirás por correo para iniciar sesión o crear tu cuenta.
          </p>

          <div class="client-auth-inline-links client-auth-inline-links--centered">
            <a href="{{ route('client.login.email') }}" class="client-auth-link">Cambiar correo</a>
          </div>
        @endif
      </section>
    </div>
  </main>
@endsection

@push('scripts')
  @if ($showConfirmation)
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const button = document.querySelector('[data-magic-resend-button]');

        if (!button) {
          return;
        }

        const defaultLabel = button.dataset.defaultLabel || 'Reenviar correo electrónico';
        let remaining = Number(button.dataset.cooldownSeconds || 0);

        const render = function () {
          if (remaining > 0) {
            button.disabled = true;
            button.textContent = `${defaultLabel} (${remaining} s)`;
            return;
          }

          button.disabled = false;
          button.textContent = defaultLabel;
        };

        render();

        if (remaining <= 0) {
          return;
        }

        const timer = window.setInterval(function () {
          remaining -= 1;
          render();

          if (remaining <= 0) {
            window.clearInterval(timer);
          }
        }, 1000);
      });
    </script>
  @endif
@endpush
