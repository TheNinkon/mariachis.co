<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Route;
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
      $routeUrl = static fn (string $name, array $parameters = [], string $fallback = '/'): string => Route::has($name)
        ? route($name, $parameters)
        : $fallback;

      $guestMenu = [
        ['url' => $routeUrl('login', [], '/admin/login'), 'name' => 'Login Interno', 'icon' => 'menu-icon icon-base ti tabler-login', 'slug' => 'login'],
        ['url' => $routeUrl('mariachi.register', [], '/signup'), 'name' => 'Registro Mariachi', 'icon' => 'menu-icon icon-base ti tabler-user', 'slug' => 'mariachi.register'],
        ['url' => $routeUrl('client.login', [], '/login'), 'name' => 'Login Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-circle', 'slug' => 'client.login'],
        ['url' => $routeUrl('client.register', [], '/registro'), 'name' => 'Registro Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-plus', 'slug' => 'client.register'],
      ];

      $roleMenu = match (auth()->user()?->role) {
        User::ROLE_ADMIN => [
          ['url' => $routeUrl('admin.dashboard', [], '/admin/dashboard'), 'name' => 'Dashboard Admin', 'icon' => 'menu-icon icon-base ti tabler-layout-dashboard', 'slug' => 'admin.dashboard'],
          ['url' => $routeUrl('admin.mariachis.index', [], '/admin/mariachis'), 'name' => 'Mariachis', 'icon' => 'menu-icon icon-base ti tabler-list-details', 'slug' => 'admin.mariachis.index'],
          ['url' => $routeUrl('admin.listings.index', [], '/admin/anuncios'), 'name' => 'Anuncios', 'icon' => 'menu-icon icon-base ti tabler-speakerphone', 'slug' => 'admin.listings'],
          ['url' => $routeUrl('admin.plans.index', [], '/admin/paquetes'), 'name' => 'Paquetes', 'icon' => 'menu-icon icon-base ti tabler-package', 'slug' => 'admin.plans.index'],
          ['url' => $routeUrl('admin.email-templates.index', [], '/admin/plantillas-correo'), 'name' => 'Plantillas de correo', 'icon' => 'menu-icon icon-base ti tabler-mail', 'slug' => 'admin.email-templates.index'],
          ['url' => $routeUrl('admin.reviews.index', [], '/admin/resenas'), 'name' => 'Resenas', 'icon' => 'menu-icon icon-base ti tabler-message-star', 'slug' => 'admin.reviews.index'],
          ['url' => $routeUrl('admin.profile-verifications.index', [], '/admin/verificaciones-perfil'), 'name' => 'Verificaciones', 'icon' => 'menu-icon icon-base ti tabler-shield-check', 'slug' => 'admin.profile-verifications.index'],
          ['url' => $routeUrl('admin.internal-users.index', [], '/admin/internal-users'), 'name' => 'Equipo Interno', 'icon' => 'menu-icon icon-base ti tabler-settings', 'slug' => 'admin.internal-users.index'],
          ['url' => $routeUrl('admin.system-settings.edit', [], '/admin/configuracion-sistema'), 'name' => 'Configuracion', 'icon' => 'menu-icon icon-base ti tabler-adjustments', 'slug' => 'admin.system-settings.edit'],
          [
            'name' => 'SEO',
            'icon' => 'menu-icon icon-base ti tabler-world-search',
            'slug' => [
              'admin.seo-settings',
              'admin.seo-ai',
              'admin.seo-pages',
            ],
            'submenu' => [
              ['url' => $routeUrl('admin.seo-settings.edit', [], '/admin/seo/configuracion'), 'name' => 'Configuracion SEO', 'slug' => 'admin.seo-settings.edit'],
              ['url' => $routeUrl('admin.seo-ai.edit', [], '/admin/seo/ia'), 'name' => 'IA SEO', 'slug' => 'admin.seo-ai.edit'],
              ['url' => $routeUrl('admin.seo-pages.index', [], '/admin/seo/paginas'), 'name' => 'Paginas SEO', 'slug' => 'admin.seo-pages.index'],
            ],
          ],
          ['url' => $routeUrl('admin.blog-posts.index', [], '/admin/blog-posts'), 'name' => 'Blog y Recursos', 'icon' => 'menu-icon icon-base ti tabler-notebook', 'slug' => 'admin.blog-posts'],
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
              ['url' => $routeUrl('admin.catalog-options.index', ['catalog' => 'event-types'], '/admin/catalogos/event-types'), 'name' => 'Tipos de evento', 'slug' => 'admin.catalog-options.index'],
              ['url' => $routeUrl('admin.catalog-options.index', ['catalog' => 'service-types'], '/admin/catalogos/service-types'), 'name' => 'Tipos de servicio', 'slug' => 'admin.catalog-options.index'],
              ['url' => $routeUrl('admin.catalog-options.index', ['catalog' => 'group-sizes'], '/admin/catalogos/group-sizes'), 'name' => 'Tamanos de grupo', 'slug' => 'admin.catalog-options.index'],
              ['url' => $routeUrl('admin.catalog-options.index', ['catalog' => 'budget-ranges'], '/admin/catalogos/budget-ranges'), 'name' => 'Presupuestos', 'slug' => 'admin.catalog-options.index'],
              ['url' => $routeUrl('admin.marketplace-cities.index', [], '/admin/catalogos-ciudades'), 'name' => 'Ciudades', 'slug' => 'admin.marketplace-cities.index'],
              ['url' => $routeUrl('admin.marketplace-zones.index', [], '/admin/catalogos-zonas'), 'name' => 'Zonas y barrios', 'slug' => 'admin.marketplace-zones.index'],
              ['url' => $routeUrl('admin.catalog-suggestions.index', [], '/admin/catalogos-sugerencias'), 'name' => 'Sugerencias pendientes', 'slug' => 'admin.catalog-suggestions.index'],
            ],
          ],
        ],
        User::ROLE_STAFF => [
          ['url' => $routeUrl('staff.dashboard', [], '/staff/dashboard'), 'name' => 'Panel Interno', 'icon' => 'menu-icon icon-base ti tabler-users-group', 'slug' => 'staff.dashboard'],
        ],
        User::ROLE_MARIACHI => [
          ['url' => $routeUrl('mariachi.metrics', [], '/metricas'), 'name' => 'Metricas', 'icon' => 'menu-icon icon-base ti tabler-chart-bar', 'slug' => 'mariachi.metrics'],
          ['url' => $routeUrl('mariachi.listings.index', [], '/anuncios'), 'name' => 'Anuncios', 'icon' => 'menu-icon icon-base ti tabler-speakerphone', 'slug' => 'mariachi.listings.index'],
          ['url' => $routeUrl('mariachi.quotes.index', [], '/solicitudes'), 'name' => 'Solicitudes', 'icon' => 'menu-icon icon-base ti tabler-message-circle', 'slug' => 'mariachi.quotes'],
          ['url' => $routeUrl('mariachi.reviews.index', [], '/opiniones'), 'name' => 'Opiniones', 'icon' => 'menu-icon icon-base ti tabler-star', 'slug' => 'mariachi.reviews.index'],
        ],
        User::ROLE_CLIENT => [
          ['url' => $routeUrl('client.dashboard', [], '/mi-cuenta/solicitudes'), 'name' => 'Panel Cliente', 'icon' => 'menu-icon icon-base ti tabler-user-heart', 'slug' => 'client.dashboard'],
        ],
        default => $guestMenu,
      };

      $menu = (object) ['menu' => array_map($castMenuItem, array_merge($baseMenu, $roleMenu))];
      $horizontalMenu = (object) ['menu' => array_map($castMenuItem, array_merge($baseMenu, $roleMenu))];

      $view->with('menuData', [$menu, $horizontalMenu]);
    });
  }
}
