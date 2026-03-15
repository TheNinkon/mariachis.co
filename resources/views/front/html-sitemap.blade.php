@extends('front.layouts.marketplace')

@section('title', $pageTitle.' | Mariachis.co')
@section('body_page', 'html-sitemap')

@section('content')
  <main class="bg-[#fcfaf7] pb-20 pt-8 md:pt-12">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 md:px-8">
      <section class="overflow-hidden rounded-[2rem] border border-brand-100/80 bg-white shadow-soft">
        <div class="grid gap-6 px-6 py-8 md:grid-cols-[1.2fr_0.8fr] md:px-10 md:py-10">
          <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.34em] text-brand-700">{{ $pageLabel }}</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-black tracking-[-0.04em] text-slate-950 md:text-5xl">
              {{ $pageTitle }}
            </h1>
            <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
              Usa este hub para descubrir las mejores URLs vivas del marketplace: ciudades con oferta real, eventos con masa crítica, zonas activas y recursos útiles para clientes y mariachis.
            </p>
          </div>

          <div class="grid gap-4 rounded-[1.75rem] bg-gradient-to-br from-brand-50 via-white to-amber-50 p-5">
            <div class="rounded-[1.4rem] border border-white/80 bg-white/80 px-5 py-4">
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Criterio editorial</p>
              <p class="mt-2 text-sm leading-6 text-slate-600">
                Solo enlazamos hubs con oferta suficiente, diversidad mínima de perfiles y utilidad real para exploración local.
              </p>
            </div>
            <div class="rounded-[1.4rem] border border-white/80 bg-white/80 px-5 py-4">
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Escalable</p>
              <p class="mt-2 text-sm leading-6 text-slate-600">
                El hub está pensado para crecer sin convertir el footer ni el sitemap en una pared infinita de enlaces.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Ciudades principales</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Dónde ya hay oferta sólida</h2>
            </div>
            <span class="rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
              {{ $cities->count() }} hubs
            </span>
          </div>

          @if($cities->isNotEmpty())
            <div class="mt-6 grid gap-3 md:grid-cols-2">
              @foreach($cities as $city)
                <a href="{{ $city['url'] }}" class="group rounded-[1.5rem] border border-slate-200 bg-slate-50/70 px-5 py-4 transition hover:-translate-y-0.5 hover:border-brand-200 hover:bg-brand-50/70">
                  <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-extrabold text-slate-900 transition group-hover:text-brand-700">{{ $city['name'] }}</h3>
                    <span class="text-sm font-bold text-brand-700">Ver</span>
                  </div>
                  <p class="mt-2 text-sm text-slate-500">{{ $city['listings_count'] }} anuncios · {{ $city['profiles_count'] }} perfiles</p>
                </a>
              @endforeach
            </div>
          @else
            <p class="mt-6 text-sm text-slate-500">Seguimos consolidando ciudades con suficiente oferta para destacar aquí.</p>
          @endif
        </article>

        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Eventos principales</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Landings nacionales que ya tienen tracción</h2>
            </div>
            <span class="rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
              {{ $events->count() }} eventos
            </span>
          </div>

          @if($events->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-3">
              @foreach($events as $event)
                <a href="{{ $event['url'] }}" class="inline-flex min-w-[13rem] flex-col rounded-[1.4rem] border border-slate-200 bg-slate-50/70 px-4 py-4 transition hover:border-brand-200 hover:bg-brand-50/70">
                  <span class="text-sm font-extrabold text-slate-900">{{ $event['name'] }}</span>
                  <span class="mt-1 text-xs text-slate-500">{{ $event['listings_count'] }} anuncios · {{ $event['cities_count'] }} ciudades</span>
                </a>
              @endforeach
            </div>
          @else
            <p class="mt-6 text-sm text-slate-500">Todavía no hay eventos con cobertura nacional suficiente para destacar aquí.</p>
          @endif
        </article>
      </section>

      <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Zonas destacadas</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Barrios y zonas con masa crítica real</h2>
            </div>
          </div>

          @if($zonesByCity->isNotEmpty())
            <div class="mt-6 grid gap-4">
              @foreach($zonesByCity as $cityGroup)
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                  <div class="flex items-center justify-between gap-4">
                    <h3 class="text-base font-extrabold text-slate-900">{{ $cityGroup['city_name'] }}</h3>
                    <a href="{{ $cityGroup['city_url'] }}" class="text-sm font-bold text-brand-700">Ver ciudad</a>
                  </div>
                  <div class="mt-4 flex flex-wrap gap-2.5">
                    @foreach($cityGroup['zones'] as $zone)
                      <a href="{{ $zone['url'] }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700">
                        <span>{{ $zone['zone_name'] }}</span>
                        <span class="text-xs text-slate-400">{{ $zone['listings_count'] }}</span>
                      </a>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="mt-6 text-sm text-slate-500">Aún no hay suficientes zonas activas con densidad y diversidad para recomendarlas aquí.</p>
          @endif
        </article>

        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Ciudad + evento</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Hubs locales especialmente útiles</h2>
            </div>
          </div>

          @if($cityEventLandings->isNotEmpty())
            <div class="mt-6 grid gap-3">
              @foreach($cityEventLandings as $landing)
                <a href="{{ $landing['url'] }}" class="rounded-[1.4rem] border border-slate-200 bg-slate-50/70 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/70">
                  <div class="flex items-center justify-between gap-4">
                    <div>
                      <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">{{ $landing['city_name'] }}</p>
                      <h3 class="mt-1 text-base font-extrabold text-slate-900">{{ $landing['event_name'] }}</h3>
                    </div>
                    <span class="text-sm font-bold text-brand-700">{{ $landing['listings_count'] }} anuncios</span>
                  </div>
                </a>
              @endforeach
            </div>
          @else
            <p class="mt-6 text-sm text-slate-500">Cuando varias ciudades tengan suficiente oferta por evento, aparecerán aquí.</p>
          @endif
        </article>
      </section>

      <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Servicios principales</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Bloque secundario de exploración</h2>
            </div>
          </div>

          @if($services->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-3">
              @foreach($services as $service)
                <a href="{{ $service['url'] }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700">
                  <span>{{ $service['name'] }}</span>
                  <span class="text-xs text-slate-400">{{ $service['cities_count'] }} ciudades</span>
                </a>
              @endforeach
            </div>
          @else
            <p class="mt-6 text-sm text-slate-500">Los servicios se muestran aquí solo cuando tienen masa crítica suficiente y aportan valor real al enlazado interno.</p>
          @endif
        </article>

        <div class="grid gap-6 md:grid-cols-2">
          <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
            <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Recursos</p>
            <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Contenido y ayuda útil</h2>
            <div class="mt-6 grid gap-3">
              @foreach($resources as $resource)
                <a href="{{ $resource['url'] }}" class="rounded-[1.35rem] border border-slate-200 bg-slate-50/70 px-5 py-4 transition hover:border-brand-200 hover:bg-brand-50/70">
                  <h3 class="text-base font-extrabold text-slate-900">{{ $resource['label'] }}</h3>
                  <p class="mt-1 text-sm leading-6 text-slate-500">{{ $resource['description'] }}</p>
                </a>
              @endforeach
            </div>
          </article>

          <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft md:p-8">
            <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-brand-700">Para mariachis</p>
            <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-slate-950">Entradas clave del portal partner</h2>
            <div class="mt-6 grid gap-3">
              @foreach($partnerLinks as $link)
                <a href="{{ $link['url'] }}" class="rounded-[1.35rem] border border-slate-200 bg-gradient-to-br from-brand-50 via-white to-white px-5 py-4 transition hover:border-brand-200 hover:shadow-soft">
                  <h3 class="text-base font-extrabold text-slate-900">{{ $link['label'] }}</h3>
                  <p class="mt-1 text-sm leading-6 text-slate-500">{{ $link['description'] }}</p>
                </a>
              @endforeach
            </div>
          </article>
        </div>
      </section>
    </div>
  </main>
@endsection
