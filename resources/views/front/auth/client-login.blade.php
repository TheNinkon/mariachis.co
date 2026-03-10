@extends('front.layouts.public-clean')

@section('title', 'Login Cliente | Mariachis.co')
@section('meta_description', 'Accede a tu cuenta de cliente para gestionar solicitudes y favoritos.')
@section('page_id', 'client-auth')

@section('auth_header_link')
  <a href="{{ route('client.register') }}">Crear cuenta</a>
@endsection

@section('content')
  <main class="client-auth-shell">
    <div class="client-auth-grid">
      <section class="client-auth-hero">
        <p class="client-auth-eyebrow">Acceso cliente</p>
        <h1 class="client-auth-title">Gestiona solicitudes, favoritos y actividad</h1>
        <p class="client-auth-copy">Inicia sesión para seguir tus conversaciones con mariachis desde un solo lugar.</p>
      </section>

      <section class="client-auth-card">
        @if (session('status'))
          <div class="client-auth-alert success">{{ session('status') }}</div>
        @endif
        @if ($errors->has('auth'))
          <div class="client-auth-alert warning">{{ $errors->first('auth') }}</div>
        @endif

        <div>
          <h2 class="client-auth-subtitle">Iniciar sesión</h2>
          <p class="client-auth-copy">Acceso por correo. Social login preparado para próxima fase.</p>
        </div>

        <div class="client-auth-socials">
          <button type="button" class="client-auth-social-btn">Continuar con Google (Próximamente)</button>
          <button type="button" class="client-auth-social-btn">Continuar con Facebook (Próximamente)</button>
          <button type="button" class="client-auth-social-btn">Continuar con Apple (Próximamente)</button>
        </div>

        <form action="{{ route('client.login.attempt') }}" method="POST" class="client-auth-form">
          @csrf
          <div>
            <label for="email" class="client-auth-label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="client-auth-input" />
            @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
          </div>
          <div>
            <label for="password" class="client-auth-label">Contraseña</label>
            <input id="password" name="password" type="password" required class="client-auth-input" />
          </div>
          <div class="client-auth-row">
            <label class="client-auth-check"><input type="checkbox" name="remember" /> Recordarme</label>
            <a href="{{ route('client.password.request') }}" class="client-auth-link">Olvidé mi contraseña</a>
          </div>
          <button type="submit" class="client-auth-btn">Entrar</button>
        </form>

        <p class="client-auth-footnote">¿No tienes cuenta? <a href="{{ route('client.register') }}" class="client-auth-link">Crear cuenta</a></p>
      </section>
    </div>
  </main>
@endsection
