@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Registro Cliente')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6" style="max-width: 680px;">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ route('home') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>

          <h4 class="mb-1">Registro de cliente</h4>
          <p class="mb-6">Crea tu cuenta para solicitar y gestionar mariachis.</p>

          <form class="mb-6" action="{{ route('client.register.store') }}" method="POST">
            @csrf
            <div class="row g-6">
              <div class="col-md-6">
                <label for="first_name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label for="last_name" class="form-label">Apellido</label>
                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label for="phone" class="form-label">Teléfono</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label for="city_name" class="form-label">Ciudad (opcional)</label>
                <input type="text" class="form-control @error('city_name') is-invalid @enderror" id="city_name" name="city_name" value="{{ old('city_name') }}">
                @error('city_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="password">Contraseña</label>
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required>
              </div>
            </div>

            <div class="my-8">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" required>
                <label class="form-check-label" for="terms">Acepto términos y condiciones</label>
                @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Crear cuenta</button>
          </form>

          <p class="text-center mb-0">
            <span>¿Ya tienes cuenta?</span>
            <a href="{{ route('client.login') }}"><span>Iniciar sesión</span></a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
