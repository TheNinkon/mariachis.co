@extends('layouts/layoutMaster')

@section('title', 'Anuncios - Moderacion')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

@section('page-style')
  <style>
    .admin-listing-thumb {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      object-fit: cover;
      flex: 0 0 56px;
    }

    .admin-listing-thumb-fallback {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      flex: 0 0 56px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(115, 103, 240, 0.12);
      color: var(--bs-primary);
      font-weight: 700;
      font-size: 1rem;
    }

    .admin-listing-cell {
      min-width: 280px;
    }

    .admin-listing-title {
      display: -webkit-box;
      overflow: hidden;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      line-height: 1.35;
    }

    .admin-listing-meta {
      color: var(--bs-body-color);
      opacity: 0.7;
      font-size: 0.8125rem;
    }

    .admin-listing-stats {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.5rem 0.75rem;
      min-width: 165px;
    }

    .admin-listing-stats span {
      font-size: 0.8125rem;
      color: var(--bs-body-color);
      opacity: 0.8;
    }

    .admin-listing-stats strong {
      display: block;
      color: var(--bs-heading-color);
      font-size: 0.9375rem;
    }

    .admin-listing-reason {
      max-width: 240px;
      display: -webkit-box;
      overflow: hidden;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      line-height: 1.45;
    }

    .admin-listing-provider .avatar-initial {
      font-size: 0.875rem;
    }

    .admin-listings-pagination .pagination {
      margin-bottom: 0;
    }
  </style>
@endsection

