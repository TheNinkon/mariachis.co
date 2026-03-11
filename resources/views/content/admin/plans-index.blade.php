@extends('layouts/layoutMaster')

@section('title', 'Paquetes')

@section('content')
  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Paquetes</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalPlans) }}</h4>
              </div>
              <small class="mb-0">Catalogo total</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-package icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Publicos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($publicPlans) }}</h4>
              </div>
              <small class="mb-0">Visibles al mariachi</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-world icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Privados</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($privatePlans) }}</h4>
              </div>
              <small class="mb-0">Solo asignables por admin</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-lock icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Activos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($activePlans) }}</h4>
              </div>
              <small class="mb-0">Disponibles para asignacion</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base ti tabler-checkup-list icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Paquetes y capacidades</h5>
        <p class="mb-0 text-body-secondary">Define limites reales y features sin depender del nombre del plan.</p>
      </div>
      <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">Nuevo paquete</a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Paquete</th>
            <th>Acceso</th>
            <th>Cuotas</th>
            <th>Contacto</th>
            <th>Visibilidad</th>
            <th>Asignaciones</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($plans as $plan)
            @php
              $entitlements = $plan->entitlements->mapWithKeys(fn ($entitlement): array => [$entitlement->key => $entitlement->value]);
            @endphp
            <tr>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $plan->name }}</span>
                  <small class="text-muted">{{ $plan->code }}{{ $plan->badge_text ? ' · '.$plan->badge_text : '' }}</small>
                  <small class="text-muted">{{ $plan->description ?: 'Sin descripcion' }}</small>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <span class="badge {{ $plan->is_public ? 'bg-label-success' : 'bg-label-warning' }}">
                    {{ $plan->is_public ? 'Publico' : 'Privado' }}
                  </span>
                  <span class="badge {{ $plan->is_active ? 'bg-label-primary' : 'bg-label-secondary' }}">
                    {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </td>
              <td>
                <div class="small">
                  <div>{{ (int) ($entitlements['max_listings_total'] ?? $plan->listing_limit) }} anuncio(s)</div>
                  <div>{{ (int) ($entitlements['max_photos_per_listing'] ?? $plan->max_photos_per_listing) }} foto(s)</div>
                  <div>{{ (int) ($entitlements['max_videos_per_listing'] ?? $plan->max_videos_per_listing) }} video(s)</div>
                  <div>{{ (int) ($entitlements['max_zones_covered'] ?? 0) }} zona(s)</div>
                </div>
              </td>
              <td>
                <div class="small">
                  <div>WhatsApp: {{ ($entitlements['can_show_whatsapp'] ?? $plan->show_whatsapp) ? 'Si' : 'No' }}</div>
                  <div>Llamada: {{ ($entitlements['can_show_phone'] ?? $plan->show_phone) ? 'Si' : 'No' }}</div>
                </div>
              </td>
              <td>
                <div class="small">
                  <div>Prioridad: {{ (int) ($entitlements['priority_level'] ?? $plan->priority_level) }}</div>
                  <div>Badge: {{ ($entitlements['has_premium_badge'] ?? $plan->has_premium_badge) ? 'Si' : 'No' }}</div>
                  <div>Stats: {{ ($entitlements['has_advanced_stats'] ?? $plan->has_advanced_stats) ? 'Si' : 'No' }}</div>
                </div>
              </td>
              <td>{{ number_format((int) $plan->subscriptions_count) }}</td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                  <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                      {{ $plan->is_active ? 'Inactivar' : 'Activar' }}
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-5">Aun no hay paquetes configurados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
