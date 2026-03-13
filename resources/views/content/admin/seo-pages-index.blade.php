@extends('layouts/layoutMaster')

@section('title', 'SEO · Páginas')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">SEO · Páginas representativas</h5>
        <small class="text-muted">Administra title, description, OG y canonical sin tocar Blade.</small>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.seo-ai.edit') }}" class="btn btn-outline-secondary">IA SEO</a>
        <a href="{{ route('admin.seo-settings.edit') }}" class="btn btn-outline-secondary">Configuración global</a>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Página</th>
              <th>Path</th>
              <th>Robots</th>
              <th>Personalizado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($pages as $page)
              @php
                $definition = $definitions[$page->key] ?? null;
                $label = $definition['label'] ?? $page->key;
                $hasCustomSeo = filled($page->title) || filled($page->meta_description) || filled($page->og_image) || filled($page->canonical_override) || filled($page->jsonld);
              @endphp
              <tr>
                <td>
                  <strong>{{ $label }}</strong>
                  <div class="text-muted small">{{ $page->key }}</div>
                </td>
                <td>{{ $page->path ?: 'Dinámica / sin path fijo' }}</td>
                <td><span class="badge bg-label-secondary">{{ $page->robots ?: ($definition['robots'] ?? 'index,follow') }}</span></td>
                <td>{{ $hasCustomSeo ? 'Sí' : 'No' }}</td>
                <td class="text-end">
                  <a href="{{ route('admin.seo-pages.edit', $page) }}" class="btn btn-sm btn-primary">Editar</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
