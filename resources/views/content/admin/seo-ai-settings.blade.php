@extends('layouts/layoutMaster')

@section('title', 'SEO · IA')

@section('page-script')
  @include('content.admin.partials.seo-ai-script')
@endsection

@section('content')
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">SEO · IA</h5>
            <small class="text-muted">Configura Gemini para autogenerar meta titles y descriptions desde admin.</small>
          </div>
          <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-secondary">Ver páginas SEO</a>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.seo-ai.update') }}">
            @csrf
            @method('PATCH')

            <div class="row g-4">
              <div class="col-md-7">
                <label class="form-label" for="seo_gemini_api_key">Gemini API Key</label>
                <input
                  id="seo_gemini_api_key"
                  name="seo_gemini_api_key"
                  type="password"
                  class="form-control @error('seo_gemini_api_key') is-invalid @enderror"
                  placeholder="{{ $seo['gemini_api_key_set'] ? 'Clave guardada. Pega otra para reemplazarla.' : 'Pega una Gemini API Key valida' }}"
                >
                @error('seo_gemini_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-check form-switch mt-2">
                  <input id="clear_seo_gemini_api_key" class="form-check-input" type="checkbox" name="clear_seo_gemini_api_key" value="1">
                  <label class="form-check-label" for="clear_seo_gemini_api_key">Eliminar clave guardada</label>
                </div>
              </div>

              <div class="col-md-5">
                <label class="form-label" for="seo_gemini_model">Modelo</label>
                <select id="seo_gemini_model" name="seo_gemini_model" class="form-select @error('seo_gemini_model') is-invalid @enderror" required>
                  @foreach($seo['gemini_models'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('seo_gemini_model', $seo['gemini_model']) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @error('seo_gemini_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted d-block mt-2">`gemini-2.5-flash` es la opcion recomendada para generar SEO rapido y consistente.</small>
              </div>

              <div class="col-12">
                <div class="rounded border bg-label-primary p-3">
                  <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                    <div>
                      <strong>Prueba de conexion</strong>
                      <div class="text-muted small">Lanza una solicitud simple al modelo seleccionado usando la clave del formulario o la ya guardada.</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary" data-seo-ai-test="{{ route('admin.seo-ai.test') }}">Probar conexion</button>
                  </div>
                  <small class="text-muted d-block mt-2" data-seo-ai-test-status>La prueba no guarda cambios. Primero valida, luego guarda la configuracion.</small>
                </div>
              </div>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Guardar IA SEO</button>
              <a href="{{ route('admin.seo-settings.edit') }}" class="btn btn-outline-secondary">Volver a configuración SEO</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
