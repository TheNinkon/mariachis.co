@extends('layouts/layoutMaster')

@section('title', 'Mis anuncios')

@section('content')
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
        <p class="mb-1">Plan actual: <strong>{{ $capabilities['name'] }} ({{ $capabilities['code'] }})</strong></p>
        <small class="text-muted">Usados {{ $listingsUsed }} de {{ $listingLimit }} · Restantes {{ $listingsRemaining }} · Ciudades incluidas {{ $capabilities['included_cities'] }} · Fotos {{ $capabilities['max_photos_per_listing'] }} · Videos {{ $capabilities['max_videos_per_listing'] }}</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('mariachi.provider-profile.edit') }}" class="btn btn-outline-primary">Perfil proveedor</a>
        <a href="{{ route('mariachi.listings.create') }}" class="btn btn-primary {{ $listingsRemaining <= 0 ? 'disabled' : '' }}">Crear anuncio</a>
      </div>
    </div>
  </div>

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
            @endphp
            <div class="col-md-6 col-xl-4">
              <div class="border rounded p-3 h-100 d-flex flex-column">
                @if($photo)
                  <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $listing->title }}" class="img-fluid rounded mb-3" style="height:160px;object-fit:cover;">
                @endif
                <h6 class="mb-1">{{ $listing->title }}</h6>
                <p class="mb-2 text-muted">{{ $listing->city_name ?: 'Sin ciudad' }}</p>
                <p class="mb-2">Estado: <span class="badge bg-label-{{ $listing->is_active ? 'success' : 'warning' }}">{{ $listing->status }}</span></p>
                <p class="mb-3">Plan: <strong>{{ $listing->selected_plan_code ?: 'sin seleccionar' }}</strong></p>
                <p class="mb-3">Completitud: <strong>{{ $listing->listing_completion }}%</strong></p>
                <div class="d-flex flex-wrap gap-2 mt-auto">
                  <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                  <a href="{{ route('mariachi.listings.plans', ['listing' => $listing->id]) }}" class="btn btn-sm btn-outline-secondary">Plan</a>
                  @if($listing->slug)
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
