@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Restablecer Contraseña Cliente')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <h4 class="mb-1">Nueva contraseña</h4>
          <p class="mb-6">Define una contraseña segura para tu cuenta de cliente.</p>

          <form action="{{ route('client.password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-6">
              <label for="email" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" required>
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-6">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-6">
              <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">Actualizar contraseña</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
