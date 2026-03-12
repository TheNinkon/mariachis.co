<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Mariachis.co')</title>
    <meta name="description" content="@yield('meta_description', 'Encuentra mariachis por ciudad, compara perfiles y contacta por WhatsApp o llamada.')" />
    <base href="{{ asset('marketplace') }}/" />
    <link rel="icon" type="image/x-icon" href="{{ asset('marketplace/favicon.ico') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('marketplace/favicon-32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('marketplace/favicon-16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('marketplace/apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: "#f2fbf7",
                100: "#d9efe7",
                200: "#b8ded1",
                300: "#8ec6b0",
                400: "#4ea27f",
                500: "#006847",
                600: "#00563b",
                700: "#00472f",
                800: "#003422",
                900: "#02261a",
              },
            },
            fontFamily: {
              sans: ["Plus Jakarta Sans", "sans-serif"],
              display: ["Playfair Display", "serif"],
            },
            boxShadow: {
              soft: "0 24px 48px -30px rgba(15, 23, 42, 0.42)",
            },
          },
        },
      };
    </script>
    @stack('head')
    <link rel="stylesheet" href="assets/theme.css?v=20260312-marketplace-layout-v13" />
    @stack('styles')
  </head>
  <body data-page="@yield('body_page', 'marketplace')" class="@yield('body_class', 'font-sans text-slate-900 antialiased')">
    <div data-component="site-header"></div>

    @yield('content')

    <div data-component="site-footer"></div>

    @include('front.partials.auth-state-script')
    <script src="js/ui.js?v=20260311-brand-green-v2"></script>
    @stack('scripts')
  </body>
</html>
