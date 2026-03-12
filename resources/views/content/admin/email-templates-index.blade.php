@extends('layouts/layoutMaster')

@section('title', 'Plantillas de correo')

@section('content')
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Plantillas</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $totalTemplates }}</h4>
              </div>
              <small class="mb-0">Gestionadas desde admin</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-mail icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Activas</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $activeTemplates }}</h4>
              </div>
              <small class="mb-0">Usando HTML editable</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-bolt icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Clientes</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $clientTemplates }}</h4>
              </div>
              <small class="mb-0">Acceso y onboarding</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base ti tabler-user-circle icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <span class="text-heading">Admin / Mariachi</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $adminTemplates + $mariachiTemplates }}</h4>
              </div>
              <small class="mb-0">Operación y próximos correos</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-layout-kanban icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-1">Plantillas de correo</h5>
        <p class="mb-0 text-body-secondary">Edita asunto, HTML y vista previa sin tocar Blade.</p>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead class="border-top">
          <tr>
            <th>Plantilla</th>
            <th>Clave</th>
            <th>Audiencia</th>
            <th>Estado</th>
            <th>Variables</th>
            <th class="text-end">Acción</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($templates as $template)
            <tr>
              <td>
                <div class="fw-semibold">{{ $template->name }}</div>
                <small class="text-body-secondary">{{ $template->description }}</small>
              </td>
              <td><code>{{ $template->key }}</code></td>
              <td>
                <span class="badge bg-label-secondary">{{ strtoupper($template->audience) }}</span>
              </td>
              <td>
                @if ($template->is_active)
                  <span class="badge bg-label-success">Activa</span>
                @else
                  <span class="badge bg-label-warning">Fallback Blade</span>
                @endif
              </td>
              <td>{{ count($template->variables_schema ?? []) }}</td>
              <td class="text-end">
                <a href="{{ route('admin.email-templates.edit', $template->key) }}" class="btn btn-sm btn-primary">
                  Editar y previsualizar
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
