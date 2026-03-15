@extends('layouts/layoutMaster')

@section('title', 'Crear anuncio')

@section('content')
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Nuevo anuncio / servicio</h5>
        <p class="mb-1">
          Plan de referencia del perfil: <strong>{{ $planSummary['name'] }}</strong>.
          Borradores abiertos: {{ $openDraftLimit === 0 ? $openDraftsCount.' (sin tope)' : $openDraftsCount.' de '.$openDraftLimit }}.
        </p>
        <small class="text-muted">
          Los límites de fotos, videos, ciudades y zonas se aplican luego según el plan que elijas para este anuncio.
        </small>
      </div>
      <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-primary">Volver</a>
    </div>
  </div>

  @if($planIssues !== [])
    <div class="alert alert-warning">
      <strong>Antes de publicar cambios con este plan debes ajustar algunas cosas.</strong>
      <ul class="mb-0 mt-2">
        @foreach($planIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-6">
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">Flujo recomendado</h5></div>
        <div class="card-body">
          <div class="d-flex gap-3 mb-3">
            <span class="badge rounded-pill bg-label-primary">1</span>
            <div>
              <p class="mb-0 fw-semibold">Crear borrador</p>
              <small class="text-muted">Título, ciudad y resumen inicial.</small>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <span class="badge rounded-pill bg-label-primary">2</span>
            <div>
              <p class="mb-0 fw-semibold">Completar anuncio</p>
              <small class="text-muted">Datos, filtros, fotos, videos y FAQ con autoguardado.</small>
            </div>
          </div>
          <div class="d-flex gap-3">
            <span class="badge rounded-pill bg-label-primary">3</span>
            <div>
              <p class="mb-0 fw-semibold">Activar plan</p>
              <small class="text-muted">Selecciona un plan publico o usa el que te haya asignado admin.</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Datos para iniciar el borrador</h5></div>
        <div class="card-body">
          @if(!$canCreate)
            <div class="alert alert-warning mb-0">
              Has alcanzado el maximo de {{ $openDraftLimit }} borradores abiertos. Publica o elimina uno para crear otro.
            </div>
          @else
            <form method="POST" action="{{ route('mariachi.listings.store') }}">
              @csrf
              <div class="row g-4">
                <div class="col-md-8">
                  <label class="form-label">Título del anuncio</label>
                  <input class="form-control" name="title" value="{{ old('title') }}" required maxlength="180" placeholder="Ej: Mariachi para bodas y serenatas" />
                </div>
                <div class="col-md-4">
                  <div class="rounded border h-100 d-flex flex-column justify-content-center px-3 py-2 bg-lighter">
                    <small class="text-muted">Ubicación</small>
                    <strong>Se completa después con Google Maps</strong>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">Descripción corta</label>
                  <textarea class="form-control" name="short_description" rows="3" maxlength="280" required>{{ old('short_description') }}</textarea>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Precio desde (opcional)</label>
                  <input type="number" step="0.01" min="0" class="form-control" name="base_price" value="{{ old('base_price') }}" />
                </div>
              </div>

              <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Crear borrador y continuar al editor</button>
                <a href="{{ route('mariachi.listings.index') }}" class="btn btn-label-secondary">Cancelar</a>
              </div>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
