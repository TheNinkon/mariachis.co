@extends('layouts/layoutMaster')

@section('title', 'Perfil del Mariachi')

@section('content')
@php
  $sections = [
    'datos' => 'Datos del mariachi',
    'whatsapp' => 'WhatsApp',
    'ubicacion' => 'Localizacion y mapa',
    'fotos' => 'Fotos',
    'videos' => 'Videos',
    'redes' => 'Redes sociales y web',
    'eventos' => 'Tipos de evento',
    'filtros' => 'Filtros del anuncio',
    'cobertura' => 'Cobertura',
  ];

  $selectedEventTypeIds = $profile->eventTypes->pluck('id')->all();
  $selectedServiceTypeIds = $profile->serviceTypes->pluck('id')->all();
  $selectedGroupSizeIds = $profile->groupSizeOptions->pluck('id')->all();
  $selectedBudgetIds = $profile->budgetRanges->pluck('id')->all();
  $additionalCitiesText = $profile->serviceAreas->pluck('city_name')->join(', ');
@endphp

<div class="card mb-6">
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-4">
    <div>
      <h5 class="mb-1">Perfil profesional de {{ $user->display_name }}</h5>
      <p class="mb-1">Estado del perfil: <span class="badge bg-label-{{ $profile->profile_completed ? 'success' : 'warning' }}">{{ $profile->profile_completed ? 'perfil completo' : 'perfil incompleto' }}</span></p>
      <small class="text-muted">Completa los modulos para preparar tu anuncio publico (fase posterior).</small>
    </div>
    <div style="min-width: 220px;">
      <div class="d-flex justify-content-between mb-1"><small>Progreso</small><small>{{ $profile->profile_completion }}%</small></div>
      <div class="progress" style="height:10px;">
        <div class="progress-bar" role="progressbar" style="width: {{ $profile->profile_completion }}%;" aria-valuenow="{{ $profile->profile_completion }}" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>
  </div>
</div>

