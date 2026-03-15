<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\Helpers\Helpers;

  $menuFixed =
      $configData['layout'] === 'vertical'
          ? $menuFixed ?? ''
          : ($configData['layout'] === 'front'
              ? ''
              : $configData['headerType']);
  $navbarType =
      $configData['layout'] === 'vertical'
          ? $configData['navbarType']
          : ($configData['layout'] === 'front'
              ? 'layout-navbar-fixed'
              : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

  // Get skin name from configData - only applies to admin layouts
  $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
  $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

  // Get semiDark value from configData - only applies to admin layouts
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);
  $portalLayoutClass = !empty($configData['isPartnerPanel']) ? 'partner-portal-layout' : '';

  // Generate primary color CSS if color is set
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }

@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }} {{ $portalLayoutClass }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}" data-framework="laravel" data-template="{{ $configData['layout'] }}-menu-template"
  data-bs-theme="{{ $configData['theme'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    @yield('title') | {{ config('variables.templateName') ?: 'Mariachis.co' }}
    - {{ config('variables.templateSuffix') ?: 'Plataforma de mariachis en Colombia' }}
  </title>
  <meta name="description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords"
    content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
  <meta property="og:title" content="{{ config('variables.ogTitle') ? config('variables.ogTitle') : '' }}" />
  <meta property="og:type" content="{{ config('variables.ogType') ? config('variables.ogType') : '' }}" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:image" content="{{ config('variables.ogImage') ? config('variables.ogImage') : '' }}" />
  <meta property="og:description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta property="og:site_name"
    content="{{ config('variables.creatorName') ? config('variables.creatorName') : '' }}" />
  <meta name="robots" content="noindex, nofollow" />
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ url()->current() }}" />
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
  <link rel="shortcut icon" href="{{ asset('marketplace/favicon.ico') }}" />
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />

  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  @if (
      $primaryColorCSS &&
          (config('custom.custom.primaryColor') ||
              isset($_COOKIE['admin-primaryColor']) ||
              isset($_COOKIE['front-primaryColor'])))
    <!-- Primary Color Style -->
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)

  @if (!empty($configData['isPartnerPanel']))
    <style>
      html.partner-portal-layout .layout-content-navbar .layout-navbar {
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      }

      html.partner-portal-layout .layout-content-navbar .content-wrapper {
        padding-bottom: 0 !important;
      }

      html.partner-portal-layout .partner-layout-fluid {
        max-width: none !important;
        padding-top: 0.5rem !important;
        padding-bottom: 0 !important;
        padding-inline: 0.5rem !important;
      }

      html.partner-portal-layout .partner-layout-fluid > .card:first-child,
      html.partner-portal-layout .partner-layout-fluid > .alert:first-child,
      html.partner-portal-layout .partner-layout-fluid > [class*='row']:first-child {
        margin-top: 0 !important;
      }

      @media (min-width: 992px) {
        html.partner-portal-layout .partner-layout-fluid {
          padding-top: 0.5rem !important;
          padding-inline: 0.75rem !important;
        }
      }
    </style>
  @endif
</head>

<body class="{{ $portalLayoutClass }}">
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  {{-- remove while creating package --}}
  {{-- remove while creating package end --}}

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)
</body>

</html>
