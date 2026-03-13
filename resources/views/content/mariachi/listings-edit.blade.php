@extends('layouts/layoutMaster')

@section('title', 'Editar anuncio')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('page-style')
  <style>
    .listing-zone-shell,
    .listing-media-shell {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
    }

    .listing-zone-panel,
    .listing-media-panel {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
    }

    .listing-zone-panel--localities {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      max-height: 25.5rem;
      overflow-y: auto;
      padding: 0.9rem !important;
      scrollbar-gutter: stable;
    }

    .listing-zone-panel__header {
      position: sticky;
      top: 0;
      z-index: 2;
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      padding-bottom: 0.6rem;
      background: linear-gradient(180deg, #fff 0%, #fff 82%, rgba(255, 255, 255, 0.88) 100%);
      border-bottom: 1px solid rgba(75, 70, 92, 0.08);
    }

    .listing-zone-panel__search {
      position: sticky;
      top: 0;
      z-index: 3;
      background: #fff;
    }

    .listing-zone-list,
    .listing-video-list {
      display: grid;
      gap: 0.75rem;
    }

    .listing-zone-list {
      min-height: 18rem;
      max-height: 26rem;
      overflow: auto;
      padding-right: 0.25rem;
    }

    .listing-zone-list--compact {
      min-height: 0;
      max-height: none;
      overflow: visible;
      gap: 0.55rem;
      padding-right: 0;
    }

    .listing-zone-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.6rem;
      padding: 0.7rem 0.8rem;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 0.75rem;
      background: #fff;
    }

    .listing-zone-item__meta {
      min-width: 0;
      display: grid;
      gap: 0.125rem;
    }

    .listing-zone-item__name {
      font-weight: 600;
      font-size: 0.875rem;
      line-height: 1.2;
      color: #444050;
    }

    .listing-zone-item__city {
      font-size: 0.75rem;
      line-height: 1.2;
      color: #8a8d93;
    }

    .listing-zone-list--compact .listing-zone-item .btn {
      padding: 0.3rem 0.55rem;
      line-height: 1.1;
    }

    .listing-zone-item--selected {
      border-color: rgba(0, 86, 59, 0.2);
      background: rgba(0, 86, 59, 0.04);
    }

    .listing-zone-item--primary {
      border-color: rgba(0, 86, 59, 0.3);
      background: rgba(0, 86, 59, 0.08);
    }

    .listing-zone-empty,
    .listing-upgrade-tile,
    .listing-photo-add-tile,
    .listing-video-upgrade {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      min-height: 11rem;
      padding: 1.25rem;
      border-radius: 1rem;
      border: 1px dashed rgba(75, 70, 92, 0.18);
      background: rgba(115, 103, 240, 0.03);
    }

    .listing-upgrade-tile {
      border-style: solid;
      background: linear-gradient(180deg, rgba(255, 193, 7, 0.12), rgba(255, 255, 255, 0.92));
    }

    .listing-zone-list--compact .listing-zone-empty {
      min-height: 7rem;
      padding: 1rem;
      border-radius: 0.85rem;
    }

    .listing-upgrade-tile--compact {
      min-height: auto;
      padding: 0.9rem 1rem;
      align-items: flex-start;
      text-align: left;
      gap: 0.35rem;
      border-radius: 0.85rem;
    }

    .listing-upgrade-tile--compact .avatar {
      width: 2.4rem;
      height: 2.4rem;
      margin-bottom: 0.15rem !important;
    }

    .listing-upgrade-tile--compact strong {
      font-size: 0.92rem;
      line-height: 1.2;
    }

    .listing-upgrade-tile--compact .small {
      line-height: 1.3;
      margin-top: 0 !important;
    }

    .listing-photo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
    }

    .listing-photo-add-tile {
      cursor: pointer;
      min-height: 14rem;
      background: rgba(0, 86, 59, 0.03);
      border-style: dashed;
    }

    .listing-photo-add-tile[disabled] {
      cursor: not-allowed;
      opacity: 0.65;
    }

    .listing-photo-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      overflow: hidden;
      background: #fff;
      display: flex;
      flex-direction: column;
      min-height: 14rem;
    }

    .listing-photo-card__media {
      position: relative;
      aspect-ratio: 1 / 1;
      overflow: hidden;
      background: #f8f7fa;
    }

    .listing-photo-card__media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .listing-photo-card__badges {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      right: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .listing-photo-card__actions,
    .listing-video-card__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .listing-photo-card__body,
    .listing-video-card {
      padding: 1rem;
    }

    .listing-video-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      align-items: start;
    }

    .listing-video-card__meta {
      min-width: 0;
    }

    .listing-video-card__url {
      font-weight: 600;
      color: #444050;
      word-break: break-word;
    }

    .listing-step-note {
      color: #8a8d93;
      font-size: 0.9375rem;
    }

    .listing-step-actions {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }

    .listing-location-trigger {
      min-width: 7rem;
    }

    .listing-location-status[hidden] {
      display: none !important;
    }

    .listing-map-canvas {
      min-height: 65vh;
      border-radius: 1rem;
      border: 1px solid rgba(75, 70, 92, 0.12);
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.06), rgba(255, 255, 255, 0.96));
      overflow: hidden;
    }

    .listing-map-sidebar {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      padding: 1.25rem;
      min-height: 100%;
    }

    .listing-map-sidebar dd {
      word-break: break-word;
    }

    @media (max-width: 991.98px) {
      .listing-zone-panel--localities {
        max-height: none;
        overflow: visible;
      }

      .listing-zone-panel__header,
      .listing-zone-panel__search {
        position: static;
      }

      .listing-zone-list {
        min-height: 13rem;
        max-height: none;
      }

      .listing-zone-list--compact {
        min-height: 0;
      }

      .listing-step-actions {
        flex-direction: column-reverse;
      }

      .listing-step-actions .btn {
        width: 100%;
      }
    }
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/mariachi-listing-wizard.js'])
@endsection

