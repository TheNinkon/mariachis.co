@php
  $accountTabs = [
    [
      'route' => 'mariachi.provider-profile.edit',
      'label' => 'Perfil',
      'icon' => 'tabler-id',
      'active' => request()->routeIs('mariachi.provider-profile.*'),
    ],
    [
      'route' => 'mariachi.account.security.edit',
      'label' => 'Seguridad',
      'icon' => 'tabler-lock',
      'active' => request()->routeIs('mariachi.account.security.*'),
    ],
    [
      'route' => 'mariachi.account.notifications.edit',
      'label' => 'Notificaciones',
      'icon' => 'tabler-bell',
      'active' => request()->routeIs('mariachi.account.notifications.*'),
    ],
    [
      'route' => 'mariachi.account.billing.edit',
      'label' => 'Facturación y planes',
      'icon' => 'tabler-credit-card',
      'active' => request()->routeIs('mariachi.account.billing.*'),
    ],
    [
      'route' => 'mariachi.verification.edit',
      'label' => 'Verificación',
      'icon' => 'tabler-shield-lock',
      'active' => request()->routeIs('mariachi.verification.*'),
    ],
  ];
@endphp

<div class="nav-align-top mb-6">
  <ul class="nav nav-pills flex-column flex-md-row gap-2 gap-md-0">
    @foreach($accountTabs as $tab)
      <li class="nav-item">
        <a class="nav-link {{ $tab['active'] ? 'active' : '' }}" href="{{ route($tab['route']) }}">
          <i class="icon-base ti {{ $tab['icon'] }} icon-sm me-1_5"></i>{{ $tab['label'] }}
        </a>
      </li>
    @endforeach
  </ul>
</div>
