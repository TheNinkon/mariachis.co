@extends('layouts/layoutMaster')

@section('title', 'Mariachi - Admin')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  ])
@endsection

@section('page-style')
  @vite('resources/assets/vendor/scss/pages/page-user-view.scss')
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  ])
@endsection

@section('page-script')
  @vite('resources/assets/js/admin-mariachi-view.js')
@endsection

@section('content')
  @php
    use App\Models\User;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $avatarUrl = $profile->logo_path ? asset('storage/'.$profile->logo_path) : asset('marketplace/img/1.webp');
    $displayName = $profile->business_name ?: $mariachi->display_name;
    $statusLabel = $mariachi->status === User::STATUS_ACTIVE ? 'Activo' : 'Inactivo';
    $statusClass = $mariachi->status === User::STATUS_ACTIVE ? 'bg-label-success' : 'bg-label-secondary';
    $verificationLabel = $profile->verification_status ? Str::headline($profile->verification_status) : 'Pendiente';
    $verificationClass = $profile->verification_status === 'verified' ? 'bg-label-success' : 'bg-label-warning';
    $plan = $profile->activeSubscription?->plan;
    $planLabel = $planSummary['name'] ?? ($plan?->name ?: ($profile->subscription_plan_code ? Str::headline($profile->subscription_plan_code) : 'Sin plan'));
    $planPrice = $plan ? '$'.number_format((int) $plan->price_cop, 0, ',', '.') : null;
    $planCycle = $plan?->billing_cycle ?: 'mes';
    $planEntitlements = $planSummary['entitlements'] ?? [];
    $profileCompletion = max(0, min(100, (int) $profile->profile_completion));
    $publicProfileUrl = Route::has('mariachi.provider.public.show') && filled($profile->slug)
      ? route('mariachi.provider.public.show', ['handle' => $profile->slug])
      : null;
    $viewsTotal = (int) ($profile->stat?->total_views ?? 0);
    $quotesTotal = (int) ($profile->quote_conversations_count ?? 0);
    $reviewsTotal = (int) ($profile->reviews_count ?? 0);
    $activeListings = (int) ($profile->active_listings_count ?? 0);
    $listingLimit = $profile->listingLimit();
    $listingUsagePercent = $listingLimit > 0 ? max(5, min(100, (int) round(($activeListings / $listingLimit) * 100))) : 0;
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

  <div class="row">
    <div class="col-xl-4 col-lg-5 order-1 order-md-0">
      <div class="card mb-6">
        <div class="card-body pt-12">
          <div class="user-avatar-section">
            <div class="d-flex align-items-center flex-column">
              <img class="img-fluid rounded mb-4 object-fit-cover" src="{{ $avatarUrl }}" height="120" width="120" alt="Avatar mariachi" />
              <div class="user-info text-center">
                <h5>{{ $displayName }}</h5>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
            <div class="d-flex align-items-center me-5 gap-4">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded">
                  <i class="icon-base ti tabler-checkbox icon-lg"></i>
                </div>
              </div>
              <div>
                <h5 class="mb-0">{{ number_format($activeListings) }}</h5>
                <span>Anuncios activos</span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-4">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded">
                  <i class="icon-base ti tabler-briefcase icon-lg"></i>
                </div>
              </div>
              <div>
                <h5 class="mb-0">{{ number_format($quotesTotal) }}</h5>
                <span>Solicitudes</span>
              </div>
            </div>
          </div>
          <h5 class="pb-4 border-bottom mb-4">Details</h5>
          <div class="info-container">
            <ul class="list-unstyled mb-6">
              <li class="mb-2">
                <span class="h6">Username:</span>
                <span>{{ '@'.($profile->slug ?: 'mariachi-'.$mariachi->id) }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Email:</span>
                <span>{{ $mariachi->email }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Status:</span>
                <span>{{ $statusLabel }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Plan:</span>
                <span>{{ $planLabel }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Contacto:</span>
                <span>{{ $mariachi->phone ?: 'Pendiente' }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">WhatsApp:</span>
                <span>{{ $profile->whatsapp ?: 'Pendiente' }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Ciudad:</span>
                <span>{{ $profile->city_name ?: 'Pendiente' }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Pais:</span>
                <span>{{ $profile->country ?: 'Pendiente' }}</span>
              </li>
              <li class="mb-2">
                <span class="h6">Verificacion:</span>
                <span class="badge {{ $verificationClass }}">{{ $verificationLabel }}</span>
              </li>
            </ul>
            <div class="d-flex justify-content-center">
              <a href="{{ route('admin.mariachis.edit', $mariachi) }}" class="btn btn-primary me-4">Edit</a>
              <form action="{{ route('admin.mariachis.toggle-status', $mariachi) }}" method="POST" class="js-toggle-status-form">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn {{ $mariachi->status === User::STATUS_ACTIVE ? 'btn-label-danger' : 'btn-label-success' }} suspend-user" data-status-label="{{ $statusLabel }}">
                  {{ $mariachi->status === User::STATUS_ACTIVE ? 'Inactivar' : 'Activar' }}
                </button>
              </form>
            </div>
            @if ($publicProfileUrl)
              <div class="text-center mt-4">
                <a href="{{ $publicProfileUrl }}" class="btn btn-text-secondary" target="_blank">Ver perfil publico</a>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="card mb-6 border border-2 border-primary rounded primary-shadow">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <span class="badge bg-label-primary">{{ $planLabel }}</span>
            <div class="d-flex justify-content-center">
              @if ($planPrice)
                <sub class="h5 pricing-currency mb-auto mt-1 text-primary">$</sub>
                <h1 class="mb-0 text-primary">{{ number_format((int) $plan->price_cop, 0, ',', '.') }}</h1>
                <sub class="h6 pricing-duration mt-auto mb-3 fw-normal">{{ $planCycle }}</sub>
              @else
                <h5 class="mb-0 text-primary">Sin cobro activo</h5>
              @endif
            </div>
          </div>
          <ul class="list-unstyled g-2 my-6">
            <li class="mb-2 d-flex align-items-center"><i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i><span>{{ $listingLimit }} anuncio(s) permitidos</span></li>
            <li class="mb-2 d-flex align-items-center"><i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i><span>{{ (int) ($planEntitlements['max_cities_covered'] ?? $plan?->included_cities ?? 1) }} ciudad(es) incluidas</span></li>
            <li class="mb-2 d-flex align-items-center"><i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i><span>{{ (int) ($planEntitlements['max_photos_per_listing'] ?? $plan?->max_photos_per_listing ?? 0) }} fotos por anuncio</span></li>
          </ul>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="h6 mb-0">Perfil</span>
            <span class="h6 mb-0">{{ $profileCompletion }}%</span>
          </div>
          <div class="progress mb-1 bg-label-primary" style="height: 6px;">
            <div class="progress-bar" role="progressbar" style="width: {{ $profileCompletion }}%;" aria-valuenow="{{ $profileCompletion }}" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <small>{{ $activeListings }} de {{ $listingLimit }} anuncios activos</small>
          <div class="d-grid w-100 mt-6">
            <a href="{{ route('admin.mariachis.edit', $mariachi) }}" class="btn btn-primary">Editar perfil</a>
          </div>

          @if ($planIssues !== [])
            <div class="alert alert-warning mt-4 mb-0">
              <strong>Plan fuera de cuota</strong>
              <ul class="mb-0 mt-2">
                @foreach ($planIssues as $issue)
                  <li>{{ $issue }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-8 col-lg-7 order-0 order-md-1">
      <div class="nav-align-top">
        <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-6 row-gap-2" data-admin-profile-nav>
          <li class="nav-item">
            <a class="nav-link active" href="#account" data-section-target="account"><i class="icon-base ti tabler-user-check icon-sm me-1_5"></i>Account</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#listings" data-section-target="listings"><i class="icon-base ti tabler-speakerphone icon-sm me-1_5"></i>Anuncios</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#reviews" data-section-target="reviews"><i class="icon-base ti tabler-star icon-sm me-1_5"></i>Opiniones</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#activity" data-section-target="activity"><i class="icon-base ti tabler-bell icon-sm me-1_5"></i>Actividad</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.profile-verifications.index') }}"><i class="icon-base ti tabler-shield-lock icon-sm me-1_5"></i>Verificacion</a>
          </li>
        </ul>
      </div>

      <div class="card mb-6" id="account" data-admin-profile-section="account">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h5 class="mb-1">Resumen de cuenta</h5>
            <p class="mb-0 text-body-secondary">Vista administrativa del perfil, desempeno y configuracion actual del mariachi.</p>
          </div>
          <span class="badge {{ $verificationClass }}">{{ $verificationLabel }}</span>
        </div>
        <div class="card-body">
          <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Vistas acumuladas</small>
                <h4 class="mb-0">{{ number_format($viewsTotal) }}</h4>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Solicitudes</small>
                <h4 class="mb-0">{{ number_format($quotesTotal) }}</h4>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Opiniones</small>
                <h4 class="mb-0">{{ number_format($reviewsTotal) }}</h4>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="border rounded p-4 h-100">
                <small class="text-body-secondary d-block mb-1">Uso del plan</small>
                <h4 class="mb-0">{{ $activeListings }}/{{ $listingLimit }}</h4>
              </div>
            </div>
          </div>

          <div class="row g-6">
            <div class="col-lg-7">
              <h6 class="mb-3">Descripcion del proveedor</h6>
              <p class="mb-4">{{ $profile->short_description ?: 'Este mariachi aun no ha completado una descripcion corta del proveedor.' }}</p>

              <div class="row g-4">
                <div class="col-md-6">
                  <div class="border rounded p-4 h-100">
                    <small class="text-body-secondary d-block mb-2">Responsable</small>
                    <div class="fw-medium">{{ $profile->responsible_name ?: 'Pendiente' }}</div>
                    <div class="text-body-secondary mt-1">{{ $mariachi->email }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded p-4 h-100">
                    <small class="text-body-secondary d-block mb-2">Ubicacion base</small>
                    <div class="fw-medium">{{ $profile->city_name ?: 'Pendiente' }}</div>
                    <div class="text-body-secondary mt-1">{{ $profile->country ?: 'Sin pais definido' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-5">
              <h6 class="mb-3">Canales y estado</h6>
              <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Sitio web</span>
                  <span class="text-body-secondary text-end">{{ $profile->website ?: 'No registrado' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Instagram</span>
                  <span class="text-body-secondary text-end">{{ $profile->instagram ?: 'No registrado' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Facebook</span>
                  <span class="text-body-secondary text-end">{{ $profile->facebook ?: 'No registrado' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>WhatsApp</span>
                  <span class="text-body-secondary text-end">{{ $profile->whatsapp ?: 'No registrado' }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-6 d-none" id="listings" data-admin-profile-section="listings" hidden>
        <div class="table-responsive mb-4">
          <table class="table datatable-listings">
            <thead class="border-top">
              <tr>
                <th></th>
                <th></th>
                <th>Anuncio</th>
                <th>Ciudad</th>
                <th>Estado</th>
                <th class="w-px-200">Progreso</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($profile->listings as $listing)
                @php
                  $listingStatusLabel = Str::headline($listing->status ?: 'draft');
                @endphp
                <tr>
                  <td></td>
                  <td>
                    <input type="checkbox" class="form-check-input" />
                  </td>
                  <td>
                    <div class="d-flex justify-content-left align-items-center">
                      <div class="avatar-wrapper">
                        <div class="avatar avatar-sm me-3">
                          <span class="avatar-initial rounded-circle bg-label-primary">{{ Str::upper(Str::substr($listing->title ?: 'A', 0, 1)) }}</span>
                        </div>
                      </div>
                      <div class="d-flex flex-column gap-50">
                        <span class="text-truncate fw-medium text-heading">{{ $listing->title ?: 'Anuncio sin titulo' }}</span>
                        <small class="text-truncate">Actualizado {{ optional($listing->updated_at)->diffForHumans() }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="text-heading">{{ $listing->city_name ?: 'Pendiente' }}</span>
                  </td>
                  <td>
                    <span class="badge {{ $listing->is_active ? 'bg-label-success' : 'bg-label-warning' }}">{{ $listingStatusLabel }}</span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="progress w-100 me-3" style="height: 6px;">
                        <div class="progress-bar" style="width: {{ (int) $listing->listing_completion }}%" aria-valuenow="{{ (int) $listing->listing_completion }}" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="text-heading">{{ (int) $listing->listing_completion }}%</span>
                    </div>
                  </td>
                  <td>
                    <div class="d-inline-block">
                      <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical icon-22px"></i></a>
                      <div class="dropdown-menu dropdown-menu-end m-0">
                        <a href="{{ route('admin.listings.show', $listing) }}" class="dropdown-item">Ver anuncio</a>
                        <a href="{{ route('admin.mariachis.show', $mariachi) }}" class="dropdown-item">Ver ficha</a>
                        <a href="{{ route('admin.mariachis.edit', $mariachi) }}" class="dropdown-item">Editar proveedor</a>
                      </div>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card mb-4 d-none" id="reviews" data-admin-profile-section="reviews" hidden>
        <div class="card-datatable table-responsive">
          <table class="table datatable-reviews">
            <thead>
              <tr>
                <th></th>
                <th>#</th>
                <th>Status</th>
                <th>Total</th>
                <th>Issued Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($profile->reviews as $review)
                <tr>
                  <td></td>
                  <td>{{ $review->id }}</td>
                  <td>
                    <span class="badge {{ $review->moderation_status === 'approved' ? 'bg-label-success' : 'bg-label-warning' }}">
                      {{ Str::headline($review->moderation_status ?: 'pending') }}
                    </span>
                  </td>
                  <td>{{ $review->rating }}/5</td>
                  <td>{{ optional($review->created_at)->format('d/m/Y H:i') }}</td>
                  <td>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-primary">Moderar</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card mb-6 d-none" id="activity" data-admin-profile-section="activity" hidden>
        <h5 class="card-header">User Activity Timeline</h5>
        <div class="card-body pt-1">
          <ul class="timeline mb-0">
            @forelse ($recentActivity as $activity)
              <li class="timeline-item timeline-item-transparent">
                <span class="timeline-point timeline-point-{{ $activity['point'] }}"></span>
                <div class="timeline-event">
                  <div class="timeline-header mb-3">
                    <h6 class="mb-0">{{ $activity['title'] }}</h6>
                    <small class="text-body-secondary">{{ optional($activity['at'])->diffForHumans() }}</small>
                  </div>
                  <p class="mb-2">{{ $activity['body'] }}</p>
                  <div class="d-flex align-items-center mb-2">
                    <div class="badge bg-lighter rounded d-flex align-items-center">
                      <span class="h6 mb-0 text-body">{{ $activity['meta'] }}</span>
                    </div>
                  </div>
                </div>
              </li>
            @empty
              <li class="timeline-item timeline-item-transparent">
                <span class="timeline-point timeline-point-secondary"></span>
                <div class="timeline-event">
                  <div class="timeline-header mb-3">
                    <h6 class="mb-0">Sin actividad reciente</h6>
                    <small class="text-body-secondary">Ahora</small>
                  </div>
                  <p class="mb-0">Todavia no hay eventos relevantes para mostrar en esta ficha.</p>
                </div>
              </li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection
