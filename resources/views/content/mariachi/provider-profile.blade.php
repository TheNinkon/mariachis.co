@extends('layouts/layoutMaster')

@section('title', 'Perfil del proveedor')

@section('content')
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-4">
      <div>
        <h5 class="mb-1">Perfil del proveedor</h5>
        <p class="mb-1">Verificacion: <span class="badge bg-label-{{ $profile->verification_status === 'verified' ? 'success' : 'warning' }}">{{ $profile->verification_status }}</span></p>
        <small class="text-muted">Este perfil concentra informacion general del grupo. El nombre de marca se usa para crear tu URL pública tipo <strong>/@handle</strong>.</small>
      </div>
      <div class="text-end">
        @if (Route::has('mariachi.provider.public.show') && filled($profile->slug))
          <a href="{{ route('mariachi.provider.public.show', ['handle' => $profile->slug]) }}" target="_blank" class="btn btn-outline-secondary">Ver perfil publico</a>
        @endif
        <a href="{{ route('mariachi.verification.edit') }}" class="btn btn-outline-primary">Verificacion</a>
        <a href="{{ route('mariachi.listings.index') }}" class="btn btn-primary">Gestionar anuncios</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="mb-0">Datos generales del proveedor</h5></div>
    <div class="card-body">
      <form method="POST" action="{{ route('mariachi.provider-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Nombre del grupo / marca</label>
            <input class="form-control" name="business_name" value="{{ old('business_name', $profile->business_name) }}" required />
            <small class="text-muted d-block mt-1">Se convertirá en tu URL pública de marca. Ejemplo: <code>/@{{ $profile->slug ?: 'mariachi-vargas-de-bogota' }}</code></small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ciudad principal</label>
            <input class="form-control" name="city_name" value="{{ old('city_name', $profile->city_name) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Nombre del responsable</label>
            <input class="form-control" name="responsible_name" value="{{ old('responsible_name', $profile->responsible_name) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Correo</label>
            <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefono</label>
            <input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" />
          </div>
          <div class="col-12">
            <label class="form-label">Descripcion corta</label>
            <textarea class="form-control" name="short_description" rows="3" maxlength="280" required>{{ old('short_description', $profile->short_description) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Sitio web</label>
            <input type="url" class="form-control" name="website" value="{{ old('website', $profile->website) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Instagram</label>
            <input type="url" class="form-control" name="instagram" value="{{ old('instagram', $profile->instagram) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Facebook</label>
            <input type="url" class="form-control" name="facebook" value="{{ old('facebook', $profile->facebook) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">TikTok</label>
            <input type="url" class="form-control" name="tiktok" value="{{ old('tiktok', $profile->tiktok) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">YouTube</label>
            <input type="url" class="form-control" name="youtube" value="{{ old('youtube', $profile->youtube) }}" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Logo / imagen institucional</label>
            <input type="file" class="form-control" name="logo" accept="image/*" />
            @if($profile->logo_path)
              <img src="{{ asset('storage/'.$profile->logo_path) }}" alt="Logo proveedor" class="img-fluid rounded mt-2 border" style="max-height:96px;" />
            @endif
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button class="btn btn-primary" type="submit">Guardar perfil del proveedor</button>
          <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-primary">Ir a anuncios</a>
        </div>
      </form>
    </div>
  </div>
@endsection
