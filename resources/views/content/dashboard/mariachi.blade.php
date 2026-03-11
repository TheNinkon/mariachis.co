@extends('layouts/layoutMaster')

@section('title', 'Metricas Mariachi')

@section('content')
@php
  $statusMap = [
    'active' => ['label' => 'Activo', 'class' => 'success'],
    'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
    'awaiting_plan' => ['label' => 'Sin plan', 'class' => 'warning'],
    'paused' => ['label' => 'Pausado', 'class' => 'danger'],
  ];

  $listingsUsed = (int) ($listings->count() ?? 0);
  $usagePercent = $listingLimit > 0 ? min(100, (int) round(($listingsUsed / $listingLimit) * 100)) : 0;
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
          <h6 class="mb-1">Capacidad de anuncios</h6>
          <h3 class="mb-1">{{ $listingsUsed }} / {{ $listingLimit }}</h3>
          <p class="mb-2 text-muted">Activos: {{ (int) ($listingTotals['active'] ?? 0) }} · Borrador: {{ (int) ($listingTotals['draft'] ?? 0) }}</p>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar" role="progressbar" style="width: {{ $usagePercent }}%;"></div>
          </div>
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

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Rendimiento por anuncio</h5>
      <small class="text-muted">Valores reales por anuncio para priorizar mejoras.</small>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Anuncio</th>
            <th>Estado</th>
            <th>Completitud</th>
            <th>Vistas</th>
            <th>Favoritos</th>
            <th>Solicitudes</th>
            <th>Resenas aprobadas</th>
            <th>Rating</th>
            <th>Conversion</th>
            <th>Ultima actividad</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($listings as $listing)
            @php
              $statusMeta = $statusMap[$listing->status] ?? ['label' => $listing->status, 'class' => 'secondary'];
              $views = (int) ($listing->views_count ?? 0);
              $quotes = (int) ($listing->quotes_count ?? 0);
              $conversion = $views > 0 ? round(($quotes / $views) * 100, 1) : 0;
            @endphp
            <tr>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $listing->title }}</span>
                  <small class="text-muted">{{ $listing->city_name ?: 'Sin ciudad' }}</small>
                </div>
              </td>
              <td><span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span>{{ (int) $listing->listing_completion }}%</span>
                  <div class="progress" style="height: 6px; min-width: 86px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ (int) $listing->listing_completion }}%;"></div>
                  </div>
                </div>
              </td>
              <td>{{ number_format($views) }}</td>
              <td>{{ number_format((int) ($listing->favorites_count ?? 0)) }}</td>
              <td>
                {{ number_format($quotes) }}
                <small class="text-muted d-block">Abiertas: {{ number_format((int) ($listing->open_quotes_count ?? 0)) }}</small>
              </td>
              <td>{{ number_format((int) ($listing->approved_reviews_count ?? 0)) }}</td>
              <td>{{ number_format((float) ($listing->approved_rating_avg ?? 0), 2) }}</td>
              <td>{{ number_format($conversion, 1) }}%</td>
              <td>{{ optional($listing->last_quote_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>
                <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-5">Aun no tienes anuncios para medir.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
