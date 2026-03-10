@extends('front.layouts.public-clean')

@section('title', 'Recuperar Contraseña | Cliente')
@section('meta_description', 'Recupera el acceso a tu cuenta de cliente.')
@section('page_id', 'client-auth')

@section('auth_header_link')
  <a href="{{ route('client.login') }}">Iniciar sesión</a>
@endsection

@section('content')
  <main class="client-auth-shell narrow">
    <section class="client-auth-card">
      <h1 class="client-auth-subtitle">Recuperar contraseña</h1>
      <p class="client-auth-copy">Te enviaremos un enlace para crear una nueva contraseña.</p>

      @if (session('status'))
        <div class="client-auth-alert success">{{ session('status') }}</div>
      @endif

      <form action="{{ route('client.password.email') }}" method="POST" class="client-auth-form">
        @csrf
        <div>
          <label for="email" class="client-auth-label">Correo electrónico</label>
          <input id="email" name="email" type="email" value="{{ old('email') }}" required class="client-auth-input" />
          @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="client-auth-btn">Enviar enlace</button>
      </form>

      <p class="client-auth-footnote"><a href="{{ route('client.login') }}" class="client-auth-link">Volver al login</a></p>
    </section>
  </main>
@endsection
