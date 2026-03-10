@extends('layouts/layoutMaster')

@section('title', 'Mariachis Registrados')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Listado de mariachis</h5>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-label-primary">Volver</a>
  </div>
  @if (session('status'))
    <div class="card-body pb-0">
      <div class="alert alert-success mb-0">{{ session('status') }}</div>
    </div>
  @endif
  <div class="table-responsive">
    <table class="table">
      <thead>
      <tr><th>Nombre</th><th>Email</th><th>Telefono</th><th>Ciudad</th><th>Perfil</th><th>Estado</th><th>Accion</th></tr>
      </thead>
      <tbody>
      @forelse($mariachis as $mariachi)
        <tr>
          <td>{{ $mariachi->display_name }}</td>
          <td>{{ $mariachi->email }}</td>
          <td>{{ $mariachi->phone ?? 'N/A' }}</td>
          <td>{{ $mariachi->mariachiProfile?->city_name ?? 'N/A' }}</td>
          <td>{{ $mariachi->mariachiProfile?->profile_completion ?? 0 }}%</td>
          <td><span class="badge bg-label-{{ $mariachi->status === 'active' ? 'success' : 'danger' }}">{{ $mariachi->status }}</span></td>
          <td>
            <form action="{{ route('admin.mariachis.toggle-status', $mariachi) }}" method="POST">
              @csrf
              @method('PATCH')
              <button class="btn btn-sm btn-outline-{{ $mariachi->status === 'active' ? 'danger' : 'success' }}" type="submit">
                {{ $mariachi->status === 'active' ? 'Desactivar' : 'Activar' }}
              </button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center">No hay mariachis registrados.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
