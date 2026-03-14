@extends('layouts/layoutMaster')

@section('title', 'Planes de activacion')

@section('content')
  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Planes</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalPlans) }}</h4>
              </div>
              <small class="mb-0">Catalogo total</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-user-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Activos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($activePlans) }}</h4>
              </div>
              <small class="mb-0">Disponibles en signup</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-checkup-list icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Inactivos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($inactivePlans) }}</h4>
              </div>
              <small class="mb-0">Fuera del flujo</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-lock icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Precio base</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">${{ number_format($baseAmount, 0, ',', '.') }}</h4>
              </div>
              <small class="mb-0">COP pago unico</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base ti tabler-cash icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Paquete inicial de activacion</h5>
        <p class="mb-0 text-body-secondary">Controla el cobro unico que debe pagar el mariachi antes de entrar al panel.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.account-activation-payments.index') }}" class="btn btn-outline-primary">Pagos de activacion</a>
        <a href="{{ route('admin.account-activation-plans.create') }}" class="btn btn-primary">Nuevo plan</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Plan</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Estado</th>
            <th>Orden</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($plans as $plan)
            <tr>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $plan->name }}</span>
                  <small class="text-muted">{{ $plan->code }}</small>
                </div>
              </td>
              <td>{{ $plan->billing_type }}</td>
              <td>${{ number_format((int) $plan->amount_cop, 0, ',', '.') }} COP</td>
              <td>
                <span class="badge {{ $plan->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                  {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>{{ number_format((int) $plan->sort_order) }}</td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a href="{{ route('admin.account-activation-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                  <form method="POST" action="{{ route('admin.account-activation-plans.toggle-status', $plan) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                      {{ $plan->is_active ? 'Inactivar' : 'Activar' }}
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-5">Aun no hay planes de activacion configurados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
