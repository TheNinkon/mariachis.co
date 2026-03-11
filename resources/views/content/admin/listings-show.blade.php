@extends('layouts/layoutMaster')

@section('title', 'Detalle del anuncio')

@section('content')
  @php
    $statusMap = [
      'draft' => ['label' => 'Borrador', 'class' => 'secondary'],
      'pending' => ['label' => 'Pendiente de revision', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
    ];

    $reviewMeta = $statusMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
    $statusClass = $listing->is_active ? 'success' : 'secondary';
    $providerName = $listing->mariachiProfile?->business_name ?: $listing->mariachiProfile?->user?->display_name ?: 'Mariachi';
    $providerUser = $listing->mariachiProfile?->user;
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

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
    <div>
      <h4 class="mb-1">{{ $listing->title ?: 'Anuncio sin titulo' }}</h4>
      <p class="mb-0 text-body-secondary">{{ $providerName }} · {{ $listing->city_name ?: 'Sin ciudad' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('admin.listings.index') }}" class="btn btn-label-secondary">Volver</a>
      @if ($providerUser)
        <a href="{{ route('admin.mariachis.show', $providerUser) }}" class="btn btn-outline-secondary">Ver ficha del mariachi</a>
      @endif
      @if ($listing->isApprovedForMarketplace() && $listing->slug)
        <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank" rel="noopener" class="btn btn-primary">Abrir publico</a>
      @endif
    </div>
  </div>

  <div class="row g-6">
    <div class="col-xl-8">
      <div class="card mb-6">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
            <span class="badge bg-label-{{ $statusClass }}">{{ \Illuminate\Support\Str::headline($listing->status) }}</span>
            <span class="badge bg-label-primary">Plan {{ $listing->selected_plan_code ?: 'sin plan' }}</span>
            <span class="badge bg-label-info">{{ (int) $listing->listing_completion }}% completado</span>
          </div>

          <div class="row g-4">
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Slug</small>
              <div class="fw-medium">{{ $listing->slug ?: 'Pendiente' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Precio base</small>
              <div class="fw-medium">{{ $listing->base_price ? '$'.number_format((float) $listing->base_price, 0, ',', '.') : 'No definido' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Ciudad principal</small>
              <div class="fw-medium">{{ $listing->city_name ?: 'Pendiente' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Zona / barrio</small>
              <div class="fw-medium">{{ $listing->zone_name ?: 'Pendiente' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Direccion</small>
              <div class="fw-medium">{{ $listing->address ?: 'Pendiente' }}</div>
            </div>
            <div class="col-md-6">
              <small class="text-body-secondary d-block mb-1">Cobertura adicional</small>
              <div class="fw-medium">{{ $listing->travels_to_other_cities ? 'Si' : 'No' }}</div>
            </div>
          </div>

          <hr class="my-4" />

          <h6 class="mb-2">Descripcion corta</h6>
          <p class="mb-4">{{ $listing->short_description ?: 'Sin descripcion corta.' }}</p>

          <h6 class="mb-2">Descripcion completa</h6>
          <p class="mb-0">{{ $listing->description ?: 'Sin descripcion completa.' }}</p>
        </div>
      </div>

      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Catalogos y cobertura</h5>
        </div>
        <div class="card-body">
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
              <h6 class="mb-2">Tamano de grupo</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->groupSizeOptions as $groupSize)
                  <span class="badge bg-label-warning">{{ $groupSize->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="mb-2">Presupuesto</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->budgetRanges as $range)
                  <span class="badge bg-label-secondary">{{ $range->name }}</span>
                @empty
                  <span class="text-body-secondary">Sin seleccionar</span>
                @endforelse
              </div>
            </div>
            <div class="col-12">
              <h6 class="mb-2">Zonas de cobertura</h6>
              <div class="d-flex flex-wrap gap-2">
                @forelse ($listing->serviceAreas as $area)
                  <span class="badge bg-label-dark">{{ $area->city_name ?: $area->marketplaceZone?->name ?: 'Zona' }}</span>
                @empty
                  <span class="text-body-secondary">Sin zonas adicionales</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Preguntas frecuentes</h5>
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

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Media y senales</h5>
        </div>
        <div class="card-body">
          <div class="row g-4 mb-4">
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <small class="text-body-secondary d-block mb-1">Fotos</small>
                <h4 class="mb-0">{{ $listing->photos->count() }}</h4>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <small class="text-body-secondary d-block mb-1">Videos</small>
                <h4 class="mb-0">{{ $listing->videos->count() }}</h4>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <small class="text-body-secondary d-block mb-1">Solicitudes / opiniones</small>
                <h4 class="mb-0">{{ (int) $listing->quote_conversations_count }} / {{ (int) $listing->reviews_count }}</h4>
              </div>
            </div>
          </div>

          @if ($listing->photos->isNotEmpty())
            <div class="row g-3 mb-4">
              @foreach ($listing->photos as $photo)
                <div class="col-sm-6 col-xl-4">
                  <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto del anuncio" class="img-fluid rounded border" style="height: 180px; width: 100%; object-fit: cover;" />
                </div>
              @endforeach
            </div>
          @endif

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
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Moderacion</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Estado actual</small>
            <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
          </div>

          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Enviado a revision</small>
            <div class="fw-medium">{{ $listing->submitted_for_review_at?->format('Y-m-d H:i') ?: 'Aun no enviado' }}</div>
          </div>

          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Ultima revision</small>
            <div class="fw-medium">{{ $listing->reviewed_at?->format('Y-m-d H:i') ?: 'Sin revisar' }}</div>
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
              <textarea name="rejection_reason" rows="4" class="form-control" placeholder="Explica al mariachi exactamente que debe corregir" required>{{ old('rejection_reason', $listing->review_status === 'rejected' ? $listing->rejection_reason : '') }}</textarea>
              <button type="submit" class="btn btn-outline-danger w-100 mt-2">Rechazar anuncio</button>
            </form>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Proveedor</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Mariachi</small>
            <div class="fw-medium">{{ $providerName }}</div>
          </div>
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Email</small>
            <div class="fw-medium">{{ $providerUser?->email ?: 'Sin email' }}</div>
          </div>
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Telefono</small>
            <div class="fw-medium">{{ $providerUser?->phone ?: ($listing->mariachiProfile?->whatsapp ?: 'Sin telefono') }}</div>
          </div>
          @if ($providerUser)
            <a href="{{ route('admin.mariachis.show', $providerUser) }}" class="btn btn-outline-primary w-100">Abrir ficha del mariachi</a>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
