@extends('layouts/layoutMaster')

@section('title', 'Editar anuncio')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
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
    $formSelectedZoneIds = collect(old('zone_ids', $selectedZoneIds))->map(fn ($id) => (int) $id)->all();
    $displayCityName = old('city_name', $listing->city_name);
    $displayZoneName = old('zone_name', $listing->zone_name ?: ($listing->serviceAreas->first()?->city_name ?? ''));
    $googlePayload = old('google_location_payload', $listing->google_location_payload ? json_encode($listing->google_location_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '');

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
    $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
    $canSubmitForReview = $listing->canBeSubmittedForReview();
    $submitForReviewLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar a revisión' : 'Enviar a revisión';
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
          · Plan activo: <strong>{{ $planSummary['name'] ?? ($listing->effectivePlanCode() ?: 'sin plan') }}</strong>
          @if(! empty($planSummary['badge_text']))
            <span class="badge bg-label-primary ms-1">{{ $planSummary['badge_text'] }}</span>
          @endif
        </p>
        <small class="text-muted">Completitud: <strong data-completion-text>{{ $listing->listing_completion }}%</strong> · Fotos {{ $capabilities['max_photos_per_listing'] }} · Videos {{ $capabilities['max_videos_per_listing'] }} · Zonas {{ $capabilities['max_zones_covered'] ?? 0 }}.</small>
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

  <div id="listing-wizard" class="bs-stepper vertical mb-6" data-listing-wizard>
    <div class="bs-stepper-header border-end">
      <div class="step" data-target="#step-basic">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-edit-circle icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Básicos</span>
            <span class="bs-stepper-subtitle">Título, resumen, precio</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-location">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-map-pin icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Ubicación</span>
            <span class="bs-stepper-subtitle">Ciudad y cobertura</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-filters">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-adjustments-horizontal icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Filtros</span>
            <span class="bs-stepper-subtitle">Servicios, FAQ y estado</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-media">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle"><i class="icon-base ti tabler-photo-video icon-sm"></i></span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">Multimedia</span>
            <span class="bs-stepper-subtitle">Fotos y videos</span>
          </span>
        </button>
      </div>
      <div class="line"></div>
      <div class="step" data-target="#step-review">
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
        <input type="hidden" name="latitude" id="listing-latitude-input" value="{{ old('latitude', $listing->latitude) }}" />
        <input type="hidden" name="longitude" id="listing-longitude-input" value="{{ old('longitude', $listing->longitude) }}" />
        <input type="hidden" name="postal_code" id="listing-postal-code-input" value="{{ old('postal_code', $listing->postal_code) }}" />
        <input type="hidden" name="google_place_id" id="listing-place-id-input" value="{{ old('google_place_id', $listing->google_place_id) }}" />
        <input type="hidden" name="google_location_payload" id="listing-google-payload-input" value="{{ $googlePayload }}" />

        <div id="step-basic" class="content">
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

        <div id="step-location" class="content">
          <div class="row g-4">
            <div class="col-12">
              <div class="alert alert-info mb-0">
                Escribe la dirección real del anuncio. El sistema detectará ciudad, barrio/zona, coordenadas y dejará el país fijo en <strong>{{ $googleMaps['default_country_name'] }}</strong>.
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
              <input
                class="form-control"
                id="listing-address-input"
                name="address"
                value="{{ old('address', $listing->address) }}"
                maxlength="255"
                autocomplete="off"
                placeholder="Empieza a escribir la dirección real del anuncio"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label">Ciudad detectada</label>
              <input class="form-control" id="listing-city-name-input" name="city_name" value="{{ $displayCityName }}" maxlength="120" placeholder="Se completará con Google o puedes ajustarla" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Zona / barrio detectado</label>
              <input class="form-control" id="listing-zone-name-input" name="zone_name" value="{{ $displayZoneName }}" maxlength="120" placeholder="Se sugerirá automáticamente si Google lo devuelve" />
            </div>

            <div class="col-12">
              <small class="text-muted d-block">Puedes corregir manualmente la ciudad o el barrio si Google los devuelve incompletos. Las coordenadas siguen siendo internas y el país permanece fijo en {{ $googleMaps['default_country_name'] }}.</small>
            </div>

            <div class="col-12">
              <label class="form-label">Departamento / región</label>
              <input class="form-control" id="listing-state-input" name="state" value="{{ old('state', $listing->state) }}" maxlength="120" readonly />
            </div>

            <div class="col-12 pt-2">
              <h6 class="mb-2">Cobertura adicional</h6>
              <small class="text-muted d-block mb-2">La zona principal se detecta automáticamente. Usa esta parte solo si además cubres otras zonas del mismo catálogo.</small>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" value="1" id="travels" name="travels_to_other_cities" {{ old('travels_to_other_cities', $listing->travels_to_other_cities) ? 'checked' : '' }}>
                <label class="form-check-label" for="travels">Me desplazo a otras ciudades</label>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Zonas adicionales de cobertura</label>
              <small class="d-block text-muted mb-1">Solo se muestran zonas de la ciudad detectada. Tu plan permite hasta {{ $maxCitiesAllowed }} ciudad(es) de cobertura y {{ $capabilities['max_zones_covered'] ?? 0 }} zona(s) por anuncio.</small>
              <select class="form-select" name="zone_ids[]" id="zone_ids" multiple size="8" data-zone-select data-selected-city="{{ $selectedCityId }}">
                @foreach($zones as $zone)
                  @php
                    $zoneCityId = (int) $zone->marketplace_city_id;
                    $isVisibleForCity = $selectedCityId > 0 ? $zoneCityId === $selectedCityId : true;
                  @endphp
                  <option
                    value="{{ $zone->id }}"
                    data-city-id="{{ $zoneCityId }}"
                    @selected(in_array((int) $zone->id, $formSelectedZoneIds, true))
                    @if(!$isVisibleForCity) hidden @endif
                  >
                    {{ $zone->name }}@if($zone->city) · {{ $zone->city->name }}@endif
                  </option>
                @endforeach
              </select>
              <small class="text-muted d-block mt-1">Ctrl/⌘ para selección múltiple.</small>
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

        <div id="step-filters" class="content">
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
                Ir a multimedia <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
              </button>
            </div>
          </div>
        </div>
      </form>

      <div id="step-media" class="content">
        <div class="row g-6">
          <div class="col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fotos del anuncio</h5>
                <span class="badge bg-label-primary">Máx {{ $capabilities['max_photos_per_listing'] }}</span>
              </div>
              <div class="card-body">
                <form method="POST" action="{{ route('mariachi.listings.photos.store', ['listing' => $listing->id]) }}" enctype="multipart/form-data" class="mb-4">
                  @csrf
                  <div class="row g-2">
                    <div class="col-md-7"><input type="file" class="form-control" name="photo" accept="image/*" required></div>
                    <div class="col-md-5"><input class="form-control" name="title" placeholder="Título opcional"></div>
                  </div>
                  <button class="btn btn-sm btn-primary mt-2" type="submit">Subir foto</button>
                </form>

                <div class="row g-3">
                  @forelse($listing->photos as $photo)
                    <div class="col-md-6">
                      <div class="border rounded p-2">
                        <img src="{{ asset('storage/'.$photo->path) }}" alt="foto" class="img-fluid rounded mb-2" style="height:130px;object-fit:cover;width:100%;">
                        @if($photo->is_featured)
                          <span class="badge bg-label-success mb-2">Destacada</span>
                        @endif
                        <div class="d-flex flex-wrap gap-1">
                          <form method="POST" action="{{ route('mariachi.listings.photos.featured', ['listing' => $listing->id, 'photo' => $photo->id]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">Destacar</button></form>
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'up']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">↑</button></form>
                          <form method="POST" action="{{ route('mariachi.listings.photos.move', ['listing' => $listing->id, 'photo' => $photo->id, 'direction' => 'down']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">↓</button></form>
                          <form method="POST" action="{{ route('mariachi.listings.photos.delete', ['listing' => $listing->id, 'photo' => $photo->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form>
                        </div>
                      </div>
                    </div>
                  @empty
                    <div class="col-12"><p class="mb-0 text-muted">No hay fotos cargadas.</p></div>
                  @endforelse
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Videos del anuncio</h5>
                <span class="badge bg-label-primary">Máx {{ $capabilities['max_videos_per_listing'] }}</span>
              </div>
              <div class="card-body">
                <form method="POST" action="{{ route('mariachi.listings.videos.store', ['listing' => $listing->id]) }}" class="mb-4">
                  @csrf
                  <div class="input-group">
                    <input type="url" class="form-control" name="url" placeholder="https://youtube.com/..." required />
                    <button class="btn btn-primary" type="submit">Agregar</button>
                  </div>
                </form>

                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead><tr><th>URL</th><th>Plataforma</th><th></th></tr></thead>
                    <tbody>
                      @forelse($listing->videos as $video)
                        <tr>
                          <td><a href="{{ $video->url }}" target="_blank" rel="noopener">{{ $video->url }}</a></td>
                          <td>{{ $video->platform }}</td>
                          <td>
                            <form method="POST" action="{{ route('mariachi.listings.videos.delete', ['listing' => $listing->id, 'video' => $video->id]) }}">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                            </form>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="3" class="text-center text-muted">No hay videos.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-label-secondary" data-step-prev>
              <i class="icon-base ti tabler-arrow-left icon-xs me-1"></i>Anterior
            </button>
            <button type="button" class="btn btn-primary" data-step-next>
              Ir al final <i class="icon-base ti tabler-arrow-right icon-xs ms-1"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="step-review" class="content">
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
                      <td class="ps-0 text-nowrap">Zona / barrio</td>
                      <td>{{ $listing->zone_name ?: 'Sin zona detectada' }}</td>
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
                <h5 class="mb-2">Guardar y activar</h5>
                <p class="text-muted">Paso 1: guarda datos. Paso 2: selecciona un plan publico o usa el ya asignado. Paso 3: envialo a revision para que quede visible en el marketplace.</p>
                <button class="btn btn-primary mb-3" type="submit" form="listing-main-form">Guardar todo el anuncio</button>

                @if(!$listing->listing_completed)
                  <div class="alert alert-warning">
                    Aún faltan bloques para completar el anuncio. Guarda cambios y verifica que la completitud llegue al nivel requerido antes de pagar.
                  </div>
                @endif

                <div class="d-grid gap-2 mt-auto">
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-success {{ $listing->listing_completed ? '' : 'disabled' }}">Elegir plan y pagar</a>
                  @if($canSubmitForReview && $listingIssues === [] && $planIssues === [])
                    <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}">
                      @csrf
                      <button type="submit" class="btn btn-outline-primary w-100">{{ $submitForReviewLabel }}</button>
                    </form>
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
                    <small class="text-muted">Para enviarlo a revision necesitas completar el anuncio y tener un plan activo.</small>
                  @endif
                  <small class="text-muted">La publicación pública exige aprobación administrativa además de tener plan y anuncio completo.</small>
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
                            <li>{{ $plan['max_zones_covered'] }} zona(s)</li>
                            <li>{{ $plan['max_photos_per_listing'] }} foto(s)</li>
                            <li>{{ $plan['can_add_video'] ? $plan['max_videos_per_listing'].' video(s)' : 'Sin videos' }}</li>
                          </ul>
                          <form method="POST" action="{{ route('mariachi.listings.plans.select', ['listing' => $listing->id]) }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan_code" value="{{ $code }}" />
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $listing->listing_completed)>Seleccionar este plan</button>
                          </form>
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
@endsection
