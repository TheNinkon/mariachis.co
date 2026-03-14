@php
  $authUser = auth()->user();
  $isClientAuth = $authUser && $authUser->role === \App\Models\User::ROLE_CLIENT;
  $initials = '';
  if ($isClientAuth) {
      $first = trim((string) ($authUser->first_name ?? ''));
      $last = trim((string) ($authUser->last_name ?? ''));
      $parts = array_filter([$first, $last], fn ($value) => $value !== '');
      if (! empty($parts)) {
          $initials = collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
          $initials = mb_substr($initials, 0, 2);
      } else {
          $name = trim((string) ($authUser->name ?? 'Cliente'));
          $initials = mb_strtoupper(mb_substr($name, 0, 1));
      }
  }
@endphp
@php
  $footerCities = \App\Models\MariachiProfile::query()
      ->published()
      ->selectRaw('city_name, count(*) as total')
      ->whereNotNull('city_name')
      ->where('city_name', '!=', '')
      ->groupBy('city_name')
      ->orderByDesc('total')
      ->limit(5)
      ->get()
      ->map(fn ($row) => [
          'name' => $row->city_name,
          'slug' => \Illuminate\Support\Str::slug($row->city_name),
          'count' => (int) $row->total,
      ])
      ->values();
  $footerPrimaryCity = $footerCities->first();
  $footerPrimaryCityUrl = $footerPrimaryCity
      ? route('seo.landing.slug', ['slug' => $footerPrimaryCity['slug']])
      : route('home');
@endphp
<script>
  window.__MM_AUTH__ = {
    isAuthenticated: @json($isClientAuth),
    firstName: @json($isClientAuth ? ($authUser->first_name ?? '') : ''),
    lastName: @json($isClientAuth ? ($authUser->last_name ?? '') : ''),
    initials: @json($initials),
    dashboardUrl: @json(route('client.dashboard')),
    csrfToken: @json(csrf_token()),
  };
  window.__MM_FOOTER__ = {
    cities: @json($footerCities),
    urls: {
      signup: @json(route('mariachi.register')),
      city: @json($footerPrimaryCityUrl),
      blog: @json(route('blog.index')),
    },
  };
</script>
