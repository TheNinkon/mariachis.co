@extends('layouts/layoutMaster')

@section('title', 'Zonas y Barrios')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">{{ $pageTitle }}</h5>
      <small class="text-muted">Catálogo oficial de zonas / barrios</small>
    </div>
    <a href="{{ route('admin.marketplace-zones.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ $formAction }}">
      @csrf
      @if($formMethod !== 'POST')
        @method($formMethod)
      @endif

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label" for="marketplace_city_id">Ciudad relacionada</label>
          <select id="marketplace_city_id" name="marketplace_city_id" class="form-select @error('marketplace_city_id') is-invalid @enderror" required>
            <option value="">Selecciona ciudad</option>
            @foreach($cities as $city)
              <option value="{{ $city->id }}" @selected((int) old('marketplace_city_id', $zone->marketplace_city_id) === (int) $city->id)>{{ $city->name }}</option>
            @endforeach
          </select>
          @error('marketplace_city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="name">Nombre de la zona</label>
          <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $zone->name) }}" required maxlength="120" />
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label" for="slug">Slug (autogenerado si se deja vacío)</label>
          <input id="slug" type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $zone->slug) }}" maxlength="160" />
          @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3">
          <label class="form-label" for="sort_order">Orden</label>
          <input id="sort_order" type="number" min="0" max="9999" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $zone->sort_order) }}" />
          @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-3 d-flex flex-column justify-content-end gap-2">
          <div class="form-check">
            <input id="is_active" class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $zone->exists ? (bool) $zone->is_active : true) ? 'checked' : '' }} />
            <label class="form-check-label" for="is_active">Activa</label>
          </div>

          <div class="form-check">
            <input id="show_in_search" class="form-check-input" type="checkbox" name="show_in_search" value="1" {{ old('show_in_search', $zone->exists ? (bool) $zone->show_in_search : true) ? 'checked' : '' }} />
            <label class="form-check-label" for="show_in_search">Visible en buscador</label>
          </div>
        </div>
      </div>

      <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
