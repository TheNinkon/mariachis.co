@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Registro Mariachi')

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
    <div class="authentication-inner py-6" style="max-width: 680px;">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ route('home') }}" class="app-brand-link">
              <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 42px; width: auto;" />
            </a>
          </div>

          <h4 class="mb-1">Registro inicial de mariachi</h4>
          <p class="mb-6">Crea tu cuenta y completa tu perfil despues desde tu panel.</p>

          <form class="mb-6" action="{{ route('mariachi.register.store') }}" method="POST">
            @csrf
            <div class="row g-6">
              <div class="col-md-6 form-control-validation">
                <label for="first_name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                @error('first_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 form-control-validation">
                <label for="last_name" class="form-label">Apellido</label>
                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 form-control-validation">
                <label for="email" class="form-label">Correo electronico</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 form-control-validation">
                <label for="phone" class="form-label">Telefono movil</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="password">Contrasena</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="************" required>
                  <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                </div>
                @error('password')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="password_confirmation">Confirmar contrasena</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="************" required>
                  <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                </div>
              </div>

            </div>

            <div class="my-8 form-control-validation">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" required>
                <label class="form-check-label" for="terms">Acepto terminos y condiciones</label>
                @error('terms')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Crear cuenta</button>
          </form>

          <p class="text-center mb-0">
            <span>Ya tienes cuenta?</span>
            <a href="{{ route('mariachi.login') }}"><span>Iniciar sesion</span></a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
