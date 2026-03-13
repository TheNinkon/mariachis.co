@php
  $seo = $seo ?? [];
  $og = $seo['og'] ?? [];
  $twitter = $seo['twitter'] ?? [];
@endphp
<title>{{ $seo['title'] ?? 'Mariachis.co' }}</title>
<meta name="description" content="{{ $seo['description'] ?? 'Marketplace local para contratar mariachis en Colombia.' }}" />
<meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}" />
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}" />
<meta property="og:site_name" content="{{ $seo['site_name'] ?? 'Mariachis.co' }}" />
<meta property="og:title" content="{{ $og['title'] ?? ($seo['title'] ?? 'Mariachis.co') }}" />
<meta property="og:description" content="{{ $og['description'] ?? ($seo['description'] ?? '') }}" />
<meta property="og:image" content="{{ $og['image'] ?? asset('marketplace/assets/logo-wordmark.png') }}" />
<meta property="og:url" content="{{ $og['url'] ?? ($seo['canonical'] ?? url()->current()) }}" />
<meta property="og:type" content="{{ $og['type'] ?? 'website' }}" />
<meta name="twitter:card" content="{{ $twitter['card'] ?? 'summary_large_image' }}" />
@if(! empty($twitter['site']))
  <meta name="twitter:site" content="{{ $twitter['site'] }}" />
@endif
<meta name="twitter:title" content="{{ $twitter['title'] ?? ($seo['title'] ?? 'Mariachis.co') }}" />
<meta name="twitter:description" content="{{ $twitter['description'] ?? ($seo['description'] ?? '') }}" />
<meta name="twitter:image" content="{{ $twitter['image'] ?? ($og['image'] ?? asset('marketplace/assets/logo-wordmark.png')) }}" />
@if(! empty($seo['jsonld']))
  <script type="application/ld+json">{!! $seo['jsonld'] !!}</script>
@endif
