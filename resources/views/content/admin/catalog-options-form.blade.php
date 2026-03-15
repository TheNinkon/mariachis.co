@extends('layouts/layoutMaster')

@section('title', $meta['title'])

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">{{ $pageTitle }}</h5>
      <small class="text-muted">{{ $meta['title'] }}</small>
    </div>
    <a href="{{ route('admin.catalog-options.index', ['catalog' => $catalog]) }}" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ $formAction }}">
      @csrf
      @if($formMethod !== 'POST')
        @method($formMethod)
      @endif

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label" for="name">Nombre</label>
          <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name) }}" required maxlength="120" />
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="slug">Slug (autogenerado si se deja vacío)</label>
          <input id="slug" type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $item->slug) }}" maxlength="160" />
          @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="icon">Icono oficial</label>
          <select id="icon" name="icon" class="form-select @error('icon') is-invalid @enderror">
            <option value="">Automático</option>
            @foreach($iconOptions as $value => $label)
              <option value="{{ $value }}" @selected(old('icon', $item->icon) === $value)>{{ $label }} ({{ $value }})</option>
            @endforeach
          </select>
          @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <small class="text-muted">Todos los iconos usan el mismo estilo visual del sistema.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="sort_order">Orden de visualización</label>
          <input id="sort_order" type="number" min="0" max="9999" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $item->sort_order) }}" />
          @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <div class="form-check mt-2">
            <input id="is_active" class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $item->exists ? (bool) $item->is_active : true) ? 'checked' : '' }} />
            <label class="form-check-label" for="is_active">Activo</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-check mt-2">
            <input id="is_featured" class="form-check-input" type="checkbox" name="is_featured" value="1" {{ old('is_featured', (bool) $item->is_featured) ? 'checked' : '' }} />
            <label class="form-check-label" for="is_featured">Destacado</label>
          </div>
        </div>

        @if($meta['supports_home_editorial'] ?? false)
          <div class="col-12">
            <div class="alert alert-label-primary">
              Este catálogo es la fuente editorial de la portada. Las sugerencias aprobadas no salen en home automáticamente: deben quedar activas, visibles en home y con prioridad definida.
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-check mt-2">
              <input id="is_visible_in_home" class="form-check-input" type="checkbox" name="is_visible_in_home" value="1" {{ old('is_visible_in_home', (bool) $item->is_visible_in_home) ? 'checked' : '' }} />
              <label class="form-check-label" for="is_visible_in_home">Mostrar en home</label>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="home_priority">Prioridad en home</label>
            <input id="home_priority" type="number" min="0" max="9999" name="home_priority" class="form-control @error('home_priority') is-invalid @enderror" value="{{ old('home_priority', $item->home_priority ?? 999) }}" />
            @error('home_priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Los números más bajos salen primero.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="seasonal_start_at">Inicio estacional (opcional)</label>
            <input id="seasonal_start_at" type="date" name="seasonal_start_at" class="form-control @error('seasonal_start_at') is-invalid @enderror" value="{{ old('seasonal_start_at', optional($item->seasonal_start_at)->format('Y-m-d')) }}" />
            @error('seasonal_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="seasonal_end_at">Fin estacional (opcional)</label>
            <input id="seasonal_end_at" type="date" name="seasonal_end_at" class="form-control @error('seasonal_end_at') is-invalid @enderror" value="{{ old('seasonal_end_at', optional($item->seasonal_end_at)->format('Y-m-d')) }}" />
            @error('seasonal_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="min_active_listings_required">Oferta mínima para mostrar en home (opcional)</label>
            <input id="min_active_listings_required" type="number" min="0" max="999999" name="min_active_listings_required" class="form-control @error('min_active_listings_required') is-invalid @enderror" value="{{ old('min_active_listings_required', $item->min_active_listings_required) }}" />
            @error('min_active_listings_required')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Si se define, la categoría solo aparece cuando tenga suficientes anuncios activos asociados.</small>
          </div>
        @endif
      </div>

      <div class="mt-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2 text-muted small">
          <span class="d-inline-flex align-items-center justify-content-center rounded bg-label-primary" style="width: 34px; height: 34px;">
            <x-catalog-icon :name="old('icon', $item->icon ?: $meta['default_icon'])" class="h-4 w-4" />
          </span>
          Vista previa del icono
        </div>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
