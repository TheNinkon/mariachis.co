@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Registro Mariachi')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/select2/select2.js'])
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

          <form id="formAuthentication" class="mb-6" action="{{ route('mariachi.register.store') }}" method="POST">
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
                <label for="phone_country_iso2" class="form-label">Telefono movil</label>
                <div class="row g-3">
                  <div class="col-sm-5">
                    <select
                      class="form-select select2-phone-country @error('phone_country_iso2') is-invalid @enderror"
                      id="phone_country_iso2"
                      name="phone_country_iso2"
                      required>
                      @foreach($phoneCountryOptions as $countryOption)
                        <option value="{{ $countryOption['iso2'] }}" @selected(old('phone_country_iso2', $defaultPhoneCountryIso2) === $countryOption['iso2'])>
                          ({{ $countryOption['dial_code'] }}) {{ $countryOption['name'] }}
                        </option>
                      @endforeach
                    </select>
                    @error('phone_country_iso2')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-sm-7">
                    <input
                      type="text"
                      class="form-control @error('phone_number') is-invalid @enderror"
                      id="phone_number"
                      name="phone_number"
                      value="{{ old('phone_number') }}"
                      placeholder="300 123 4567"
                      inputmode="tel"
                      required>
                    @error('phone_number')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="password">Contrasena</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="************" required>
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

              <div class="col-md-6 form-password-toggle form-control-validation">
                <label class="form-label" for="password_confirmation">Confirmar contrasena</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="************" required>
                  <button
                    type="button"
                    class="input-group-text cursor-pointer"
                    aria-label="Mostrar u ocultar contraseña"
                    aria-controls="password_confirmation"
                    data-password-toggle>
                    <i class="icon-base ti tabler-eye-off"></i>
                  </button>
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
