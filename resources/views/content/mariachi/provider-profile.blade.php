@extends('layouts/layoutMaster')

@section('title', 'Perfil del proveedor')

@section('content')
  @php
    $verificationBadgeClass = $profile->hasActiveVerification() ? 'success' : ($profile->verification_status === 'payment_pending' ? 'warning' : 'secondary');
    $verificationLabel = match ((string) $profile->verification_status) {
      'verified' => $profile->hasActiveVerification() ? 'Verificado' : 'Verificación vencida',
      'payment_pending' => 'Pago en revisión',
      'rejected' => 'Rechazado',
      default => 'Sin verificación',
    };
    $autoHandlePreview = \Illuminate\Support\Str::slug((string) (old('business_name', $profile->business_name ?: $user->display_name ?: 'mariachi')));
    $autoHandlePreview = $autoHandlePreview !== '' ? $autoHandlePreview : 'mariachi';
    $publicHandle = $profile->slug ?: $autoHandlePreview;
  @endphp

  @include('content.mariachi.partials.account-settings-nav')

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validación.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex align-items-start align-items-sm-center gap-6 flex-column flex-sm-row">
      <img
        src="{{ $profile->logo_path ? asset('storage/'.$profile->logo_path) : asset('assets/img/avatars/1.png') }}"
        alt="logo del proveedor"
        class="d-block rounded border"
        style="width:100px;height:100px;object-fit:cover;"
      />
      <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <h5 class="mb-0">{{ $profile->business_name ?: $user->display_name ?: 'Perfil del proveedor' }}</h5>
          <span class="badge bg-label-{{ $verificationBadgeClass }}">{{ $verificationLabel }}</span>
          @if($profile->slug_locked)
            <span class="badge bg-label-info">Handle premium activo</span>
          @endif
        </div>
        <p class="mb-1 text-muted">Gestiona aquí la información básica del grupo: nombre de marca, contacto, descripción, web, redes y logo institucional.</p>
        <div class="small text-muted">
          URL pública actual:
          <strong>/&#64;{{ $publicHandle }}</strong>
          @if(\Illuminate\Support\Facades\Route::has('mariachi.provider.public.show') && filled($profile->slug))
            · <a href="{{ route('mariachi.provider.public.show', ['handle' => $profile->slug]) }}" target="_blank" rel="noopener noreferrer">Ver perfil público</a>
          @endif
        </div>
        <div class="small text-muted mt-1">
          Preview automático desde el nombre:
          <code>/&#64;{{ $autoHandlePreview }}</code>.
          El handle premium solo se edita desde <a href="{{ route('mariachi.verification.edit') }}">Verificación</a>.
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Datos del proveedor</h5>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('mariachi.provider-profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="row gy-4 gx-6 mb-6">
          <div class="col-md-6">
            <label class="form-label">Nombre del grupo / marca</label>
            <input class="form-control" name="business_name" value="{{ old('business_name', $profile->business_name) }}" maxlength="140" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Correo</label>
            <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Teléfono</label>
            <input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" />
          </div>
          <div class="col-md-6">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" maxlength="30" />
          </div>
          <div class="col-12">
            <label class="form-label">Descripción corta</label>
            <textarea class="form-control" name="short_description" rows="3" maxlength="280" required>{{ old('short_description', $profile->short_description) }}</textarea>
            <div class="form-text">Resume lo esencial del grupo en una descripción breve y clara.</div>
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
            <label class="form-label">Logo institucional</label>
            <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp" />
            <div class="form-text">Sube una imagen clara del grupo o de tu marca.</div>
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit">Guardar cambios</button>
          <a href="{{ route('mariachi.verification.edit') }}" class="btn btn-outline-primary">Ir a verificación</a>
          <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-secondary">Gestionar anuncios</a>
        </div>
      </form>
    </div>
  </div>
@endsection
