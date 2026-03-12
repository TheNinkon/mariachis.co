@php
$customizerHidden = 'customizer-hide';
$portal = $portal ?? 'admin';
$formAction = $portal === 'mariachi' ? route('mariachi.password.update') : route('password.update');
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Restablecer Contrasena')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <h4 class="mb-1">Nueva contrasena</h4>
          <p class="mb-6">Define una contrasena segura para tu cuenta.</p>

          <form action="{{ $formAction }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-6">
              <label for="email" class="form-label">Correo electronico</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6">
              <label for="password" class="form-label">Contrasena</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6">
              <label for="password_confirmation" class="form-label">Confirmar contrasena</label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Actualizar contrasena</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
