@extends('layouts/layoutMaster')

@section('title', 'Detalle del anuncio')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

@section('page-style')
  <style>
    .admin-order-item-thumb {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      object-fit: cover;
      flex: 0 0 52px;
    }

    .admin-order-item-fallback {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      flex: 0 0 52px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(115, 103, 240, 0.14);
      color: var(--bs-primary);
      font-size: 0.9rem;
      font-weight: 700;
    }

    .admin-listing-cover {
      width: 88px;
      height: 88px;
      border-radius: 18px;
      object-fit: cover;
    }

    .admin-provider-avatar {
      width: 52px;
      height: 52px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(40, 199, 111, 0.12);
      color: #28c76f;
      font-weight: 700;
    }

    .admin-order-calculations {
      min-width: 240px;
    }

    .admin-order-description {
      color: var(--bs-body-color);
      opacity: 0.88;
      line-height: 1.65;
    }

    .admin-listing-nav-shell {
      margin-bottom: 1.5rem;
    }

    .admin-listing-nav-shell .nav-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.95rem 1.35rem;
      border-radius: 0.65rem;
      color: var(--bs-heading-color);
      font-weight: 600;
    }

    .admin-listing-nav-shell .nav-link i {
      font-size: 1rem;
    }

    .admin-listing-nav-shell .nav-link.active {
      box-shadow: 0 0.25rem 0.9rem rgba(11, 42, 32, 0.12);
    }

    .admin-listing-section.d-none {
      display: none !important;
    }
  </style>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const navLinks = Array.from(document.querySelectorAll('[data-admin-listing-nav] .nav-link[data-section-target]'));
      const sections = Array.from(document.querySelectorAll('[data-admin-listing-section]'));
      const mainColumn = document.querySelector('[data-admin-listing-main-col]');
      const sideColumn = document.querySelector('[data-admin-listing-side-col]');

      if (!navLinks.length || !sections.length) {
        return;
      }

      const activateSection = (section, syncHash = true) => {
        const fallback = sections[0]?.dataset.adminListingSection || 'details';
        const nextSection = sections.some(item => item.dataset.adminListingSection === section) ? section : fallback;

        navLinks.forEach(link => {
          link.classList.toggle('active', link.dataset.sectionTarget === nextSection);
        });

        sections.forEach(sectionElement => {
          const isActive = sectionElement.dataset.adminListingSection === nextSection;
          sectionElement.classList.toggle('d-none', !isActive);
          sectionElement.hidden = !isActive;
        });

        const showSidePanel = nextSection === 'details';
        if (mainColumn) {
          mainColumn.classList.toggle('col-lg-8', showSidePanel);
          mainColumn.classList.toggle('col-lg-12', !showSidePanel);
        }

        if (sideColumn) {
          sideColumn.classList.toggle('d-none', !showSidePanel);
          sideColumn.hidden = !showSidePanel;
        }

        if (syncHash) {
          window.history.replaceState(null, '', `#${nextSection}`);
        }
      };

      navLinks.forEach(link => {
        link.addEventListener('click', event => {
          event.preventDefault();
          activateSection(link.dataset.sectionTarget);
        });
      });

      const initialSection = window.location.hash ? window.location.hash.replace('#', '') : 'details';
      activateSection(initialSection, false);
    });
  </script>
@endsection

