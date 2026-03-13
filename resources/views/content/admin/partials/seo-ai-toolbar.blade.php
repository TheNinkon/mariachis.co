<div
  class="rounded border bg-label-primary p-3"
  data-seo-ai-toolbar
  data-seo-ai-endpoint="{{ route('admin.seo-ai.generate') }}"
  data-seo-ai-type="{{ $type }}"
  data-seo-ai-language="{{ $language ?? 'es' }}"
  data-seo-ai-title-target="{{ $titleTarget }}"
  data-seo-ai-description-target="{{ $descriptionTarget }}"
  @isset($keywordsTarget)
    data-seo-ai-keywords-target="{{ $keywordsTarget }}"
  @endisset
  @isset($templateTarget)
    data-seo-ai-template-target="{{ $templateTarget }}"
  @endisset
  @isset($twitterTarget)
    data-seo-ai-twitter-target="{{ $twitterTarget }}"
  @endisset
  data-seo-ai-context='@json($context ?? [])'
  data-seo-ai-selectors='@json($selectors ?? [])'
>
  <div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-end">
    <div class="flex-grow-1">
      <label class="form-label mb-1" for="{{ $keywordsInputId ?? 'seo-ai-keywords' }}">Keywords objetivo</label>
      <input
        id="{{ $keywordsInputId ?? 'seo-ai-keywords' }}"
        type="text"
        class="form-control"
        data-seo-ai-keywords
        placeholder="{{ $keywordsPlaceholder ?? 'mariachis en Bogota, serenatas, bodas, eventos' }}"
      >
      <small class="text-muted d-block mt-1">{{ $help ?? 'Brief opcional para orientar la IA. Si ya tienes `keywords objetivo`, se usarán como base.' }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-primary" data-seo-ai-action="title">Generar titulo</button>
      <button type="button" class="btn btn-outline-primary" data-seo-ai-action="description">Generar descripcion</button>
      @isset($keywordsTarget)
        <button type="button" class="btn btn-outline-primary" data-seo-ai-action="keywords">Generar keywords sugeridas</button>
      @endisset
      <button type="button" class="btn btn-primary" data-seo-ai-action="all">Generar todo</button>
    </div>
  </div>
  <small class="text-muted d-block mt-2" data-seo-ai-status>Usa IA como primer borrador y ajusta el copy antes de guardar.</small>
</div>
