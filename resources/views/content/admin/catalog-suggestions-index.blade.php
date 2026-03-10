@extends('layouts/layoutMaster')

@section('title', 'Sugerencias de catálogos')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
    <div>
      <h5 class="mb-0">Sugerencias pendientes</h5>
      <small class="text-muted">El mariachi puede sugerir opciones; solo admin las convierte en catálogo oficial.</small>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Volver al dashboard</a>
  </div>

  <div class="card-body pb-0">
    <div class="d-flex flex-wrap gap-2">
      @foreach($statuses as $value => $label)
        <a
          href="{{ route('admin.catalog-suggestions.index', ['status' => $value]) }}"
          class="btn btn-sm {{ $selectedStatus === $value ? 'btn-primary' : 'btn-outline-primary' }}"
        >
          {{ $label }}
        </a>
      @endforeach
    </div>
  </div>

  <div class="card-body">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($suggestions->isEmpty())
      <div class="text-center py-8">
        <h6 class="mb-2">No hay sugerencias para mostrar</h6>
        <p class="text-muted mb-0">Cuando los mariachis propongan nuevas opciones aparecerán aquí.</p>
      </div>
    @else
      <div class="table-responsive text-nowrap">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Propuesta</th>
              <th>Contexto</th>
              <th>Estado</th>
              <th>Enviado por</th>
              <th>Revisión</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($suggestions as $suggestion)
              @php
                $context = (array) ($suggestion->context_data ?? []);
                $contextCityId = (int) ($context['marketplace_city_id'] ?? 0);
                $contextCityName = $contextCityId > 0 ? ($citiesById[$contextCityId] ?? null) : null;
              @endphp
              <tr>
                <td>
                  @switch($suggestion->catalog_type)
                    @case(\App\Models\CatalogSuggestion::TYPE_EVENT)
                      Tipo de evento
                      @break
                    @case(\App\Models\CatalogSuggestion::TYPE_SERVICE)
                      Tipo de servicio
                      @break
                    @case(\App\Models\CatalogSuggestion::TYPE_ZONE)
                      Zona / barrio
                      @break
                    @default
                      {{ $suggestion->catalog_type }}
                  @endswitch
                </td>
                <td>
                  <strong>{{ $suggestion->proposed_name }}</strong>
                  <div class="text-muted small">/{{ $suggestion->proposed_slug ?: \Illuminate\Support\Str::slug($suggestion->proposed_name) }}</div>
                </td>
                <td>
                  @if($contextCityName)
                    <div>Ciudad: <strong>{{ $contextCityName }}</strong></div>
                  @endif

                  @if($suggestion->admin_notes)
                    <div class="text-muted small mt-1">Nota admin: {{ $suggestion->admin_notes }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-{{ $suggestion->status === 'pending' ? 'warning' : ($suggestion->status === 'approved' ? 'success' : 'danger') }}">
                    {{ ucfirst($suggestion->status) }}
                  </span>
                </td>
                <td>{{ $suggestion->submittedBy?->display_name ?: 'Sistema' }}</td>
                <td>
                  {{ $suggestion->reviewedBy?->display_name ?: '—' }}
                  <div class="text-muted small">{{ optional($suggestion->reviewed_at)->format('Y-m-d H:i') ?: '—' }}</div>
                </td>
                <td>
                  @if($suggestion->status === 'pending')
                    <div class="d-flex flex-column gap-2" style="min-width: 240px;">
                      <form method="POST" action="{{ route('admin.catalog-suggestions.approve', $suggestion) }}" class="d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Nota opcional" maxlength="2000" />
                        <button type="submit" class="btn btn-sm btn-success">Aprobar</button>
                      </form>

                      <form method="POST" action="{{ route('admin.catalog-suggestions.reject', $suggestion) }}" class="d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Motivo (requerido)" maxlength="2000" required />
                        <button type="submit" class="btn btn-sm btn-outline-danger">Rechazar</button>
                      </form>
                    </div>
                  @else
                    —
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $suggestions->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
