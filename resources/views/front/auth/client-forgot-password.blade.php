@extends('front.layouts.public-clean')

@section('title', 'Crear o restablecer contraseña | Mariachis.co')
@section('meta_description', 'Crea o restablece la contraseña de tu cuenta de cliente.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.login.password'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow client-auth-stage--flow-centered client-auth-stage--email-options">
        @include('front.auth.partials.client-auth-flashes')

        @if ($linkSent)
          <div>
            <h1 class="client-auth-subtitle">
              {{ $intent === 'create'
                ? 'Hemos enviado un correo para crear tu contraseña a'
                : 'Hemos enviado un enlace para restablecer tu contraseña a' }}
            </h1>
            <p class="client-auth-confirm-email client-auth-confirm-email--large">{{ $email }}</p>
          </div>

          <div class="client-auth-confirm-copy">
            <p class="client-auth-copy client-auth-copy--centered">
              {{ $intent === 'create'
                ? 'Revisa tu bandeja de entrada y toca el enlace para crear tu contraseña y terminar de preparar tu cuenta.'
                : 'Revisa tu bandeja de entrada y toca el enlace para definir tu nueva contraseña.' }}
            </p>
            <p class="client-auth-footnote client-auth-footnote--centered">
              ¿No has recibido el enlace? Revisa promociones, spam o correo no deseado.
            </p>
          </div>

          <form action="{{ route('client.password.email') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}" />
            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn secondary">Reenviar correo electrónico</button>
            </div>
          </form>
        @else
          <div>
            <h1 class="client-auth-subtitle">Prepara tu contraseña</h1>
            <p class="client-auth-copy">Te enviaremos un enlace para crear o actualizar tu contraseña de acceso.</p>
          </div>

          <form action="{{ route('client.password.email') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            <div>
              <input
                id="email"
                name="email"
                type="email"
                value="{{ $email }}"
                autocomplete="email"
                placeholder="Tu correo electrónico"
                aria-label="Tu correo electrónico"
                required
                class="client-auth-input client-auth-input--centered"
              />
              @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>

            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn">Enviar enlace</button>
            </div>
          </form>
        @endif
      </section>
    </div>
  </main>
@endsection
