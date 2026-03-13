@extends('layouts/layoutMaster')

@section('title', 'Mis anuncios')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/mariachi-listings-index.js'])
@endsection

@section('page-style')
  <style>
    .partner-listing-thumb {
      width: 56px;
      height: 56px;
      border-radius: 0.9rem;
      object-fit: cover;
      flex-shrink: 0;
    }

    .partner-listing-thumb-fallback {
      width: 56px;
      height: 56px;
      border-radius: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(115, 103, 240, 0.12);
      color: #7367f0;
      flex-shrink: 0;
    }

    .partner-listing-progress {
      min-width: 120px;
    }

    .partner-listing-progress .progress {
      height: 6px;
    }

    .partner-listing-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .partner-listing-note {
      max-width: 420px;
    }
  </style>
@endsection

@section('content')
  @php
    $reviewMap = [
      'draft' => ['label' => 'Borrador de revisión', 'class' => 'secondary'],
      'pending' => ['label' => 'En revisión', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
    ];
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago en revisión', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
    $statusMap = [
      \App\Models\MariachiListing::STATUS_DRAFT => ['label' => 'Borrador', 'class' => 'secondary'],
      \App\Models\MariachiListing::STATUS_AWAITING_PLAN => ['label' => 'Sin plan', 'class' => 'warning'],
      \App\Models\MariachiListing::STATUS_AWAITING_PAYMENT => ['label' => 'Esperando pago', 'class' => 'warning'],
      \App\Models\MariachiListing::STATUS_ACTIVE => ['label' => 'Activo', 'class' => 'success'],
      \App\Models\MariachiListing::STATUS_PAUSED => ['label' => 'Pausado', 'class' => 'danger'],
    ];
    $draftUsagePercent = $openDraftLimit > 0 ? min(100, (int) round(($openDraftsCount / $openDraftLimit) * 100)) : 0;
    $attentionCount = $listings->filter(fn (\App\Models\MariachiListing $listing): bool => $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED || $listing->payment_status === \App\Models\MariachiListing::PAYMENT_REJECTED)->count();
  @endphp

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="card mb-6">
    <div class="card-widget-separator-wrapper">
      <div class="card-body card-widget-separator">
        <div class="row gy-4 gy-sm-1">
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-1">{{ $openDraftsCount }} / {{ $openDraftLimit }}</h4>
                <p class="mb-2">Borradores abiertos</p>
                <div class="progress" style="height: 6px; width: 112px;">
                  <div class="progress-bar" role="progressbar" style="width: {{ $draftUsagePercent }}%;"></div>
                </div>
              </div>
              <span class="avatar me-sm-6">
                <span class="avatar-initial bg-label-secondary rounded text-heading">
                  <i class="icon-base ti tabler-pencil icon-26px text-heading"></i>
                </span>
              </span>
            </div>
            <hr class="d-none d-sm-block d-lg-none me-6" />
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-1">{{ $pendingReviewCount }}</h4>
                <p class="mb-0">En revisión</p>
              </div>
              <span class="avatar p-2 me-lg-6">
                <span class="avatar-initial bg-label-secondary rounded">
                  <i class="icon-base ti tabler-hourglass-high icon-26px text-heading"></i>
                </span>
              </span>
            </div>
            <hr class="d-none d-sm-block d-lg-none" />
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
              <div>
                <h4 class="mb-1">{{ $activeCount }}</h4>
                <p class="mb-0">Activos</p>
              </div>
              <span class="avatar p-2 me-sm-6">
                <span class="avatar-initial bg-label-secondary rounded">
                  <i class="icon-base ti tabler-rosette-discount-check icon-26px text-heading"></i>
                </span>
              </span>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h4 class="mb-1">{{ $awaitingPaymentCount }}</h4>
                <p class="mb-0">Esperando pago</p>
                <small class="text-muted">Pausados: {{ $pausedCount }} · Ajustes: {{ $attentionCount }}</small>
              </div>
              <span class="avatar p-2">
                <span class="avatar-initial bg-label-secondary rounded">
                  <i class="icon-base ti tabler-credit-card-pay icon-26px text-heading"></i>
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if(! $canCreateListingDraft)
    <div class="alert alert-warning">
      <strong>Tope de borradores alcanzado.</strong> Publica o elimina uno para liberar espacio antes de crear otro.
    </div>
  @endif

  @if($planIssues !== [])
    <div class="alert alert-warning">
      <strong>Tu configuración actual requiere ajuste.</strong>
      <ul class="mb-0 mt-2 ps-3">
        @foreach($planIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <h5 class="card-title mb-0">Listado de anuncios</h5>

      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-outline-primary">Perfil</a>
        @if($canCreateListingDraft)
          <a href="{{ route('mariachi.listings.create') }}" class="btn btn-primary">Crear anuncio</a>
        @else
          <button type="button" class="btn btn-primary" disabled>Crear anuncio</button>
        @endif
      </div>
    </div>

    @if($listings->isEmpty())
      <div class="card-body">
        <p class="mb-0 text-muted">Aún no has creado anuncios. Empieza con un borrador, complétalo y activa el plan solo en el anuncio que quieras publicar.</p>
      </div>
    @else
      <div class="card-datatable table-responsive">
        <table class="table datatables-partner-listings border-top">
          <thead>
            <tr>
              <th></th>
              <th>Anuncio</th>
              <th>Estado</th>
              <th>Pago</th>
              <th>Plan</th>
              <th>Completitud</th>
              <th>Actualizado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($listings as $listing)
              @php
                $photo = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
                $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
                $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
                $statusMeta = $statusMap[$listing->status] ?? ['label' => \Illuminate\Support\Str::headline($listing->status), 'class' => 'secondary'];
                $canSubmit = $listing->canBeSubmittedForReview();
                $submitLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar' : 'Enviar';
                $currentListingIssues = $listingIssues->get($listing->id, []);
              @endphp
              <tr>
                <td></td>
                <td>
                  <div class="d-flex align-items-start gap-3">
                    @if($photo)
                      <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $listing->title }}" class="partner-listing-thumb" />
                    @else
                      <span class="partner-listing-thumb-fallback">
                        <i class="icon-base ti tabler-speakerphone"></i>
                      </span>
                    @endif

                    <div class="d-flex flex-column">
                      <span class="fw-semibold text-heading">{{ $listing->title }}</span>
                      <small class="text-muted">{{ $listing->city_name ?: 'Sin ciudad definida' }}</small>

                      @if($listing->submitted_for_review_at)
                        <small class="text-body-secondary mt-1">Enviado {{ $listing->submitted_for_review_at->diffForHumans() }}</small>
                      @endif

                      @if($listing->rejection_reason)
                        <small class="text-danger mt-1">{{ \Illuminate\Support\Str::limit($listing->rejection_reason, 110) }}</small>
                      @elseif($listing->isPaymentRejected() && $listing->latestPayment?->rejection_reason)
                        <small class="text-danger mt-1">{{ \Illuminate\Support\Str::limit($listing->latestPayment->rejection_reason, 110) }}</small>
                      @elseif($currentListingIssues !== [])
                        <small class="text-warning mt-1">{{ \Illuminate\Support\Str::limit(implode(' ', $currentListingIssues), 110) }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column gap-2">
                    <span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                    <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ \Illuminate\Support\Str::headline($listing->selected_plan_code ?: 'sin seleccionar') }}</span>
                    <small class="text-muted">Efectivo: {{ \Illuminate\Support\Str::headline($listing->effectivePlanCode() ?: 'sin plan') }}</small>
                  </div>
                </td>
                <td data-order="{{ (int) $listing->listing_completion }}">
                  <div class="partner-listing-progress">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="small text-body-secondary">Progreso</span>
                      <span class="fw-semibold">{{ (int) $listing->listing_completion }}%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" role="progressbar" style="width: {{ (int) $listing->listing_completion }}%;"></div>
                    </div>
                  </div>
                </td>
                <td data-order="{{ optional($listing->updated_at)->timestamp ?: 0 }}">
                  <div class="d-flex flex-column">
                    <span>{{ $listing->updated_at?->diffForHumans() ?: '-' }}</span>
                    <small class="text-muted">{{ $listing->updated_at?->format('d/m/Y H:i') ?: '-' }}</small>
                  </div>
                </td>
                <td>
                  <div class="partner-listing-actions">
                    @if($listing->isPendingReview() || $listing->isPaymentPending())
                      <button type="button" class="btn btn-sm btn-outline-primary" disabled>{{ $listing->isPaymentPending() ? 'Pago en revisión' : 'En revisión' }}</button>
                    @else
                      <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    @endif

                    <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-secondary">Plan</a>

                    @if($listing->canOwnerPause())
                      <form method="POST" action="{{ route('mariachi.listings.pause', ['listing' => $listing->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">Pausar</button>
                      </form>
                    @elseif($listing->canOwnerResume())
                      <form method="POST" action="{{ route('mariachi.listings.resume', ['listing' => $listing->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">Reanudar</button>
                      </form>
                    @endif

                    @if($canSubmit && $currentListingIssues === [])
                      <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">{{ $submitLabel }}</button>
                      </form>
                    @elseif($canSubmit)
                      <button type="button" class="btn btn-sm btn-secondary" disabled>Requiere ajuste</button>
                    @endif

                    @if($listing->isApprovedForMarketplace() && $listing->slug)
                      <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" class="btn btn-sm btn-outline-dark">Ver público</a>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
