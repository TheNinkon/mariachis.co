@extends('layouts/layoutMaster')

@section('title', 'Moderacion de anuncios')

@section('content')
  @php
    $statusMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
      'pending' => ['label' => 'Pendiente', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
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

  <div class="row g-6 mb-6">
    @foreach (['pending', 'approved', 'rejected', 'draft'] as $cardStatus)
      @php
        $meta = $statusMap[$cardStatus];
        $total = (int) ($statusTotals[$cardStatus] ?? 0);
      @endphp
      <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-between">
              <div class="content-left">
                <span class="text-heading">{{ $meta['label'] }}</span>
                <div class="d-flex align-items-center my-1">
                  <h4 class="mb-0 me-2">{{ number_format($total) }}</h4>
                </div>
                <small class="mb-0">Anuncios en este estado</small>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-{{ $meta['class'] }}">
                  <i class="icon-base ti {{ $cardStatus === 'approved' ? 'tabler-circle-check' : ($cardStatus === 'pending' ? 'tabler-clock-hour-4' : ($cardStatus === 'rejected' ? 'tabler-alert-circle' : 'tabler-edit')) }} icon-26px"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card mb-6">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('admin.listings.index') }}" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Estado de revision</label>
          <select name="review_status" class="form-select">
            <option value="all" @selected($reviewStatus === 'all')>Todos</option>
            @foreach ($statuses as $statusOption)
              <option value="{{ $statusOption }}" @selected($reviewStatus === $statusOption)>{{ $statusMap[$statusOption]['label'] ?? $statusOption }}</option>
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
          <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Titulo, slug, mariachi o email" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Motivo</label>
          <input type="text" name="reason" value="{{ $reason }}" class="form-control" placeholder="Texto dentro del rechazo" />
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Aplicar filtros</button>
          <a href="{{ route('admin.listings.index') }}" class="btn btn-label-secondary">Limpiar</a>
        </div>
      </form>
    </div>
  </div>

  @if ($listings->isEmpty())
    <div class="card">
      <div class="card-body">
        <p class="mb-0 text-muted">No hay anuncios que coincidan con este filtro.</p>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach ($listings as $listing)
        @php
          $meta = $statusMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
          $providerName = $listing->mariachiProfile?->business_name ?: $listing->mariachiProfile?->user?->display_name ?: 'Mariachi';
        @endphp
        <div class="col-12">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                <div class="flex-grow-1">
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <h5 class="mb-0">{{ $listing->title ?: 'Anuncio sin titulo' }}</h5>
                    <span class="badge bg-label-{{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    <span class="badge bg-label-{{ $listing->is_active ? 'success' : 'secondary' }}">{{ \Illuminate\Support\Str::headline($listing->status) }}</span>
                  </div>
                  <p class="mb-2 text-body-secondary">
                    {{ $providerName }} · {{ $listing->city_name ?: 'Sin ciudad' }} · {{ $listing->selected_plan_code ?: 'Sin plan' }}
                  </p>
                  <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                    <span>Slug: {{ $listing->slug ?: 'pendiente' }}</span>
                    <span>Completitud: {{ (int) $listing->listing_completion }}%</span>
                    <span>Fotos: {{ (int) $listing->photos_count }}</span>
                    <span>Videos: {{ (int) $listing->videos_count }}</span>
                    <span>Opiniones: {{ (int) $listing->reviews_count }}</span>
                    <span>Solicitudes: {{ (int) $listing->quote_conversations_count }}</span>
                  </div>

                  @if ($listing->submitted_for_review_at)
                    <p class="mb-0 mt-2 small text-body-secondary">
                      Enviado: {{ $listing->submitted_for_review_at->format('Y-m-d H:i') }}
                      @if ($listing->reviewed_at)
                        · Revisado: {{ $listing->reviewed_at->format('Y-m-d H:i') }}
                      @endif
                    </p>
                  @endif

                  @if ($listing->rejection_reason)
                    <div class="alert alert-danger py-2 px-3 mt-3 mb-0">
                      <p class="mb-1 fw-semibold">Motivo del rechazo</p>
                      <p class="mb-0">{{ \Illuminate\Support\Str::limit($listing->rejection_reason, 180) }}</p>
                    </div>
                  @endif
                </div>

                <div class="d-flex flex-column gap-2 align-items-lg-end">
                  <a href="{{ route('admin.listings.show', $listing) }}" class="btn btn-primary">Ver detalle</a>
                  @if ($listing->mariachiProfile?->user_id)
                    <a href="{{ route('admin.mariachis.show', $listing->mariachiProfile->user_id) }}" class="btn btn-outline-secondary">Ver mariachi</a>
                  @endif
                  @if ($listing->isApprovedForMarketplace() && $listing->slug)
                    <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" rel="noopener" class="btn btn-outline-dark">Abrir publico</a>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $listings->links() }}
    </div>
  @endif
@endsection
