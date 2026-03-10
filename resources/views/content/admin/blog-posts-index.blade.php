@extends('layouts/layoutMaster')

@section('title', 'Blog y Recursos')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Entradas del blog</h5>
      <small class="text-muted">Gestiona contenido SEO para ciudades, zonas y eventos.</small>
    </div>
    <a href="{{ route('admin.blog-posts.create') }}" class="btn btn-primary">Nueva entrada</a>
  </div>

  <div class="card-body">
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($posts->isEmpty())
      <div class="text-center py-8">
        <h6 class="mb-2">No hay entradas creadas</h6>
        <p class="text-muted mb-4">Crea la primera publicación para alimentar home y landings en próximas fases.</p>
        <a href="{{ route('admin.blog-posts.create') }}" class="btn btn-outline-primary">Crear entrada</a>
      </div>
    @else
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Título</th>
              <th>Estado</th>
              <th>Ciudades / Zonas</th>
              <th>Eventos</th>
              <th>Autor</th>
              <th>Actualizado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($posts as $post)
              <tr>
                <td>
                  <strong>{{ $post->title }}</strong>
                  <div class="text-muted small">/{{ $post->slug }}</div>
                </td>
                <td>
                  <span class="badge bg-label-{{ $post->status === 'published' ? 'success' : 'secondary' }}">
                    {{ $post->status === 'published' ? 'Publicado' : 'Borrador' }}
                  </span>
                </td>
                <td>
                  @if($post->cities->isNotEmpty())
                    <div>{{ $post->cities->pluck('name')->join(', ') }}</div>
                  @else
                    <div>—</div>
                  @endif
                  @if($post->zones->isNotEmpty())
                    <div class="text-muted small">{{ $post->zones->pluck('name')->join(', ') }}</div>
                  @endif
                </td>
                <td>{{ $post->eventTypes->isNotEmpty() ? $post->eventTypes->pluck('name')->join(', ') : '—' }}</td>
                <td>{{ $post->author?->display_name ?: 'Sin autor' }}</td>
                <td>{{ optional($post->updated_at)->format('Y-m-d H:i') }}</td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="{{ route('admin.blog-posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form action="{{ route('admin.blog-posts.destroy', $post) }}" method="POST" onsubmit="return confirm('¿Eliminar esta entrada?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $posts->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
