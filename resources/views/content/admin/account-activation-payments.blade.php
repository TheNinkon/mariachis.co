@extends('layouts/layoutMaster')

@section('title', 'Pagos de activacion')

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
        <h5 class="mb-1">Pagos de activacion</h5>
        <p class="mb-0 text-muted">Aprueba o rechaza el pago inicial antes de habilitar el acceso al panel partner.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.account-activation-plans.index') }}" class="btn btn-outline-primary btn-sm">Plan de activacion</a>
        <a href="{{ route('admin.account-activation-payments.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Todos ({{ $totals->sum() }})</a>
        @foreach($statuses as $item)
          <a href="{{ route('admin.account-activation-payments.index', ['status' => $item]) }}" class="btn btn-sm {{ $status === $item ? 'btn-primary' : 'btn-outline-primary' }}">{{ ucfirst(str_replace('_', ' ', $item)) }} ({{ (int) ($totals[$item] ?? 0) }})</a>
        @endforeach
      </div>
    </div>
  </div>

  @if($payments->isEmpty())
    <div class="card">
      <div class="card-body">
        <p class="mb-0 text-muted">No hay pagos para este filtro.</p>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach($payments as $payment)
        @php
          $statusClass = match ($payment->status) {
            \App\Models\AccountActivationPayment::STATUS_APPROVED => 'success',
            \App\Models\AccountActivationPayment::STATUS_REJECTED => 'danger',
            default => 'warning',
          };
        @endphp
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2 align-items-start">
              <div>
                <h6 class="mb-1">{{ $payment->user?->display_name ?: 'Usuario sin nombre' }}</h6>
                <p class="mb-0 small text-muted">
                  {{ $payment->user?->email ?: '-' }} · {{ $payment->user?->phone ?: '-' }} · Estado cuenta: <strong>{{ $payment->user?->status ?: '-' }}</strong>
                </p>
              </div>
              <div class="small text-muted text-end">
                <div>Enviado: {{ $payment->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                <div>Revisado: {{ $payment->reviewed_at?->format('Y-m-d H:i') ?: '-' }}</div>
              </div>
            </div>
            <div class="card-body">
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <div class="bg-lighter rounded p-3 h-100">
                    <div class="small text-muted">Plan</div>
                    <div class="fw-semibold">{{ $payment->plan?->name ?: 'Plan' }}</div>
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
                    <div class="small text-muted">Estado pago</div>
                    <div><span class="badge bg-label-{{ $statusClass }}">{{ $payment->statusLabel() }}</span></div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="bg-lighter rounded p-3 h-100">
                    <div class="small text-muted">Referencia</div>
                    <div class="fw-semibold">{{ $payment->reference_text ?: '-' }}</div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Ver comprobante</a>
              </div>

              @if($payment->rejection_reason)
                <p class="mb-3 text-danger"><strong>Motivo de rechazo:</strong> {{ $payment->rejection_reason }}</p>
              @endif

              <div class="row g-3">
                <div class="col-md-6">
                  <form method="POST" action="{{ route('admin.account-activation-payments.update', $payment) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="approve" />
                    <button type="submit" class="btn btn-success btn-sm">Aprobar y activar cuenta</button>
                  </form>
                </div>
                <div class="col-md-6">
                  <form method="POST" action="{{ route('admin.account-activation-payments.update', $payment) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject" />
                    <label class="form-label">Motivo de rechazo</label>
                    <textarea class="form-control" name="rejection_reason" rows="2" placeholder="Motivo para rechazar" required></textarea>
                    <button type="submit" class="btn btn-outline-danger btn-sm mt-2">Rechazar comprobante</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $payments->links() }}
    </div>
  @endif
@endsection
