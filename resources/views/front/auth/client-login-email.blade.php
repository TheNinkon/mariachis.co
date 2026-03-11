@extends('front.layouts.public-clean')

@section('title', 'Ingresa tu correo | Mariachis.co')
@section('meta_description', 'Introduce tu correo para continuar con el acceso a tu cuenta de cliente.')
@section('page_id', 'client-auth')

@section('auth_header_link')
  <a href="{{ route('client.register') }}">Crear cuenta</a>
@endsection

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-grid">
      <section class="client-auth-card">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <span class="client-auth-step">Paso 1 de 3</span>
          <h1 class="client-auth-subtitle">Ingresa tu correo</h1>
          <p class="client-auth-copy">Usaremos tu email para mostrarte la mejor forma de entrar a tu cuenta.</p>
        </div>

        <form action="{{ route('client.login.email.capture') }}" method="POST" class="client-auth-form">
          @csrf
          <div>
            <label for="email" class="client-auth-label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ $email }}" autocomplete="email" required class="client-auth-input" />
            @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
          </div>

          <div class="client-auth-actions">
            <button type="submit" class="client-auth-btn">Continuar</button>
            <a href="{{ route('client.login') }}" class="client-auth-btn secondary client-auth-btn--linkish">Volver</a>
          </div>
        </form>

        <p class="client-auth-footnote">¿No tienes cuenta? <a href="{{ route('client.register') }}" class="client-auth-link">Crear cuenta</a></p>
      </section>
    </div>
  </main>
@endsection
