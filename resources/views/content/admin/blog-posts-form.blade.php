@extends('layouts/layoutMaster')

@section('title', $pageTitle)

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('blog-editor');
    const input = document.getElementById('content');

    if (editor && input && typeof Quill !== 'undefined') {
      const quill = new Quill(editor, {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image', 'blockquote', 'code-block'],
            ['clean']
          ]
        }
      });

      const initial = input.value || '';
      if (initial.trim() !== '') {
        quill.root.innerHTML = initial;
      }

      const form = document.getElementById('blog-post-form');
      if (form) {
        form.addEventListener('submit', function () {
          input.value = quill.root.innerHTML;
        });
      }
    }

    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput) {
      const slugify = function (value) {
        return (value || '')
          .toString()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .trim()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .replace(/^-|-$/g, '');
      };

      const titleBasedSlug = slugify(titleInput.value);
      let manualSlug = slugInput.value.trim() !== '' && slugInput.value.trim() !== titleBasedSlug;

      const syncSlugFromTitle = function () {
        if (!manualSlug) {
          slugInput.value = slugify(titleInput.value);
        }
      };

      titleInput.addEventListener('input', syncSlugFromTitle);

      slugInput.addEventListener('input', function () {
        const current = slugInput.value.trim();

        if (current === '') {
          manualSlug = false;
          syncSlugFromTitle();
          return;
        }

        manualSlug = current !== slugify(titleInput.value);
      });

      if (slugInput.value.trim() === '') {
        syncSlugFromTitle();
      }
    }

    const citySelect = document.getElementById('city_ids');
    const zoneSelect = document.getElementById('zone_ids');
    const zoneHelp = document.getElementById('zone-help');

    if (citySelect && zoneSelect) {
      const selectedCityIds = function () {
        return new Set(Array.from(citySelect.selectedOptions).map(function (option) {
          return option.value;
        }));
      };

      const syncZonesByCity = function () {
        const cities = selectedCityIds();
        const hasCities = cities.size > 0;
        let visibleZones = 0;

        Array.from(zoneSelect.options).forEach(function (option) {
          const cityId = option.dataset.cityId || '';
          const isVisible = hasCities && cityId !== '' && cities.has(cityId);

          option.hidden = !isVisible;
          option.disabled = !isVisible;

          if (!isVisible) {
            option.selected = false;
          } else {
            visibleZones += 1;
          }
        });

        if (!zoneHelp) {
          return;
        }

        if (!hasCities) {
          zoneHelp.textContent = 'Primero selecciona una o varias ciudades para habilitar zonas.';
          return;
        }

        zoneHelp.textContent = visibleZones > 0
          ? 'Selecciona una o varias zonas de las ciudades elegidas.'
          : 'No hay zonas registradas para las ciudades elegidas.';
      };

      citySelect.addEventListener('change', syncZonesByCity);
      syncZonesByCity();
    }
  });
</script>
@endsection

@section('content')
@php
  $post->loadMissing(['cities:id,name', 'zones:id,name,blog_city_id', 'eventTypes:id,name']);

  $selectedEventTypeIds = collect(old('event_type_ids', $post->eventTypes->pluck('id')->all()))
      ->map(fn ($id) => (int) $id)
      ->filter(fn (int $id) => $id > 0)
      ->values();

  $selectedCityIds = collect(old('city_ids', $post->cities->pluck('id')->all()))
      ->map(fn ($id) => (int) $id)
      ->filter(fn (int $id) => $id > 0)
      ->values();

  $selectedZoneIds = collect(old('zone_ids', $post->zones->pluck('id')->all()))
      ->map(fn ($id) => (int) $id)
      ->filter(fn (int $id) => $id > 0)
      ->values();
