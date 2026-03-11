@extends('layouts/layoutMaster')

@section('title', 'Verificacion de perfil')

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
        <h5 class="mb-1">Verificacion de perfil</h5>
        <p class="mb-1">Estado actual: <strong>{{ $profile->verification_status }}</strong></p>
        <small class="text-muted">Plan actual: {{ $planSummary['name'] }}. Sube cédula colombiana y prueba visual del grupo para revision manual.</small>
      </div>
      <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-outline-primary">Volver al perfil</a>
    </div>
  </div>

  @if(! $capabilities['allows_verification'])
    <div class="alert alert-warning">
      La verificacion de perfil solo esta disponible en planes que incluyan esta capacidad. Puedes seguir viendo el historial, pero no enviar nuevas solicitudes.
    </div>
  @endif

  @if($latestRequest)
    <div class="card mb-6">
      <div class="card-header"><h5 class="mb-0">Ultima solicitud</h5></div>
      <div class="card-body">
        <p class="mb-1">Estado: <strong>{{ $latestRequest->status }}</strong></p>
        <p class="mb-1">Enviada: {{ $latestRequest->submitted_at?->format('Y-m-d H:i') ?: '-' }}</p>
        <p class="mb-1">Revisada: {{ $latestRequest->reviewed_at?->format('Y-m-d H:i') ?: '-' }}</p>
        @if($latestRequest->notes)
          <p class="mb-1"><strong>Nota:</strong> {{ $latestRequest->notes }}</p>
        @endif
        @if($latestRequest->rejection_reason)
          <p class="mb-0 text-danger"><strong>Motivo de rechazo:</strong> {{ $latestRequest->rejection_reason }}</p>
        @endif
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-header"><h5 class="mb-0">Enviar nueva solicitud</h5></div>
    <div class="card-body">
      <form method="POST" action="{{ route('mariachi.verification.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Foto de cédula</label>
            <input type="file" name="id_document" class="form-control" accept="image/*" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Prueba visual del grupo o identidad</label>
            <input type="file" name="identity_proof" class="form-control" accept="image/*" required />
          </div>
          <div class="col-12">
            <label class="form-label">Notas para el equipo (opcional)</label>
            <textarea name="notes" rows="3" class="form-control" placeholder="Contexto adicional para validar tu perfil">{{ old('notes') }}</textarea>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary" @disabled(! $capabilities['allows_verification'])>Enviar solicitud de verificacion</button>
        </div>
      </form>
    </div>
  </div>
@endsection
