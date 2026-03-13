@extends('layouts/layoutMaster')

@section('title', 'Mariachis - Admin')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  ])
@endsection

@section('page-script')
  @vite('resources/assets/js/admin-mariachis-index.js')
@endsection

@section('content')
  @php
    use App\Models\User;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;

    $planOptions = $mariachis
      ->map(fn ($mariachi) => $mariachi->mariachiProfile?->activeSubscription?->plan?->name ?: ($mariachi->mariachiProfile?->subscription_plan_code ? Str::headline($mariachi->mariachiProfile->subscription_plan_code) : 'Sin plan'))
      ->filter()
      ->unique()
      ->sort()
      ->values();

    $verificationShare = $totalMariachis > 0 ? (int) round(($pendingVerificationMariachis / $totalMariachis) * 100) : 0;
    $activeShare = $totalMariachis > 0 ? (int) round(($activeMariachis / $totalMariachis) * 100) : 0;
    $subscribedShare = $totalMariachis > 0 ? (int) round(($subscribedMariachis / $totalMariachis) * 100) : 0;
  @endphp

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total mariachis</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($totalMariachis) }}</h4>
              </div>
              <small class="mb-0">Registros totales en plataforma</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-users icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Plan activo</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($subscribedMariachis) }}</h4>
                <p class="text-success mb-0">({{ $subscribedShare }}%)</p>
              </div>
              <small class="mb-0">Con suscripcion vigente</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="icon-base ti tabler-user-plus icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Activos</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($activeMariachis) }}</h4>
                <p class="text-success mb-0">({{ $activeShare }}%)</p>
              </div>
              <small class="mb-0">Cuentas habilitadas</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-user-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Verificacion pendiente</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ number_format($pendingVerificationMariachis) }}</h4>
                <p class="text-warning mb-0">({{ $verificationShare }}%)</p>
              </div>
              <small class="mb-0">Con solicitud por revisar</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-user-search icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom">
      <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <h5 class="card-title mb-0">Filters</h5>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-label-primary">Volver al dashboard</a>
      </div>
      <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
        <div class="col-md-4 user_role">
          <select id="MariachiRoleFilter" class="select2 form-select">
            <option value="">Todos los roles</option>
            <option value="Mariachi">Mariachi</option>
          </select>
        </div>
        <div class="col-md-4 user_plan">
          <select id="MariachiPlanFilter" class="select2 form-select">
            <option value="">Todos los planes</option>
            @foreach ($planOptions as $planOption)
              <option value="{{ $planOption }}">{{ $planOption }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 user_status">
          <select id="MariachiStatusFilter" class="select2 form-select">
            <option value="">Todos los estados</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-users table">
        <thead class="border-top">
          <tr>
            <th></th>
            <th></th>
            <th>User</th>
            <th>Role</th>
            <th>Plan</th>
            <th>Billing</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($mariachis as $mariachi)
            @php
              $profile = $mariachi->mariachiProfile;
              $subscription = $profile?->activeSubscription;
              $plan = $subscription?->plan;
              $planLabel = $plan?->name ?: ($profile?->subscription_plan_code ? Str::headline($profile->subscription_plan_code) : 'Sin plan');
              $billingLabel = $plan
                ? '$'.number_format((int) $plan->price_cop, 0, ',', '.').' / '.($plan->billing_cycle ?: 'ciclo')
                : 'Sin facturacion';
              $statusLabel = $mariachi->status === User::STATUS_ACTIVE ? 'Activo' : 'Inactivo';
              $statusClass = $mariachi->status === User::STATUS_ACTIVE ? 'bg-label-success' : 'bg-label-secondary';
              $avatarUrl = $profile?->logo_path ? asset('storage/'.$profile->logo_path) : asset('marketplace/img/1.webp');
              $mainName = $profile?->business_name ?: $mariachi->display_name;
              $publicProfileUrl = \Illuminate\Support\Facades\Route::has('mariachi.provider.public.show') && filled($profile?->slug)
                ? route('mariachi.provider.public.show', ['handle' => $profile->slug])
                : null;
            @endphp
            <tr>
              <td></td>
              <td>
                <input type="checkbox" class="form-check-input" />
              </td>
              <td>
                <div class="d-flex justify-content-start align-items-center user-name">
                  <div class="avatar-wrapper">
                    <div class="avatar avatar-sm me-4">
                      <img src="{{ $avatarUrl }}" alt="Avatar {{ $mainName }}" class="rounded-circle object-fit-cover" />
                    </div>
                  </div>
                  <div class="d-flex flex-column">
                    <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="text-heading text-truncate">
                      <span class="fw-medium">{{ $mainName }}</span>
                    </a>
                    <small>{{ $mariachi->email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-truncate d-flex align-items-center text-heading">
                  <i class="icon-base ti tabler-music icon-md text-primary me-2"></i>
                  Mariachi
                </span>
              </td>
              <td>
                <span class="text-heading">{{ $planLabel }}</span>
                <small class="d-block text-body-secondary">
                  {{ $profile?->active_listings_count ?? 0 }} anuncio(s) activo(s)
                </small>
              </td>
              <td>
                <span class="text-heading">{{ $billingLabel }}</span>
                <small class="d-block text-body-secondary">
                  {{ $profile?->city_name ?: 'Ciudad pendiente' }}
                </small>
              </td>
              <td>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="btn btn-text-secondary rounded-pill waves-effect btn-icon" title="Ver ficha admin">
                    <i class="icon-base ti tabler-eye icon-22px"></i>
                  </a>
                  <a href="{{ route('admin.mariachis.edit', $mariachi) }}" class="btn btn-text-secondary rounded-pill waves-effect btn-icon" title="Editar perfil">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <a href="javascript:;" class="btn btn-text-secondary rounded-pill waves-effect btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-dots-vertical icon-22px"></i>
                  </a>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="dropdown-item">Ver detalle</a>
                    <a href="{{ route('admin.mariachis.edit', $mariachi) }}" class="dropdown-item">Editar</a>
                    @if ($publicProfileUrl)
                      <a href="{{ $publicProfileUrl }}" class="dropdown-item" target="_blank">Perfil publico</a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('admin.mariachis.toggle-status', $mariachi) }}" method="POST" class="js-toggle-status-form">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="dropdown-item text-{{ $mariachi->status === User::STATUS_ACTIVE ? 'danger' : 'success' }}">
                        {{ $mariachi->status === User::STATUS_ACTIVE ? 'Inactivar' : 'Activar' }}
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
