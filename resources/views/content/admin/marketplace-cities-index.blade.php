@extends('layouts/layoutMaster')

@section('title', 'Ciudades')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">Ciudades del marketplace</h5>
      <small class="text-muted">Gestiona ciudades oficiales para anuncios, filtros y buscador.</small>
    </div>
    <a href="{{ route('admin.marketplace-cities.create') }}" class="btn btn-primary">Nueva ciudad</a>
  </div>

  <div class="card-body">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($cities->isEmpty())
      <div class="text-center py-8">
        <h6 class="mb-2">No hay ciudades registradas</h6>
        <p class="text-muted mb-4">Crea ciudades oficiales para evitar texto libre en anuncios y buscador.</p>
        <a href="{{ route('admin.marketplace-cities.create') }}" class="btn btn-outline-primary">Crear ciudad</a>
      </div>
    @else
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Ciudad</th>
              <th>Slug</th>
              <th>Zonas</th>
              <th>Anuncios</th>
              <th>Orden</th>
              <th>Destacada</th>
              <th>Buscar</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($cities as $city)
              <tr>
                <td>{{ $city->name }}</td>
                <td><code>{{ $city->slug }}</code></td>
                <td>{{ $city->zones_count }}</td>
                <td>{{ $city->listings_count }}</td>
                <td>{{ $city->sort_order }}</td>
                <td><span class="badge bg-label-{{ $city->is_featured ? 'warning' : 'secondary' }}">{{ $city->is_featured ? 'Sí' : 'No' }}</span></td>
                <td><span class="badge bg-label-{{ $city->show_in_search ? 'info' : 'secondary' }}">{{ $city->show_in_search ? 'Visible' : 'Oculta' }}</span></td>
                <td><span class="badge bg-label-{{ $city->is_active ? 'success' : 'secondary' }}">{{ $city->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="{{ route('admin.marketplace-cities.edit', $city) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form method="POST" action="{{ route('admin.marketplace-cities.toggle-status', $city) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-outline-{{ $city->is_active ? 'danger' : 'success' }}">
                        {{ $city->is_active ? 'Desactivar' : 'Activar' }}
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
        {{ $cities->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
