@extends('layouts/layoutMaster')

@section('title', $title)

@section('content')
  <div class="row justify-content-center">
    <div class="col-xl-7 col-lg-8">
      <div class="card mt-6">
        <div class="card-body p-6 p-lg-8">
          <span class="badge bg-label-{{ $contextClass }} mb-3">Wompi</span>
          <h3 class="mb-3">{{ $title }}</h3>
          <p class="text-muted mb-4">{{ $message }}</p>

          <dl class="row small mb-5">
            <dt class="col-sm-4 text-muted">Referencia</dt>
            <dd class="col-sm-8">{{ $reference }}</dd>

            <dt class="col-sm-4 text-muted">Transacción</dt>
            <dd class="col-sm-8">{{ $transactionId !== '' ? $transactionId : 'Pendiente de confirmación' }}</dd>

            <dt class="col-sm-4 text-muted">Estado local</dt>
            <dd class="col-sm-8">{{ $payment->statusLabel() }}</dd>
          </dl>

          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ $returnUrl }}" class="btn btn-primary">{{ $returnLabel }}</a>
            <button type="button" class="btn btn-label-secondary" onclick="window.location.reload()">Actualizar estado</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
