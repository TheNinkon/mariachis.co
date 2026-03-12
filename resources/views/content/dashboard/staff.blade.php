@extends('layouts/layoutMaster')

@section('title', 'Panel Interno')

@section('content')
<div class="card mb-6">
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h5 class="mb-1">Panel de equipo interno</h5>
      <p class="mb-0">Acceso limitado para soporte operativo.</p>
    </div>
    <form action="{{ route('admin.logout') }}" method="POST">@csrf<button class="btn btn-label-secondary">Cerrar sesion</button></form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Ultimos mariachis registrados</h5></div>
  <div class="table-responsive">
    <table class="table">
      <thead>
      <tr><th>Nombre</th><th>Email</th><th>Telefono</th><th>Estado</th></tr>
      </thead>
      <tbody>
      @forelse($mariachis as $mariachi)
        <tr>
          <td>{{ $mariachi->display_name }}</td>
          <td>{{ $mariachi->email }}</td>
          <td>{{ $mariachi->phone ?? 'N/A' }}</td>
          <td><span class="badge bg-label-{{ $mariachi->status === 'active' ? 'success' : 'danger' }}">{{ $mariachi->status }}</span></td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center">Sin registros</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
