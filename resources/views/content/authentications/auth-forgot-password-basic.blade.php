@php
$customizerHidden = 'customizer-hide';
$portal = $portal ?? 'admin';
$formAction = $portal === 'mariachi' ? route('mariachi.password.email') : route('password.email');
$loginRoute = $portal === 'mariachi' ? route('mariachi.login') : route('login');
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Recuperar Contrasena')

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
              <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 42px; width: auto;" />
            </a>
          </div>

          <h4 class="mb-1">Recuperar contrasena</h4>
          <p class="mb-6">Te enviaremos un enlace para restablecerla.</p>

          @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
          @endif

          <form action="{{ $formAction }}" method="POST" class="mb-4">
            @csrf
            <div class="mb-6">
              <label for="email" class="form-label">Correo electronico</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Enviar enlace</button>
          </form>

          <p class="text-center mb-0">
            <a href="{{ $loginRoute }}">Volver al login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
