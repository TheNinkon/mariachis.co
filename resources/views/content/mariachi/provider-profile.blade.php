@extends('layouts/layoutMaster')

@section('title', 'Perfil del proveedor')

@section('page-style')
  <style>
    .provider-profile-public-card {
      overflow: hidden;
    }

    .provider-profile-public-cover {
      min-height: 240px;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: flex-end;
      padding: 1.5rem;
      background: linear-gradient(135deg, #d9efe7 0%, #eef6f3 44%, #f7f9fc 100%);
      isolation: isolate;
    }

    .provider-profile-public-cover::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(17, 24, 39, 0.04) 0%, rgba(17, 24, 39, 0.18) 100%);
      pointer-events: none;
    }

    .provider-profile-public-cover img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .provider-profile-public-cover__fallback {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #d9efe7 0%, #eef6f3 44%, #f7f9fc 100%);
    }

    .provider-profile-public-cover__hint {
      position: absolute;
      top: 1rem;
      right: 1rem;
      z-index: 2;
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.45rem 0.8rem;
      background: rgba(255, 255, 255, 0.92);
      color: #52627a;
      font-size: 0.78rem;
      font-weight: 700;
    }

    .provider-profile-public-summary {
      width: 100%;
      display: grid;
      grid-template-columns: 96px minmax(0, 1fr);
      gap: 1rem;
      align-items: end;
      position: relative;
      z-index: 2;
    }

    .provider-profile-public-avatar,
    .provider-profile-public-avatar .avatar,
    .provider-profile-public-avatar .avatar-initial,
    .provider-profile-public-avatar img {
      width: 96px;
      height: 96px;
    }

    .provider-profile-public-avatar img {
      display: block;
      object-fit: cover;
      border-radius: 1.25rem;
      border: 4px solid #fff;
      box-shadow: 0 18px 32px -22px rgba(17, 24, 39, 0.42);
      background: #fff;
    }

    .provider-profile-public-avatar .avatar .avatar-initial {
      border-radius: 1.25rem;
      border: 4px solid #fff;
      box-shadow: 0 18px 32px -22px rgba(17, 24, 39, 0.42);
    }

    .provider-profile-public-title {
      margin-bottom: 0.3rem;
    }

    .provider-profile-public-title h4,
    .provider-profile-public-title-input {
      margin: 0;
      color: #fff;
      font-size: clamp(1.75rem, 2.4vw, 2.3rem);
      line-height: 1.05;
      letter-spacing: -0.04em;
      font-weight: 700;
      text-shadow: 0 8px 24px rgba(17, 24, 39, 0.28);
    }

    .provider-profile-public-url {
      display: inline-flex;
      align-items: center;
    }

    .provider-profile-public-url a,
    .provider-profile-public-url strong {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 0.42rem 0.8rem;
      background: rgba(255, 255, 255, 0.94);
      color: #0f766e;
      font-size: 0.9rem;
      font-weight: 800;
      text-decoration: none;
      box-shadow: 0 18px 32px -24px rgba(17, 24, 39, 0.4);
    }

    .provider-profile-public-title-row {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      flex-wrap: wrap;
    }

    .provider-profile-public-title-btn {
      width: 2.25rem;
      height: 2.25rem;
      border: 0;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.9);
      color: #0f766e;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 18px 32px -24px rgba(17, 24, 39, 0.4);
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    .provider-profile-public-title-btn:hover {
      background: #fff;
      color: #0b5d58;
    }

    .provider-profile-public-title-btn svg {
      width: 1rem;
      height: 1rem;
    }

    .provider-profile-public-title-edit {
      display: none;
      align-items: center;
      gap: 0.65rem;
      flex-wrap: wrap;
    }

    .provider-profile-public-title-edit.is-visible {
      display: flex;
    }

    .provider-profile-public-title-display.is-hidden {
      display: none;
    }

    .provider-profile-public-title-input {
      min-width: min(32rem, 100%);
      max-width: 100%;
      padding: 0;
      border: 0;
      background: transparent;
      box-shadow: none;
      outline: none;
      appearance: none;
    }

    .provider-profile-public-title-input::placeholder {
      color: rgba(255, 255, 255, 0.78);
    }

    .provider-profile-public-title-error {
      display: none;
      margin-top: 0.45rem;
      color: #fff1f2;
      font-size: 0.82rem;
      font-weight: 700;
      text-shadow: 0 6px 18px rgba(17, 24, 39, 0.28);
    }

    .provider-profile-public-title-error.is-visible {
      display: block;
    }

    .provider-profile-upload-card {
      height: 100%;
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1rem;
      background: #fff;
      padding: 1rem;
    }

    .provider-profile-upload-card h6 {
      margin-bottom: 0.35rem;
    }

    .provider-profile-upload-card p {
      margin-bottom: 0.75rem;
      color: #6b7280;
    }

    @media (max-width: 767.98px) {
      .provider-profile-public-summary {
        grid-template-columns: 1fr;
        gap: 0.85rem;
      }

      .provider-profile-public-cover {
        min-height: 220px;
        padding: 1rem;
      }

      .provider-profile-public-avatar,
      .provider-profile-public-avatar .avatar,
      .provider-profile-public-avatar .avatar-initial,
      .provider-profile-public-avatar img {
        width: 84px;
        height: 84px;
      }
    }
  </style>
