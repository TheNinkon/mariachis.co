@extends('layouts/layoutMaster')

@section('title', $pageTitle)

@section('content')
  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">{{ $pageTitle }}</h5>
        <p class="mb-0 text-body-secondary">Este paquete se cobra una sola vez antes de habilitar el panel del mariachi.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.account-activation-payments.index') }}" class="btn btn-outline-secondary">Pagos de activacion</a>
        <a href="{{ route('admin.account-activation-plans.index') }}" class="btn btn-outline-primary">Volver a activacion</a>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
      @method($formMethod)
    @endif

    <div class="row g-6">
      <div class="col-xl-7">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Datos base</h5>
          </div>
          <div class="card-body">
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input class="form-control" name="name" value="{{ old('name', $plan->name) }}" maxlength="140" required />
              </div>

              <div class="col-md-6">
                <label class="form-label">Codigo interno</label>
                <input class="form-control" name="code" value="{{ old('code', $plan->code ?: 'ACTIVACION_CUENTA') }}" maxlength="50" required @readonly($plan->exists) />
              </div>

              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <input class="form-control" name="billing_type" value="{{ old('billing_type', $plan->billing_type ?: 'one_time') }}" readonly />
              </div>

              <div class="col-md-4">
                <label class="form-label">Precio COP</label>
                <input type="number" min="0" class="form-control" name="amount_cop" value="{{ old('amount_cop', $plan->amount_cop ?? 18900) }}" required />
              </div>

              <div class="col-md-4">
                <label class="form-label">Orden</label>
                <input type="number" min="0" max="9999" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 10) }}" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Estado</h5>
          </div>
          <div class="card-body">
            <input type="hidden" name="is_active" value="0" />
            <div class="form-check form-switch border rounded p-4">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $plan->exists ? $plan->is_active : true) ? 'checked' : '' }} />
              <label class="form-check-label fw-semibold" for="is_active">Plan activo</label>
              <div class="small text-muted mt-2">Solo un plan debe quedar activo. Si activas este, los demas se desactivan.</div>
            </div>

            <div class="bg-lighter rounded p-4 mt-4">
              <div class="fw-semibold mb-2">Que controla este paquete</div>
              <ul class="small text-muted ps-3 mb-0">
                <li>Desbloquea el primer acceso al panel partner</li>
                <li>Se paga una sola vez por Nequi</li>
                <li>Requiere revision manual del admin</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 flex-wrap">
      <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
      <a href="{{ route('admin.account-activation-plans.index') }}" class="btn btn-outline-primary">Cancelar</a>
    </div>
  </form>
@endsection
