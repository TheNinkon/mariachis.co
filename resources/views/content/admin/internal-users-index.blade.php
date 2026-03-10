@extends('layouts/layoutMaster')

@section('title', 'Usuarios Internos')

@section('content')
<div class="row g-6">
  <div class="col-xl-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Crear usuario interno</h5></div>
      <div class="card-body">
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form action="{{ route('admin.internal-users.store') }}" method="POST">
          @csrf
          <div class="mb-4"><label class="form-label" for="first_name">Nombre</label><input class="form-control @error('first_name') is-invalid @enderror" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          <div class="mb-4"><label class="form-label" for="last_name">Apellido</label><input class="form-control @error('last_name') is-invalid @enderror" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          <div class="mb-4"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          <div class="mb-4"><label class="form-label" for="phone">Telefono</label><input class="form-control @error('phone') is-invalid @enderror" type="text" id="phone" name="phone" value="{{ old('phone') }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          <div class="mb-4"><label class="form-label" for="password">Contrasena</label><input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
          <div class="mb-4"><label class="form-label" for="password_confirmation">Confirmar contrasena</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required></div>
          <button class="btn btn-primary w-100" type="submit">Crear usuario</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de equipo interno</h5>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-label-primary">Volver</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
          <tr><th>Nombre</th><th>Email</th><th>Telefono</th><th>Estado</th><th>Accion</th></tr>
          </thead>
          <tbody>
          @forelse($staffUsers as $staff)
            <tr>
              <td>{{ $staff->display_name }}</td>
              <td>{{ $staff->email }}</td>
              <td>{{ $staff->phone ?? 'N/A' }}</td>
              <td><span class="badge bg-label-{{ $staff->status === 'active' ? 'success' : 'danger' }}">{{ $staff->status }}</span></td>
              <td>
                <form action="{{ route('admin.internal-users.toggle-status', $staff) }}" method="POST">
                  @csrf
                  @method('PATCH')
                  <button class="btn btn-sm btn-outline-{{ $staff->status === 'active' ? 'danger' : 'success' }}" type="submit">
                    {{ $staff->status === 'active' ? 'Desactivar' : 'Activar' }}
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center">No hay usuarios internos.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
