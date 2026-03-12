@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
$resolveMenuUrl = static function (?string $menuUrl): string {
  if (! $menuUrl) {
    return 'javascript:void(0);';
  }

  return preg_match('/^(https?:)?\/\//i', $menuUrl) ? $menuUrl : url($menuUrl);
};
$showWordmarkBrand = (Auth::user()?->isMariachi() ?? false) || request()->routeIs('mariachi.*');
$brandUrl = $showWordmarkBrand && Route::has('mariachi.metrics')
  ? route('mariachi.metrics')
  : url('/');
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu" @foreach ($configData['menuAttributes'] as $attribute=>
  $value)
  {{ $attribute }}="{{ $value }}" @endforeach>

  <!-- ! Hide app brand if navbar-full -->
  @if (!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ $brandUrl }}" class="app-brand-link">
      @if ($showWordmarkBrand)
      <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 34px; width: auto;" />
      @else
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold ms-3">{{ config('variables.templateName') }}</span>
      @endif
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
      <i class="icon-base ti tabler-x d-block d-xl-none"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)
    {{-- adding active and open class if child is active --}}

    {{-- menu headers --}}
    @if (isset($menu->menuHeader))
    <li class="menu-header small">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>
    @else
    {{-- active menu method --}}
    @php
    $activeClass = null;
    $currentRouteName = Route::currentRouteName() ?? '';
    $hasSubmenu = isset($menu->submenu);
    $activeStateClass = $hasSubmenu ? 'active open' : 'active';

    if ($currentRouteName === $menu->slug) {
    $activeClass = $activeStateClass;
    } elseif (is_string($menu->slug ?? null) && str_starts_with($currentRouteName, $menu->slug . '.')) {
    $activeClass = $activeStateClass;
    } elseif (isset($menu->submenu)) {
    if (gettype($menu->slug) === 'array') {
    foreach ($menu->slug as $slug) {
    if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
    $activeClass = 'active open';
    }
    }
    } else {
    if (
    str_contains($currentRouteName, $menu->slug) and
    strpos($currentRouteName, $menu->slug) === 0
    ) {
    $activeClass = 'active open';
    }
    }
    }
    @endphp

    {{-- main menu --}}
    <li class="menu-item {{ $activeClass }}">
      <a href="{{ $resolveMenuUrl($menu->url ?? null) }}"
        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and
        !empty($menu->target)) target="_blank" @endif>
        @isset($menu->icon)
        <i class="{{ $menu->icon }}"></i>
        @endisset
        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
        @isset($menu->badge)
        <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
        @endisset
      </a>

      {{-- submenu --}}
      @isset($menu->submenu)
      @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
      @endisset
    </li>
    @endif
    @endforeach
  </ul>

</aside>