@section('content')
  @php
    $statusMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
      'pending' => ['label' => 'Pendiente de revision', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
    ];

    $operationalMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
      'awaiting_plan' => ['label' => 'Sin plan', 'class' => 'warning'],
      'active' => ['label' => 'Activo', 'class' => 'success'],
      'paused' => ['label' => 'Pausado', 'class' => 'secondary'],
    ];

    $reviewMeta = $statusMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
    $operationalMeta = $operationalMap[$listing->status] ?? ['label' => \Illuminate\Support\Str::headline($listing->status ?: 'draft'), 'class' => 'secondary'];
    $providerName = $listing->mariachiProfile?->business_name ?: $listing->mariachiProfile?->user?->display_name ?: 'Mariachi';
    $providerUser = $listing->mariachiProfile?->user;
    $coverPhoto = $listing->photos->first();
    $listingInitials = collect(preg_split('/\s+/', trim($listing->title ?: $providerName)))
      ->filter()
      ->take(2)
      ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
      ->implode('');
    $providerInitials = collect(preg_split('/\s+/', trim($providerName)))
      ->filter()
      ->take(2)
      ->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))
      ->implode('');

    $contentRows = [
      [
        'name' => 'Portada principal',
        'detail' => $listing->title ?: 'Anuncio sin titulo',
        'sub' => $listing->slug ?: 'slug pendiente',
        'state' => $coverPhoto ? 'Cargada' : 'Pendiente',
        'stateClass' => $coverPhoto ? 'success' : 'warning',
        'qty' => $coverPhoto ? 1 : 0,
        'signal' => 'Hero',
        'thumb' => $coverPhoto?->path ? asset('storage/'.$coverPhoto->path) : null,
      ],
      [
        'name' => 'Fotos',
        'detail' => 'Galeria visual del anuncio',
        'sub' => 'Fotos publicables y de apoyo',
        'state' => $listing->photos->isNotEmpty() ? 'Completa' : 'Pendiente',
        'stateClass' => $listing->photos->isNotEmpty() ? 'success' : 'warning',
        'qty' => $listing->photos->count(),
        'signal' => 'Media',
        'thumb' => $coverPhoto?->path ? asset('storage/'.$coverPhoto->path) : null,
      ],
      [
        'name' => 'Videos',
        'detail' => 'Apoyo audiovisual y prueba social',
        'sub' => $listing->videos->isNotEmpty() ? $listing->videos->first()->platform : 'Sin videos cargados',
        'state' => $listing->videos->isNotEmpty() ? 'Disponible' : 'Vacio',
        'stateClass' => $listing->videos->isNotEmpty() ? 'info' : 'secondary',
        'qty' => $listing->videos->count(),
        'signal' => 'Video',
        'thumb' => null,
      ],
      [
        'name' => 'Cobertura',
        'detail' => 'Ciudad principal y zonas extra',
        'sub' => $listing->city_name ?: 'Sin ciudad definida',
        'state' => $listing->travels_to_other_cities || $listing->serviceAreas->isNotEmpty() ? 'Extendida' : 'Local',
        'stateClass' => $listing->travels_to_other_cities || $listing->serviceAreas->isNotEmpty() ? 'primary' : 'secondary',
        'qty' => $listing->serviceAreas->count(),
        'signal' => 'Zona',
        'thumb' => null,
      ],
      [
        'name' => 'FAQs',
        'detail' => 'Preguntas frecuentes del anuncio',
        'sub' => $listing->faqs->isNotEmpty() ? $listing->faqs->first()->question : 'Sin preguntas frecuentes',
        'state' => $listing->faqs->isNotEmpty() ? 'Completo' : 'Vacio',
        'stateClass' => $listing->faqs->isNotEmpty() ? 'success' : 'secondary',
        'qty' => $listing->faqs->count(),
        'signal' => 'Soporte',
        'thumb' => null,
      ],
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

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
    <div class="d-flex flex-column justify-content-center">
      <div class="mb-1">
        <span class="h5">Anuncio #{{ $listing->id }}</span>
        <span class="badge bg-label-{{ $reviewMeta['class'] }} me-1 ms-2">{{ $reviewMeta['label'] }}</span>
        <span class="badge bg-label-{{ $operationalMeta['class'] }}">{{ $operationalMeta['label'] }}</span>
      </div>
      <p class="mb-0">
        {{ optional($listing->submitted_for_review_at ?: $listing->updated_at)->format('d/m/Y H:i') ?: 'Sin fecha registrada' }}
        · {{ $listing->city_name ?: 'Sin ciudad' }}
      </p>
    </div>
    <div class="d-flex align-content-center flex-wrap gap-2">
      <a href="{{ route('admin.listings.index') }}" class="btn btn-label-secondary">Volver</a>
      @if ($providerUser)
        <a href="{{ route('admin.mariachis.show', $providerUser) }}" class="btn btn-label-info">Ver mariachi</a>
      @endif
      @if ($listing->isApprovedForMarketplace() && $listing->slug)
        <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" rel="noopener" class="btn btn-primary">Abrir publico</a>
      @endif
    </div>
  </div>

  <div class="nav-align-top admin-listing-nav-shell">
    <ul class="nav nav-pills flex-column flex-md-row flex-wrap row-gap-2" data-admin-listing-nav>
      <li class="nav-item">
        <a class="nav-link active" href="#details" data-section-target="details"><i class="icon-base ti tabler-layout-grid"></i>Detalles</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#description" data-section-target="description"><i class="icon-base ti tabler-file-description"></i>Descripcion</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#media" data-section-target="media"><i class="icon-base ti tabler-photo"></i>Fotos y videos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#faqs" data-section-target="faqs"><i class="icon-base ti tabler-help-circle"></i>FAQs</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#activity" data-section-target="activity"><i class="icon-base ti tabler-activity"></i>Actividad y logs</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-12 col-lg-8" data-admin-listing-main-col>
      <div class="card mb-6 admin-listing-section" id="details" data-admin-listing-section="details">
        <div class="card-datatable">
          <table class="table mb-0">
            <thead>
              <tr>
                <th></th>
                <th></th>
                <th class="w-50">contenido</th>
                <th class="w-25">estado</th>
                <th class="w-25">qty</th>
                <th>senal</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($contentRows as $row)
                <tr>
                  <td></td>
                  <td>
                    @if ($row['thumb'])
                      <img src="{{ $row['thumb'] }}" alt="{{ $row['name'] }}" class="admin-order-item-thumb" />
                    @else
                      <span class="admin-order-item-fallback">{{ strtoupper(mb_substr($row['name'], 0, 2)) }}</span>
                    @endif
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <h6 class="text-body mb-0">{{ $row['name'] }}</h6>
                      <small>{{ $row['detail'] }}</small>
                      <small class="text-body-secondary mt-1">{{ $row['sub'] }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-label-{{ $row['stateClass'] }}">{{ $row['state'] }}</span>
                  </td>
                  <td><span class="text-body">{{ $row['qty'] }}</span></td>
                  <td><span class="text-body">{{ $row['signal'] }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="d-flex justify-content-end align-items-center m-6 mb-2">
            <div class="admin-order-calculations">
              <div class="d-flex justify-content-start mb-2">
                <span class="w-px-140 text-heading">Completitud:</span>
                <h6 class="mb-0">{{ (int) $listing->listing_completion }}%</h6>
              </div>
              <div class="d-flex justify-content-start mb-2">
                <span class="w-px-140 text-heading">Precio base:</span>
                <h6 class="mb-0">{{ $listing->base_price ? '$'.number_format((float) $listing->base_price, 0, ',', '.') : 'Pendiente' }}</h6>
              </div>
              <div class="d-flex justify-content-start mb-2">
                <span class="w-px-140 text-heading">Plan:</span>
                <h6 class="mb-0">{{ \Illuminate\Support\Str::headline($listing->selected_plan_code ?: 'sin plan') }}</h6>
              </div>
              <div class="d-flex justify-content-start">
                <h6 class="w-px-140 mb-0">Interacciones:</h6>
                <h6 class="mb-0">{{ (int) $listing->quote_conversations_count }} leads / {{ (int) $listing->reviews_count }} opiniones</h6>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-6 admin-listing-section d-none" id="description" data-admin-listing-section="description" hidden>
        <div class="card-header">
          <h5 class="card-title m-0">Descripcion y propuesta comercial</h5>
        </div>
        <div class="card-body">
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Descripcion corta</small>
              <p class="mb-0 admin-order-description">{{ $listing->short_description ?: 'Sin descripcion corta.' }}</p>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Descripcion completa</small>
              <p class="mb-0 admin-order-description">{{ $listing->description ?: 'Sin descripcion completa.' }}</p>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <h6 class="mb-2">Tipos de evento</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->eventTypes as $eventType)
                  <span class="badge bg-label-primary">{{ $eventType->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Tipos de servicio</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->serviceTypes as $serviceType)
                  <span class="badge bg-label-info">{{ $serviceType->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Tamano del grupo</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->groupSizeOptions as $groupSize)
                  <span class="badge bg-label-warning">{{ $groupSize->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>

            <div class="col-md-6">
              <h6 class="mb-2">Rango de presupuesto</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->budgetRanges as $range)
                  <span class="badge bg-label-secondary">{{ $range->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>

            <div class="col-12">
              <h6 class="mb-2">Cobertura adicional</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->serviceAreas as $area)
                  <span class="badge bg-label-dark">{{ $area->city_name ?: $area->marketplaceZone?->name ?: 'Zona extra' }}</span>
                @empty
                  <span class="text-body-secondary">Sin zonas extra registradas</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-6 admin-listing-section d-none" id="media" data-admin-listing-section="media" hidden>
        <div class="card-header">
          <h5 class="card-title m-0">Fotos y videos del anuncio</h5>
        </div>
        <div class="card-body">
          <div class="row g-4 mb-5">
            <div class="col-md-4">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Fotos cargadas</small>
                <h4 class="mb-0">{{ $listing->photos->count() }}</h4>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Videos cargados</small>
                <h4 class="mb-0">{{ $listing->videos->count() }}</h4>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Portada</small>
                <h4 class="mb-0">{{ $coverPhoto ? 'Lista' : 'Pendiente' }}</h4>
              </div>
            </div>
          </div>

          @if ($listing->photos->isNotEmpty())
            <div class="row g-3 mb-5">
              @foreach ($listing->photos as $photo)
                <div class="col-sm-6 col-xl-4">
                  <img
                    src="{{ asset('storage/'.$photo->path) }}"
                    alt="Foto del anuncio"
                    class="img-fluid rounded border"
                    style="height: 180px; width: 100%; object-fit: cover;" />
                </div>
              @endforeach
            </div>
          @else
            <div class="alert alert-warning mb-5">Este anuncio aun no tiene fotos cargadas.</div>
          @endif

          <h6 class="mb-3">Videos</h6>
          @if ($listing->videos->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>URL</th>
                    <th>Plataforma</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($listing->videos as $video)
                    <tr>
                      <td><a href="{{ $video->url }}" target="_blank" rel="noopener">{{ $video->url }}</a></td>
                      <td>{{ $video->platform }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="mb-0 text-body-secondary">Este anuncio aun no tiene videos registrados.</p>
          @endif
        </div>
      </div>

      <div class="card mb-6 admin-listing-section d-none" id="faqs" data-admin-listing-section="faqs" hidden>
        <div class="card-header">
          <h5 class="card-title m-0">Preguntas frecuentes</h5>
        </div>
        <div class="card-body">
          @forelse ($listing->faqs as $faq)
            <div class="border rounded p-3 mb-3">
              <p class="fw-semibold mb-1">{{ $faq->question }}</p>
              <p class="mb-0 text-body-secondary">{{ $faq->answer }}</p>
            </div>
          @empty
            <p class="mb-0 text-body-secondary">Este anuncio no tiene preguntas frecuentes.</p>
          @endforelse
        </div>
      </div>

      <div class="card mb-6 admin-listing-section d-none" id="activity" data-admin-listing-section="activity" hidden>
        <div class="card-header">
          <h5 class="card-title m-0">Actividad y logs del anuncio</h5>
        </div>
        <div class="card-body pt-1">
          <ul class="timeline pb-0 mb-0">
            @forelse ($activityTimeline as $activity)
              <li class="timeline-item timeline-item-transparent border-primary">
                <span class="timeline-point timeline-point-{{ $activity['point'] }}"></span>
                <div class="timeline-event">
                  <div class="timeline-header">
                    <h6 class="mb-0">{{ $activity['title'] }}</h6>
                    <small class="text-body-secondary">{{ optional($activity['at'])->format('d/m/Y H:i') }}</small>
                  </div>
                  <p class="mt-3 mb-3">{{ $activity['body'] }}</p>
                  <div class="badge bg-lighter rounded d-inline-flex align-items-center">
                    <span class="h6 mb-0 text-body">{{ $activity['meta'] }}</span>
                  </div>
                </div>
              </li>
            @empty
              <li class="timeline-item timeline-item-transparent border-transparent pb-0">
                <span class="timeline-point timeline-point-secondary"></span>
                <div class="timeline-event pb-0">
                  <div class="timeline-header">
                    <h6 class="mb-0">Sin actividad relevante</h6>
                  </div>
                  <p class="mt-1 mb-0">Todavia no hay hitos suficientes para construir una linea de tiempo del anuncio.</p>
                </div>
              </li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4" data-admin-listing-side-col>
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="card-title m-0">Detalles del mariachi</h5>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-start align-items-center mb-6">
            <div class="me-3">
              <span class="admin-provider-avatar">{{ $providerInitials ?: 'MR' }}</span>
            </div>
            <div class="d-flex flex-column">
              <h6 class="mb-0">{{ $providerName }}</h6>
              <span>Perfil #{{ $listing->mariachiProfile?->id ?: 'N/D' }}</span>
            </div>
          </div>

          <div class="d-flex justify-content-start align-items-center mb-6">
            <span class="avatar rounded-circle bg-label-success me-3 d-flex align-items-center justify-content-center">
              <i class="icon-base ti tabler-layout-grid-add icon-lg"></i>
            </span>
            <h6 class="text-nowrap mb-0">{{ (int) $listing->quote_conversations_count }} leads asociados</h6>
          </div>

          <div class="d-flex justify-content-between">
            <h6 class="mb-1">Contacto</h6>
            @if ($providerUser)
              <h6 class="mb-1"><a href="{{ route('admin.mariachis.show', $providerUser) }}">Abrir ficha</a></h6>
            @endif
          </div>
          <p class="mb-1">Email: {{ $providerUser?->email ?: 'Sin email' }}</p>
          <p class="mb-1">Mobile: {{ $providerUser?->phone ?: ($listing->mariachiProfile?->whatsapp ?: 'Sin telefono') }}</p>
          <p class="mb-0">Ciudad: {{ $listing->mariachiProfile?->city_name ?: $listing->city_name ?: 'Sin ciudad' }}</p>
        </div>
      </div>

      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between">
          <h5 class="card-title m-0">Ubicacion y publicacion</h5>
        </div>
        <div class="card-body">
          <p class="mb-3">
            {{ $listing->address ?: 'Direccion pendiente' }}<br />
            {{ $listing->zone_name ?: 'Zona pendiente' }}<br />
            {{ $listing->city_name ?: 'Ciudad pendiente' }} {{ $listing->postal_code ?: '' }}<br />
            {{ $listing->country ?: 'Pais pendiente' }}
          </p>

          <div class="d-flex justify-content-start mb-2">
            <span class="w-px-140 text-heading">Market city:</span>
            <span>{{ $listing->marketplaceCity?->name ?: 'Sin relacion' }}</span>
          </div>
          <div class="d-flex justify-content-start mb-2">
            <span class="w-px-140 text-heading">Plan vigente:</span>
            <span>{{ \Illuminate\Support\Str::headline($listing->selected_plan_code ?: 'sin plan') }}</span>
          </div>
          <div class="d-flex justify-content-start mb-2">
            <span class="w-px-140 text-heading">Viaja a otras:</span>
            <span>{{ $listing->travels_to_other_cities ? 'Si' : 'No' }}</span>
          </div>
          <div class="d-flex justify-content-start">
            <span class="w-px-140 text-heading">Visible ahora:</span>
            <span>{{ $listing->isApprovedForMarketplace() ? 'Si' : 'No' }}</span>
          </div>
        </div>
      </div>

      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between">
          <h5 class="card-title m-0">Moderacion</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Estado editorial</small>
            <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
          </div>

          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Enviado a revision</small>
            <div class="fw-medium">{{ $listing->submitted_for_review_at?->format('d/m/Y H:i') ?: 'Aun no enviado' }}</div>
          </div>

          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Ultima revision</small>
            <div class="fw-medium">{{ $listing->reviewed_at?->format('d/m/Y H:i') ?: 'Sin revisar' }}</div>
            <div class="text-body-secondary">{{ $listing->reviewedBy?->display_name ?: 'Sin revisor asignado' }}</div>
          </div>

          @if ($listing->rejection_reason)
            <div class="alert alert-danger py-2 px-3">
              <p class="mb-1 fw-semibold">Motivo del rechazo</p>
              <p class="mb-0">{{ $listing->rejection_reason }}</p>
            </div>
          @endif

          <div class="d-grid gap-3">
            <form action="{{ route('admin.listings.moderate', $listing) }}" method="POST">
              @csrf
              @method('PATCH')
              <input type="hidden" name="action" value="approve" />
              <button type="submit" class="btn btn-success w-100">Aprobar anuncio</button>
            </form>

            <form action="{{ route('admin.listings.moderate', $listing) }}" method="POST">
              @csrf
              @method('PATCH')
              <input type="hidden" name="action" value="reject" />
              <label class="form-label mb-1">Rechazar con motivo</label>
              <textarea
                name="rejection_reason"
                rows="4"
                class="form-control"
                placeholder="Explica al mariachi exactamente que debe corregir"
                required>{{ old('rejection_reason', $listing->review_status === 'rejected' ? $listing->rejection_reason : '') }}</textarea>
              <button type="submit" class="btn btn-outline-danger w-100 mt-2">Rechazar anuncio</button>
            </form>
          </div>
        </div>
      </div>

      @if ($listing->photos->isNotEmpty())
        <div class="card">
          <div class="card-header">
            <h5 class="card-title m-0">Galeria rapida</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              @foreach ($listing->photos->take(6) as $photo)
                <div class="col-6">
                  <img
                    src="{{ asset('storage/'.$photo->path) }}"
                    alt="Foto del anuncio"
                    class="img-fluid rounded border"
                    style="height: 120px; width: 100%; object-fit: cover;" />
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
