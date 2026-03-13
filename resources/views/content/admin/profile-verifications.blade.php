@extends('layouts/layoutMaster')

@section('title', 'Verificacion de perfiles')

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
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1">Solicitudes de verificacion</h5>
        <p class="mb-0 text-muted">Revisa documentos y aprueba/rechaza perfiles de mariachis.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.profile-verifications.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Todas ({{ $totals->sum() }})</a>
        @foreach($statuses as $item)
          <a href="{{ route('admin.profile-verifications.index', ['status' => $item]) }}" class="btn btn-sm {{ $status === $item ? 'btn-primary' : 'btn-outline-primary' }}">{{ ucfirst($item) }} ({{ (int) ($totals[$item] ?? 0) }})</a>
        @endforeach
      </div>
    </div>
  </div>

  @if($requests->isEmpty())
    <div class="card">
      <div class="card-body">
        <p class="mb-0 text-muted">No hay solicitudes para este filtro.</p>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach($requests as $item)
        @php
          $payment = $item->payment;
          $paymentClass = match ($payment?->status) {
            \App\Models\ProfileVerificationPayment::STATUS_APPROVED => 'success',
            \App\Models\ProfileVerificationPayment::STATUS_REJECTED => 'danger',
            \App\Models\ProfileVerificationPayment::STATUS_PENDING => 'warning',
            default => 'secondary',
          };
        @endphp
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2 align-items-start">
              <div>
                <h6 class="mb-1">{{ $item->mariachiProfile?->business_name ?: 'Mariachi sin nombre' }}</h6>
                <p class="mb-0 small text-muted">
                  {{ $item->mariachiProfile?->user?->display_name ?: '-' }} ·
                  {{ $item->mariachiProfile?->user?->email ?: '-' }} ·
                  Estado solicitud: <strong>{{ $item->status }}</strong>
                </p>
              </div>
              <div class="small text-muted text-end">
                <div>Enviada: {{ $item->submitted_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <div>Revisada: {{ $item->reviewed_at?->format('Y-m-d H:i') ?: '-' }}</div>
              </div>
            </div>
            <div class="card-body">
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <a href="{{ asset('storage/'.$item->id_document_path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Ver cédula</a>
                </div>
                <div class="col-md-4">
                  <a href="{{ asset('storage/'.$item->identity_proof_path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Ver prueba visual</a>
                </div>
                <div class="col-md-4">
                  @if($payment?->proof_path)
                    <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Ver comprobante Nequi</a>
                  @endif
                </div>
              </div>

              @if($payment)
                <div class="row g-3 mb-3">
                  <div class="col-md-3">
                    <div class="bg-lighter rounded p-3 h-100">
                      <div class="small text-muted">Plan</div>
                      <div class="fw-semibold">{{ $payment->plan_code }}</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="bg-lighter rounded p-3 h-100">
                      <div class="small text-muted">Monto</div>
                      <div class="fw-semibold">${{ number_format((int) $payment->amount_cop, 0, ',', '.') }} COP</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="bg-lighter rounded p-3 h-100">
                      <div class="small text-muted">Duracion</div>
                      <div class="fw-semibold">{{ $payment->duration_months }} mes(es)</div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="bg-lighter rounded p-3 h-100">
                      <div class="small text-muted">Estado pago</div>
                      <div><span class="badge bg-label-{{ $paymentClass }}">{{ $payment->statusLabel() }}</span></div>
                    </div>
                  </div>
                </div>

                <p class="mb-2 small text-muted">
                  Referencia: <strong>{{ $payment->reference_text ?: '-' }}</strong>
                  · Vigencia propuesta:
                  <strong>
                    @if($payment->starts_at || $payment->ends_at)
                      {{ $payment->starts_at?->format('Y-m-d') ?: '-' }} a {{ $payment->ends_at?->format('Y-m-d') ?: '-' }}
                    @else
                      Se calcula al aprobar
                    @endif
                  </strong>
                </p>
              @endif

              @if($item->notes)
                <p class="mb-2"><strong>Notas del mariachi:</strong> {{ $item->notes }}</p>
              @endif
              @if($item->rejection_reason)
                <p class="mb-2 text-danger"><strong>Motivo de rechazo:</strong> {{ $item->rejection_reason }}</p>
              @endif
              @if($item->mariachiProfile?->verification_expires_at)
                <p class="mb-2 small text-muted"><strong>Vigencia actual del perfil:</strong> {{ $item->mariachiProfile->verification_expires_at->format('Y-m-d') }}</p>
              @endif

              <div class="row g-3">
                <div class="col-md-6">
                  <form method="POST" action="{{ route('admin.profile-verifications.update', ['verificationRequest' => $item->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="approve" />
                    <label class="form-label">Nota de aprobacion (opcional)</label>
                    <textarea class="form-control" name="note" rows="2" placeholder="Observaciones internas"></textarea>
                    <button type="submit" class="btn btn-success btn-sm mt-2">Aprobar pago y perfil</button>
                  </form>
                </div>
                <div class="col-md-6">
                  <form method="POST" action="{{ route('admin.profile-verifications.update', ['verificationRequest' => $item->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject" />
                    <label class="form-label">Motivo de rechazo</label>
                    <textarea class="form-control" name="rejection_reason" rows="2" placeholder="Motivo para rechazar" required></textarea>
                    <label class="form-label mt-2">Nota adicional (opcional)</label>
                    <textarea class="form-control" name="note" rows="2" placeholder="Nota interna"></textarea>
                    <button type="submit" class="btn btn-outline-danger btn-sm mt-2">Rechazar pago y perfil</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $requests->links() }}
    </div>
  @endif
@endsection
