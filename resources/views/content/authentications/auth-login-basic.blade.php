@php
$customizerHidden = 'customizer-hide';
$portal = $portal ?? 'admin';
$loginAction = $portal === 'mariachi' ? route('mariachi.login.attempt') : route('login.attempt');
$forgotPasswordRoute = $portal === 'mariachi' ? route('mariachi.password.request') : route('password.request');
$panelLabel = $portal === 'mariachi' ? 'Panel mariachi' : 'Panel interno';
$supportText = $portal === 'mariachi' ? 'Ingresa para gestionar tu anuncio y solicitudes' : 'Ingresa para continuar';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Iniciar Sesion')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ route('home') }}" class="app-brand-link">
              <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 42px; width: auto;" />
            </a>
          </div>

          <h4 class="mb-1">{{ $panelLabel }}</h4>
          <p class="mb-6">{{ $supportText }}</p>

          @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
          @endif

          <form id="formAuthentication" class="mb-4" action="{{ $loginAction }}" method="POST">
            @csrf

            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">Correo electronico</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                value="{{ old('email') }}" placeholder="tu-correo@dominio.com" autofocus />
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">Contrasena</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password"
                  placeholder="************" aria-describedby="password" />
                <button
                  type="button"
                  class="input-group-text cursor-pointer"
                  aria-label="Mostrar u ocultar contraseña"
                  aria-controls="password"
                  data-password-toggle>
                  <i class="icon-base ti tabler-eye-off"></i>
                </button>
              </div>
              @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                  <label class="form-check-label" for="remember">Recordarme</label>
                </div>
                <a href="{{ $forgotPasswordRoute }}">
                  <p class="mb-0">Olvide mi contrasena</p>
                </a>
              </div>
            </div>

            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
            </div>
          </form>

          @if ($portal === 'mariachi')
            <p class="text-center mb-0">
              <span>Mariachi nuevo?</span>
              <a href="{{ route('mariachi.register') }}"><span>Crear cuenta</span></a>
            </p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
