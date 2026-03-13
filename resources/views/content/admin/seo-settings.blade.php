@extends('layouts/layoutMaster')

@section('title', 'SEO · Configuración')

@section('page-script')
  @include('content.admin.partials.seo-ai-script')
@endsection

@section('content')
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">SEO · Configuración global</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.seo-settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label" for="seo_site_name">Nombre del sitio</label>
                <input id="seo_site_name" name="seo_site_name" type="text" class="form-control @error('seo_site_name') is-invalid @enderror" value="{{ old('seo_site_name', $seo['site_name']) }}" required>
                @error('seo_site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="seo_default_title_template">Template de title</label>
                <input id="seo_default_title_template" name="seo_default_title_template" type="text" class="form-control @error('seo_default_title_template') is-invalid @enderror" value="{{ old('seo_default_title_template', $seo['default_title_template']) }}" required>
                <small class="text-muted">Usa `@{{title}}` y `@{{site_name}}`.</small>
                @error('seo_default_title_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="seo_default_robots">Robots default</label>
                <select id="seo_default_robots" name="seo_default_robots" class="form-select @error('seo_default_robots') is-invalid @enderror">
                  @foreach(['index,follow', 'noindex,follow', 'noindex,nofollow'] as $robotsOption)
                    <option value="{{ $robotsOption }}" @selected(old('seo_default_robots', $seo['default_robots']) === $robotsOption)>{{ $robotsOption }}</option>
                  @endforeach
                </select>
                @error('seo_default_robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="seo_twitter_site">Twitter site</label>
                <input id="seo_twitter_site" name="seo_twitter_site" type="text" class="form-control @error('seo_twitter_site') is-invalid @enderror" value="{{ old('seo_twitter_site', $seo['twitter_site']) }}" placeholder="@mariachisco">
                @error('seo_twitter_site')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <label class="form-label" for="seo_default_meta_description">Meta description default</label>
                <textarea id="seo_default_meta_description" name="seo_default_meta_description" rows="3" class="form-control @error('seo_default_meta_description') is-invalid @enderror" required>{{ old('seo_default_meta_description', $seo['default_meta_description']) }}</textarea>
                @error('seo_default_meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <div class="rounded border bg-label-primary p-3">
                  <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                    <div>
                      <div class="fw-semibold">Sugerencias con IA</div>
                      <div class="text-muted small">Genera una propuesta para `meta_description_default`, `title_template` y `twitter_site` si aún está vacío.</div>
                    </div>
                    <button type="button" class="btn btn-primary" data-seo-ai-global-generate="{{ route('admin.seo-ai.generate') }}">Generar sugerencias con IA</button>
                  </div>
                  <small class="text-muted d-block mt-2" data-seo-ai-global-status>La IA no guarda cambios por ti. Revisa el copy y luego guarda la configuración.</small>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="seo_default_og_image">Imagen OG por defecto</label>
                <input id="seo_default_og_image" name="seo_default_og_image" type="file" class="form-control @error('seo_default_og_image') is-invalid @enderror" accept="image/*">
                @error('seo_default_og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($seo['default_og_image_path'])
                  <div class="form-check form-switch mt-2">
                    <input id="clear_seo_default_og_image" class="form-check-input" type="checkbox" name="clear_seo_default_og_image" value="1" {{ old('clear_seo_default_og_image') ? 'checked' : '' }}>
                    <label class="form-check-label" for="clear_seo_default_og_image">Eliminar imagen actual</label>
                  </div>
                  <div class="mt-2">
                    <img src="{{ $seo['default_og_image_url'] }}" alt="Imagen SEO actual" class="rounded" style="max-height: 120px;">
                  </div>
                @endif
              </div>

              <div class="col-md-6">
                <div class="rounded border bg-label-primary p-3 h-100">
                  <div class="fw-semibold">IA SEO</div>
                  <div class="text-muted small mt-1">
                    {{ $seo['gemini_api_key_set'] ? 'Ya hay una clave Gemini guardada.' : 'Aún no hay una clave Gemini configurada.' }}
                  </div>
                  <div class="text-muted small mt-1">Modelo actual: {{ $seo['gemini_model'] }}</div>
                  <a href="{{ route('admin.seo-ai.edit') }}" class="btn btn-sm btn-outline-primary mt-3">Configurar IA</a>
                </div>
              </div>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Guardar configuración SEO</button>
              <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-secondary">Ver páginas SEO</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
