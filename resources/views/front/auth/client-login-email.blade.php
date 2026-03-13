@extends('front.layouts.public-clean')

@section('title', 'Ingresa tu correo | Mariachis.co')
@section('meta_description', 'Introduce tu correo para continuar con el acceso a tu cuenta de cliente.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.login'), 'label' => 'Atrás'])
      </div>

      <section class="client-auth-stage client-auth-stage--flow client-auth-stage--flow-centered">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <h1 class="client-auth-subtitle">Continúa con tu correo electrónico</h1>
          <p class="client-auth-copy">Introduce tu correo electrónico para iniciar sesión o crear una cuenta.</p>
        </div>

        <form action="{{ route('client.login.email.capture') }}" method="POST" class="client-auth-form client-auth-form--compact">
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
            <button type="submit" class="client-auth-btn">Continuar</button>
          </div>
        </form>

        <p class="client-auth-legal client-auth-legal--centered">
          Al crear una cuenta, aceptas nuestros <a href="{{ route('static.terms') }}" class="client-auth-link">Términos y condiciones</a>, la
          <a href="{{ route('static.privacy') }}" class="client-auth-link">Política de privacidad</a> y el acuerdo con Mariachis.co.
        </p>
      </section>
    </div>
  </main>
@endsection
