@extends('layouts/layoutMaster')

@section('title', 'Panel Cliente')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="mb-2">Bienvenido, {{ auth()->user()?->display_name }}</h4>
        <p class="text-muted mb-0">Este es tu panel inicial de cliente. Aquí se habilitarán tus solicitudes, favoritos y seguimiento de cotizaciones en próximas fases.</p>
      </div>
    </div>
  </div>
</div>
@endsection
