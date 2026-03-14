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
        <p class="mb-0 text-body-secondary">Estos planes alimentan directamente la compra de verificacion del partner.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Paquetes de anuncios</a>
        <a href="{{ route('admin.profile-verification-plans.index') }}" class="btn btn-outline-primary">Volver a verificacion</a>
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
                <input class="form-control" name="name" value="{{ old('name', $plan->name) }}" maxlength="120" required />
              </div>

              <div class="col-md-6">
                <label class="form-label">Codigo interno</label>
                <input class="form-control" name="code" value="{{ old('code', $plan->code) }}" maxlength="40" required @readonly($plan->exists) />
              </div>

              <div class="col-md-4">
                <label class="form-label">Duracion en meses</label>
                <input type="number" min="1" max="36" class="form-control" name="duration_months" value="{{ old('duration_months', $plan->duration_months ?: 1) }}" required />
              </div>

              <div class="col-md-4">
                <label class="form-label">Precio COP</label>
                <input type="number" min="0" class="form-control" name="amount_cop" value="{{ old('amount_cop', $plan->amount_cop ?? 18900) }}" required />
              </div>

              <div class="col-md-4">
                <label class="form-label">Orden</label>
                <input type="number" min="0" max="9999" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" />
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
              <div class="small text-muted mt-2">Si lo apagas, deja de aparecer en partner para nuevas compras.</div>
            </div>

            <div class="bg-lighter rounded p-4 mt-4">
              <div class="fw-semibold mb-2">Lo que habilita este producto</div>
              <ul class="small text-muted ps-3 mb-0">
                <li>Insignia verificada en el perfil publico</li>
                <li>Handle premium /@personalizado</li>
                <li>Foto de perfil desbloqueada</li>
                <li>Revision manual de identidad y marca</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 flex-wrap">
      <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
      <a href="{{ route('admin.profile-verification-plans.index') }}" class="btn btn-outline-primary">Cancelar</a>
    </div>
  </form>
@endsection
