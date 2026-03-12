@php
  $searchCityOptions = collect($searchCityOptions ?? [])->values();
@endphp

<form data-search-form data-default-landing-slug="{{ $countryLandingSlug }}" class="hero-search-form hero-search-form--immersive hero-search-form--home-split">
  <div class="hero-search-grid hero-search-grid--immersive">
    <label class="hero-search-field-wrap hero-search-field-wrap--event" data-event-menu>
      <span class="hero-search-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="6.5"></circle><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 5 5"></path></svg>
      </span>
      <input
        type="text"
        name="keyword"
        placeholder="Buscar por nombre o por categoría"
        class="hero-field hero-field--immersive"
        data-event-input
        autocomplete="off"
        aria-haspopup="true"
        aria-expanded="false"
      />
      <input type="hidden" name="cat" value="" data-event-cat />
      <input type="hidden" name="cat_type" value="" data-event-cat-type />
      <div class="event-mega-menu hidden" data-event-dropdown>
        <div class="event-mega-menu-col">
          <p class="event-mega-menu-title">Tipo de evento</p>
          @forelse($eventTypes as $item)
            <button type="button" class="event-mega-item" data-event-option data-cat="{{ $item->slug ?: \Illuminate\Support\Str::slug($item->name) }}" data-cat-type="event" data-label="{{ $item->name }}"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></button>
          @empty
            <span class="event-mega-item is-empty"><span>⏳</span><span>Sin eventos publicados aún</span></span>
          @endforelse
        </div>
        <div class="event-mega-menu-col">
          <p class="event-mega-menu-title">Tipo de servicio</p>
          @forelse($serviceTypes as $item)
            <button type="button" class="event-mega-item" data-event-option data-cat="{{ $item->slug ?: \Illuminate\Support\Str::slug($item->name) }}" data-cat-type="service" data-label="{{ $item->name }}"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></button>
          @empty
            <span class="event-mega-item is-empty"><span>⏳</span><span>Sin servicios publicados aún</span></span>
          @endforelse
        </div>
        <div class="event-mega-menu-col">
          <p class="event-mega-menu-title">Tamaño del grupo</p>
          @forelse($groupSizeOptions as $item)
            <button type="button" class="event-mega-item" data-event-option data-cat="{{ $item->slug ?: \Illuminate\Support\Str::slug($item->name) }}" data-cat-type="group" data-label="{{ $item->name }}"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></button>
          @empty
            <span class="event-mega-item is-empty"><span>⏳</span><span>Sin tamaños publicados aún</span></span>
          @endforelse
          <p class="event-mega-menu-title event-mega-menu-title--sub">Presupuesto</p>
          @forelse($budgetRanges as $item)
            <button type="button" class="event-mega-item" data-event-option data-cat="{{ $item->slug ?: \Illuminate\Support\Str::slug($item->name) }}" data-cat-type="budget" data-label="{{ $item->name }}"><span><x-catalog-icon :name="$item->icon" class="h-4 w-4" /></span><span>{{ $item->name }}</span></button>
          @empty
            <span class="event-mega-item is-empty"><span>⏳</span><span>Sin rangos publicados aún</span></span>
          @endforelse
        </div>
      </div>
    </label>
    <label class="hero-search-field-wrap hero-search-field-wrap--city" data-city-menu>
      <span class="hero-search-inline-prefix">en</span>
      <input
        type="text"
        name="city"
        placeholder="Dónde"
        class="hero-field hero-field--immersive"
        data-city-input-menu
        autocomplete="off"
        aria-haspopup="true"
        aria-expanded="false"
      />
      <input type="hidden" name="zone" value="" data-city-zone />
      <div class="city-dropdown-menu hidden" data-city-dropdown>
        <div class="city-dropdown-tabs" role="tablist" aria-label="Ciudades disponibles">
          <button type="button" class="city-dropdown-tab active" data-city-tab="provincia" role="tab" aria-selected="true">Provincia</button>
          <button type="button" class="city-dropdown-tab" data-city-tab="internacional" role="tab" aria-selected="false">Internacional</button>
        </div>
        <div class="city-dropdown-panel active" data-city-panel="provincia" role="tabpanel">
          @forelse($searchCityOptions as $city)
            <div class="city-dropdown-tree">
              <button type="button" class="city-dropdown-item city-dropdown-item--city" data-city-option data-city-value="{{ $city['name'] }}" data-city-option-slug="{{ $city['slug'] }}" data-zone-slug="">
                <span>{{ $city['name'] }} <small>({{ $city['count'] }})</small></span>
                @if($city['zones']->isNotEmpty())
                  <span class="city-dropdown-arrow" data-city-expand-arrow aria-hidden="true">▾</span>
                @endif
              </button>

              @if($city['zones']->isNotEmpty())
                <div class="city-dropdown-children hidden" data-city-children>
                  <button type="button" class="city-dropdown-item city-dropdown-item--zone city-dropdown-item--zone-all" data-city-option data-city-value="{{ $city['name'] }}" data-city-option-slug="{{ $city['slug'] }}" data-zone-slug="" data-zone-label="Toda la zona">
                    Toda la zona
                  </button>
                  @foreach($city['zones'] as $zone)
                    <button type="button" class="city-dropdown-item city-dropdown-item--zone" data-city-option data-city-value="{{ $city['name'] }}" data-city-option-slug="{{ $city['slug'] }}" data-zone-slug="{{ $zone['slug'] }}" data-zone-label="{{ $zone['name'] }}">
                      {{ $zone['name'] }} <small>({{ $zone['count'] }})</small>
                    </button>
                  @endforeach
                </div>
              @endif
            </div>
          @empty
            <span class="city-dropdown-item is-empty">Aún no hay ciudades publicadas.</span>
          @endforelse
        </div>
        <div class="city-dropdown-panel" data-city-panel="internacional" role="tabpanel">
          <span class="city-dropdown-item is-empty">Próximamente zonas internacionales.</span>
        </div>
      </div>
    </label>
    <div class="hero-search-submit-wrap">
      <button type="submit" class="hero-search-btn hero-search-btn--immersive" aria-label="Buscar mariachis">Buscar</button>
    </div>
  </div>
</form>
