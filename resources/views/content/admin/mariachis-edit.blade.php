@extends('layouts/layoutMaster')

@section('title', 'Editar Mariachi - Admin')

@section('content')
  @php
    use Illuminate\Support\Facades\Route;

    $entitlements = $planSummary['entitlements'] ?? [];
  @endphp

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-4">
      <div>
        <h5 class="mb-1">Editar perfil del mariachi</h5>
        <p class="mb-1">{{ $profile->business_name ?: $mariachi->display_name }}</p>
        <small class="text-muted">Actualiza la informacion general del proveedor desde el panel admin.</small>
      </div>
      <div class="text-end">
        <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="btn btn-outline-primary">Volver a la ficha</a>
      </div>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-xl-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Datos generales del proveedor</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.mariachis.update', $mariachi) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">Nombre del grupo</label>
                <input class="form-control" name="business_name" value="{{ old('business_name', $profile->business_name) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Ciudad principal</label>
                <input class="form-control" name="city_name" value="{{ old('city_name', $profile->city_name) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Nombre del responsable</label>
                <input class="form-control" name="responsible_name" value="{{ old('responsible_name', $profile->responsible_name) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $mariachi->email) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Telefono</label>
                <input class="form-control" name="phone" value="{{ old('phone', $mariachi->phone) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">WhatsApp</label>
                <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" />
              </div>
              <div class="col-12">
                <label class="form-label">Descripcion corta</label>
                <textarea class="form-control" name="short_description" rows="3" maxlength="280" required>{{ old('short_description', $profile->short_description) }}</textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Sitio web</label>
                <input type="url" class="form-control" name="website" value="{{ old('website', $profile->website) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Instagram</label>
                <input type="url" class="form-control" name="instagram" value="{{ old('instagram', $profile->instagram) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Facebook</label>
                <input type="url" class="form-control" name="facebook" value="{{ old('facebook', $profile->facebook) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">TikTok</label>
                <input type="url" class="form-control" name="tiktok" value="{{ old('tiktok', $profile->tiktok) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">YouTube</label>
                <input type="url" class="form-control" name="youtube" value="{{ old('youtube', $profile->youtube) }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Logo / imagen institucional</label>
                <input type="file" class="form-control" name="logo" accept="image/*" />
                @if ($profile->logo_path)
                  <img src="{{ asset('storage/'.$profile->logo_path) }}" alt="Logo proveedor" class="img-fluid rounded mt-2 border" style="max-height: 96px;" />
                @endif
              </div>
            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
              <button class="btn btn-primary" type="submit">Guardar cambios</button>
              <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="btn btn-outline-primary">Cancelar</a>
              @if (\Illuminate\Support\Facades\Route::has('mariachi.provider.public.show') && filled($profile->slug))
                <a href="{{ route('mariachi.provider.public.show', ['handle' => $profile->slug]) }}" target="_blank" class="btn btn-label-secondary">Perfil publico</a>
              @endif
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0">Plan actual</h5>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
              <h5 class="mb-1">{{ $planSummary['name'] }}</h5>
              <p class="mb-0 text-muted">{{ $planSummary['description'] ?: 'Sin descripcion comercial.' }}</p>
            </div>
            <div class="text-end">
              <span class="badge {{ $planSummary['is_public'] ? 'bg-label-success' : 'bg-label-warning' }}">
                {{ $planSummary['is_public'] ? 'Publico' : 'Privado' }}
              </span>
            </div>
          </div>

          <ul class="list-unstyled mb-4">
            <li class="mb-2"><span class="fw-semibold">Anuncios:</span> {{ (int) ($entitlements['max_listings_total'] ?? 0) }}</li>
            <li class="mb-2"><span class="fw-semibold">Fotos:</span> {{ (int) ($entitlements['max_photos_per_listing'] ?? 0) }}</li>
            <li class="mb-2"><span class="fw-semibold">Videos:</span> {{ (int) ($entitlements['max_videos_per_listing'] ?? 0) }}</li>
            <li class="mb-2"><span class="fw-semibold">Ciudades:</span> {{ (int) ($entitlements['max_cities_covered'] ?? 0) }}</li>
            <li class="mb-2"><span class="fw-semibold">Zonas:</span> {{ (int) ($entitlements['max_zones_covered'] ?? 0) }}</li>
            <li class="mb-2"><span class="fw-semibold">WhatsApp visible:</span> {{ !empty($entitlements['can_show_whatsapp']) ? 'Si' : 'No' }}</li>
            <li class="mb-2"><span class="fw-semibold">Telefono visible:</span> {{ !empty($entitlements['can_show_phone']) ? 'Si' : 'No' }}</li>
          </ul>

          @if ($planIssues !== [])
            <div class="alert alert-warning mb-0">
              <strong>Requiere ajuste</strong>
              <ul class="mb-0 mt-2">
                @foreach ($planIssues as $issue)
                  <li>{{ $issue }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Asignar paquete</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.mariachis.assign-plan', $mariachi) }}">
            @csrf
            @method('PATCH')

            <div class="mb-4">
              <label class="form-label">Selecciona un paquete</label>
              <select class="form-select" name="plan_id" required>
                @foreach ($plans as $plan)
                  <option value="{{ $plan->id }}" @selected((int) old('plan_id', $profile->activeSubscription?->plan_id) === (int) $plan->id)>
                    {{ $plan->name }} · {{ $plan->is_public ? 'Publico' : 'Privado' }} · ${{ number_format((int) $plan->price_cop, 0, ',', '.') }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted d-block mt-1">Los planes privados tambien se pueden asignar desde aqui.</small>
            </div>

            <button class="btn btn-primary w-100" type="submit">Asignar plan</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
