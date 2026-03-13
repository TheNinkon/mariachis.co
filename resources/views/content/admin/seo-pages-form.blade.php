@extends('layouts/layoutMaster')

@section('title', 'SEO · Editar página')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">SEO · {{ $definition['label'] ?? $page->key }}</h5>
      <small class="text-muted">{{ $page->path ?: 'Página dinámica / sin path fijo' }}</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.seo-pages.update', $page) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="title">Meta title</label>
            <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" placeholder="{{ $definition['title'] ?? '' }}">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="robots">Robots</label>
            <select id="robots" name="robots" class="form-select @error('robots') is-invalid @enderror">
              <option value="">Usar default de la página</option>
              @foreach($robotsOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('robots', $page->robots) === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description" rows="4" class="form-control @error('meta_description') is-invalid @enderror" placeholder="{{ $definition['meta_description'] ?? '' }}">{{ old('meta_description', $page->meta_description) }}</textarea>
            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="canonical_override">Canonical override</label>
            <input id="canonical_override" name="canonical_override" type="url" class="form-control @error('canonical_override') is-invalid @enderror" value="{{ old('canonical_override', $page->canonical_override) }}" placeholder="https://...">
            @error('canonical_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="og_image">Imagen OG</label>
            <input id="og_image" name="og_image" type="file" class="form-control @error('og_image') is-invalid @enderror" accept="image/*">
            @error('og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($page->og_image)
              <div class="form-check form-switch mt-2">
                <input id="clear_og_image" class="form-check-input" type="checkbox" name="clear_og_image" value="1" {{ old('clear_og_image') ? 'checked' : '' }}>
                <label class="form-check-label" for="clear_og_image">Eliminar imagen actual</label>
              </div>
              <div class="mt-2">
                <img src="{{ asset('storage/'.$page->og_image) }}" alt="Imagen OG actual" class="rounded" style="max-height: 120px;">
              </div>
            @endif
          </div>

          <div class="col-12">
            <label class="form-label" for="jsonld">JSON-LD</label>
            <textarea id="jsonld" name="jsonld" rows="8" class="form-control @error('jsonld') is-invalid @enderror" placeholder='{"@@context":"https://schema.org"}'>{{ old('jsonld', $page->jsonld) }}</textarea>
            @error('jsonld')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Guardar SEO</button>
          <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
      </form>
    </div>
  </div>
@endsection
