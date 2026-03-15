@extends('layouts/layoutMaster')

@section('title', 'Metricas Mariachi')

@section('content')
@php
  $statusMap = [
    'active' => ['label' => 'Activo', 'class' => 'success'],
    'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
    'awaiting_plan' => ['label' => 'Sin plan', 'class' => 'warning'],
    'awaiting_payment' => ['label' => 'Esperando pago', 'class' => 'warning'],
    'paused' => ['label' => 'Pausado', 'class' => 'danger'],
  ];

  $usagePercent = $openDraftLimit > 0 ? min(100, (int) round(($openDraftsCount / $openDraftLimit) * 100)) : 0;
@endphp

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Metricas de anuncios</h5>
        <p class="mb-0 text-muted">Datos reales tomados de tus anuncios, conversaciones, favoritos y reseñas.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-primary" href="{{ route('mariachi.listings.index') }}">Gestionar anuncios</a>
      <a class="btn btn-outline-primary" href="{{ route('mariachi.quotes.index') }}">Abrir solicitudes</a>
      <a class="btn btn-outline-primary" href="{{ route('mariachi.reviews.index') }}">Ver opiniones</a>
    </div>
  </div>
</div>

  @if(! $profile)
  <div class="card">
    <div class="card-body">
      <p class="mb-0 text-muted">Aun no tienes perfil de mariachi. Completa tu perfil para ver metricas.</p>
    </div>
  </div>
  @else
  @if($planIssues !== [])
    <div class="alert alert-warning mb-6">
      <strong>Tu plan actual requiere ajuste.</strong>
      <ul class="mb-0 mt-2">
        @foreach($planIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4 mb-6">
    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Borradores abiertos</h6>
          <h3 class="mb-1">{{ $openDraftLimit === 0 ? $openDraftsCount : $openDraftsCount.' / '.$openDraftLimit }}</h3>
          <p class="mb-2 text-muted">Activos: {{ (int) ($listingTotals['active'] ?? 0) }} · En revisión: {{ number_format((int) $listings->where('review_status', \App\Models\MariachiListing::REVIEW_PENDING)->count()) }}</p>
          @if($openDraftLimit > 0)
            <div class="progress" style="height: 8px;">
              <div class="progress-bar" role="progressbar" style="width: {{ $usagePercent }}%;"></div>
            </div>
          @else
            <small class="text-muted">Sin tope para borradores en tu paquete actual.</small>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Vistas reales</h6>
          <h3 class="mb-1">{{ number_format($viewsTotal) }}</h3>
          <p class="mb-0 text-muted">Ultimos 30 dias: {{ number_format($views30d) }}</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Favoritos reales</h6>
          <h3 class="mb-1">{{ number_format($favoritesTotal) }}</h3>
          <p class="mb-0 text-muted">Ultimos 30 dias: {{ number_format($favorites30d) }}</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Solicitudes reales</h6>
          <h3 class="mb-1">{{ number_format($quotesTotal) }}</h3>
          <p class="mb-0 text-muted">Abiertas: {{ number_format($openQuotesTotal) }} · Ultimos 30 dias: {{ number_format($quotes30d) }}</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Tasa de respuesta</h6>
          <h3 class="mb-1">{{ $responseRate }}%</h3>
          <p class="mb-0 text-muted">Respondidas: {{ $repliedConversations }} / {{ $totalConversations }} · Pendientes primera respuesta: {{ $pendingFirstReply }}</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Reseñas aprobadas</h6>
          <h3 class="mb-1">{{ number_format($approvedReviewsTotal) }}</h3>
          <p class="mb-0 text-muted">Calificacion promedio: {{ number_format($approvedRatingAvg, 2) }} / 5</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Anuncios nuevos (30d)</h6>
          <h3 class="mb-1">{{ number_format($newListings30d) }}</h3>
          <p class="mb-0 text-muted">Periodo de analisis: ultimos 30 dias.</p>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Estado del perfil</h6>
          <h3 class="mb-1 text-capitalize">{{ $profile->verification_status ?: 'unverified' }}</h3>
          <p class="mb-0 text-muted">
            Plan: {{ $planSummary['name'] ?? strtoupper($profile->subscription_plan_code ?: 'basic') }}
            @if(! empty($planSummary['badge_text']))
              · {{ $planSummary['badge_text'] }}
            @endif
          </p>
        </div>
      </div>
    </div>
  </div>

@endif
@endsection
