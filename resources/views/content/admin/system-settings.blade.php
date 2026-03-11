@extends('layouts/layoutMaster')

@section('title', 'Configuracion del sistema')

@section('content')
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-6">
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">Resumen</h5></div>
        <div class="card-body">
          <div class="d-flex gap-3 mb-4">
            <span class="badge bg-label-primary">Maps</span>
            <div>
              <p class="mb-0 fw-semibold">Google Maps</p>
              <small class="text-muted">Clave centralizada, cifrada y con respaldo en <code>.env</code>.</small>
            </div>
          </div>

          <div class="d-flex gap-3 mb-4">
            <span class="badge bg-label-success">SMTP</span>
            <div>
              <p class="mb-0 fw-semibold">Correo saliente</p>
              <small class="text-muted">
                Mailer activo:
                <strong>{{ strtoupper($smtp['mailer']) }}</strong>
                · Password {{ $smtp['password_configured'] ? 'guardado' : 'no configurado' }}
              </small>
            </div>
          </div>

          <div class="d-flex gap-3">
            <span class="badge bg-label-warning">Test</span>
            <div>
              <p class="mb-0 fw-semibold">Prueba inmediata</p>
              <small class="text-muted">Envia un correo simple para verificar conexión, credenciales y remitente.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <form method="POST" action="{{ route('admin.system-settings.update') }}" class="d-grid gap-6">
        @csrf
        @method('PATCH')

        <div class="card">
          <div class="card-header"><h5 class="mb-0">Google Maps</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Configura Places Autocomplete, geocodificacion y el pais por defecto del marketplace.</p>

            <div class="row g-4">
              <div class="col-12">
                <label class="form-label" for="google_maps_api_key">API key de Google Maps</label>
                <input
                  id="google_maps_api_key"
                  type="password"
                  name="google_maps_api_key"
                  class="form-control @error('google_maps_api_key') is-invalid @enderror"
                  value=""
                  autocomplete="off"
                  placeholder="{{ $googleMaps['enabled'] ? 'Deja vacio para conservar la clave actual o pega una nueva' : 'Pega aqui la clave para Places / Maps / Geocoding' }}"
                />
                @error('google_maps_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">La clave guardada no se vuelve a imprimir en la vista.</small>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input
                    id="clear_google_maps_api_key"
                    type="checkbox"
                    name="clear_google_maps_api_key"
                    value="1"
                    class="form-check-input"
                    {{ old('clear_google_maps_api_key') ? 'checked' : '' }}
                  />
                  <label class="form-check-label" for="clear_google_maps_api_key">Eliminar clave guardada y usar solo el respaldo del entorno</label>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="google_places_country_restriction">Restriccion de Places</label>
                <input
                  id="google_places_country_restriction"
                  type="text"
                  name="google_places_country_restriction"
                  class="form-control @error('google_places_country_restriction') is-invalid @enderror"
                  value="{{ old('google_places_country_restriction', $googleMaps['places_country_restriction']) }}"
                  maxlength="2"
                  required
                />
                @error('google_places_country_restriction')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="marketplace_default_country_code">Codigo de pais por defecto</label>
                <input
                  id="marketplace_default_country_code"
                  type="text"
                  name="marketplace_default_country_code"
                  class="form-control @error('marketplace_default_country_code') is-invalid @enderror"
                  value="{{ old('marketplace_default_country_code', $googleMaps['default_country_code']) }}"
                  maxlength="2"
                  required
                />
                @error('marketplace_default_country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label" for="marketplace_default_country_name">Pais por defecto</label>
                <input
                  id="marketplace_default_country_name"
                  type="text"
                  name="marketplace_default_country_name"
                  class="form-control @error('marketplace_default_country_name') is-invalid @enderror"
                  value="{{ old('marketplace_default_country_name', $googleMaps['default_country_name']) }}"
                  maxlength="120"
                  required
                />
                @error('marketplace_default_country_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h5 class="mb-0">SMTP</h5></div>
          <div class="card-body">
            <p class="text-muted mb-4">Todos los correos del sistema, incluido el magic link de clientes, usarán esta configuración cuando el mailer activo sea <code>smtp</code>.</p>

            <div class="row g-4">
              <div class="col-md-4">
                <label class="form-label" for="mail_mailer">Mailer activo</label>
                <select
                  id="mail_mailer"
                  name="mail_mailer"
                  class="form-select @error('mail_mailer') is-invalid @enderror"
                  required
                >
                  <option value="smtp" {{ old('mail_mailer', $smtp['mailer']) === 'smtp' ? 'selected' : '' }}>SMTP real</option>
                  <option value="log" {{ old('mail_mailer', $smtp['mailer']) === 'log' ? 'selected' : '' }}>Log / desarrollo</option>
                </select>
                @error('mail_mailer')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label" for="mail_smtp_encryption">Seguridad</label>
                <select
                  id="mail_smtp_encryption"
                  name="mail_smtp_encryption"
                  class="form-select @error('mail_smtp_encryption') is-invalid @enderror"
                  required
                >
                  <option value="tls" {{ old('mail_smtp_encryption', $smtp['encryption']) === 'tls' ? 'selected' : '' }}>TLS (587 recomendado)</option>
                  <option value="ssl" {{ old('mail_smtp_encryption', $smtp['encryption']) === 'ssl' ? 'selected' : '' }}>SSL (465 clásico)</option>
                  <option value="none" {{ old('mail_smtp_encryption', $smtp['encryption']) === 'none' ? 'selected' : '' }}>Sin cifrado</option>
                </select>
                @error('mail_smtp_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label" for="mail_smtp_port">Puerto</label>
                <input
                  id="mail_smtp_port"
                  type="number"
                  name="mail_smtp_port"
                  class="form-control @error('mail_smtp_port') is-invalid @enderror"
                  value="{{ old('mail_smtp_port', $smtp['port']) }}"
                  placeholder="587"
                />
                @error('mail_smtp_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="mail_smtp_host">Host SMTP</label>
                <input
                  id="mail_smtp_host"
                  type="text"
                  name="mail_smtp_host"
                  class="form-control @error('mail_smtp_host') is-invalid @enderror"
                  value="{{ old('mail_smtp_host', $smtp['host']) }}"
                  placeholder="smtp.tu-proveedor.com"
                />
                @error('mail_smtp_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="mail_smtp_username">Usuario SMTP</label>
                <input
                  id="mail_smtp_username"
                  type="text"
                  name="mail_smtp_username"
                  class="form-control @error('mail_smtp_username') is-invalid @enderror"
                  value="{{ old('mail_smtp_username', $smtp['username']) }}"
                  placeholder="usuario@dominio.com"
                />
                @error('mail_smtp_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label" for="mail_smtp_password">Password SMTP</label>
                <input
                  id="mail_smtp_password"
                  type="password"
                  name="mail_smtp_password"
                  class="form-control @error('mail_smtp_password') is-invalid @enderror"
                  value=""
                  autocomplete="new-password"
                  placeholder="{{ $smtp['password_configured'] ? 'Deja vacio para conservar la contraseña actual' : 'Pega aqui la contraseña o app password' }}"
                />
                @error('mail_smtp_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Nunca se vuelve a mostrar. Estado actual: {{ $smtp['password_configured'] ? 'configurada' : 'sin guardar' }}.</small>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input
                    id="clear_mail_smtp_password"
                    type="checkbox"
                    name="clear_mail_smtp_password"
                    value="1"
                    class="form-check-input"
                    {{ old('clear_mail_smtp_password') ? 'checked' : '' }}
                  />
                  <label class="form-check-label" for="clear_mail_smtp_password">Eliminar contraseña guardada</label>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="mail_from_address">From address</label>
                <input
                  id="mail_from_address"
                  type="email"
                  name="mail_from_address"
                  class="form-control @error('mail_from_address') is-invalid @enderror"
                  value="{{ old('mail_from_address', $smtp['from_address']) }}"
                  placeholder="hola@tudominio.com"
                  required
                />
                @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="mail_from_name">From name</label>
                <input
                  id="mail_from_name"
                  type="text"
                  name="mail_from_name"
                  class="form-control @error('mail_from_name') is-invalid @enderror"
                  value="{{ old('mail_from_name', $smtp['from_name']) }}"
                  placeholder="Mariachis.co"
                  required
                />
                @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Guardar configuracion</button>
        </div>
      </form>

      <div class="card mt-6">
        <div class="card-header"><h5 class="mb-0">Enviar correo de prueba</h5></div>
        <div class="card-body">
          <p class="text-muted mb-4">Envía un correo inmediato con el mailer activo para confirmar conexión, remitente y capacidad de entrega.</p>

          <form method="POST" action="{{ route('admin.system-settings.smtp.test') }}" class="row g-4 align-items-end">
            @csrf

            <div class="col-md-8">
              <label class="form-label" for="mail_test_recipient">Destinatario</label>
              <input
                id="mail_test_recipient"
                type="email"
                name="mail_test_recipient"
                class="form-control @error('mail_test_recipient') is-invalid @enderror"
                value="{{ old('mail_test_recipient') }}"
                placeholder="tu-correo@dominio.com"
                required
              />
              @error('mail_test_recipient')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 d-grid">
              <button type="submit" class="btn btn-outline-primary">Enviar prueba</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
