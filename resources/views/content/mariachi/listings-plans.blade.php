@extends('layouts/layoutMaster')

@section('title', 'Seleccionar plan')

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
        <h5 class="mb-1">Paso final: plan y pago</h5>
        <p class="mb-1">Anuncio: <strong>{{ $listing->title }}</strong> · Completitud actual: <strong>{{ $listing->listing_completion }}%</strong></p>
        <small class="text-muted">Plan actual del perfil: {{ $planSummary['name'] }}. Si admin ya te asigno un plan privado, puedes volver al editor y enviarlo a revision sin volver a escoger.</small>
      </div>
      <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-outline-primary">Volver al editor</a>
    </div>
  </div>

  @if(! $listing->listing_completed)
    <div class="alert alert-warning">
      Este anuncio aún no cumple el mínimo para activación. Completa datos, ubicación, filtros y fotos antes de seleccionar plan.
    </div>
  @endif

  <div class="row g-4">
    @foreach($plans as $code => $plan)
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="mb-1">
              {{ $plan['name'] }}
              @if($plan['badge_text'])
                <span class="badge bg-label-primary">{{ $plan['badge_text'] }}</span>
              @endif
            </h5>
            <p class="text-muted mb-2">{{ $plan['description'] }}</p>
            <p class="mb-2"><strong>${{ number_format((int) $plan['price_cop'], 0, ',', '.') }} COP / mes</strong></p>

            <ul class="small text-muted mb-4 ps-3">
              <li>Hasta {{ $plan['listing_limit'] }} anuncio(s)</li>
              <li>Hasta {{ $plan['included_cities'] }} ciudad(es)</li>
              <li>Hasta {{ $plan['max_zones_covered'] }} zona(s)</li>
              <li>{{ $plan['max_photos_per_listing'] }} foto(s) por anuncio</li>
              <li>{{ $plan['can_add_video'] ? $plan['max_videos_per_listing'].' video(s) por anuncio' : 'Sin videos incluidos' }}</li>
              <li>WhatsApp visible: {{ $plan['show_whatsapp'] ? 'Sí' : 'No' }}</li>
              <li>Teléfono visible: {{ $plan['show_phone'] ? 'Sí' : 'No' }}</li>
              <li>Ciudad adicional: ${{ number_format((int) config('monetization.additional_city_price_cop', 9900), 0, ',', '.') }} COP / mes</li>
            </ul>

            <form method="POST" action="{{ route('mariachi.listings.plans.select', ['listing' => $listing->id]) }}" class="mt-auto">
              @csrf
              <input type="hidden" name="plan_code" value="{{ $code }}" />
              <button type="submit" class="btn btn-primary w-100" @disabled(! $listing->listing_completed)>
                {{ $listing->selected_plan_code === $code ? 'Activar con este plan' : 'Seleccionar plan' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endsection
