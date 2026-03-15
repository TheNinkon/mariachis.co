@extends('layouts/layoutMaster')

@section('title', 'Pagos')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

@section('page-style')
  <style>
    .payments-page .payments-hero-copy {
      max-width: 720px;
    }

    .payments-page .payments-filter-card {
      background:
        radial-gradient(circle at top right, rgba(255, 193, 7, 0.14), transparent 32%),
        linear-gradient(180deg, rgba(255, 250, 241, 0.92), rgba(255, 255, 255, 1));
    }

    .payments-page .payment-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      border: 1px solid rgba(34, 41, 47, 0.08);
      border-radius: 999px;
      padding: 0.45rem 0.75rem;
      background: rgba(255, 255, 255, 0.88);
      font-size: 0.8125rem;
      color: var(--bs-body-color);
    }

    .payments-page .payment-row-title {
      min-width: 240px;
    }

    .payments-page .payment-row-title small,
    .payments-page .payment-system-table td small {
      display: block;
      line-height: 1.45;
    }

    .payments-page .payment-kpi-copy {
      max-width: 168px;
    }

    .payments-page .payment-system-table td {
      vertical-align: top;
    }

    .payments-page .payment-system-table .payment-id {
      white-space: nowrap;
    }

    .payments-page .payment-system-table .payment-amount {
      min-width: 180px;
    }

    .payments-page .payment-system-table .payment-checkout {
      min-width: 210px;
    }

    .payments-page .payment-system-table .payment-review {
      min-width: 180px;
    }

    .payments-page .payment-system-table .payment-actions {
      min-width: 230px;
    }

    .payments-page .payment-reason {
      max-width: 220px;
      display: -webkit-box;
      overflow: hidden;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .payments-page .payment-pagination .pagination {
      margin-bottom: 0;
    }
  </style>
@endsection

@section('content')
  @php
    $approvedTotal = (int) ($totals[\App\Models\ListingPayment::STATUS_APPROVED] ?? 0);
    $pendingTotal = (int) ($totals[\App\Models\ListingPayment::STATUS_PENDING] ?? 0);
    $activationTotal = (int) ($operationTotals['activation'] ?? 0);
    $rejectedTotal = (int) ($totals[\App\Models\ListingPayment::STATUS_REJECTED] ?? 0);
    $upgradeTotal = (int) ($operationTotals[\App\Models\ListingPayment::OPERATION_UPGRADE] ?? 0);
    $retryTotal = (int) ($operationTotals[\App\Models\ListingPayment::OPERATION_RETRY] ?? 0);
    $renewalTotal = (int) ($operationTotals[\App\Models\ListingPayment::OPERATION_RENEWAL] ?? 0);
  @endphp

  <div class="payments-page">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card mb-6">
      <div class="card-widget-separator-wrapper">
        <div class="card-body card-widget-separator">
          <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 mb-5">
            <div class="payments-hero-copy">
              <h4 class="mb-1">Pagos del sistema</h4>
              <p class="mb-0 text-body-secondary">
                Centraliza activaciones, compras iniciales, upgrades, renovaciones y reintentos sin mezclar el estado financiero con la moderación editorial.
              </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('admin.account-activation-payments.index') }}" class="btn btn-outline-primary btn-sm">Pagos activación</a>
              <a href="{{ route('admin.profile-verifications.index') }}" class="btn btn-outline-primary btn-sm">Verificaciones</a>
            </div>
          </div>

          <div class="row gy-4 gy-sm-1">
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                <div class="payment-kpi-copy">
                  <h4 class="mb-1">{{ number_format($pendingTotal) }}</h4>
                  <p class="mb-0">Pendientes</p>
                  <small class="text-muted">Cobros esperando webhook o validación final.</small>
                </div>
                <span class="avatar me-sm-6">
                  <span class="avatar-initial bg-label-warning rounded text-heading">
                    <i class="icon-base ti tabler-calendar-stats icon-26px text-heading"></i>
                  </span>
                </span>
              </div>
              <hr class="d-none d-sm-block d-lg-none me-6" />
            </div>

            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                <div class="payment-kpi-copy">
                  <h4 class="mb-1">{{ number_format($approvedTotal) }}</h4>
                  <p class="mb-0">Completados</p>
                  <small class="text-muted">${{ number_format($approvedRevenue, 0, ',', '.') }} COP conciliados.</small>
                </div>
                <span class="avatar p-2 me-lg-6">
                  <span class="avatar-initial bg-label-success rounded">
                    <i class="icon-base ti tabler-checks icon-26px text-heading"></i>
                  </span>
                </span>
              </div>
              <hr class="d-none d-sm-block d-lg-none" />
            </div>

            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                <div class="payment-kpi-copy">
                  <h4 class="mb-1">{{ number_format($activationTotal) }}</h4>
                  <p class="mb-0">Activaciones</p>
                  <small class="text-muted">Altas cobradas antes del acceso al panel partner.</small>
                </div>
                <span class="avatar p-2 me-sm-6">
                  <span class="avatar-initial bg-label-info rounded">
                    <i class="icon-base ti tabler-user-plus icon-26px text-heading"></i>
                  </span>
                </span>
              </div>
            </div>

            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-start">
                <div class="payment-kpi-copy">
                  <h4 class="mb-1">{{ number_format($rejectedTotal) }}</h4>
                  <p class="mb-0">Fallidos o rechazados</p>
                  <small class="text-muted">Crédito aplicado: ${{ number_format($approvedCredits, 0, ',', '.') }} COP.</small>
                </div>
                <span class="avatar p-2">
                  <span class="avatar-initial bg-label-danger rounded">
                    <i class="icon-base ti tabler-alert-octagon icon-26px text-heading"></i>
                  </span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card payments-filter-card mb-6">
      <div class="card-body">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 mb-4">
          <div>
            <h5 class="card-title mb-1">Radar de cobros y movimientos</h5>
            <p class="mb-0 text-body-secondary">
              Filtra por estado, tipo de operación, ciudad o cualquier referencia Wompi para encontrar rápido un cobro específico.
            </p>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <span class="payment-chip">
              <i class="icon-base ti tabler-receipt-2 icon-16px"></i>
              {{ number_format($payments->total()) }} registros filtrados
            </span>
            <span class="payment-chip">
              <i class="icon-base ti tabler-arrow-up-right-circle icon-16px"></i>
              Upgrades {{ number_format($upgradeTotal + $retryTotal) }}
            </span>
            <span class="payment-chip">
              <i class="icon-base ti tabler-refresh icon-16px"></i>
              Renovaciones {{ number_format($renewalTotal) }}
            </span>
            <span class="payment-chip">
              <i class="icon-base ti tabler-layout-grid icon-16px"></i>
              Página {{ $payments->currentPage() }} / {{ max(1, $payments->lastPage()) }}
            </span>
          </div>
        </div>

        <form method="GET" action="{{ route('admin.payments.index') }}" class="row g-4">
          <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select">
              <option value="all" @selected($status === 'all')>Todos</option>
              @foreach($statuses as $item)
                <option value="{{ $item }}" @selected($status === $item)>{{ ucfirst($item) }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Operación</label>
            <select name="operation" class="form-select">
              <option value="all" @selected($operation === 'all')>Todas</option>
              @foreach($operations as $item)
                <option value="{{ $item }}" @selected($operation === $item)>{{ $item === 'activation' ? 'Activación' : ucfirst($item) }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Ciudad</label>
            <select name="city" class="form-select">
              <option value="">Todas</option>
              @foreach($cities as $cityOption)
                <option value="{{ $cityOption }}" @selected($city === $cityOption)>{{ $cityOption }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Buscar</label>
            <input
              type="text"
              name="search"
              value="{{ $search }}"
              class="form-control"
              placeholder="Referencia, transacción, anuncio, mariachi o email" />
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-label-secondary">Limpiar</a>
            <button type="submit" class="btn btn-primary">Aplicar filtros</button>
          </div>
        </form>
      </div>
    </div>

    @if($payments->isEmpty())
      <div class="card">
        <div class="card-body py-8">
          <div class="text-center mx-auto" style="max-width: 460px;">
            <span class="avatar avatar-xl mb-4">
              <span class="avatar-initial bg-label-secondary rounded">
                <i class="icon-base ti tabler-credit-card-off icon-30px text-heading"></i>
              </span>
            </span>
            <h5 class="mb-2">No hay pagos para este filtro</h5>
            <p class="mb-0 text-body-secondary">
              Ajusta los criterios de búsqueda o limpia los filtros para volver a ver todos los cobros del sistema.
            </p>
          </div>
        </div>
      </div>
    @else
      <div class="card">
        <div class="card-datatable table-responsive">
          <table class="table border-top payment-system-table">
            <thead>
              <tr>
                <th>Pago</th>
                <th>Origen</th>
                <th>Operación</th>
                <th>Monto</th>
                <th>Checkout</th>
                <th>Estado</th>
                <th>Revisión</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($payments as $payment)
                <tr>
                  <td class="payment-id">
                    <span class="badge bg-label-secondary mb-2">{{ $payment->source_label }}</span>
                    <strong class="d-block">#{{ $payment->id }}</strong>
                    <small>{{ $payment->created_at?->format('d/m/Y H:i') ?: '-' }}</small>
                  </td>

                  <td class="payment-row-title">
                    @if($payment->subject_url)
                      <a href="{{ $payment->subject_url }}" class="fw-semibold text-heading d-block">{{ $payment->subject_title }}</a>
                    @else
                      <span class="fw-semibold text-heading d-block">{{ $payment->subject_title }}</span>
                    @endif
                    <small>{{ $payment->subject_meta }}</small>
                  </td>

                  <td>
                    <strong class="d-block">{{ $payment->operation_label }}</strong>
                    <small>{{ $payment->operation_detail }}</small>
                  </td>

                  <td class="payment-amount">
                    <strong class="d-block">${{ number_format($payment->amount_cop, 0, ',', '.') }} COP</strong>
                    <small>Base ${{ number_format($payment->base_amount_cop, 0, ',', '.') }}</small>
                    <small>Crédito ${{ number_format($payment->applied_credit_cop, 0, ',', '.') }}</small>
                  </td>

                  <td class="payment-checkout">
                    <strong class="d-block">{{ $payment->checkout_reference }}</strong>
                    <small>{{ $payment->provider_transaction_id }}</small>
                    @if($payment->provider_transaction_status)
                      <small>{{ $payment->provider_transaction_status }}</small>
                    @endif
                  </td>

                  <td>
                    <span class="badge bg-label-{{ $payment->status_class }}">{{ $payment->status_label }}</span>
                  </td>

                  <td class="payment-review">
                    <div>{{ $payment->reviewed_at?->format('d/m/Y H:i') ?: 'Sin revisar' }}</div>
                    <small>{{ $payment->reviewed_by_name }}</small>
                    @if($payment->rejection_reason)
                      <small class="text-danger payment-reason">{{ $payment->rejection_reason }}</small>
                    @endif
                  </td>

                  <td class="text-end payment-actions">
                    @if($payment->is_pending)
                      <div class="d-flex flex-column gap-2 align-items-end">
                        <form action="{{ $payment->approve_url }}" method="POST">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="action" value="approve" />
                          <button type="submit" class="btn btn-sm btn-success">Aprobar</button>
                        </form>

                        <form action="{{ $payment->reject_url }}" method="POST" class="w-100" style="min-width: 220px;">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="action" value="reject" />
                          <textarea
                            class="form-control form-control-sm"
                            name="rejection_reason"
                            rows="2"
                            placeholder="Motivo del rechazo"
                            required></textarea>
                          <button type="submit" class="btn btn-sm btn-outline-danger mt-2 w-100">Rechazar</button>
                        </form>
                      </div>
                    @else
                      <span class="text-muted small">Sin acciones pendientes</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4 payment-pagination">
        {{ $payments->links() }}
      </div>
    @endif
  </div>
@endsection
