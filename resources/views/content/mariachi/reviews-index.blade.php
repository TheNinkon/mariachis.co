@extends('layouts/layoutMaster')

@section('title', 'Opiniones - Panel Mariachi')

@section('content')
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

@php
  $statusMap = [
    'pending' => ['label' => 'Pendiente', 'class' => 'warning'],
    'approved' => ['label' => 'Aprobada', 'class' => 'success'],
    'rejected' => ['label' => 'Rechazada', 'class' => 'danger'],
    'reported' => ['label' => 'Reportada', 'class' => 'info'],
    'hidden' => ['label' => 'Oculta', 'class' => 'secondary'],
  ];

  $verificationMap = [
    'basic' => ['label' => 'Opinion basica', 'class' => 'secondary'],
    'manual_validated' => ['label' => 'Validada manualmente', 'class' => 'primary'],
    'evidence_attached' => ['label' => 'Con foto/prueba', 'class' => 'info'],
  ];

  $totalForDistribution = max($totalApproved, 1);
  $statusTotalForChart = max((int) $totalReviews, 1);
  $weeklyIsPositive = $weeklyGrowthPercentage >= 0;
  $weeklyGrowthLabel = ($weeklyIsPositive ? '+' : '').number_format($weeklyGrowthPercentage, 1).'%';
@endphp

