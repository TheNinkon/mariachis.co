@extends('layouts/layoutMaster')

@section('title', 'SEO · Editar página')

@section('page-script')
  @include('content.admin.partials.seo-ai-script')
@endsection

@section('content')
  @php
    $canonicalRuleContext = [
      'page_key' => $page->key,
      'path' => $page->path ?: ($definition['path'] ?? null),
    ];
    $canonicalRuleSelectors = [
      'title' => '#title',
      'canonical_override' => '#canonical_override',
    ];
    $jsonldRuleContext = [
      'page_key' => $page->key,
      'path' => $page->path ?: ($definition['path'] ?? null),
      'faq_items' => $page->key === 'help'
        ? [
            [
              'question' => '¿Cómo funciona Mariachis.co?',
              'answer' => 'Mariachis.co conecta clientes con grupos de mariachis y muestra perfiles, anuncios y recursos para facilitar la contratación.',
            ],
            [
              'question' => '¿Cómo contacto soporte?',
              'answer' => 'Puedes usar los formularios y canales de contacto visibles en el sitio o escribir al equipo administrador para revisión manual.',
            ],
          ]
        : [],
    ];
    $jsonldRuleSelectors = [
      'title' => '#title',
      'description' => '#meta_description',
      'canonical_override' => '#canonical_override',
    ];
  @endphp

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">SEO · {{ $definition['label'] ?? $page->key }}</h5>
      <small class="text-muted">{{ $page->path ?: 'Página dinámica / sin path fijo' }}</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.seo-pages.update', $page) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-12">
            @include('content.admin.partials.seo-ai-toolbar', [
              'type' => 'page',
              'titleTarget' => '#title',
              'descriptionTarget' => '#meta_description',
              'keywordsTarget' => '#keywords_target',
              'keywordsInputId' => 'seo-ai-page-keywords',
              'keywordsPlaceholder' => 'mariachis en colombia, terminos, privacidad, ayuda, home',
              'help' => 'Genera un borrador SEO a partir de la página, su path y los campos actuales.',
              'context' => [
                'page_key' => $page->key,
                'page_label' => $definition['label'] ?? $page->key,
                'path' => $page->path,
                'title_placeholder' => $definition['title'] ?? '',
                'description_placeholder' => $definition['meta_description'] ?? '',
              ],
              'selectors' => [
                'title' => '#title',
                'meta_description' => '#meta_description',
                'robots' => '#robots',
                'canonical_override' => '#canonical_override',
              ],
            ])
          </div>

          <div class="col-md-6">
            <label class="form-label" for="title">Meta title</label>
            <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" placeholder="{{ $definition['title'] ?? '' }}">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="robots">Robots</label>
            <select id="robots" name="robots" class="form-select @error('robots') is-invalid @enderror">
              <option value="">Usar default de la página</option>
              @foreach($robotsOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('robots', $page->robots) === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description" rows="4" class="form-control @error('meta_description') is-invalid @enderror" placeholder="{{ $definition['meta_description'] ?? '' }}">{{ old('meta_description', $page->meta_description) }}</textarea>
            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="keywords_target">Keywords objetivo</label>
            <input id="keywords_target" name="keywords_target" type="text" class="form-control @error('keywords_target') is-invalid @enderror" value="{{ old('keywords_target', $page->keywords_target) }}" placeholder="mariachis bogota, contratar mariachi, serenatas bogota">
            <small class="text-muted">Uso interno para enfoque editorial e IA. No se renderiza como meta keywords en frontend.</small>
            @error('keywords_target')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <div
              data-seo-rule-tool
              data-seo-rule-mode="canonical"
              data-seo-rule-type="page"
              data-seo-rule-endpoint="{{ route('admin.seo-tools.canonical') }}"
              data-seo-rule-field-target="#canonical_override"
              data-seo-rule-context='@json($canonicalRuleContext)'
              data-seo-rule-selectors='@json($canonicalRuleSelectors)'
            >
              <div class="d-flex justify-content-between align-items-center gap-3">
                <label class="form-label mb-0" for="canonical_override">Canonical override</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-seo-rule-action>Sugerir canonical</button>
              </div>
              <input id="canonical_override" name="canonical_override" type="url" class="form-control @error('canonical_override') is-invalid @enderror" value="{{ old('canonical_override', $page->canonical_override) }}" placeholder="https://...">
              <small class="text-muted d-block">Solo úsalo si la misma página existe con varias URLs.</small>
              <small class="text-muted d-block" data-seo-rule-status>La sugerencia usa la URL pública limpia, sin querystring.</small>
            </div>
            @error('canonical_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label" for="og_image">Imagen OG</label>
            <input id="og_image" name="og_image" type="file" class="form-control @error('og_image') is-invalid @enderror" accept="image/*">
            @error('og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($page->og_image)
              <div class="form-check form-switch mt-2">
                <input id="clear_og_image" class="form-check-input" type="checkbox" name="clear_og_image" value="1" {{ old('clear_og_image') ? 'checked' : '' }}>
                <label class="form-check-label" for="clear_og_image">Eliminar imagen actual</label>
              </div>
              <div class="mt-2">
                <img src="{{ asset('storage/'.$page->og_image) }}" alt="Imagen OG actual" class="rounded" style="max-height: 120px;">
              </div>
            @endif
          </div>

          <div class="col-12">
            <div
              data-seo-rule-tool
              data-seo-rule-mode="jsonld"
              data-seo-rule-type="page"
              data-seo-rule-endpoint="{{ route('admin.seo-tools.jsonld') }}"
              data-seo-rule-field-target="#jsonld"
              data-seo-rule-context='@json($jsonldRuleContext)'
              data-seo-rule-selectors='@json($jsonldRuleSelectors)'
            >
              <div class="d-flex justify-content-between align-items-center gap-3">
                <label class="form-label mb-0" for="jsonld">JSON-LD</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-seo-rule-action>Generar JSON-LD recomendado</button>
              </div>
              <textarea id="jsonld" name="jsonld" rows="8" class="form-control @error('jsonld') is-invalid @enderror" placeholder='{"@@context":"https://schema.org"}'>{{ old('jsonld', $page->jsonld) }}</textarea>
              <small class="text-muted d-block">Usa plantillas recomendadas por tipo de página. El textarea sigue funcionando como override avanzado.</small>
              <small class="text-muted d-block" data-seo-rule-status>Genera WebSite, WebPage, CollectionPage o FAQPage según el contexto.</small>
            </div>
            @error('jsonld')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Guardar SEO</button>
          <a href="{{ route('admin.seo-pages.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
      </form>
    </div>
  </div>
@endsection
