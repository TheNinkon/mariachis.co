@extends('layouts/layoutMaster')

@section('title', 'Social Login')

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
        <div class="card-header">
          <h5 class="mb-0">Resumen</h5>
        </div>
        <div class="card-body d-grid gap-4">
          @foreach($providers as $provider)
            <div class="d-flex gap-3">
              <span class="badge {{ $provider['enabled'] ? 'bg-label-success' : 'bg-label-secondary' }}">
                {{ $provider['label'] }}
              </span>
              <div>
                <p class="mb-0 fw-semibold">{{ $provider['enabled'] ? 'Visible en login' : 'Oculto en login' }}</p>
                <small class="text-muted">
                  Client ID {{ $provider['client_id'] !== '' ? 'cargado' : 'pendiente' }}
                  · Secret {{ $provider['secret_configured'] ? 'en .env' : 'faltante' }}
                </small>
              </div>
            </div>
          @endforeach

          <div class="border rounded p-4 bg-lighter">
            <p class="mb-2 fw-semibold">Apple</p>
            <small class="text-muted">Desactivado por ahora. No se muestra en el login cliente ni tiene rutas activas.</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <form method="POST" action="{{ route('admin.social-login-settings.update') }}" class="d-grid gap-6">
        @csrf
        @method('PATCH')

        @foreach($providers as $provider)
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0">{{ $provider['label'] }}</h5>
                <small class="text-muted">Visible solo si está activo y el secret existe en el entorno.</small>
              </div>
              <div class="form-check form-switch m-0">
                <input
                  id="{{ $provider['key'] }}_enabled"
                  type="checkbox"
                  name="{{ $provider['key'] }}_enabled"
                  value="1"
                  class="form-check-input"
                  {{ old($provider['key'].'_enabled', $provider['enabled']) ? 'checked' : '' }}
                />
                <label class="form-check-label" for="{{ $provider['key'] }}_enabled">Activo</label>
              </div>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-12">
                  <label class="form-label" for="{{ $provider['key'] }}_client_id">
                    {{ $provider['key'] === 'facebook' ? 'App ID' : 'Client ID' }}
                  </label>
                  <input
                    id="{{ $provider['key'] }}_client_id"
                    type="text"
                    name="{{ $provider['key'] }}_client_id"
                    class="form-control @error($provider['key'].'_client_id') is-invalid @enderror"
                    value="{{ old($provider['key'].'_client_id', $provider['client_id']) }}"
                    placeholder="{{ $provider['key'] === 'facebook' ? 'Pega aquí el App ID de Meta' : 'Pega aquí el Client ID de Google' }}"
                  />
                  @error($provider['key'].'_client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                  <label class="form-label" for="{{ $provider['key'] }}_redirect_uri">Redirect URI</label>
                  <input
                    id="{{ $provider['key'] }}_redirect_uri"
                    type="url"
                    name="{{ $provider['key'] }}_redirect_uri"
                    class="form-control @error($provider['key'].'_redirect_uri') is-invalid @enderror"
                    value="{{ old($provider['key'].'_redirect_uri', $provider['redirect']) }}"
                    placeholder="{{ $provider['callback_url'] }}"
                  />
                  @error($provider['key'].'_redirect_uri')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                  <label class="form-label">Callback URL para copiar</label>
                  <div class="input-group">
                    <input
                      id="{{ $provider['key'] }}_callback_url"
                      type="text"
                      class="form-control"
                      value="{{ $provider['callback_url'] }}"
                      readonly
                    />
                    <button
                      type="button"
                      class="btn btn-outline-secondary js-copy-callback"
                      data-copy-target="{{ $provider['key'] }}_callback_url"
                    >
                      Copiar callback URL
                    </button>
                  </div>
                </div>

                <div class="col-12">
                  <div class="alert {{ $provider['is_ready'] ? 'alert-success' : 'alert-warning' }} mb-0">
                    {{ $provider['is_ready'] ? 'Listo para usar en el login cliente.' : 'Incompleto: revisa Client ID/App ID, redirect y el secret en .env.' }}
                    <div class="small mt-1">Secret esperado en entorno: <code>{{ $provider['secret_env'] }}</code></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.querySelectorAll('.js-copy-callback').forEach((button) => {
      button.addEventListener('click', async () => {
        const target = document.getElementById(button.dataset.copyTarget);

        if (!target) {
          return;
        }

        try {
          await navigator.clipboard.writeText(target.value);
          button.textContent = 'Copiado';
          window.setTimeout(() => {
            button.textContent = 'Copiar callback URL';
          }, 1600);
        } catch (error) {
          target.select();
        }
      });
    });
  </script>
@endsection
