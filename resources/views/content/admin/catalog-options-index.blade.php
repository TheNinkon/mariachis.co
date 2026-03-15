@extends('layouts/layoutMaster')

@section('title', $meta['title'])

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">{{ $meta['title'] }}</h5>
      <small class="text-muted">{{ $meta['description'] }}</small>
    </div>
    <a href="{{ route('admin.catalog-options.create', ['catalog' => $catalog]) }}" class="btn btn-primary">Nueva opción</a>
  </div>

  <div class="card-body">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($items->isEmpty())
      <div class="text-center py-8">
        <h6 class="mb-2">No hay opciones registradas</h6>
        <p class="text-muted mb-4">Crea la primera opción oficial para comenzar a estandarizar anuncios y buscador.</p>
        <a href="{{ route('admin.catalog-options.create', ['catalog' => $catalog]) }}" class="btn btn-outline-primary">Crear opción</a>
      </div>
    @else
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Icono</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th>Orden</th>
              @if($meta['supports_home_editorial'] ?? false)
                <th>Home</th>
                <th>Prioridad</th>
                <th>Oferta mínima</th>
                <th>Temporada</th>
                <th>Clics</th>
              @endif
              <th>Destacado</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td>
                  <span class="d-inline-flex align-items-center justify-content-center rounded bg-label-primary" style="width: 34px; height: 34px;">
                    <x-catalog-icon :name="$item->icon" class="h-4 w-4" />
                  </span>
                </td>
                <td>{{ $item->name }}</td>
                <td><code>{{ $item->slug }}</code></td>
                <td>{{ $item->sort_order }}</td>
                @if($meta['supports_home_editorial'] ?? false)
                  <td>
                    <span class="badge bg-label-{{ $item->is_visible_in_home ? 'success' : 'secondary' }}">
                      {{ $item->is_visible_in_home ? 'Visible' : 'Oculta' }}
                    </span>
                  </td>
                  <td>{{ $item->home_priority ?? '—' }}</td>
                  <td>{{ $item->min_active_listings_required ?? '—' }}</td>
                  <td>
                    @if($item->seasonal_start_at || $item->seasonal_end_at)
                      <div class="small">{{ optional($item->seasonal_start_at)->format('d/m/Y') ?: '—' }}</div>
                      <div class="small text-muted">{{ optional($item->seasonal_end_at)->format('d/m/Y') ?: '—' }}</div>
                    @else
                      <span class="text-muted">Todo el año</span>
                    @endif
                  </td>
                  <td>{{ number_format((int) ($item->home_clicks_count ?? 0), 0, ',', '.') }}</td>
                @endif
                <td>
                  <span class="badge bg-label-{{ $item->is_featured ? 'warning' : 'secondary' }}">
                    {{ $item->is_featured ? 'Sí' : 'No' }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-label-{{ $item->is_active ? 'success' : 'secondary' }}">
                    {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="{{ route('admin.catalog-options.edit', ['catalog' => $catalog, 'id' => $item->id]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form method="POST" action="{{ route('admin.catalog-options.toggle-status', ['catalog' => $catalog, 'id' => $item->id]) }}">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-outline-{{ $item->is_active ? 'danger' : 'success' }}">
                        {{ $item->is_active ? 'Desactivar' : 'Activar' }}
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
        {{ $items->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