@section('content')
  @php
    $statusMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary', 'icon' => 'tabler-edit-circle'],
      'pending' => ['label' => 'Pendiente', 'class' => 'warning', 'icon' => 'tabler-clock-hour-4'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success', 'icon' => 'tabler-circle-check'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger', 'icon' => 'tabler-alert-circle'],
    ];

    $operationalMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
      'awaiting_plan' => ['label' => 'Sin plan', 'class' => 'warning'],
      'awaiting_payment' => ['label' => 'Esperando pago', 'class' => 'warning'],
      'active' => ['label' => 'Activo', 'class' => 'success'],
      'paused' => ['label' => 'Pausado', 'class' => 'secondary'],
    ];

    $paymentMap = [
      'none' => ['label' => 'Sin pago', 'class' => 'secondary'],
      'pending' => ['label' => 'Pago en revision', 'class' => 'warning'],
      'approved' => ['label' => 'Pago aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
  @endphp

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-widget-separator-wrapper">
      <div class="card-body card-widget-separator">
        <div class="row gy-4 gy-sm-1">
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-0">{{ number_format($listingMetrics['pending']) }}</h4>
                <p class="mb-0">Pendientes de revision</p>
              </div>
              <span class="avatar me-sm-6">
                <span class="avatar-initial bg-label-warning rounded text-heading">
                  <i class="icon-base ti tabler-clock-hour-4 icon-26px text-heading"></i>
                </span>
              </span>
            </div>
            <hr class="d-none d-sm-block d-lg-none me-6" />
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-0">{{ number_format($listingMetrics['approved']) }}</h4>
                <p class="mb-0">Aprobados</p>
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
              <div>
                <h4 class="mb-0">{{ number_format($listingMetrics['rejected']) }}</h4>
                <p class="mb-0">Rechazados</p>
              </div>
              <span class="avatar p-2 me-sm-6">
                <span class="avatar-initial bg-label-danger rounded">
                  <i class="icon-base ti tabler-alert-octagon icon-26px text-heading"></i>
                </span>
              </span>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h4 class="mb-0">{{ number_format($listingMetrics['live']) }}</h4>
                <p class="mb-0">Publicados</p>
              </div>
              <span class="avatar p-2">
                <span class="avatar-initial bg-label-info rounded">
                  <i class="icon-base ti tabler-world icon-26px text-heading"></i>
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom">
      <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
        <div>
          <h5 class="card-title mb-1">Moderacion de anuncios</h5>
          <p class="mb-0 text-body-secondary">Supervisa revision editorial, estado operativo y calidad de contenido de cada anuncio.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="badge bg-label-primary">Total {{ number_format($listingMetrics['total']) }}</span>
          <span class="badge bg-label-warning">Pagos pendientes {{ number_format($listingMetrics['payment_pending']) }}</span>
          <span class="badge bg-label-dark">Pagina {{ $listings->currentPage() }} / {{ $listings->lastPage() }}</span>
        </div>
      </div>

      <form method="GET" action="{{ route('admin.listings.index') }}" class="row g-4 pt-4">
        <div class="col-md-3">
          <label class="form-label">Estado editorial</label>
          <select name="review_status" class="form-select">
            <option value="all" @selected($reviewStatus === 'all')>Todos</option>
            @foreach ($statuses as $statusOption)
              <option value="{{ $statusOption }}" @selected($reviewStatus === $statusOption)>{{ $statusMap[$statusOption]['label'] ?? \Illuminate\Support\Str::headline($statusOption) }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Estado de pago</label>
          <select name="payment_status" class="form-select">
            <option value="all" @selected($paymentStatus === 'all')>Todos</option>
            @foreach ($paymentStatuses as $paymentOption)
              <option value="{{ $paymentOption }}" @selected($paymentStatus === $paymentOption)>{{ $paymentMap[$paymentOption]['label'] ?? \Illuminate\Support\Str::headline($paymentOption) }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Ciudad</label>
          <select name="city" class="form-select">
            <option value="">Todas</option>
            @foreach ($cities as $cityOption)
              <option value="{{ $cityOption }}" @selected($city === $cityOption)>{{ $cityOption }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Busqueda</label>
          <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-control"
            placeholder="Titulo, slug, mariachi o email" />
        </div>

        <div class="col-md-3">
          <label class="form-label">Motivo del rechazo</label>
          <input
            type="text"
            name="reason"
            value="{{ $reason }}"
            class="form-control"
            placeholder="Texto dentro del rechazo" />
        </div>

        <div class="col-12 d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-primary">Aplicar filtros</button>
          <a href="{{ route('admin.listings.index') }}" class="btn btn-label-secondary">Limpiar</a>
        </div>
      </form>
    </div>

    <div class="card-datatable table-responsive">
      <table class="table border-top align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Anuncio</th>
            <th>Fechas</th>
            <th>Mariachi</th>
            <th>Senales</th>
            <th>Revision</th>
            <th>Publicacion</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($listings as $listing)
            @php
              $reviewMeta = $statusMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary', 'icon' => 'tabler-circle'];
              $operationalMeta = $operationalMap[$listing->status] ?? ['label' => \Illuminate\Support\Str::headline($listing->status ?: 'draft'), 'class' => 'secondary'];
              $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
              $providerUser = $listing->mariachiProfile?->user;
              $providerName = $listing->mariachiProfile?->business_name ?: $providerUser?->display_name ?: 'Mariachi sin nombre';
              $thumb = $listing->photos->first();
              $initials = collect(preg_split('/\s+/', trim($listing->title ?: $providerName)))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
            @endphp
            <tr>
              <td class="text-nowrap">
                <span class="fw-semibold">#{{ $listing->id }}</span>
              </td>

              <td class="admin-listing-cell">
                <div class="d-flex align-items-center gap-3">
                  @if ($thumb?->path)
                    <img
                      src="{{ asset('storage/'.$thumb->path) }}"
                      alt="Miniatura del anuncio"
                      class="admin-listing-thumb border" />
                  @else
                    <span class="admin-listing-thumb-fallback">{{ $initials ?: 'AN' }}</span>
                  @endif

                  <div class="d-flex flex-column">
                    <a href="{{ route('admin.listings.show', $listing) }}" class="fw-semibold text-heading admin-listing-title">
                      {{ $listing->title ?: 'Anuncio sin titulo' }}
                    </a>
                    <span class="admin-listing-meta">{{ $listing->city_name ?: 'Sin ciudad' }} · {{ $listing->slug ?: 'slug pendiente' }}</span>
                    <span class="admin-listing-meta">
                      {{ $listing->base_price ? '$'.number_format((float) $listing->base_price, 0, ',', '.') : 'Precio pendiente' }}
                    </span>
                  </div>
                </div>
              </td>

              <td class="text-nowrap">
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ optional($listing->submitted_for_review_at ?: $listing->updated_at)->format('d/m/Y H:i') ?: 'Sin fecha' }}</span>
                  <small class="text-body-secondary">Revision / actividad</small>
                </div>
                <div class="d-flex flex-column mt-2">
                  <span class="fw-medium">{{ optional($listing->created_at)->format('d/m/Y H:i') ?: 'Sin fecha' }}</span>
                  <small class="text-body-secondary">Creado</small>
                </div>
              </td>

              <td class="admin-listing-provider">
                <div class="d-flex align-items-center text-nowrap">
                  <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ collect(preg_split('/\s+/', trim($providerName)))->filter()->take(2)->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'MR' }}
                    </span>
                  </div>
                  <div class="d-flex flex-column">
                    <h6 class="mb-0">{{ $providerName }}</h6>
                    <small>{{ $providerUser?->email ?: 'Sin email' }}</small>
                  </div>
                </div>
              </td>

              <td>
                <div class="admin-listing-stats">
                  <div>
                    <strong>{{ (int) $listing->photos_count }}</strong>
                    <span>Fotos</span>
                  </div>
                  <div>
                    <strong>{{ (int) $listing->videos_count }}</strong>
                    <span>Videos</span>
                  </div>
                  <div>
                    <strong>{{ (int) $listing->reviews_count }}</strong>
                    <span>Opiniones</span>
                  </div>
                  <div>
                    <strong>{{ (int) $listing->quote_conversations_count }}</strong>
                    <span>Leads</span>
                  </div>
                </div>
              </td>

              <td>
                <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
                @if ($listing->rejection_reason)
                  <div class="text-danger small mt-2 admin-listing-reason">{{ $listing->rejection_reason }}</div>
                @else
                  <div class="text-body-secondary small mt-2">
                    {{ $listing->reviewedBy?->display_name ? 'Por '.$listing->reviewedBy->display_name : 'Sin observaciones' }}
                  </div>
                @endif
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <span class="badge bg-label-{{ $operationalMeta['class'] }}">{{ $operationalMeta['label'] }}</span>
                  <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                  <span class="badge bg-label-{{ $listing->isApprovedForMarketplace() ? 'success' : 'secondary' }}">
                    {{ $listing->isApprovedForMarketplace() ? 'Visible' : 'No visible' }}
                  </span>
                  <small class="text-body-secondary">Plan {{ \Illuminate\Support\Str::upper($listing->selected_plan_code ?: 'sin plan') }}</small>
                </div>
              </td>

              <td>
                <div class="d-flex justify-content-sm-start align-items-sm-center">
                  <button class="btn btn-text-secondary rounded-pill waves-effect btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <a href="{{ route('admin.listings.show', $listing) }}" class="dropdown-item">Ver detalle</a>
                    @if ($providerUser)
                      <a href="{{ route('admin.mariachis.show', $providerUser) }}" class="dropdown-item">Ver mariachi</a>
                    @endif
                    @if ($listing->isApprovedForMarketplace() && $listing->slug)
                      <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" rel="noopener" class="dropdown-item">Abrir publico</a>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-body-secondary">
                No hay anuncios que coincidan con este filtro.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($listings->hasPages())
      <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 admin-listings-pagination">
        <p class="mb-0 text-body-secondary">
          Mostrando {{ $listings->firstItem() }} a {{ $listings->lastItem() }} de {{ $listings->total() }} anuncios
        </p>
        {{ $listings->links() }}
      </div>
    @endif
  </div>
@endsection