<div class="row mb-6 g-6">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body row widget-separator g-0">
        <div class="col-sm-5 border-shift border-end pe-sm-6">
          <h3 class="text-primary d-flex align-items-center gap-2 mb-2">
            {{ number_format($averageRating, 1) }}
            <i class="icon-base ti tabler-star-filled icon-32px"></i>
          </h3>
          <p class="h6 mb-2">Total {{ $totalApproved }} opiniones publicas</p>
          <p class="pe-2 mb-2">Metrica sobre opiniones aprobadas y visibles.</p>
          <span class="badge bg-label-primary mb-4 mb-sm-0">+{{ $thisWeekReviews }} esta semana</span>
          <hr class="d-sm-none" />
        </div>

        <div class="col-sm-7 gap-2 text-nowrap d-flex flex-column justify-content-between ps-sm-6 pt-2 py-sm-2">
          @foreach([5,4,3,2,1] as $rating)
            @php
              $count = (int) ($ratingDistribution[$rating] ?? 0);
              $width = (int) round(($count / $totalForDistribution) * 100);
            @endphp
            <div class="d-flex align-items-center gap-2">
              <small>{{ $rating }} Star</small>
              <div class="progress w-100 bg-label-primary" style="height:8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $width }}%" aria-valuenow="{{ $width }}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small class="w-px-20 text-end">{{ $count }}</small>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body row">
        <div class="col-sm-5">
          <div class="mb-12">
            <h5 class="mb-2 text-nowrap">Reviews statistics</h5>
            <p class="mb-0">
              <span class="me-2">{{ $thisWeekReviews }} nuevas opiniones</span>
              <span class="badge bg-label-{{ $weeklyIsPositive ? 'success' : 'danger' }}">{{ $weeklyGrowthLabel }}</span>
            </p>
          </div>

          <div>
            <h6 class="mb-2 fw-normal"><span class="text-success me-1">{{ $positivePercentage }}%</span>opiniones positivas</h6>
            <small>Reporte semanal vs semana anterior</small>
          </div>
        </div>
        <div class="col-sm-7 d-flex justify-content-sm-end align-items-end">
          <div class="w-100">
            @foreach($statusMap as $status => $meta)
              @php
                $statusCount = (int) ($statusTotals[$status] ?? 0);
                $statusWidth = (int) round(($statusCount / $statusTotalForChart) * 100);
              @endphp
              <div class="d-flex align-items-center gap-2 mb-2">
                <small class="w-px-80 text-truncate">{{ $meta['label'] }}</small>
                <div class="progress w-100 bg-label-{{ $meta['class'] }}" style="height:8px;">
                  <div class="progress-bar bg-{{ $meta['class'] }}" role="progressbar" style="width: {{ $statusWidth }}%"></div>
                </div>
                <small class="w-px-24 text-end">{{ $statusCount }}</small>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body border-bottom">
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('mariachi.reviews.index', ['status' => 'all']) }}" class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Todas ({{ (int) $totalReviews }})</a>
      @foreach($statusMap as $status => $meta)
        <a href="{{ route('mariachi.reviews.index', ['status' => $status]) }}" class="btn btn-sm {{ $statusFilter === $status ? 'btn-'.$meta['class'] : 'btn-outline-'.$meta['class'] }}">{{ $meta['label'] }} ({{ (int) ($statusTotals[$status] ?? 0) }})</a>
      @endforeach
    </div>
  </div>

  <div class="card-body">
    @if($reviews->isEmpty())
      <p class="mb-0 text-muted">No hay opiniones para este filtro.</p>
    @else
      <div class="d-grid gap-4">
        @foreach($reviews as $review)
          @php
            $statusMeta = $statusMap[$review->moderation_status] ?? ['label' => $review->moderation_status, 'class' => 'secondary'];
            $verificationMeta = $verificationMap[$review->verification_status] ?? ['label' => $review->verification_status, 'class' => 'secondary'];
            $clientName = $review->clientUser?->display_name ?: 'Cliente';
            $listingTitle = $review->mariachiListing?->title ?: 'Anuncio sin titulo';
          @endphp

          <article class="border rounded-3 p-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
              <div>
                <h6 class="mb-1">{{ $clientName }}</h6>
                <p class="text-muted small mb-1">{{ $listingTitle }}</p>
                <div class="d-flex flex-wrap gap-1 mb-1">
                  <span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                  <span class="badge bg-label-{{ $verificationMeta['class'] }}">{{ $verificationMeta['label'] }}</span>
                  @if($review->is_spam)
                    <span class="badge bg-label-danger">Posible spam</span>
                  @endif
                  @if($review->has_offensive_language)
                    <span class="badge bg-label-warning">Lenguaje sensible</span>
                  @endif
                </div>
                <p class="mb-0 text-muted small">
                  {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} ·
                  {{ $review->event_type ?: 'Evento sin tipo' }} ·
                  {{ $review->event_date?->format('Y-m-d') ?: 'Sin fecha de evento' }}
                </p>
              </div>
              <p class="mb-0 small text-muted">Enviada: {{ $review->created_at->format('Y-m-d H:i') }}</p>
            </div>

            @if($review->title)
              <p class="fw-semibold mb-1">{{ $review->title }}</p>
            @endif
            <p class="mb-3">{{ $review->comment }}</p>

            @if($review->photos->isNotEmpty())
              <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($review->photos as $photo)
                  <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" rel="noopener noreferrer" class="d-inline-block border rounded overflow-hidden">
                    <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto adjunta" style="width:72px;height:72px;object-fit:cover;" />
                  </a>
                @endforeach
              </div>
            @endif

            @if($review->mariachi_reply)
              <div class="alert alert-secondary py-2 px-3 mb-3">
                <p class="mb-1 fw-semibold">Tu respuesta publica</p>
                <p class="mb-1">{{ $review->mariachi_reply }}</p>
                <p class="mb-0 small text-muted">
                  {{ $review->mariachi_replied_at?->format('Y-m-d H:i') ?: '' }}
                  @if(! $review->mariachi_reply_visible)
                    · Oculta por moderacion
                  @endif
                </p>
              </div>
            @endif

            @if($review->latest_report_reason)
              <p class="mb-2 small text-muted"><strong>Ultimo reporte:</strong> {{ $review->latest_report_reason }}</p>
            @endif

            <div class="row g-3">
              <div class="col-lg-8">
                <form action="{{ route('mariachi.reviews.reply', ['review' => $review->id]) }}" method="POST">
                  @csrf
                  <label class="form-label mb-1">Responder publicamente</label>
                  <textarea name="reply" rows="2" class="form-control" placeholder="Agradece al cliente o aclara el contexto." required>{{ old('reply') }}</textarea>
                  <button type="submit" class="btn btn-primary btn-sm mt-2">Guardar respuesta</button>
                </form>
              </div>
              <div class="col-lg-4">
                <form action="{{ route('mariachi.reviews.report', ['review' => $review->id]) }}" method="POST">
                  @csrf
                  <label class="form-label mb-1">Reportar resena</label>
                  <textarea name="reason" rows="2" class="form-control" placeholder="Motivo del reporte" required></textarea>
                  <button type="submit" class="btn btn-outline-danger btn-sm mt-2">Reportar</button>
                </form>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
