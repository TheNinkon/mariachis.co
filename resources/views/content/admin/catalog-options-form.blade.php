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
