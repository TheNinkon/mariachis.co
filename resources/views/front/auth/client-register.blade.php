@extends('front.layouts.public-clean')

@section('title', 'Registro Cliente | Mariachis.co')
@section('meta_description', 'Crea tu cuenta de cliente para solicitar presupuestos y guardar favoritos.')
@section('page_id', 'client-auth')

@section('auth_header_link')
  <a href="{{ route('client.login') }}">Iniciar sesión</a>
@endsection

@section('content')
  <main class="client-auth-shell">
    <div class="client-auth-grid client-auth-grid--split">
      <section class="client-auth-hero">
        <p class="client-auth-eyebrow">Cuenta cliente</p>
        <h1 class="client-auth-title">Regístrate en menos de un minuto</h1>
        <p class="client-auth-copy">Desde tu panel podrás ver solicitudes, favoritos y actividad reciente.</p>
      </section>

      <section class="client-auth-card">
        <h2 class="client-auth-subtitle">Crear cuenta</h2>
        <p class="client-auth-copy">Registro en 2 pasos.</p>

        <form id="client-register-form" action="{{ route('client.register.store') }}" method="POST" class="client-auth-form">
          @csrf
          <div data-register-step="1">
            <div>
              <label for="email" class="client-auth-label">Paso 1 · Correo electrónico</label>
              <input id="email" name="email" type="email" value="{{ old('email') }}" required class="client-auth-input" />
              @error('email')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>
            <button id="go-step-2" type="button" class="client-auth-btn">Continuar</button>
          </div>

          <div data-register-step="2" class="is-hidden">
            <div>
              <label for="first_name" class="client-auth-label">Paso 2 · Nombre</label>
              <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="client-auth-input" />
              @error('first_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="last_name" class="client-auth-label">Apellido</label>
              <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="client-auth-input" />
              @error('last_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="password" class="client-auth-label">Contraseña</label>
              <input id="password" name="password" type="password" class="client-auth-input" />
              @error('password')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="password_confirmation" class="client-auth-label">Confirmar contraseña</label>
              <input id="password_confirmation" name="password_confirmation" type="password" class="client-auth-input" />
            </div>

            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn">Crear cuenta</button>
              <button id="back-step-1" type="button" class="client-auth-btn secondary">Volver</button>
            </div>
          </div>
        </form>

        <p class="client-auth-footnote">¿Ya tienes cuenta? <a href="{{ route('client.login') }}" class="client-auth-link">Iniciar sesión</a></p>
      </section>
    </div>
  </main>
@endsection

@push('scripts')
<script>
  (function () {
    const step1 = document.querySelector('[data-register-step="1"]');
    const step2 = document.querySelector('[data-register-step="2"]');
    const email = document.getElementById('email');
    const first = document.getElementById('first_name');
    const last = document.getElementById('last_name');
    const pass = document.getElementById('password');
    const pass2 = document.getElementById('password_confirmation');

    document.getElementById('go-step-2')?.addEventListener('click', function () {
      if (!email || !email.value.trim()) {
        email?.focus();
        return;
      }

      step1?.classList.add('is-hidden');
      step2?.classList.remove('is-hidden');
      first?.setAttribute('required', 'required');
      last?.setAttribute('required', 'required');
      pass?.setAttribute('required', 'required');
      pass2?.setAttribute('required', 'required');
      first?.focus();
    });

    document.getElementById('back-step-1')?.addEventListener('click', function () {
      step2?.classList.add('is-hidden');
      step1?.classList.remove('is-hidden');
    });

    @if($errors->has('first_name') || $errors->has('last_name') || $errors->has('password'))
      step1?.classList.add('is-hidden');
      step2?.classList.remove('is-hidden');
      first?.setAttribute('required', 'required');
      last?.setAttribute('required', 'required');
      pass?.setAttribute('required', 'required');
      pass2?.setAttribute('required', 'required');
    @endif
  })();
</script>
@endpush
