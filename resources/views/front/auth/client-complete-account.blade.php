@extends('front.layouts.public-clean')

@section('title', 'Completa tu cuenta | Mariachis.co')
@section('meta_description', 'Completa tu nombre para dejar listo tu acceso de cliente.')
@section('page_id', 'client-auth')

@php
  $passwordAlreadySet = (bool) ($passwordAlreadySet ?? false);
@endphp

@section('content')
  <main class="client-auth-shell narrow">
    <div class="client-auth-frame">
      <div class="client-auth-back-row">
        @include('front.auth.partials.client-auth-back', ['href' => route('client.dashboard'), 'label' => 'Atrás'])
      </div>

      @if ($passwordAlreadySet)
        <section class="client-auth-stage client-auth-stage--flow client-auth-stage--flow-centered client-auth-stage--email-options">
          @include('front.auth.partials.client-auth-flashes')

          <div>
            <h1 class="client-auth-subtitle">Terminemos tu cuenta</h1>
            <p class="client-auth-copy">Ya casi está, <strong>{{ $user->email }}</strong>.</p>
            <p class="client-auth-copy client-auth-copy--centered">Solo añade tu nombre para completar tu acceso en Mariachis.co.</p>
          </div>

          <form action="{{ route('client.login.complete-account.update') }}" method="POST" class="client-auth-form client-auth-form--compact client-auth-form--centered">
            @csrf
            @method('PATCH')

            <div>
              <input
                id="first_name"
                name="first_name"
                type="text"
                value="{{ old('first_name', $user->first_name) }}"
                autocomplete="given-name"
                required
                placeholder="Nombre"
                class="client-auth-input client-auth-input--centered"
              />
              @error('first_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>

            <div>
              <input
                id="last_name"
                name="last_name"
                type="text"
                value="{{ old('last_name', $user->last_name) }}"
                autocomplete="family-name"
                required
                placeholder="Apellido"
                class="client-auth-input client-auth-input--centered"
              />
              @error('last_name')<p class="client-auth-error">{{ $message }}</p>@enderror
            </div>

            <div class="client-auth-actions">
              <button type="submit" class="client-auth-btn">Crear mi cuenta</button>
            </div>
          </form>
        </section>
      @else
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
      @endif
    </div>
  </main>
@endsection
