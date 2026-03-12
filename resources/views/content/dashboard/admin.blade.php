@extends('layouts/layoutMaster')

@section('title', 'Dashboard Admin')

@section('content')
<div class="row g-6">
  <div class="col-md-4">
    <div class="card"><div class="card-body"><h6 class="mb-2">Mariachis registrados</h6><h3 class="mb-0">{{ $totalMariachis }}</h3></div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body"><h6 class="mb-2">Mariachis activos</h6><h3 class="mb-0">{{ $activeMariachis }}</h3></div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body"><h6 class="mb-2">Usuarios internos</h6><h3 class="mb-0">{{ $staffUsers }}</h3></div></div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex gap-3 flex-wrap">
        <a class="btn btn-primary" href="{{ route('admin.mariachis.index') }}">Ver mariachis</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.reviews.index') }}">Moderar resenas</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.internal-users.index') }}">Gestionar equipo interno</a>
        <a class="btn btn-outline-primary" href="{{ route('admin.blog-posts.index') }}">Gestionar blog</a>
        <form action="{{ route('admin.logout') }}" method="POST">@csrf<button class="btn btn-label-secondary">Cerrar sesion</button></form>
      </div>
    </div>
  </div>
</div>
@endsection
