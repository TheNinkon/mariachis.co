@extends('layouts/layoutMaster')

@section('title', 'Seguridad')

@section('content')
  @include('content.mariachi.partials.account-settings-nav')

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validación.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <h5 class="card-header">Cambiar contraseña</h5>
    <div class="card-body pt-1">
      <form method="POST" action="{{ route('mariachi.account.security.update') }}">
        @csrf
        @method('PATCH')

        <div class="row gy-4 gx-6">
          <div class="col-md-6">
            <label class="form-label" for="current_password">Contraseña actual</label>
            <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required />
          </div>
          <div class="col-md-6"></div>
          <div class="col-md-6">
            <label class="form-label" for="password">Nueva contraseña</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
          </div>
        </div>

        <h6 class="text-body mt-6">Recomendaciones</h6>
        <ul class="ps-4 mb-0">
          <li class="mb-2">Usa una contraseña larga y única.</li>
          <li class="mb-2">Evita reutilizar la misma clave en otros servicios.</li>
          <li>Guárdala en un gestor seguro si la cambias con frecuencia.</li>
        </ul>

        <div class="mt-6">
          <button type="submit" class="btn btn-primary me-3">Guardar cambios</button>
          <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-label-secondary">Volver al perfil</a>
        </div>
      </form>
    </div>
  </div>
@endsection