@section('content')
  @php
    $selectedEventTypeIds = $listing->eventTypes->pluck('id')->all();
    $selectedServiceTypeIds = $listing->serviceTypes->pluck('id')->all();
    $selectedGroupSizeIds = $listing->groupSizeOptions->pluck('id')->all();
    $selectedBudgetIds = $listing->budgetRanges->pluck('id')->all();
    $selectedZoneIds = $listing->serviceAreas->pluck('marketplace_zone_id')->filter()->map(fn ($id) => (int) $id)->all();
    $selectedCityId = (int) old('marketplace_city_id', $listing->marketplace_city_id);
    $primaryZoneId = (int) old('primary_marketplace_zone_id', $selectedZoneIds[0] ?? 0);
    $formSelectedZoneIds = collect(old('zone_ids', $selectedZoneIds))
      ->map(fn ($id) => (int) $id)
      ->filter(fn ($id) => $id > 0 && $id !== $primaryZoneId)
      ->values()
      ->all();
    $displayCityName = old('city_name', $listing->city_name);
    $displayZoneName = old('zone_name', $listing->zone_name ?: ($listing->serviceAreas->first()?->city_name ?? ''));
    $googlePayload = old('google_location_payload', $listing->google_location_payload ? json_encode($listing->google_location_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');
    $decodedGooglePayload = is_string($googlePayload) && $googlePayload !== '' ? json_decode($googlePayload, true) : [];
    $decodedGooglePayload = is_array($decodedGooglePayload) ? $decodedGooglePayload : [];
    $extractPayloadComponent = function (array $payload, array $candidateTypes): string {
        $components = $payload['address_components'] ?? null;

        if (! is_array($components)) {
            return '';
        }

        foreach ($candidateTypes as $candidateType) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }

                $types = $component['types'] ?? [];
                if (is_array($types) && in_array($candidateType, $types, true)) {
                    return trim((string) ($component['long_name'] ?? ''));
                }
            }
        }

        return '';
    };
    $displayNeighborhood = $extractPayloadComponent($decodedGooglePayload, ['neighborhood'])
      ?: $extractPayloadComponent($decodedGooglePayload, ['sublocality_level_2'])
      ?: $extractPayloadComponent($decodedGooglePayload, ['administrative_area_level_5']);
    $pendingLocalityName = old('suggest_zone', ($selectedCityId > 0 && $displayZoneName !== '' && ! $primaryZoneId ? $displayZoneName : ''));

    $faqRows = old('faq_question')
      ? collect(old('faq_question'))->map(function ($question, $index) {
          return ['question' => $question, 'answer' => old('faq_answer')[$index] ?? ''];
        })
      : $listing->faqs->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer]);

    if ($faqRows->isEmpty()) {
      $faqRows = collect([['question' => '', 'answer' => '']]);
    }

    $status = old('status', $listing->status);
    $hasPlan = $listing->hasEffectivePlan();
    $reviewMap = [
      \App\Models\MariachiListing::REVIEW_DRAFT => ['label' => 'Borrador de revision', 'class' => 'secondary'],
      \App\Models\MariachiListing::REVIEW_PENDING => ['label' => 'En revision', 'class' => 'warning'],
      \App\Models\MariachiListing::REVIEW_APPROVED => ['label' => 'Aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::REVIEW_REJECTED => ['label' => 'Rechazado', 'class' => 'danger'],
    ];
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago en revision', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
    $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
    $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
    $canSubmitForReview = $listing->canBeSubmittedForReview();
    $submitForReviewLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar a revisión' : 'Enviar a revisión';
    $maxPhotos = (int) ($capabilities['max_photos_per_listing'] ?? 0);
    $maxVideos = (int) ($capabilities['max_videos_per_listing'] ?? 0);
    $maxZones = (int) ($capabilities['max_zones_covered'] ?? 0);
    $photoCount = $listing->photos->count();
    $videoCount = $listing->videos->count();
    $canAddMorePhotos = $photoCount < $maxPhotos;
    $canAddMoreVideos = $videoCount < $maxVideos;
  @endphp

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validación.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if($listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED && $listing->rejection_reason)
    <div class="alert alert-danger">
      <strong>El anuncio fue rechazado.</strong> Corrige lo siguiente y luego vuelve a enviarlo a revisión.
      <div class="mt-2">{{ $listing->rejection_reason }}</div>
    </div>
  @elseif($listing->isPaymentPending())
    <div class="alert alert-warning">
      <strong>Pago enviado.</strong> El anuncio está bloqueado mientras el equipo valida tu comprobante.
    </div>
  @elseif($listing->isPaymentRejected())
    <div class="alert alert-danger">
      <strong>Pago rechazado.</strong>
      {{ $listing->latestPayment?->rejection_reason ?: 'Revisa el comprobante y vuelve a intentar.' }}
    </div>
  @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_APPROVED)
    <div class="alert alert-info">
      <strong>Este anuncio ya fue aprobado.</strong> Si cambias contenido, fotos, videos o filtros, saldrá de publicación y volverá a borrador de revisión.
    </div>
  @endif

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

  @if($listingIssues !== [])
    <div class="alert alert-warning">
      <strong>Este anuncio requiere ajuste para volver a publicarse.</strong>
      <ul class="mb-0 mt-2">
        @foreach($listingIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">{{ $listing->title }}</h5>
        <p class="mb-1">
          Estado:
          <span class="badge bg-label-{{ $listing->is_active ? 'success' : 'warning' }}">{{ $listing->status }}</span>
          · Revisión:
          <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
          · Pago:
          <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
          · Plan activo: <strong>{{ $planSummary['name'] ?? ($listing->effectivePlanCode() ?: 'sin plan') }}</strong>
          @if(! empty($planSummary['badge_text']))
            <span class="badge bg-label-primary ms-1">{{ $planSummary['badge_text'] }}</span>
          @endif
        </p>
        <small class="text-muted">Completitud: <strong data-completion-text>{{ $listing->listing_completion }}%</strong> · Fotos {{ $capabilities['max_photos_per_listing'] }} · Videos {{ $capabilities['max_videos_per_listing'] }} · Localidades {{ $capabilities['max_zones_covered'] ?? 0 }}.</small>
        @if($listing->submitted_for_review_at)
          <div class="small text-muted mt-1">Último envío a revisión: {{ $listing->submitted_for_review_at->format('Y-m-d H:i') }}</div>
        @endif
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-label-secondary" data-autosave-status>Autoguardado listo</span>
        <small class="text-muted" data-autosave-time></small>
        <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-secondary">Volver</a>
      </div>
    </div>
  </div>

  <div id="listing-wizard" class="bs-stepper vertical mb-6" data-listing-wizard data-listing-id="{{ $listing->id }}">
    <div class="bs-stepper-header border-end">
      <div class="step" data-target="#step-basic" data-step-key="basic">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-edit-circle icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Básicos</span>
            <span class="bs-stepper-subtitle">Título, resumen, precio</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-location" data-step-key="location">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-map-pin icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Ubicación</span>
            <span class="bs-stepper-subtitle">Ciudad y cobertura</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-filters" data-step-key="filters">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-adjustments-horizontal icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Filtros</span>
            <span class="bs-stepper-subtitle">Servicios, FAQ y estado</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-photos" data-step-key="photos">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-photo icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Fotos</span>
            <span class="bs-stepper-subtitle">Portada y galeria</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-videos" data-step-key="videos">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-video icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Videos</span>
            <span class="bs-stepper-subtitle">URLs y soporte visual</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-review" data-step-key="final">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-credit-card icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Final</span>
            <span class="bs-stepper-subtitle">Revisar, plan y pago</span>
          </span>
        </button>
      </div>
    </div>

    <div class="bs-stepper-content">
      <form
        id="listing-main-form"
        method="POST"
        action="{{ route('mariachi.listings.update', ['listing' => $listing->id]) }}"
        data-autosave="true"
        data-autosave-url="{{ route('mariachi.listings.autosave', ['listing' => $listing->id]) }}"
        data-autosave-sync="true"
        data-google-maps-enabled="{{ $googleMaps['enabled'] ? 'true' : 'false' }}"
        data-google-maps-key="{{ $googleMaps['browser_api_key'] }}"
        data-google-country="{{ $googleMaps['places_country_restriction'] }}"
        data-default-country="{{ $googleMaps['default_country_name'] }}"
        data-location-cities='@json($cities->map(fn ($city) => ['id' => (int) $city->id, 'name' => $city->name])->values())'
        data-location-zones='@json($zones->map(fn ($zone) => ['id' => (int) $zone->id, 'city_id' => (int) $zone->marketplace_city_id, 'name' => $zone->name])->values())'
      >
        @csrf
        @method('PATCH')
        <input type="hidden" name="country" id="listing-country-input" value="{{ $googleMaps['default_country_name'] }}" />
        <input type="hidden" name="marketplace_city_id" id="listing-city-id" value="{{ $selectedCityId ?: '' }}" />
        <input type="hidden" name="primary_marketplace_zone_id" id="listing-primary-zone-id" value="{{ $primaryZoneId ?: '' }}" />
        <input type="hidden" name="suggest_zone" id="listing-suggest-zone" value="{{ old('suggest_zone', '') }}" />
        <input type="hidden" name="latitude" id="listing-latitude-input" value="{{ old('latitude', $listing->latitude) }}" />
        <input type="hidden" name="longitude" id="listing-longitude-input" value="{{ old('longitude', $listing->longitude) }}" />
        <input type="hidden" name="postal_code" id="listing-postal-code-input" value="{{ old('postal_code', $listing->postal_code) }}" />
        <input type="hidden" name="google_place_id" id="listing-place-id-input" value="{{ old('google_place_id', $listing->google_place_id) }}" />
        <input type="hidden" name="google_location_payload" id="listing-google-payload-input" value="{{ $googlePayload }}" />

        <div id="step-basic" class="content" data-step-key="basic">
          <div class="row g-4">
            <div class="col-md-8">
              <label class="form-label">Título del anuncio</label>
              <input class="form-control" name="title" value="{{ old('title', $listing->title) }}" required maxlength="180" placeholder="Ej: Mariachi para bodas y serenatas" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Precio base</label>
              <input type="number" step="0.01" min="0" class="form-control" name="base_price" value="{{ old('base_price', $listing->base_price) }}" placeholder="Ej: 450000" />
            </div>
            <div class="col-12">
              <label class="form-label">Descripción corta</label>
              <textarea class="form-control" name="short_description" rows="2" maxlength="280" required>{{ old('short_description', $listing->short_description) }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción completa</label>
              <textarea class="form-control" name="description" rows="5" maxlength="5000">{{ old('description', $listing->description) }}</textarea>
            </div>

            <div class="col-12 d-flex justify-content-between">
              <button type="button" class="btn btn-label-secondary" disabled>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Siguiente <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div id="step-location" class="content" data-step-key="location">
          <div class="row g-4">
            <div class="col-12">
              <div class="alert alert-info mb-0">
                Escribe la dirección real del anuncio o usa el pin del mapa. El sistema detectará ciudad, localidad, barrio informativo, coordenadas y dejará el país fijo en <strong>{{ $googleMaps['default_country_name'] }}</strong>.
              </div>
            </div>

            @if(! $googleMaps['enabled'])
              <div class="col-12">
                <div class="alert alert-warning mb-0">
                  Google Maps no está configurado. Desde admin debes registrar la API key para activar el autocompletado.
                </div>
              </div>
            @endif

            <div class="col-12">
              <label class="form-label">Dirección</label>
              <div class="input-group">
                <input
                  class="form-control"
                  id="listing-address-input"
                  name="address"
                  value="{{ old('address', $listing->address) }}"
                  maxlength="255"
                  autocomplete="off"
                  placeholder="Escribe la calle o usa el mapa para fijar el pin"
                />
                <button
                  type="button"
                  class="btn btn-outline-primary listing-location-trigger"
                  id="listing-map-picker-open"
                  @disabled(! $googleMaps['enabled'])
                >
                  <i class="icon-base ti tabler-map-pin icon-sm"></i>
                  <span class="ms-1">Mapa</span>
                </button>
              </div>
              <small class="text-muted d-block mt-2">Busca la dirección o usa el mapa. Aquí dejamos calle y número para una lectura más limpia; el detalle completo queda en Google Maps.</small>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ciudad detectada</label>
              <input class="form-control" id="listing-city-name-input" name="city_name" value="{{ $displayCityName }}" maxlength="120" placeholder="Se completa con Google Maps" readonly />
            </div>
            <div class="col-md-4">
              <label class="form-label">Localidad detectada</label>
              <input class="form-control" id="listing-zone-name-input" name="zone_name" value="{{ $displayZoneName }}" maxlength="120" placeholder="Se completa con Google Maps" readonly />
            </div>
            <div class="col-md-4">
              <label class="form-label">Barrio detectado (informativo)</label>
              <input class="form-control" id="listing-neighborhood-input" value="{{ $displayNeighborhood }}" maxlength="120" placeholder="Se mostrará si Google lo devuelve" readonly />
            </div>

            <div class="col-12">
              <div
                class="small mt-1 listing-location-status {{ $pendingLocalityName ? 'text-warning' : 'text-muted' }}"
                data-locality-status
                @if(! $pendingLocalityName) hidden @endif
              >
                @if($pendingLocalityName)
                  Localidad detectada pendiente de catálogo: {{ $pendingLocalityName }}. La enviaremos como sugerencia para aprobación admin.
                @endif
              </div>
              <small class="text-muted d-block mt-2">Ciudad, localidad y departamento se detectan desde Google Maps y no se editan manualmente. Las coordenadas siguen siendo internas y el país permanece fijo en {{ $googleMaps['default_country_name'] }}.</small>
            </div>

            <div class="col-12">
              <label class="form-label">Departamento / región</label>
              <input class="form-control" id="listing-state-input" name="state" value="{{ old('state', $listing->state) }}" maxlength="120" readonly />
            </div>

            <div class="col-12 pt-2">
              <h6 class="mb-2">Cobertura adicional</h6>
              <small class="text-muted d-block mb-2">La localidad principal se detecta automáticamente. Usa esta parte solo si además cubres otras localidades del mismo catálogo.</small>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" value="1" id="travels" name="travels_to_other_cities" {{ old('travels_to_other_cities', $listing->travels_to_other_cities) ? 'checked' : '' }}>
                <label class="form-check-label" for="travels">Me desplazo a otras ciudades</label>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Localidades adicionales de cobertura</label>
              <small class="d-block text-muted mb-3">Usa esta interfaz para ampliar cobertura. Tu plan permite hasta {{ $maxCitiesAllowed }} ciudad(es) y {{ $maxZones }} localidad(es) por anuncio, contando la localidad principal detectada.</small>
              <div
                class="listing-zone-shell p-3"
                data-zone-picker
                data-max-zones="{{ $maxZones }}"
                data-plan-url="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}"
              >
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="listing-zone-panel listing-zone-panel--localities h-100">
                      <div class="listing-zone-panel__header">
                        <div>
                          <h6 class="mb-1">Localidades disponibles</h6>
                          <small class="text-muted">Solo del catálogo oficial de la ciudad principal.</small>
                        </div>
                        <div class="listing-zone-panel__search">
                          <input type="search" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Buscar localidad" data-zone-search>
                        </div>
                      </div>
                      <div class="listing-zone-list listing-zone-list--compact" data-zone-available></div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="listing-zone-panel listing-zone-panel--localities h-100">
                      <div class="listing-zone-panel__header">
                        <div>
                          <h6 class="mb-1">Localidades seleccionadas (<span data-zone-count>0</span> / {{ $maxZones }})</h6>
                          <small class="text-muted">La localidad principal se detecta automáticamente y cuenta dentro del límite.</small>
                        </div>
                        <span class="badge bg-label-primary">Máx {{ $maxZones }}</span>
                      </div>
                      <div class="listing-zone-list listing-zone-list--compact" data-zone-selected></div>
                      <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="listing-upgrade-tile listing-upgrade-tile--compact text-decoration-none" data-zone-upgrade data-step-link="location" hidden>
                        <span class="avatar avatar-sm bg-label-warning">
                          <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-md"></i></span>
                        </span>
                        <strong class="text-heading">Agrega más localidades con Plan Pro</strong>
                        <span class="text-muted small">Mejora tu plan para ampliar cobertura sin salir del wizard.</span>
                      </a>
                      <div data-zone-hidden-inputs>
                        @foreach($formSelectedZoneIds as $zoneId)
                          <input type="hidden" name="zone_ids[]" value="{{ $zoneId }}">
                        @endforeach
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              @error('zone_ids')
                <div class="text-danger small mt-2">{{ $message }}</div>
              @enderror
              <div class="text-danger small mt-2" data-zone-feedback></div>
            </div>

            <div class="col-12 d-flex justify-content-between">
              <button type="button" class="btn btn-label-secondary" data-step-prev>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Siguiente <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>

        <div id="step-filters" class="content" data-step-key="filters">
          <div class="row g-4">
            <div class="col-md-6">
              <h6 class="mb-2">Tipos de evento</h6>
              @foreach($eventTypes as $eventType)
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="event_type_ids[]" value="{{ $eventType->id }}" id="event-{{ $eventType->id }}" {{ in_array($eventType->id, old('event_type_ids', $selectedEventTypeIds)) ? 'checked' : '' }}>
                  <label class="form-check-label d-inline-flex align-items-center gap-1" for="event-{{ $eventType->id }}"><x-catalog-icon :name="$eventType->icon" class="h-4 w-4" />{{ $eventType->name }}</label>
                </div>
              @endforeach
              <label class="form-label mt-3">Sugerir tipo de evento (opcional)</label>
              <input class="form-control" name="suggest_event_type" value="{{ old('suggest_event_type') }}" placeholder="Ej: Pedida de mano" maxlength="120">
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Tipos de servicio</h6>
              @foreach($serviceTypes as $serviceType)
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="service_type_ids[]" value="{{ $serviceType->id }}" id="service-{{ $serviceType->id }}" {{ in_array($serviceType->id, old('service_type_ids', $selectedServiceTypeIds)) ? 'checked' : '' }}>
                  <label class="form-check-label d-inline-flex align-items-center gap-1" for="service-{{ $serviceType->id }}"><x-catalog-icon :name="$serviceType->icon" class="h-4 w-4" />{{ $serviceType->name }}</label>
                </div>
              @endforeach
              <label class="form-label mt-3">Sugerir tipo de servicio (opcional)</label>
              <input class="form-control" name="suggest_service_type" value="{{ old('suggest_service_type') }}" placeholder="Ej: Show con trompeta solista" maxlength="120">
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Tamaño del grupo</h6>
              @foreach($groupSizeOptions as $option)
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="group_size_option_ids[]" value="{{ $option->id }}" id="group-{{ $option->id }}" {{ in_array($option->id, old('group_size_option_ids', $selectedGroupSizeIds)) ? 'checked' : '' }}>
                  <label class="form-check-label d-inline-flex align-items-center gap-1" for="group-{{ $option->id }}"><x-catalog-icon :name="$option->icon" class="h-4 w-4" />{{ $option->name }}</label>
                </div>
              @endforeach
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Presupuesto</h6>
              @foreach($budgetRanges as $range)
                <div class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="budget_range_ids[]" value="{{ $range->id }}" id="budget-{{ $range->id }}" {{ in_array($range->id, old('budget_range_ids', $selectedBudgetIds)) ? 'checked' : '' }}>
                  <label class="form-check-label d-inline-flex align-items-center gap-1" for="budget-{{ $range->id }}"><x-catalog-icon :name="$range->icon" class="h-4 w-4" />{{ $range->name }}</label>
                </div>
              @endforeach
            </div>

            <div class="col-md-6">
              <label class="form-label">Estado del anuncio</label>
              @if($hasPlan)
                <select class="form-select" name="status">
                  @foreach([\App\Models\MariachiListing::STATUS_DRAFT, \App\Models\MariachiListing::STATUS_ACTIVE, \App\Models\MariachiListing::STATUS_PAUSED] as $statusOption)
                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ $statusOption }}</option>
                  @endforeach
                </select>
              @else
                <input type="hidden" name="status" value="{{ \App\Models\MariachiListing::STATUS_DRAFT }}" />
                <div class="alert alert-warning mb-0">
                  El anuncio permanecerá en <strong>borrador</strong> hasta que elijas plan en el paso final.
                </div>
              @endif
            </div>

            <div class="col-12">
              <h6 class="mb-3">Preguntas frecuentes</h6>
              @foreach($faqRows->take(5) as $index => $faq)
                <div class="row g-2 mb-3">
                  <div class="col-md-5">
                    <input class="form-control" name="faq_question[]" value="{{ $faq['question'] }}" placeholder="Pregunta frecuente">
                  </div>
                  <div class="col-md-7">
                    <input class="form-control" name="faq_answer[]" value="{{ $faq['answer'] }}" placeholder="Respuesta">
                  </div>
                </div>
              @endforeach
            </div>

            <div class="col-12 d-flex justify-content-between">
              <button type="button" class="btn btn-label-secondary" data-step-prev>
                <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
              </button>
              <button type="button" class="btn btn-primary" data-step-next>
                Ir a fotos <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>
      </form>

      <div id="step-photos" class="content" data-step-key="photos">
        <div class="row g-6">
          <div class="col-12">
            <div class="listing-media-shell p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                  <h5 class="mb-1">Fotos del anuncio</h5>
                  <p class="listing-step-note mb-0">Tu plan permite hasta {{ $maxPhotos }} fotos. La primera destacada se usa como portada principal del anuncio.</p>
                </div>
                <span class="badge bg-label-primary">{{ $photoCount }} / {{ $maxPhotos }}</span>
              </div>

              @error('photo')
                <div class="alert alert-danger">{{ $message }}</div>
              @enderror

              <form method="POST" action="{{ route('mariachi.listings.photos.store', ['listing' => $listing->id]) }}" enctype="multipart/form-data" data-preserve-step="photos" class="d-none">
                @csrf
                <input type="hidden" name="return_step" value="photos">
                <input type="file" name="photo" accept="image/*" data-photo-input>
              </form>

              <div class="listing-photo-grid">
                @if($maxPhotos > 0 && $canAddMorePhotos)
                  <button type="button" class="listing-photo-add-tile" data-photo-trigger>
                    <span class="avatar avatar-xl bg-label-primary mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-plus icon-lg"></i></span>
                    </span>
                    <strong class="text-heading">Agregar foto</strong>
                    <span class="text-muted small mt-1">Sube JPG, PNG o WebP de hasta 5 MB.</span>
                  </button>
                @endif

                @foreach($listing->photos as $photo)
                  <div class="listing-photo-card">
                    <div class="listing-photo-card__media">
                      <img src="{{ asset('storage/'.$photo->path) }}" alt="foto del anuncio">
                      <div class="listing-photo-card__badges">
                        @if($photo->is_featured)
                          <span class="badge bg-success">Destacada</span>
                        @else
                          <span class="badge bg-label-secondary">Foto {{ $loop->iteration }}</span>
                        @endif
                        <span class="badge bg-dark">{{ $loop->iteration }}</span>
                      </div>
                    </div>
                    <div class="listing-photo-card__body">
                      <div class="listing-photo-card__actions">
                        @unless($photo->is_featured)
                          <form method="POST" action="{{ route('mariachi.listings.photos.featured', ['listing' => $listing->id, 'photo' => $photo->id]) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-primary" type="submit">Destacar</button>
                          </form>
                        @endunless
                        @if($listing->photos->count() > 1)
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'up']) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Mover arriba">↑</button>
                          </form>
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'down']) }}" data-preserve-step="photos">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="return_step" value="photos">
                            <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Mover abajo">↓</button>
                          </form>
                        @endif
                        <form method="POST" action="{{ route('mariachi.listings.photos.delete', ['listing' => $listing->id, 'photo' => $photo->id]) }}" data-preserve-step="photos">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="return_step" value="photos">
                          <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach

                @if($maxPhotos <= 0 || ! $canAddMorePhotos)
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="listing-upgrade-tile text-decoration-none text-body" data-step-link="photos">
                    <span class="avatar avatar-xl bg-label-warning mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                    </span>
                    <strong>Agrega más fotos con Plan Pro</strong>
                    <span class="text-muted small mt-1">Tu plan actual permite hasta {{ $maxPhotos }} foto(s) por anuncio.</span>
                  </a>
                @endif
              </div>
            </div>
          </div>

          <div class="col-12 listing-step-actions">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-step-next>
              Ir a videos <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="step-videos" class="content" data-step-key="videos">
        <div class="row g-6">
          <div class="col-12">
            <div class="listing-media-shell p-4">
              <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                  <h5 class="mb-1">Videos del anuncio</h5>
                  <p class="listing-step-note mb-0">Agrega enlaces de YouTube o videos externos para reforzar confianza y mostrar el repertorio.</p>
                </div>
                <span class="badge bg-label-primary">{{ $videoCount }} / {{ $maxVideos }}</span>
              </div>

              @error('url')
                <div class="alert alert-danger">{{ $message }}</div>
              @enderror

              @if($maxVideos <= 0)
                <div class="listing-video-upgrade mb-4">
                  <span class="avatar avatar-xl bg-label-warning mb-3">
                    <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                  </span>
                  <h6 class="mb-2">Tu plan actual no incluye videos</h6>
                  <p class="text-muted mb-3">Mejora tu plan para añadir videos al anuncio y aumentar la conversión.</p>
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-warning" data-step-link="videos">Mejorar plan</a>
                </div>
              @else
                <form method="POST" action="{{ route('mariachi.listings.videos.store', ['listing' => $listing->id]) }}" class="mb-4" data-preserve-step="videos">
                  @csrf
                  <input type="hidden" name="return_step" value="videos">
                  <div class="row g-2">
                    <div class="col-lg-9">
                      <input type="url" class="form-control" name="url" placeholder="https://youtube.com/watch?v=..." required @disabled(! $canAddMoreVideos)>
                    </div>
                    <div class="col-lg-3">
                      <button class="btn btn-primary w-100" type="submit" @disabled(! $canAddMoreVideos)>Agregar video</button>
                    </div>
                  </div>
                </form>
              @endif

              <div class="listing-video-list">
                @forelse($listing->videos as $video)
                  <div class="listing-video-card">
                    <div class="listing-video-card__meta">
                      <div class="listing-video-card__url">{{ $video->platform === 'youtube' ? 'Video de YouTube' : 'Video externo' }}</div>
                      <a href="{{ $video->url }}" target="_blank" rel="noopener" class="small text-muted">{{ $video->url }}</a>
                    </div>
                    <div class="listing-video-card__actions">
                      <form method="POST" action="{{ route('mariachi.listings.videos.delete', ['listing' => $listing->id, 'video' => $video->id]) }}" data-preserve-step="videos">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="return_step" value="videos">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                      </form>
                    </div>
                  </div>
                @empty
                  <div class="listing-zone-empty">
                    <span class="avatar avatar-xl bg-label-secondary mb-3">
                      <span class="avatar-initial rounded"><i class="icon-base ti tabler-video-off icon-lg"></i></span>
                    </span>
                    <strong class="text-heading">Todavía no has agregado videos</strong>
                    <span class="text-muted small mt-1">Cuando añadas uno, aparecerá aquí con acceso rápido para eliminarlo.</span>
                  </div>
                @endforelse
              </div>

              @if($maxVideos > 0 && ! $canAddMoreVideos)
                <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="listing-upgrade-tile text-decoration-none text-body mt-4" data-step-link="videos">
                  <span class="avatar avatar-xl bg-label-warning mb-3">
                    <span class="avatar-initial rounded"><i class="icon-base ti tabler-crown icon-lg"></i></span>
                  </span>
                  <strong>Agrega más videos con Plan Pro</strong>
                  <span class="text-muted small mt-1">Ya alcanzaste el tope de {{ $maxVideos }} video(s) para tu plan actual.</span>
                </a>
              @endif
            </div>
          </div>

          <div class="col-12 listing-step-actions">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-step-next>
              Ir al final <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="step-review" class="content" data-step-key="final">
        <div class="row g-6">
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="mb-3">Revisión final</h5>
                <p class="text-muted mb-3">Guarda los cambios y luego selecciona plan para activar y pagar este anuncio.</p>
                <table class="table table-borderless mb-0">
                  <tbody>
                    <tr>
                      <td class="ps-0 text-nowrap">Título</td>
                      <td>{{ $listing->title ?: 'Sin título' }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Ciudad principal</td>
                      <td>{{ $listing->city_name ?: 'Sin ciudad' }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Localidad principal</td>
                      <td>{{ $listing->zone_name ?: 'Sin localidad detectada' }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Barrio detectado</td>
                      <td>{{ $displayNeighborhood ?: 'Sin barrio detectado' }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Fotos</td>
                      <td>{{ $listing->photos->count() }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Videos</td>
                      <td>{{ $listing->videos->count() }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Completitud</td>
                      <td><strong data-completion-text>{{ $listing->listing_completion }}%</strong></td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Estado actual</td>
                      <td>{{ $listing->status }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0 text-nowrap">Estado de revisión</td>
                      <td>{{ $reviewMeta['label'] }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h5 class="mb-2">Plan y pago</h5>
                <p class="text-muted">Este formulario se autoguarda. Cuando la ficha esté completa, el siguiente paso es elegir plan y subir el comprobante de Nequi.</p>

                @if(!$listing->listing_completed)
                  <div class="alert alert-warning">
                    Aún faltan bloques para completar el anuncio. Termina los campos requeridos antes de pasar a planes.
                  </div>
                @endif

                <div class="d-grid gap-2 mt-auto">
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-success {{ $listing->listing_completed ? '' : 'disabled' }}">Elegir plan y pagar</a>
                  @if($canSubmitForReview && $listingIssues === [] && $planIssues === [])
                    <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}">
                      @csrf
                      <button type="submit" class="btn btn-outline-primary w-100">{{ $submitForReviewLabel }}</button>
                    </form>
                  @elseif($listing->isPaymentPending())
                    <div class="alert alert-warning mb-0">
                      Ya enviaste el comprobante. El anuncio se activará solo después de la validación admin.
                    </div>
                  @elseif($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED)
                    <div class="alert alert-success mb-0">
                      El pago ya está aprobado. Si el anuncio cumple el flujo actual, no necesitas volver a pagar.
                    </div>
                  @elseif($canSubmitForReview)
                    <div class="alert alert-warning mb-0">
                      Este anuncio necesita ajustes de plan antes de poder enviarse a revision.
                    </div>
                  @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_PENDING)
                    <div class="alert alert-warning mb-0">
                      Este anuncio ya está en revisión. No puedes modificarlo hasta recibir respuesta.
                    </div>
                  @elseif($listing->review_status === \App\Models\MariachiListing::REVIEW_APPROVED)
                    <div class="alert alert-success mb-0">
                      El anuncio está aprobado y, si además está activo, ya puede mostrarse públicamente.
                    </div>
                  @else
                    <small class="text-muted">Para publicarse necesita anuncio completo y un pago aprobado para el plan seleccionado.</small>
                  @endif
                  <small class="text-muted">La publicación pública exige validación administrativa del pago y de la ficha cuando aplique.</small>
                </div>
              </div>
            </div>
          </div>

          @if($plans)
            <div class="col-12">
              <div class="card">
                <div class="card-header"><h5 class="mb-0">Planes disponibles</h5></div>
                <div class="card-body">
                  <div class="row g-4">
                    @foreach($plans as $code => $plan)
                      <div class="col-md-4">
                        <div class="border rounded p-3 h-100 d-flex flex-column">
                          <h6 class="mb-1">
                            {{ $plan['name'] }}
                            @if($plan['badge_text'])
                              <span class="badge bg-label-primary">{{ $plan['badge_text'] }}</span>
                            @endif
                          </h6>
                          <p class="text-muted mb-2">{{ $plan['description'] }}</p>
                          <p class="mb-2"><strong>${{ number_format((int) $plan['price_cop'], 0, ',', '.') }} COP / mes</strong></p>
                          <ul class="small text-muted ps-3 mb-3">
                            <li>{{ $plan['included_cities'] }} ciudad(es)</li>
                            <li>{{ $plan['max_zones_covered'] }} localidad(es)</li>
                            <li>{{ $plan['max_photos_per_listing'] }} foto(s)</li>
                            <li>{{ $plan['can_add_video'] ? $plan['max_videos_per_listing'].' video(s)' : 'Sin videos' }}</li>
                          </ul>
                          <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-outline-primary w-100 mt-auto">Ver planes y pagar</a>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          @endif

          <div class="col-12 d-flex justify-content-start">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="listing-map-picker-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Elegir ubicación en el mapa</h5>
            <p class="text-muted mb-0">Mueve el pin hasta la entrada real del anuncio. Al confirmar, detectaremos dirección, ciudad, localidad y barrio.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <div class="col-lg-8">
              <div id="listing-map-picker-canvas" class="listing-map-canvas"></div>
            </div>
            <div class="col-lg-4">
              <div class="listing-map-sidebar">
                <h6 class="mb-2">Ubicación seleccionada</h6>
                <p class="text-muted small mb-3">Puedes arrastrar el pin o hacer clic en cualquier punto del mapa para afinar la posición.</p>
                <div class="alert alert-info small mb-3">
                  El mapa actualiza la localidad a partir del reverse geocode. Si esa localidad aún no existe en catálogo, la enviaremos como sugerencia admin.
                </div>
                <dl class="row mb-0 small">
                  <dt class="col-sm-4">Dirección</dt>
                  <dd class="col-sm-8" id="listing-map-picker-address">Mueve el pin para resolver la dirección exacta.</dd>
                  <dt class="col-sm-4">Coordenadas</dt>
                  <dd class="col-sm-8" id="listing-map-picker-coordinates">Sin coordenadas todavía.</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="listing-map-picker-confirm">Usar esta ubicación</button>
        </div>
      </div>
    </div>
  </div>
@endsection
