@extends('front.layouts.public-clean')

@section('title', 'Nueva Contraseña | Cliente')
@section('meta_description', 'Define tu nueva contraseña de cliente.')
@section('page_id', 'client-auth')

@section('auth_header_link')
  <a href="{{ route('client.login') }}">Iniciar sesión</a>
@endsection

@section('content')
  <main class="client-auth-shell narrow">
    <section class="client-auth-card">
      <h1 class="client-auth-subtitle">Crear nueva contraseña</h1>
      <p class="client-auth-copy">Actualiza tu acceso para volver al panel de cliente.</p>

      <form action="{{ route('client.password.update') }}" method="POST" class="client-auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div>
          <label for="email" class="client-auth-label">Correo electrónico</label>
          <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required class="client-auth-input" />
          @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
        </div>
        <div>
          <label for="password" class="client-auth-label">Contraseña</label>
          <input id="password" name="password" type="password" required class="client-auth-input" />
          @error('password')<p class="client-auth-error">{{ $message }}</p>@enderror
        </div>
        <div>
          <label for="password_confirmation" class="client-auth-label">Confirmar contraseña</label>
          <input id="password_confirmation" name="password_confirmation" type="password" required class="client-auth-input" />
        </div>

        <button type="submit" class="client-auth-btn">Actualizar contraseña</button>
      </form>
    </section>
  </main>
@endsection