@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Hay errores de validacion.</strong>
    <ul class="mb-0 mt-2">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-6">
  <div class="col-xl-3">
    <div class="card">
      <div class="card-header"><h6 class="mb-0">Modulos del perfil</h6></div>
      <div class="list-group list-group-flush">
        @foreach($sections as $key => $label)
          <a href="{{ route('mariachi.profile.index', ['section' => $key]) }}" class="list-group-item list-group-item-action {{ $activeSection === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
      </div>
    </div>
  </div>

  <div class="col-xl-9">
    @if($activeSection === 'datos')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Datos del mariachi</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.core-data.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label">Nombre comercial</label><input class="form-control" name="business_name" value="{{ old('business_name', $profile->business_name) }}" required></div>
              <div class="col-md-6"><label class="form-label">Nombre del responsable</label><input class="form-control" name="responsible_name" value="{{ old('responsible_name', $profile->responsible_name) }}" required></div>
              <div class="col-md-6"><label class="form-label">Correo electronico</label><input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required></div>
              <div class="col-md-6"><label class="form-label">Telefono</label><input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}" required></div>
              <div class="col-12"><label class="form-label">Descripcion corta</label><textarea class="form-control" name="short_description" rows="2" maxlength="280" required>{{ old('short_description', $profile->short_description) }}</textarea></div>
              <div class="col-12"><label class="form-label">Descripcion completa</label><textarea class="form-control" name="full_description" rows="5" required>{{ old('full_description', $profile->full_description) }}</textarea></div>
              <div class="col-md-4"><label class="form-label">Precio desde</label><input type="number" step="0.01" min="0" class="form-control" name="base_price" value="{{ old('base_price', $profile->base_price) }}" required></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Guardar datos principales</button></div>
          </form>
        </div>
      </div>
    @elseif($activeSection === 'whatsapp')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">WhatsApp</h5></div>
        <div class="card-body">
          <p class="text-muted">Este campo queda preparado para modalidad premium futura.</p>
          <form method="POST" action="{{ route('mariachi.profile.whatsapp.update') }}">
            @csrf
            @method('PATCH')
            <div class="mb-4"><label class="form-label">Numero de WhatsApp</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" placeholder="+34..."></div>
            <button class="btn btn-primary" type="submit">Guardar WhatsApp</button>
          </form>
        </div>
      </div>
    @elseif($activeSection === 'ubicacion')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Localizacion y mapa</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.location.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label">Pais</label><input class="form-control" name="country" value="{{ old('country', $profile->country) }}" required></div>
              <div class="col-md-6"><label class="form-label">Departamento / Provincia</label><input class="form-control" name="state" value="{{ old('state', $profile->state) }}" required></div>
              <div class="col-md-6"><label class="form-label">Ciudad / Poblacion</label><input class="form-control" name="city_name" value="{{ old('city_name', $profile->city_name) }}" required></div>
              <div class="col-md-6"><label class="form-label">Codigo postal</label><input class="form-control" name="postal_code" value="{{ old('postal_code', $profile->postal_code) }}" required></div>
              <div class="col-12"><label class="form-label">Direccion</label><input class="form-control" id="address-input" name="address" value="{{ old('address', $profile->address) }}" required></div>
              <div class="col-md-6"><label class="form-label">Latitud</label><input class="form-control" id="lat-input" name="latitude" value="{{ old('latitude', $profile->latitude) }}" required></div>
              <div class="col-md-6"><label class="form-label">Longitud</label><input class="form-control" id="lng-input" name="longitude" value="{{ old('longitude', $profile->longitude) }}" required></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Guardar localizacion</button></div>
          </form>

          <div class="mt-6">
            <h6>Mapa</h6>
            @if($googleMaps['enabled'])
              <div id="map" style="width:100%;height:360px;border-radius:12px;"></div>
              <small class="text-muted">Puedes mover el marcador para ajustar coordenadas.</small>
            @else
              <div class="alert alert-warning mb-0">Configura Google Maps desde admin para activar mapa interactivo.</div>
            @endif
          </div>
        </div>
      </div>
    @elseif($activeSection === 'fotos')
      <div class="card mb-6">
        <div class="card-header"><h5 class="mb-0">Subir fotos</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.photos.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
              <div class="col-md-8"><label class="form-label">Foto</label><input type="file" class="form-control" name="photo" accept="image/*" required></div>
              <div class="col-md-4"><label class="form-label">Titulo (opcional)</label><input class="form-control" name="title" value="{{ old('title') }}"></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Subir foto</button></div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h5 class="mb-0">Galeria</h5></div>
        <div class="card-body">
          <div class="row g-4">
            @forelse($profile->photos as $photo)
              <div class="col-md-6 col-xl-4">
                <div class="border rounded p-3 h-100 d-flex flex-column">
                  <img src="{{ asset('storage/'.$photo->path) }}" alt="foto" class="img-fluid rounded mb-3" style="height:160px;object-fit:cover;">
                  <div class="mb-2"><strong>{{ $photo->title ?? 'Sin titulo' }}</strong></div>
                  <div class="mb-3">@if($photo->is_featured)<span class="badge bg-label-success">Destacada</span>@endif</div>
                  <div class="d-flex flex-wrap gap-2 mt-auto">
                    <form method="POST" action="{{ route('mariachi.profile.photos.featured', $photo) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">Destacar</button></form>
                    <form method="POST" action="{{ route('mariachi.profile.photos.move', ['photo' => $photo, 'direction' => 'up']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">Subir</button></form>
                    <form method="POST" action="{{ route('mariachi.profile.photos.move', ['photo' => $photo, 'direction' => 'down']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" type="submit">Bajar</button></form>
                    <form method="POST" action="{{ route('mariachi.profile.photos.delete', $photo) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12"><p class="mb-0 text-muted">Aun no hay fotos cargadas.</p></div>
            @endforelse
          </div>
        </div>
      </div>
    @elseif($activeSection === 'videos')
      <div class="card mb-6">
        <div class="card-header"><h5 class="mb-0">Agregar video externo</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.videos.store') }}">
            @csrf
            <div class="mb-4"><label class="form-label">Enlace de video (YouTube u otro)</label><input type="url" class="form-control" name="url" value="{{ old('url') }}" placeholder="https://..." required></div>
            <button class="btn btn-primary" type="submit">Agregar video</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h5 class="mb-0">Videos registrados</h5></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>URL</th><th>Plataforma</th><th>Accion</th></tr></thead>
              <tbody>
              @forelse($profile->videos as $video)
                <tr>
                  <td><a href="{{ $video->url }}" target="_blank">{{ $video->url }}</a></td>
                  <td>{{ $video->platform }}</td>
                  <td>
                    <form method="POST" action="{{ route('mariachi.profile.videos.delete', $video) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center">No hay videos.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @elseif($activeSection === 'redes')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Redes sociales y sitio web</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.social.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label">Pagina web</label><input type="url" class="form-control" name="website" value="{{ old('website', $profile->website) }}"></div>
              <div class="col-md-6"><label class="form-label">Instagram</label><input type="url" class="form-control" name="instagram" value="{{ old('instagram', $profile->instagram) }}"></div>
              <div class="col-md-6"><label class="form-label">Facebook</label><input type="url" class="form-control" name="facebook" value="{{ old('facebook', $profile->facebook) }}"></div>
              <div class="col-md-6"><label class="form-label">TikTok</label><input type="url" class="form-control" name="tiktok" value="{{ old('tiktok', $profile->tiktok) }}"></div>
              <div class="col-md-6"><label class="form-label">YouTube</label><input type="url" class="form-control" name="youtube" value="{{ old('youtube', $profile->youtube) }}"></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Guardar redes</button></div>
          </form>
        </div>
      </div>
    @elseif($activeSection === 'eventos')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Eventos o tipos de servicio</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.events.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-3">
              @foreach($eventTypes as $eventType)
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="event_type_ids[]" value="{{ $eventType->id }}" id="event-{{ $eventType->id }}" {{ in_array($eventType->id, old('event_type_ids', $selectedEventTypeIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="event-{{ $eventType->id }}">{{ $eventType->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>
            <div class="mt-4"><button class="btn btn-primary" type="submit">Guardar eventos</button></div>
          </form>
        </div>
      </div>
    @elseif($activeSection === 'filtros')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Filtros del anuncio</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.filters.update') }}">
            @csrf
            @method('PATCH')

            <h6 class="mb-3">Tipo de servicio</h6>
            <div class="row g-3 mb-5">
              @foreach($serviceTypes as $serviceType)
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="service_type_ids[]" value="{{ $serviceType->id }}" id="service-{{ $serviceType->id }}" {{ in_array($serviceType->id, old('service_type_ids', $selectedServiceTypeIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="service-{{ $serviceType->id }}">{{ $serviceType->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>

            <h6 class="mb-3">Tamano del grupo</h6>
            <div class="row g-3 mb-5">
              @foreach($groupSizeOptions as $option)
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="group_size_option_ids[]" value="{{ $option->id }}" id="group-{{ $option->id }}" {{ in_array($option->id, old('group_size_option_ids', $selectedGroupSizeIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="group-{{ $option->id }}">{{ $option->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>

            <h6 class="mb-3">Rango de presupuesto</h6>
            <div class="row g-3">
              @foreach($budgetRanges as $range)
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="budget_range_ids[]" value="{{ $range->id }}" id="budget-{{ $range->id }}" {{ in_array($range->id, old('budget_range_ids', $selectedBudgetIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="budget-{{ $range->id }}">{{ $range->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="mt-4"><button class="btn btn-primary" type="submit">Guardar filtros</button></div>
          </form>
        </div>
      </div>
    @elseif($activeSection === 'cobertura')
      <div class="card">
        <div class="card-header"><h5 class="mb-0">Cobertura</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('mariachi.profile.coverage.update') }}">
            @csrf
            @method('PATCH')
            <div class="mb-4"><label class="form-label">Ciudad principal</label><input class="form-control" name="city_name" value="{{ old('city_name', $profile->city_name) }}" required></div>

            <div class="mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="travels" name="travels_to_other_cities" {{ old('travels_to_other_cities', $profile->travels_to_other_cities) ? 'checked' : '' }}>
                <label class="form-check-label" for="travels">Me desplazo a otras ciudades</label>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Ciudades adicionales (separadas por coma)</label>
              <textarea class="form-control" rows="4" name="additional_cities" placeholder="Madrid, Guadalajara, Alcala de Henares">{{ old('additional_cities', $additionalCitiesText) }}</textarea>
            </div>

            <button class="btn btn-primary" type="submit">Guardar cobertura</button>
          </form>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@if($activeSection === 'ubicacion' && $googleMaps['enabled'])
  @section('page-script')
    @parent
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMaps['browser_api_key'] }}&libraries=places"></script>
    <script>
      (function () {
        const latInput = document.getElementById('lat-input');
        const lngInput = document.getElementById('lng-input');
        const addressInput = document.getElementById('address-input');
        const mapContainer = document.getElementById('map');
        if (!mapContainer || !latInput || !lngInput) return;

        const lat = parseFloat(latInput.value || '19.432608');
        const lng = parseFloat(lngInput.value || '-99.133209');
        const center = { lat: isNaN(lat) ? 19.432608 : lat, lng: isNaN(lng) ? -99.133209 : lng };

        const map = new google.maps.Map(mapContainer, { center, zoom: 13 });
        const marker = new google.maps.Marker({ map, position: center, draggable: true });

        marker.addListener('dragend', function (event) {
          latInput.value = event.latLng.lat().toFixed(7);
          lngInput.value = event.latLng.lng().toFixed(7);
        });

        if (addressInput) {
          const autocomplete = new google.maps.places.Autocomplete(addressInput);
          autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) return;
            const newPos = {
              lat: place.geometry.location.lat(),
              lng: place.geometry.location.lng(),
            };
            marker.setPosition(newPos);
            map.setCenter(newPos);
            latInput.value = newPos.lat.toFixed(7);
            lngInput.value = newPos.lng.toFixed(7);
          });
        }
      })();
    </script>
  @endsection
@endif
