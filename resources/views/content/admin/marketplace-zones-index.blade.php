@extends('layouts/layoutMaster')

@section('title', 'Zonas y Barrios')

@section('content')
<div class="card mb-6">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">Zonas / barrios por ciudad</h5>
      <small class="text-muted">Gestiona cobertura oficial para buscador y anuncios.</small>
    </div>
    <a href="{{ route('admin.marketplace-zones.create') }}" class="btn btn-primary">Nueva zona</a>
  </div>

  <div class="card-body pb-0">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="city_id">Filtrar por ciudad</label>
        <select id="city_id" name="city_id" class="form-select">
          <option value="">Todas</option>
          @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected((int) request('city_id') === (int) $city->id)>{{ $city->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-auto">
        <button type="submit" class="btn btn-outline-primary">Aplicar</button>
      </div>
    </form>
  </div>

  <div class="card-body">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($zones->isEmpty())
      <div class="text-center py-8">
        <h6 class="mb-2">No hay zonas registradas</h6>
        <p class="text-muted mb-4">Crea zonas oficiales por ciudad para eliminar texto libre.</p>
        <a href="{{ route('admin.marketplace-zones.create') }}" class="btn btn-outline-primary">Crear zona</a>
      </div>
    @else
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Ciudad</th>
              <th>Zona</th>
              <th>Slug</th>
              <th>Anuncios</th>
              <th>Orden</th>
              <th>Buscar</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($zones as $zone)
              <tr>
                <td>{{ $zone->city?->name ?: '—' }}</td>
                <td>{{ $zone->name }}</td>
                <td><code>{{ $zone->slug }}</code></td>
                <td>{{ $zone->service_areas_count }}</td>
                <td>{{ $zone->sort_order }}</td>
                <td><span class="badge bg-label-{{ $zone->show_in_search ? 'info' : 'secondary' }}">{{ $zone->show_in_search ? 'Visible' : 'Oculta' }}</span></td>
                <td><span class="badge bg-label-{{ $zone->is_active ? 'success' : 'secondary' }}">{{ $zone->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="{{ route('admin.marketplace-zones.edit', $zone) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form method="POST" action="{{ route('admin.marketplace-zones.toggle-status', $zone) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-outline-{{ $zone->is_active ? 'danger' : 'success' }}">
                        {{ $zone->is_active ? 'Desactivar' : 'Activar' }}
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $zones->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