@endsection

@section('content')
  @php
    $publicHandle = $profile->slug ?: 'm-xxxxxxx';
    $displayName = $profile->avatarDisplayName();
    $avatarInitials = $profile->avatarInitials();
    $canManageProfilePhoto = $profile->canManageProfilePhoto();
    $showProfilePhoto = $profile->shouldShowProfilePhoto();
    $canManageProfileCover = $profile->canManageProfileCover();
    $showProfileCover = $profile->shouldShowProfileCover();
    $publicProfileUrl = \Illuminate\Support\Facades\Route::has('mariachi.provider.public.show') && filled($profile->slug)
      ? route('mariachi.provider.public.show', ['handle' => $profile->slug])
      : null;
  @endphp

  @include('content.mariachi.partials.account-settings-nav')

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

  <form method="POST" action="{{ route('mariachi.provider-profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="card mb-6 provider-profile-public-card">
      <div class="provider-profile-public-cover">
        @if($showProfileCover)
          <img src="{{ asset('storage/'.$profile->cover_path) }}" alt="Portada del perfil" />
        @else
          <div class="provider-profile-public-cover__fallback" aria-hidden="true"></div>
          <span class="provider-profile-public-cover__hint">Portada disponible con verificacion</span>
        @endif
        <div class="provider-profile-public-summary">
          <div class="provider-profile-public-avatar">
            @if($showProfilePhoto)
              <img src="{{ asset('storage/'.$profile->logo_path) }}" alt="Foto del proveedor" />
            @else
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-primary text-heading fw-bold fs-2">
                  {{ $avatarInitials }}
                </span>
              </div>
            @endif
          </div>

          <div class="pb-3">
            <div
              class="provider-profile-public-title"
              data-provider-name-editor
              data-update-url="{{ route('mariachi.provider-profile.update') }}"
              data-initial-name="{{ $displayName }}"
            >
              <div class="provider-profile-public-title-display" data-provider-name-display>
                <div class="provider-profile-public-title-row">
                  <h4 data-provider-name-text>{{ $displayName }}</h4>
                  <button type="button" class="provider-profile-public-title-btn" data-provider-name-edit-btn aria-label="Editar nombre">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="m4 20 4.5-1 9.5-9.5a2.1 2.1 0 1 0-3-3L5.5 16 4 20Z" />
                      <path d="m13.5 6.5 4 4" />
                    </svg>
                  </button>
                </div>
              </div>

              <div class="provider-profile-public-title-edit" data-provider-name-edit>
                <input
                  type="text"
                  class="provider-profile-public-title-input"
                  data-provider-name-input
                  value="{{ $displayName }}"
                  maxlength="140"
                  autocomplete="off"
                />
                <button type="button" class="provider-profile-public-title-btn" data-provider-name-save-btn aria-label="Guardar nombre">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M5 21h14a1 1 0 0 0 1-1V7.4a1 1 0 0 0-.3-.7l-2.4-2.4a1 1 0 0 0-.7-.3H5a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1Z" />
                    <path d="M8 21v-6h8v6" />
                    <path d="M8 4v5h7" />
                  </svg>
                </button>
              </div>

              <div class="provider-profile-public-title-error" data-provider-name-error></div>
            </div>

            <div class="small text-muted provider-profile-public-url">
              @if($publicProfileUrl)
                <a href="{{ $publicProfileUrl }}" rel="noopener noreferrer">&#64;{{ $publicHandle }}</a>
              @else
                <strong>&#64;{{ $publicHandle }}</strong>
              @endif
            </div>
          </div>
        </div>
      </div>

      @if($canManageProfilePhoto || $canManageProfileCover)
        <div class="card-body pt-4">
          <div class="row g-4 mt-1">
            @if($canManageProfilePhoto)
              <div class="{{ $canManageProfileCover ? 'col-md-6' : 'col-12' }}">
                <div class="provider-profile-upload-card">
                <label class="form-label fw-semibold mb-2">Cambiar foto</label>
                <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp" />
                </div>
              </div>
            @endif

            @if($canManageProfileCover)
              <div class="{{ $canManageProfilePhoto ? 'col-md-6' : 'col-12' }}">
                <div class="provider-profile-upload-card">
                <label class="form-label fw-semibold mb-2">Cambiar portada</label>
                <input type="file" class="form-control" name="cover" accept="image/png,image/jpeg,image/webp" />
                </div>
              </div>
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Datos del proveedor</h5>
      </div>
      <div class="card-body">
        <div class="row gy-4 gx-6 mb-6">
          <div class="col-md-6">
            <label class="form-label">Correo</label>
            <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Telefono</label>
            <input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" />
          </div>
          <div class="col-md-6">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" maxlength="30" />
          </div>
          <div class="col-12">
            <label class="form-label">Descripcion corta</label>
            <textarea class="form-control" name="short_description" rows="3" maxlength="280" required>{{ old('short_description', $profile->short_description) }}</textarea>
            <div class="form-text">Resume lo esencial del grupo en una descripcion breve y clara.</div>
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
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit">Guardar cambios</button>
          <a href="{{ route('mariachi.verification.edit') }}" class="btn btn-outline-primary">Ir a verificacion</a>
          <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-secondary">Gestionar anuncios</a>
        </div>
      </div>
    </div>
  </form>

  <script>
    (function () {
      const editor = document.querySelector('[data-provider-name-editor]');
      if (!editor) {
        return;
      }

      const display = editor.querySelector('[data-provider-name-display]');
      const text = editor.querySelector('[data-provider-name-text]');
      const edit = editor.querySelector('[data-provider-name-edit]');
      const input = editor.querySelector('[data-provider-name-input]');
      const editButton = editor.querySelector('[data-provider-name-edit-btn]');
      const saveButton = editor.querySelector('[data-provider-name-save-btn]');
      const error = editor.querySelector('[data-provider-name-error]');
      const initialName = editor.dataset.initialName || '';
      const updateUrl = editor.dataset.updateUrl || '';
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

      const setEditing = (isEditing) => {
        display.classList.toggle('is-hidden', isEditing);
        edit.classList.toggle('is-visible', isEditing);
        error.classList.remove('is-visible');
        error.textContent = '';

        if (isEditing) {
          input.focus();
          input.select();
        }
      };

      const setBusy = (isBusy) => {
        input.disabled = isBusy;
        saveButton.disabled = isBusy;
        editButton.disabled = isBusy;
      };

      const saveName = async () => {
        const businessName = input.value.trim();
        if (!businessName) {
          error.textContent = 'Escribe un nombre para continuar.';
          error.classList.add('is-visible');
          input.focus();
          return;
        }

        setBusy(true);

        try {
          const formData = new FormData();
          formData.append('_method', 'PATCH');
          formData.append('_token', csrfToken);
          formData.append('business_name', businessName);

          const response = await fetch(updateUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
          });

          const payload = await response.json().catch(() => ({}));
          if (!response.ok) {
            const message = payload?.errors?.business_name?.[0] || payload?.message || 'No se pudo guardar el nombre.';
            throw new Error(message);
          }

          const nextName = String(payload.business_name || businessName);
          text.textContent = nextName;
          input.value = nextName;
          editor.dataset.initialName = nextName;
          setEditing(false);
        } catch (err) {
          error.textContent = err instanceof Error ? err.message : 'No se pudo guardar el nombre.';
          error.classList.add('is-visible');
        } finally {
          setBusy(false);
        }
      };

      editButton.addEventListener('click', () => {
        input.value = text.textContent.trim() || initialName;
        setEditing(true);
      });

      saveButton.addEventListener('click', () => {
        void saveName();
      });

      input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          void saveName();
        }

        if (event.key === 'Escape') {
          input.value = editor.dataset.initialName || initialName;
          setEditing(false);
        }
      });
    })();
  </script>
@endsection
