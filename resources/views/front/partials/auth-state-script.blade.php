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
  $footerEvents = \App\Models\EventType::query()
      ->active()
      ->select(['id', 'name', 'slug'])
      ->withCount([
          'mariachiListings as published_listings_count' => fn ($query) => $query->published(),
      ])
      ->get()
      ->filter(fn ($eventType) => (int) $eventType->published_listings_count > 0)
      ->sortByDesc('published_listings_count')
      ->take(5)
      ->map(fn ($eventType) => [
          'name' => $eventType->name,
          'slug' => $eventType->slug ?: \Illuminate\Support\Str::slug($eventType->name),
          'count' => (int) $eventType->published_listings_count,
      ])
      ->values();
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
    events: @json($footerEvents),
    urls: {
      signup: @json(route('mariachi.register'), JSON_UNESCAPED_SLASHES),
      partnerLogin: @json(\App\Support\PortalHosts::absoluteUrl(\App\Support\PortalHosts::partner(), '/login'), JSON_UNESCAPED_SLASHES),
      blog: @json(route('blog.index'), JSON_UNESCAPED_SLASHES),
      help: @json(route('static.help'), JSON_UNESCAPED_SLASHES),
      sitemap: @json(route('seo.html-sitemap'), JSON_UNESCAPED_SLASHES),
    },
  };
</script>