@endphp

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ $pageTitle }}</h5>
  </div>

  <div class="card-body">
    <form id="blog-post-form" action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
      @csrf
      @if($formMethod !== 'POST')
        @method($formMethod)
      @endif

      <div class="row g-4">
        <div class="col-md-8">
          <label class="form-label" for="title">Titulo</label>
          <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $post->title) }}" required>
          @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
          <label class="form-label" for="slug">Slug</label>
          <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $post->slug) }}" placeholder="se-autocompleta-desde-el-titulo">
          <small class="text-muted">Se completa automaticamente desde el titulo, pero puedes editarlo.</small>
          @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="status">Estado</label>
          <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $value => $label)
              <option value="{{ $value }}" @selected(old('status', $post->status ?: 'draft') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="event_type_ids">Tipos de evento (opcionales)</label>
          <select id="event_type_ids" name="event_type_ids[]" class="form-select @error('event_type_ids') is-invalid @enderror @error('event_type_ids.*') is-invalid @enderror" multiple size="{{ min(max($eventTypes->count(), 4), 10) }}">
            @foreach($eventTypes as $eventType)
              <option value="{{ $eventType->id }}" @selected($selectedEventTypeIds->contains((int) $eventType->id))>{{ $eventType->name }}</option>
            @endforeach
          </select>
          <small class="text-muted">Usa Cmd/Ctrl + click para seleccionar varios tipos de evento.</small>
          @error('event_type_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          @error('event_type_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="city_ids">Ciudades relacionadas (opcionales)</label>
          <select id="city_ids" name="city_ids[]" class="form-select @error('city_ids') is-invalid @enderror @error('city_ids.*') is-invalid @enderror" multiple size="{{ min(max($cities->count(), 4), 10) }}">
            @foreach($cities as $city)
              <option value="{{ $city->id }}" @selected($selectedCityIds->contains((int) $city->id))>{{ $city->name }}</option>
            @endforeach
          </select>
          <small class="text-muted">Se cargan desde ciudades reales del marketplace.</small>
          @error('city_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          @error('city_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="zone_ids">Zonas / Barrios (opcionales)</label>
          <select id="zone_ids" name="zone_ids[]" class="form-select @error('zone_ids') is-invalid @enderror @error('zone_ids.*') is-invalid @enderror" multiple size="{{ min(max($zones->count(), 4), 10) }}">
            @foreach($zones as $zone)
              <option value="{{ $zone->id }}" data-city-id="{{ $zone->blog_city_id }}" @selected($selectedZoneIds->contains((int) $zone->id))>
                {{ $zone->name }}
              </option>
            @endforeach
          </select>
          <small id="zone-help" class="text-muted">Primero selecciona una o varias ciudades para habilitar zonas.</small>
          @error('zone_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          @error('zone_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <label class="form-label" for="excerpt">Extracto</label>
          <textarea id="excerpt" name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $post->excerpt) }}</textarea>
          @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <div class="card border">
            <div class="card-header">
              <h6 class="mb-0">SEO</h6>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <label class="form-label" for="meta_title">Meta title</label>
                  <input type="text" id="meta_title" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $post->meta_title) }}" placeholder="Si lo dejas vacío, usaremos el título del post">
                  @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label" for="robots">Robots</label>
                  <select id="robots" name="robots" class="form-select @error('robots') is-invalid @enderror">
                    <option value="">Usar default indexable</option>
                    <option value="index,follow" @selected(old('robots', $post->robots) === 'index,follow')>index,follow</option>
                    <option value="noindex,follow" @selected(old('robots', $post->robots) === 'noindex,follow')>noindex,follow</option>
                    <option value="noindex,nofollow" @selected(old('robots', $post->robots) === 'noindex,nofollow')>noindex,nofollow</option>
                  </select>
                  @error('robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                  <label class="form-label" for="meta_description">Meta description</label>
                  <textarea id="meta_description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror" placeholder="Fallback: extracto del artículo">{{ old('meta_description', $post->meta_description) }}</textarea>
                  @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label" for="canonical_override">Canonical override</label>
                  <input type="url" id="canonical_override" name="canonical_override" class="form-control @error('canonical_override') is-invalid @enderror" value="{{ old('canonical_override', $post->canonical_override) }}" placeholder="https://...">
                  @error('canonical_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label" for="og_image">Imagen OG</label>
                  <input type="file" id="og_image" name="og_image" class="form-control @error('og_image') is-invalid @enderror" accept="image/*">
                  @error('og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  @if($post->og_image)
                    <div class="form-check form-switch mt-2">
                      <input id="clear_og_image" class="form-check-input" type="checkbox" name="clear_og_image" value="1" {{ old('clear_og_image') ? 'checked' : '' }}>
                      <label class="form-check-label" for="clear_og_image">Eliminar imagen OG actual</label>
                    </div>
                    <div class="mt-2">
                      <img src="{{ asset('storage/'.$post->og_image) }}" alt="Imagen OG actual" class="rounded" style="max-height: 120px;">
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label" for="featured_image">Imagen destacada</label>
          <input type="file" id="featured_image" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/*">
          @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
          @if($post->featured_image)
            <div class="mt-2">
              <img src="{{ asset('storage/'.$post->featured_image) }}" alt="Imagen actual" class="rounded" style="max-height: 120px;">
            </div>
          @endif
        </div>

        <div class="col-12">
          <label class="form-label">Contenido completo</label>
          <input type="hidden" id="content" name="content" value="{{ old('content', $post->content) }}">
          <div id="blog-editor" style="min-height: 280px;">{!! old('content', $post->content) !!}</div>
          @error('content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
