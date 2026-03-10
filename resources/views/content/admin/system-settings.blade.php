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
        <div class="card-header"><h5 class="mb-0">Google Maps</h5></div>
        <div class="card-body">
          <p class="text-muted mb-3">Esta configuracion alimenta Places Autocomplete, geocodificacion y cualquier mapa que se use en admin o frontend.</p>
          <div class="d-flex gap-3 mb-3">
            <span class="badge bg-label-primary">1</span>
            <div>
              <p class="mb-0 fw-semibold">Clave centralizada</p>
              <small class="text-muted">Se guarda en configuracion interna cifrada y puede seguir usando <code>.env</code> como respaldo.</small>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <span class="badge bg-label-primary">2</span>
            <div>
              <p class="mb-0 fw-semibold">Exposicion controlada</p>
              <small class="text-muted">Solo se inyecta en pantallas que realmente cargan Google Maps.</small>
            </div>
          </div>
          <div class="d-flex gap-3">
            <span class="badge bg-label-primary">3</span>
            <div>
              <p class="mb-0 fw-semibold">Preparado para Colombia</p>
              <small class="text-muted">La restriccion por pais y el pais por defecto quedan configurables.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Parametros activos</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.system-settings.update') }}">
            @csrf
            @method('PATCH')

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
                <small class="text-muted">La clave guardada no se vuelve a imprimir en la vista. Si este campo queda vacio, se conserva la actual y el sistema puede seguir usando <code>GOOGLE_MAPS_API_KEY</code> como respaldo.</small>
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

            <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <small class="text-muted">Estado actual: {{ $googleMaps['enabled'] ? 'configurado' : 'sin clave activa' }}</small>
              <button type="submit" class="btn btn-primary">Guardar configuracion</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
