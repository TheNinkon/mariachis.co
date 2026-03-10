@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login Cliente')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ route('home') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>

          <h4 class="mb-1">Acceso cliente</h4>
          <p class="mb-6">Ingresa para gestionar tus solicitudes y favoritos.</p>

          @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
          @endif

          <form class="mb-4" action="{{ route('client.login.attempt') }}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="email" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-6">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
              @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="my-8 d-flex justify-content-between">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" />
                <label class="form-check-label" for="remember">Recordarme</label>
              </div>
              <a href="{{ route('client.password.request') }}"><p class="mb-0">Olvidé mi contraseña</p></a>
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
          </form>

          <p class="text-center mb-0">
            <span>¿No tienes cuenta?</span>
            <a href="{{ route('client.register') }}"><span>Registrarme</span></a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
