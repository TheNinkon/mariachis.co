<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    View::composer('*', function ($view): void {
      $castMenuItem = function (array $item) use (&$castMenuItem): object {
        if (isset($item['submenu']) && is_array($item['submenu'])) {
          $item['submenu'] = array_map(
            static fn (array $subItem): object => $castMenuItem($subItem),
            $item['submenu']
          );
        }

        return (object) $item;
      };

      $baseMenu = [];

      $guestMenu = [
        ['url' => '/admin/login', 'name' => 'Login Interno', 'icon' => 'menu-icon icon-base ti tabler-login', 'slug' => 'login'],
        ['url' => '/auth/register-basic', 'name' => 'Registro Mariachi', 'icon' => 'menu-icon icon-base ti tabler-user', 'slug' => 'register'],
        ['url' => '/login', 'name' => 'Login Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-circle', 'slug' => 'client.login'],
        ['url' => '/registro', 'name' => 'Registro Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-plus', 'slug' => 'client.register'],
      ];

      $roleMenu = match (auth()->user()?->role) {
        User::ROLE_ADMIN => [
          ['url' => '/admin/dashboard', 'name' => 'Dashboard Admin', 'icon' => 'menu-icon icon-base ti tabler-layout-dashboard', 'slug' => 'admin.dashboard'],
          ['url' => '/admin/mariachis', 'name' => 'Mariachis', 'icon' => 'menu-icon icon-base ti tabler-list-details', 'slug' => 'admin.mariachis.index'],
          ['url' => '/admin/resenas', 'name' => 'Resenas', 'icon' => 'menu-icon icon-base ti tabler-message-stars', 'slug' => 'admin.reviews.index'],
          ['url' => '/admin/verificaciones-perfil', 'name' => 'Verificaciones', 'icon' => 'menu-icon icon-base ti tabler-shield-check', 'slug' => 'admin.profile-verifications.index'],
          ['url' => '/admin/internal-users', 'name' => 'Equipo Interno', 'icon' => 'menu-icon icon-base ti tabler-settings', 'slug' => 'admin.internal-users.index'],
          ['url' => '/admin/configuracion-sistema', 'name' => 'Configuracion', 'icon' => 'menu-icon icon-base ti tabler-adjustments', 'slug' => 'admin.system-settings.edit'],
          ['url' => '/admin/blog-posts', 'name' => 'Blog y Recursos', 'icon' => 'menu-icon icon-base ti tabler-notebook', 'slug' => 'admin.blog-posts'],
          [
            'name' => 'Catalogos',
            'icon' => 'menu-icon icon-base ti tabler-category',
            'slug' => [
              'admin.catalog-options',
              'admin.marketplace-cities',
              'admin.marketplace-zones',
              'admin.catalog-suggestions',
            ],
            'submenu' => [
              ['url' => '/admin/catalogos/event-types', 'name' => 'Tipos de evento', 'slug' => 'admin.catalog-options.index'],
              ['url' => '/admin/catalogos/service-types', 'name' => 'Tipos de servicio', 'slug' => 'admin.catalog-options.index'],
              ['url' => '/admin/catalogos/group-sizes', 'name' => 'Tamanos de grupo', 'slug' => 'admin.catalog-options.index'],
              ['url' => '/admin/catalogos/budget-ranges', 'name' => 'Presupuestos', 'slug' => 'admin.catalog-options.index'],
              ['url' => '/admin/catalogos-ciudades', 'name' => 'Ciudades', 'slug' => 'admin.marketplace-cities.index'],
              ['url' => '/admin/catalogos-zonas', 'name' => 'Zonas y barrios', 'slug' => 'admin.marketplace-zones.index'],
              ['url' => '/admin/catalogos-sugerencias', 'name' => 'Sugerencias pendientes', 'slug' => 'admin.catalog-suggestions.index'],
            ],
          ],
        ],
        User::ROLE_STAFF => [
          ['url' => '/staff/dashboard', 'name' => 'Panel Interno', 'icon' => 'menu-icon icon-base ti tabler-users-group', 'slug' => 'staff.dashboard'],
        ],
        User::ROLE_MARIACHI => [
          ['url' => '/mariachi/metricas', 'name' => 'Metricas', 'icon' => 'menu-icon icon-base ti tabler-chart-bar', 'slug' => 'mariachi.metrics'],
          ['url' => '/mariachi/solicitudes', 'name' => 'Solicitudes', 'icon' => 'menu-icon icon-base ti tabler-message-circle', 'slug' => 'mariachi.quotes'],
          ['url' => '/mariachi/opiniones', 'name' => 'Opiniones', 'icon' => 'menu-icon icon-base ti tabler-star', 'slug' => 'mariachi.reviews.index'],
          ['url' => '/mariachi/perfil-proveedor', 'name' => 'Perfil Proveedor', 'icon' => 'menu-icon icon-base ti tabler-id', 'slug' => 'mariachi.provider-profile.edit'],
          ['url' => '/mariachi/anuncios', 'name' => 'Anuncios', 'icon' => 'menu-icon icon-base ti tabler-speakerphone', 'slug' => 'mariachi.listings.index'],
          ['url' => '/mariachi/verificacion', 'name' => 'Verificacion', 'icon' => 'menu-icon icon-base ti tabler-shield-lock', 'slug' => 'mariachi.verification.edit'],
        ],
        User::ROLE_CLIENT => [
          ['url' => '/mi-cuenta/solicitudes', 'name' => 'Panel Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-heart', 'slug' => 'client.dashboard'],
        ],
        default => $guestMenu,
      };

      $menu = (object) ['menu' => array_map($castMenuItem, array_merge($baseMenu, $roleMenu))];
      $horizontalMenu = (object) ['menu' => array_map($castMenuItem, array_merge($baseMenu, $guestMenu))];

      $view->with('menuData', [$menu, $horizontalMenu]);
    });
  }
}
