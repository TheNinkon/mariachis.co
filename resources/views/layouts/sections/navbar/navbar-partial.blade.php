@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$authUser = Auth::user();
$userAvatar = asset('assets/img/avatars/1.png');
$useIconAvatar = false;
$iconAvatarClass = 'icon-base ti tabler-user icon-md';
$iconAvatarTone = 'bg-label-primary';
$accountHeaderUrl = 'javascript:void(0);';
$primaryAction = null;
$secondaryAction = null;
$roleLabel = 'Usuario';
$showWordmarkBrand = ($authUser?->isMariachi() ?? false) || request()->routeIs('mariachi.*');
$brandUrl = $showWordmarkBrand && Route::has('mariachi.metrics')
  ? route('mariachi.metrics')
  : url('/');

if ($authUser) {
  $roleLabel = match ((string) $authUser->role) {
    \App\Models\User::ROLE_ADMIN => 'Administrador',
    \App\Models\User::ROLE_STAFF => 'Equipo interno',
    \App\Models\User::ROLE_MARIACHI => 'Mariachi',
    \App\Models\User::ROLE_CLIENT => 'Cliente',
    default => 'Usuario',
  };
}

if ($authUser?->isMariachi()) {
  $authUser->loadMissing('mariachiProfile');
  $userAvatar = $authUser->mariachiProfile?->logo_path
    ? asset('storage/' . $authUser->mariachiProfile->logo_path)
    : asset('marketplace/img/1.webp');

  $accountHeaderUrl = Route::has('mariachi.metrics') ? route('mariachi.metrics') : url('/mariachi/panel');
  $primaryAction = [
    'url' => Route::has('mariachi.provider-profile.edit') ? route('mariachi.provider-profile.edit') : $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-user me-3 icon-md',
    'label' => 'Mi perfil',
  ];
  $secondaryAction = [
    'url' => Route::has('mariachi.quotes.index') ? route('mariachi.quotes.index') : $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-message-circle me-3 icon-md',
    'label' => 'Solicitudes',
  ];
} elseif ($authUser?->isAdmin()) {
  $useIconAvatar = true;
  $iconAvatarClass = 'icon-base ti tabler-shield-star icon-md';
  $iconAvatarTone = 'bg-label-success';
  $accountHeaderUrl = Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin');
  $primaryAction = [
    'url' => $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-layout-dashboard me-3 icon-md',
    'label' => 'Panel admin',
  ];
  $secondaryAction = [
    'url' => Route::has('admin.mariachis.index') ? route('admin.mariachis.index') : $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-list-details me-3 icon-md',
    'label' => 'Mariachis',
  ];
} elseif ($authUser?->isStaff()) {
  $accountHeaderUrl = Route::has('staff.dashboard') ? route('staff.dashboard') : url('/staff/dashboard');
  $primaryAction = [
    'url' => $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-layout-dashboard me-3 icon-md',
    'label' => 'Panel interno',
  ];
} elseif ($authUser?->isClient()) {
  $accountHeaderUrl = Route::has('client.dashboard') ? route('client.dashboard') : url('/mi-cuenta/solicitudes');
  $primaryAction = [
    'url' => Route::has('client.account.profile') ? route('client.account.profile') : $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-user me-3 icon-md',
    'label' => 'Mi perfil',
  ];
  $secondaryAction = [
    'url' => $accountHeaderUrl,
    'icon' => 'icon-base ti tabler-message-circle me-3 icon-md',
    'label' => 'Mis solicitudes',
  ];
}
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
  <a href="{{ $brandUrl }}" class="app-brand-link">
    @if ($showWordmarkBrand)
    <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 34px; width: auto;" />
    @else
    <span class="app-brand-logo demo">@include('_partials.macros')</span>
    <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
    @endif
  </a>

  <!-- Display menu close icon only for horizontal-menu with navbar-full -->
  @if (isset($menuHorizontal))
  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
    <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
  </a>
  @endif
</div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
<div
  class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
  <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
    <i class="icon-base ti tabler-menu-2 icon-md"></i>
  </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
  @if ($configData['hasCustomizer'] == true)
  <!-- Style Switcher -->
  <div class="navbar-nav align-items-center">
    <li class="nav-item dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
        data-bs-toggle="dropdown">
        <i class="icon-base ti tabler-sun icon-md theme-icon-active"></i>
        <span class="d-none ms-2" id="nav-theme-text">Cambiar apariencia</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
        <li>
          <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
            aria-pressed="false">
            <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Claro</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
            <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Oscuro</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
            aria-pressed="false">
            <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3"
                data-icon="device-desktop-analytics"></i>Sistema</span>
          </button>
        </li>
      </ul>
    </li>
  </div>
  <!-- / Style Switcher-->
  @endif
  <ul class="navbar-nav flex-row align-items-center ms-auto">
    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          @if ($useIconAvatar)
            <span class="avatar-initial rounded-circle {{ $iconAvatarTone }}">
              <i class="{{ $iconAvatarClass }}"></i>
            </span>
          @else
            <img src="{{ $userAvatar }}" alt="Avatar usuario" class="rounded-circle object-fit-cover" />
          @endif
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item mt-0" href="{{ $accountHeaderUrl }}">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  @if ($useIconAvatar)
                    <span class="avatar-initial rounded-circle {{ $iconAvatarTone }}">
                      <i class="{{ $iconAvatarClass }}"></i>
                    </span>
                  @else
                    <img src="{{ $userAvatar }}" alt="Avatar usuario" class="rounded-circle object-fit-cover" />
                  @endif
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">
                  @if (Auth::check())
                  {{ Auth::user()->display_name }}
                  @else
                  Usuario
                  @endif
                </h6>
                <small class="text-body-secondary">{{ $roleLabel }}</small>
              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        @if ($primaryAction)
        <li>
          <a class="dropdown-item" href="{{ $primaryAction['url'] }}">
            <i class="{{ $primaryAction['icon'] }}"></i><span class="align-middle">{{ $primaryAction['label'] }}</span>
          </a>
        </li>
        @endif
        @if ($secondaryAction)
        <li>
          <a class="dropdown-item" href="{{ $secondaryAction['url'] }}">
            <i class="{{ $secondaryAction['icon'] }}"></i><span class="align-middle">{{ $secondaryAction['label'] }}</span>
          </a>
        </li>
        @endif
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        @if (Auth::check())
        <li>
          <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Cerrar sesión</span>
          </a>
        </li>
        <form method="POST" id="logout-form" action="{{ route('logout') }}">
          @csrf
        </form>
        @else
        <li>
          <div class="d-grid px-2 pt-2 pb-1">
            <a class="btn btn-sm btn-danger d-flex"
              href="{{ Route::has('login') ? route('login') : url('/admin/login') }}" target="_blank">
              <small class="align-middle">Iniciar sesión</small>
              <i class="icon-base ti tabler-login ms-2 icon-14px"></i>
            </a>
          </div>
        </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>
