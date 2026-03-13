@extends('layouts/layoutMaster')

@section('title', 'Mis anuncios')

@section('page-style')
  <style>
    .listing-vip-card {
      position: relative;
      overflow: hidden;
    }

    .listing-vip-ribbon {
      position: absolute;
      top: 1rem;
      right: -2.25rem;
      z-index: 2;
      width: 8rem;
      transform: rotate(45deg);
      background: linear-gradient(135deg, #ff6b7c 0%, #d92f58 100%);
      color: #fff;
      text-align: center;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      padding: 0.3rem 0;
      box-shadow: 0 14px 28px -18px rgba(217, 47, 88, 0.85);
      pointer-events: none;
    }
  </style>
@endsection

@section('content')
  @php
    $reviewMap = [
      'draft' => ['label' => 'Borrador de revision', 'class' => 'secondary'],
      'pending' => ['label' => 'En revision', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
    ];
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago en revision', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
  @endphp

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Anuncios / servicios</h5>
        <p class="mb-1">
          Plan actual:
          <strong>{{ $planSummary['name'] }}</strong>
          @if($planSummary['badge_text'])
            <span class="badge bg-label-primary ms-1">{{ $planSummary['badge_text'] }}</span>
          @endif
        </p>
        <small class="text-muted">
          Usados {{ $listingsUsed }} de {{ $listingLimit }}
          · Restantes {{ $listingsRemaining }}
          · Ciudades {{ $capabilities['included_cities'] }}
          · Zonas {{ $capabilities['max_zones_covered'] ?? 0 }}
          · Fotos {{ $capabilities['max_photos_per_listing'] }}
          · Videos {{ $capabilities['max_videos_per_listing'] }}
        </small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-outline-primary">Perfil proveedor</a>
        <a href="{{ route('mariachi.listings.create') }}" class="btn btn-primary {{ $listingsRemaining <= 0 ? 'disabled' : '' }}">Crear anuncio</a>
      </div>
    </div>
  </div>

  @if($planIssues !== [])
    <div class="alert alert-warning">
      <strong>Tu plan actual requiere ajuste.</strong>
      <ul class="mb-0 mt-2">
        @foreach($planIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-header"><h5 class="mb-0">Listado de anuncios</h5></div>
    <div class="card-body">
      @if($listings->isEmpty())
        <p class="mb-0 text-muted">Aún no has creado anuncios. Crea un borrador, complétalo y al final elige plan para activarlo.</p>
      @else
        <div class="row g-4">
          @foreach($listings as $listing)
            @php
              $photo = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
              $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
              $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
              $canSubmit = $listing->canBeSubmittedForReview();
              $submitLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar a revisión' : 'Enviar a revisión';
              $currentListingIssues = $listingIssues->get($listing->id, []);
              $isVip = $listing->hasPremiumMarketplaceBadge();
            @endphp
            <div class="col-md-6 col-xl-4">
              <div class="border rounded p-3 h-100 d-flex flex-column {{ $isVip ? 'listing-vip-card' : '' }}">
                @if($isVip)
                  <span class="listing-vip-ribbon">{{ $listing->marketplaceBadgeLabel() }}</span>
                @endif
                @if($photo)
                  <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $listing->title }}" class="img-fluid rounded mb-3" style="height:160px;object-fit:cover;">
                @endif
                <h6 class="mb-1">{{ $listing->title }}</h6>
                <p class="mb-2 text-muted">{{ $listing->city_name ?: 'Sin ciudad' }}</p>
                <p class="mb-2">Estado: <span class="badge bg-label-{{ $listing->is_active ? 'success' : 'warning' }}">{{ $listing->status }}</span></p>
                <p class="mb-2">Revision: <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span></p>
                <p class="mb-2">Pago: <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span></p>
                <p class="mb-3">Plan: <strong>{{ $listing->effectivePlanCode() ?: 'sin seleccionar' }}</strong></p>
                <p class="mb-3">Completitud: <strong>{{ $listing->listing_completion }}%</strong></p>

                @if($listing->submitted_for_review_at)
                  <p class="mb-2 small text-muted">Enviado {{ $listing->submitted_for_review_at->diffForHumans() }}</p>
                @endif

                @if($listing->rejection_reason)
                  <div class="alert alert-danger py-2 px-3 mb-3">
                    <p class="mb-1 fw-semibold">Motivo del rechazo</p>
                    <p class="mb-0 small">{{ $listing->rejection_reason }}</p>
                  </div>
                @elseif($listing->isPaymentRejected() && $listing->latestPayment?->rejection_reason)
                  <div class="alert alert-danger py-2 px-3 mb-3">
                    <p class="mb-1 fw-semibold">Pago rechazado</p>
                    <p class="mb-0 small">{{ $listing->latestPayment->rejection_reason }}</p>
                  </div>
                @elseif($listing->isPaymentPending())
                  <div class="alert alert-warning py-2 px-3 mb-3">
                    <p class="mb-0 small">Comprobante enviado. No puedes editar el anuncio mientras el admin valida el pago.</p>
                  </div>
                @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_PENDING)
                  <div class="alert alert-warning py-2 px-3 mb-3">
                    <p class="mb-0 small">Tu anuncio ya está en revisión. Mientras tanto no podrás editarlo.</p>
                  </div>
                @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_DRAFT && $listing->selected_plan_code)
                  <div class="alert alert-secondary py-2 px-3 mb-3">
                    <p class="mb-0 small">Ya tiene plan, pero todavía falta enviarlo a revisión para publicarlo.</p>
                  </div>
                @endif

                @if($currentListingIssues !== [])
                  <div class="alert alert-warning py-2 px-3 mb-3">
                    <p class="mb-1 fw-semibold">Requiere ajuste para publicar</p>
                    <ul class="mb-0 small ps-3">
                      @foreach($currentListingIssues as $issue)
                        <li>{{ $issue }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <div class="d-flex flex-wrap gap-2 mt-auto">
                  @if($listing->isPendingReview() || $listing->isPaymentPending())
                    <button type="button" class="btn btn-sm btn-outline-primary" disabled>{{ $listing->isPaymentPending() ? 'Pago en revisión' : 'En revisión' }}</button>
                  @else
                    <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                  @endif
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
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-secondary">Plan</a>
                  @if($canSubmit && $currentListingIssues === [])
                    <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-primary">{{ $submitLabel }}</button>
                    </form>
                  @elseif($canSubmit)
                    <button type="button" class="btn btn-sm btn-secondary" disabled>Requiere ajuste</button>
                  @endif
                  @if($listing->isApprovedForMarketplace() && $listing->slug)
                    <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" class="btn btn-sm btn-outline-dark">Ver publico</a>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endsection
