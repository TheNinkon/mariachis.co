@extends('layouts/layoutMaster')

@section('title', 'Notificaciones')

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

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Preferencias de notificación</h5>
      <div class="text-muted small">Controla qué correos quieres seguir recibiendo desde el panel partner.</div>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('mariachi.account.notifications.update') }}">
        @csrf
        @method('PATCH')

        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Tipo</th>
                <th class="text-center">Correo</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="text-heading">Nuevas solicitudes</td>
                <td class="text-center">
                  <div class="form-check d-inline-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" name="quotes_email" value="1" @checked($preferences['quotes_email'])>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="text-heading">Nuevas opiniones o respuestas</td>
                <td class="text-center">
                  <div class="form-check d-inline-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" name="reviews_email" value="1" @checked($preferences['reviews_email'])>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="text-heading">Pagos y validaciones</td>
                <td class="text-center">
                  <div class="form-check d-inline-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" name="payments_email" value="1" @checked($preferences['payments_email'])>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="text-heading">Novedades del producto</td>
                <td class="text-center">
                  <div class="form-check d-inline-flex justify-content-center mb-0">
                    <input class="form-check-input" type="checkbox" name="product_email" value="1" @checked($preferences['product_email'])>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-3">Guardar cambios</button>
          <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-label-secondary">Volver al perfil</a>
        </div>
      </form>
    </div>
  </div>
@endsection
