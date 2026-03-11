@extends('front.layouts.public-clean')

@section('title', 'Completa tu cuenta | Mariachis.co')
@section('meta_description', 'Completa tu nombre y, si quieres, define una contraseña para tu acceso de cliente.')
@section('page_id', 'client-auth')

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <section class="client-auth-stage client-auth-stage--flow">
        @include('front.auth.partials.client-auth-flashes')

        <div>
          <span class="client-auth-step">Cuenta nueva</span>
          <h1 class="client-auth-subtitle">Completa tu cuenta</h1>
          <p class="client-auth-copy">Ya confirmaste tu correo. Añade tu nombre y, si quieres, una contraseña para entrar más rápido la próxima vez.</p>
        </div>

        <div class="client-auth-chip" title="{{ $user->email }}">
          <span>Correo confirmado</span>
          <strong>{{ $user->email }}</strong>
        </div>

        <form action="{{ route('client.login.complete-account.update') }}" method="POST" class="client-auth-form">
          @csrf
          @method('PATCH')

          <div class="client-auth-grid">
            <div>
              <label for="first_name" class="client-auth-label">Nombre</label>
              <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" required class="client-auth-input" />
              @error('first_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>

            <div>
              <label for="last_name" class="client-auth-label">Apellido</label>
              <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" required class="client-auth-input" />
              @error('last_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>
          </div>

          <div>
            <label for="password" class="client-auth-label">Contraseña opcional</label>
            <input id="password" name="password" type="password" autocomplete="new-password" class="client-auth-input" placeholder="Déjala vacía si prefieres entrar con enlace" />
            @error('password')<p class="client-auth-error">{{ $message }}</p>@enderror
          </div>

          <div>
            <label for="password_confirmation" class="client-auth-label">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="client-auth-input" placeholder="Repite la contraseña si decidiste crearla" />
          </div>

          <div class="client-auth-actions">
            <button type="submit" class="client-auth-btn">Guardar y continuar</button>
          </div>
        </form>
      </section>
    </div>
  </main>
@endsection
