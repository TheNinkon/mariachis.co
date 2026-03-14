@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$isActivationStep = ($step ?? 'register') === 'activation';
$registerErrors = $errors->hasAny([
  'register',
  'first_name',
  'last_name',
  'email',
  'phone_country_iso2',
  'phone_number',
  'password',
  'password_confirmation',
  'terms',
]);
$activationErrors = $errors->hasAny([
  'activation',
  'proof_image',
  'reference_text',
]);
$activationPlan ??= null;
$activationPayment ??= null;
$activationUser ??= null;
$activationToken ??= null;
$nequi ??= ['phone' => '', 'beneficiary_name' => '', 'qr_image_url' => null, 'is_configured' => false];
$activationPaymentStatus = $activationPayment?->status;
$canSubmitActivation = $isActivationStep
  && $activationUser
  && $activationUser->status !== \App\Models\User::STATUS_ACTIVE
  && $activationPlan
  && $nequi['is_configured']
  && $activationPaymentStatus !== \App\Models\AccountActivationPayment::STATUS_PENDING_REVIEW;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Registro Mariachi')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  .partner-signup-cover {
    min-height: 100vh;
  }

  .partner-signup-cover .authentication-inner {
    min-height: 100vh;
    height: auto;
    align-items: stretch;
  }

  .partner-signup-cover .authentication-inner > [class*='col-'] {
    min-height: 100vh;
  }

  .partner-signup-hero {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid rgba(75, 70, 92, 0.08);
    background:
      radial-gradient(circle at top left, rgba(0, 86, 59, 0.18), transparent 42%),
      radial-gradient(circle at bottom right, rgba(0, 86, 59, 0.12), transparent 36%),
      linear-gradient(160deg, rgba(244, 249, 246, 0.96), rgba(255, 255, 255, 0.98));
    padding-top: 6rem !important;
    padding-bottom: 3rem !important;
  }

  .partner-signup-hero::before,
  .partner-signup-hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    background: rgba(0, 86, 59, 0.06);
    pointer-events: none;
  }

  .partner-signup-hero::before {
    width: 18rem;
    height: 18rem;
    top: -4rem;
    right: -7rem;
  }

  .partner-signup-hero::after {
    width: 12rem;
    height: 12rem;
    bottom: -3rem;
    left: -4rem;
  }

  .partner-signup-hero__card {
    position: relative;
    z-index: 1;
    width: min(100%, 30rem);
    border: 1px solid rgba(75, 70, 92, 0.08);
    border-radius: 1.5rem;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(14px);
    box-shadow: 0 1.5rem 3rem -2rem rgba(15, 23, 42, 0.24);
    display: grid;
    gap: 1.35rem;
  }

  .partner-signup-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 999px;
    background: rgba(0, 86, 59, 0.08);
    color: #00563b;
    font-weight: 700;
    font-size: 0.82rem;
    padding: 0.55rem 0.9rem;
  }

  .partner-signup-hero__title {
    font-size: clamp(2rem, 3vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.03em;
    margin: 0;
    color: #2f2b3d;
  }

  .partner-signup-hero__lead {
    margin: 0.9rem 0 0;
    color: #6d6b77;
    font-size: 1rem;
    line-height: 1.65;
    max-width: 26rem;
  }

  .partner-signup-hero__mockup {
    display: grid;
    gap: 0.75rem;
  }

  .partner-signup-hero__mockup-card {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 0.85rem;
    padding: 0.9rem 1rem;
    border-radius: 1.1rem;
    border: 1px solid rgba(75, 70, 92, 0.08);
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 1rem 1.8rem -1.6rem rgba(15, 23, 42, 0.34);
  }

  .partner-signup-hero__mockup-card.is-primary {
    background: linear-gradient(135deg, rgba(0, 86, 59, 0.12), rgba(255, 255, 255, 0.98));
    border-color: rgba(0, 86, 59, 0.14);
  }

  .partner-signup-hero__mockup-icon {
    display: inline-flex !important;
    width: 2.5rem;
    height: 2.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.85rem;
    background: linear-gradient(135deg, #0a6a4b, #0f8d66);
    color: #fff;
    flex: 0 0 auto;
    box-shadow: 0 0.8rem 1.4rem -1rem rgba(0, 86, 59, 0.65);
  }

  .partner-signup-hero__mockup-icon svg {
    width: 1.3rem;
    height: 1.3rem;
    stroke: currentColor;
    stroke-width: 2.15;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
    display: block;
    overflow: visible;
  }

  .partner-signup-hero__mockup-copy strong,
  .partner-signup-hero__mockup-copy span {
    display: block;
  }

  .partner-signup-hero__mockup-copy strong {
    color: #2f2b3d;
    font-size: 1rem;
    line-height: 1.15;
    margin-bottom: 0.15rem;
  }

  .partner-signup-hero__mockup-copy span {
    color: #6d6b77;
    font-size: 0.83rem;
    line-height: 1.35;
  }

  .partner-signup-main {
    padding-top: 6rem !important;
    padding-bottom: 3rem !important;
  }

  .partner-signup-steps {
    border: none;
    box-shadow: none;
  }

  .partner-signup-steps .bs-stepper-header {
    border: 0;
    padding: 0;
    margin-bottom: 2rem;
  }

  .partner-signup-steps .step-trigger {
    padding: 0;
  }

  .partner-signup-steps .bs-stepper-circle {
    background: rgba(0, 86, 59, 0.12);
    color: #00563b;
  }

  .partner-signup-steps .step.active .bs-stepper-circle,
  .partner-signup-steps .step.crossed .bs-stepper-circle {
    background: #00563b;
    color: #fff;
  }

  .partner-signup-steps .line {
    color: rgba(75, 70, 92, 0.4);
  }

  .partner-signup-plan {
    border: 1px solid rgba(0, 86, 59, 0.12);
    border-radius: 1.35rem;
    background: linear-gradient(180deg, rgba(0, 86, 59, 0.04), rgba(255, 255, 255, 0.98));
    box-shadow: 0 1.5rem 3rem -2rem rgba(15, 23, 42, 0.2);
  }

  .partner-signup-plan__price {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    color: #00563b;
  }

  .partner-signup-summary {
    border: 1px solid rgba(75, 70, 92, 0.08);
    border-radius: 1.15rem;
    background: rgba(75, 70, 92, 0.03);
  }

  .partner-signup-qr {
    width: 100%;
    max-width: 220px;
    border-radius: 1rem;
    border: 1px solid rgba(75, 70, 92, 0.1);
    background: rgba(75, 70, 92, 0.03);
  }

  .partner-signup-qr-placeholder {
    min-height: 220px;
    border: 1px dashed rgba(75, 70, 92, 0.18);
    border-radius: 1rem;
    color: var(--bs-secondary-color);
  }

  .partner-signup-phone-select + .select2 .select2-selection--single {
    height: calc(2.75rem + 2px) !important;
    min-height: calc(2.75rem + 2px) !important;
    display: flex;
    align-items: center;
    border-radius: var(--bs-border-radius);
  }

  .partner-signup-phone-select + .select2 {
    width: 100% !important;
  }

  .partner-signup-phone-select + .select2 .select2-selection__rendered {
    display: flex !important;
    align-items: center;
    gap: 0.55rem;
    padding-left: 0.95rem !important;
    padding-right: 2rem !important;
    line-height: calc(2.75rem + 2px) !important;
  }

  .partner-signup-phone-select + .select2 .select2-selection__arrow {
    height: 100% !important;
    right: 0.6rem !important;
  }

  .partner-signup-phone-option {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
  }

  .partner-signup-phone-option__flag {
    font-size: 1rem;
    line-height: 1;
    flex: 0 0 auto;
  }

  .partner-signup-phone-option__dial {
    font-weight: 700;
    color: #2f2b3d;
    flex: 0 0 auto;
  }

  .partner-signup-phone-option__name {
    color: #6d6b77;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .partner-signup-phone-option--selected .partner-signup-phone-option__name {
    display: none;
  }

  .partner-signup-phone-row {
    display: flex;
    gap: 0.75rem;
  }

  .partner-signup-phone-row__country {
    flex: 0 0 8.25rem;
    max-width: 8.25rem;
  }

  .partner-signup-phone-row__number {
    flex: 1 1 auto;
    min-width: 0;
  }

  @media (max-width: 991.98px) {
    .partner-signup-cover .authentication-inner,
    .partner-signup-cover .authentication-inner > [class*='col-'] {
      min-height: auto;
    }

    .partner-signup-hero {
      min-height: auto;
      border-right: 0;
      border-bottom: 1px solid rgba(75, 70, 92, 0.08);
    }

    .partner-signup-main {
      padding-top: 6.5rem !important;
      padding-bottom: 2rem !important;
    }

    .partner-signup-steps .bs-stepper-header {
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .partner-signup-steps .line {
      display: none;
    }

    .partner-signup-phone-row {
      gap: 0.5rem;
    }

    .partner-signup-phone-row__country {
      flex-basis: 8rem;
      max-width: 8rem;
    }
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const phoneCountry = document.querySelector('.select2-phone-country');
    if (phoneCountry && window.jQuery && window.jQuery.fn.select2) {
      const getFlagEmoji = iso2 => {
        const normalized = String(iso2 || '').trim().toUpperCase();
        if (!/^[A-Z]{2}$/.test(normalized)) {
          return '🌐';
        }

        return String.fromCodePoint(...normalized.split('').map(char => 127397 + char.charCodeAt(0)));
      };

      const renderPhoneOption = option => {
        if (!option.id) {
          return option.text;
        }

        const element = option.element;
        const dialCode = element?.dataset?.dialCode || option.text || '';
        const countryName = element?.dataset?.countryName || '';
        const flag = getFlagEmoji(element?.dataset?.countryIso2 || option.id);
        const optionMarkup = document.createElement('span');

        optionMarkup.className = 'partner-signup-phone-option partner-signup-phone-option--selected';
        optionMarkup.innerHTML = `
          <span class="partner-signup-phone-option__flag">${flag}</span>
          <span class="partner-signup-phone-option__dial">${dialCode}</span>
        `;

        return optionMarkup;
      };

      window.jQuery(phoneCountry).select2({
        width: '100%',
        minimumResultsForSearch: 8,
        templateResult: option => renderPhoneOption(option),
        templateSelection: option => renderPhoneOption(option),
        escapeMarkup: markup => markup
      });
    }

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      button.addEventListener('click', function () {
        const targetId = button.getAttribute('aria-controls');
        const input = targetId ? document.getElementById(targetId) : null;
        if (!input) {
          return;
        }

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = button.querySelector('i');
        if (icon) {
          icon.className = isPassword ? 'icon-base ti tabler-eye' : 'icon-base ti tabler-eye-off';
        }
      });
    });

    const modalElement = document.getElementById('activationPaymentModal');
    if (modalElement && window.bootstrap && @json($isActivationStep)) {
      const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
      if (@json($activationErrors || old('proof_image') || old('reference_text'))) {
        modal.show();
      }
    }
  });
</script>
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover authentication-bg partner-signup-cover">
  <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
    <img src="{{ asset('marketplace/assets/logo-wordmark.png') }}" alt="Mariachis.co" style="max-height: 42px; width: auto;" />
  </a>

  <div class="authentication-inner row m-0">
    <div class="d-none d-lg-flex col-lg-4 align-items-center justify-content-center p-5 partner-signup-hero">
      <div class="partner-signup-hero__card p-5">
        <span class="partner-signup-hero__badge mb-4">
          <i class="icon-base ti tabler-shield-check icon-sm"></i>
          Cuenta partner
        </span>
        <div>
          <h2 class="partner-signup-hero__title">Abre tu cuenta y empieza a mostrar tu grupo</h2>
          <p class="partner-signup-hero__lead">
            Deja tus datos, activa tu acceso y cuando todo quede listo podrás entrar al panel para publicar tus anuncios.
          </p>
        </div>
        <div class="partner-signup-hero__mockup">
          <div class="partner-signup-hero__mockup-card is-primary">
            <span class="partner-signup-hero__mockup-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 12h3l8-6v12l-8-6H4z"></path>
                <path d="M17.5 9.5a4 4 0 0 1 0 5"></path>
                <path d="M19.5 7a7 7 0 0 1 0 10"></path>
              </svg>
            </span>
            <div class="partner-signup-hero__mockup-copy">
              <strong>Anuncios</strong>
              <span>Publica y organiza la presencia de tu grupo</span>
            </div>
          </div>
          <div class="partner-signup-hero__mockup-card">
            <span class="partner-signup-hero__mockup-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 17l-3 3v-12a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v5a4 4 0 0 1-4 4H7z"></path>
                <path d="M8.5 10h7"></path>
                <path d="M8.5 13.5h4.5"></path>
              </svg>
            </span>
            <div class="partner-signup-hero__mockup-copy">
              <strong>Solicitudes</strong>
              <span>Recibe contacto y mantén todo en un solo lugar</span>
            </div>
          </div>
          <div class="partner-signup-hero__mockup-card">
            <span class="partner-signup-hero__mockup-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8.5" r="3.25"></circle>
                <path d="M6.5 18a5.8 5.8 0 0 1 11 0"></path>
                <path d="M4 4h16v16H4z" opacity="0.18"></path>
              </svg>
            </span>
            <div class="partner-signup-hero__mockup-copy">
              <strong>Perfil público</strong>
              <span>Prepara tu cuenta para crecer dentro del marketplace</span>
            </div>
          </div>
        </div>
        <div class="small text-muted">Tu acceso se habilita cuando la activación quede aprobada.</div>
      </div>
    </div>

    <div class="d-flex col-lg-8 align-items-center justify-content-center authentication-bg p-5 partner-signup-main">
      <div class="w-100" style="max-width: 760px;">
        @if(session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if($registerErrors && ! $isActivationStep)
          <div class="alert alert-danger">
            <strong>No pudimos continuar con el registro.</strong>
            <ul class="mb-0 mt-2">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="bs-stepper partner-signup-steps border-none shadow-none">
          <div class="bs-stepper-header">
            <div class="step {{ $isActivationStep ? 'crossed' : 'active' }}">
              <button type="button" class="step-trigger" disabled>
                <span class="bs-stepper-circle"><i class="icon-base ti tabler-user icon-md"></i></span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Datos</span>
                  <span class="bs-stepper-subtitle">Cuenta y contacto</span>
                </span>
              </button>
            </div>
            <div class="line">
              <i class="icon-base ti tabler-chevron-right"></i>
            </div>
            <div class="step {{ $isActivationStep ? 'active' : '' }}">
              <button type="button" class="step-trigger" disabled>
                <span class="bs-stepper-circle"><i class="icon-base ti tabler-credit-card icon-md"></i></span>
                <span class="bs-stepper-label">
                  <span class="bs-stepper-title">Activacion</span>
                  <span class="bs-stepper-subtitle">Habilita tu acceso</span>
                </span>
              </button>
            </div>
          </div>

          <div class="card">
            <div class="card-body p-sm-8 p-5">
              @if(! $isActivationStep)
                <div class="content-header mb-6">
                  <h4 class="mb-1">Crea tu cuenta partner</h4>
                  <p class="mb-0 text-muted">Organiza tu perfil, tus anuncios y tus solicitudes en un solo lugar.</p>
                </div>

                <form action="{{ route('mariachi.register.store') }}" method="POST">
                  @csrf
                  <div class="row g-6">
                    <div class="col-sm-6 form-control-validation">
                      <label class="form-label" for="first_name">Nombre</label>
                      <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required />
                      @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6 form-control-validation">
                      <label class="form-label" for="last_name">Apellido</label>
                      <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" required />
                      @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6 form-control-validation">
                      <label class="form-label" for="email">Correo electronico</label>
                      <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required />
                      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6 form-control-validation">
                      <label class="form-label" for="phone_country_iso2">Telefono movil</label>
                      <div class="partner-signup-phone-row">
                        <div class="partner-signup-phone-row__country">
                          <select class="form-select select2-phone-country partner-signup-phone-select @error('phone_country_iso2') is-invalid @enderror" id="phone_country_iso2" name="phone_country_iso2" required>
                            @foreach($phoneCountryOptions as $countryOption)
                              <option
                                value="{{ $countryOption['iso2'] }}"
                                data-country-iso2="{{ $countryOption['iso2'] }}"
                                data-dial-code="{{ $countryOption['dial_code'] }}"
                                data-country-name="{{ $countryOption['name'] }}"
                                @selected(old('phone_country_iso2', $defaultPhoneCountryIso2) === $countryOption['iso2'])>
                                {{ $countryOption['dial_code'] }} {{ $countryOption['name'] }}
                              </option>
                            @endforeach
                          </select>
                          @error('phone_country_iso2')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="partner-signup-phone-row__number">
                          <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" placeholder="300 123 4567" inputmode="tel" required />
                          @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-6 form-password-toggle form-control-validation">
                      <label class="form-label" for="password">Contrasena</label>
                      <div class="input-group input-group-merge">
                        <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="************" required />
                        <button type="button" class="input-group-text cursor-pointer" aria-label="Mostrar u ocultar contrasena" aria-controls="password" data-password-toggle>
                          <i class="icon-base ti tabler-eye-off"></i>
                        </button>
                      </div>
                      @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-sm-6 form-password-toggle form-control-validation">
                      <label class="form-label" for="password_confirmation">Confirmar contrasena</label>
                      <div class="input-group input-group-merge">
                        <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="************" required />
                        <button type="button" class="input-group-text cursor-pointer" aria-label="Mostrar u ocultar contrasena" aria-controls="password_confirmation" data-password-toggle>
                          <i class="icon-base ti tabler-eye-off"></i>
                        </button>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="form-check mb-0">
                        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" required />
                        <label class="form-check-label" for="terms">Acepto terminos y condiciones</label>
                        @error('terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                      </div>
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                      <a href="{{ route('mariachi.login') }}" class="btn btn-label-secondary">Ya tengo cuenta</a>
                      <button class="btn btn-primary" type="submit">Continuar</button>
                    </div>
                  </div>
                </form>
              @else
                <div class="content-header mb-6">
                  <h4 class="mb-1">Activa tu acceso</h4>
                  <p class="mb-0 text-muted">Cuando la activación quede aprobada ya podrás entrar al panel partner.</p>
                </div>

                <div class="row g-6">
                  <div class="col-12">
                    <div class="partner-signup-summary p-4">
                      <div class="row g-3">
                        <div class="col-md-4">
                          <div class="small text-muted mb-1">Nombre</div>
                          <div class="fw-semibold">{{ $activationUser->display_name }}</div>
                        </div>
                        <div class="col-md-4">
                          <div class="small text-muted mb-1">Correo</div>
                          <div class="fw-semibold">{{ $activationUser->email }}</div>
                        </div>
                        <div class="col-md-4">
                          <div class="small text-muted mb-1">Telefono</div>
                          <div class="fw-semibold">{{ $activationUser->phone ?: '-' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  @if($activationUser->status === \App\Models\User::STATUS_ACTIVE)
                    <div class="col-12">
                      <div class="alert alert-success mb-0">
                        Tu cuenta ya esta activa. Ya puedes iniciar sesion en el panel partner.
                      </div>
                    </div>
                  @elseif($activationPaymentStatus === \App\Models\AccountActivationPayment::STATUS_PENDING_REVIEW)
                    <div class="col-12">
                      <div class="alert alert-warning mb-0">
                        Pago enviado. Tu comprobante esta en revision. En cuanto el admin lo apruebe, podras iniciar sesion.
                      </div>
                    </div>
                  @elseif($activationPaymentStatus === \App\Models\AccountActivationPayment::STATUS_REJECTED)
                    <div class="col-12">
                      <div class="alert alert-danger mb-0">
                        El ultimo comprobante fue rechazado. {{ $activationPayment->rejection_reason ?: 'Revisa la imagen y vuelve a enviarla.' }}
                      </div>
                    </div>
                  @elseif(! $activationPlan)
                    <div class="col-12">
                      <div class="alert alert-danger mb-0">
                        No hay un paquete de activacion disponible en este momento. Intenta mas tarde.
                      </div>
                    </div>
                  @elseif(! $nequi['is_configured'])
                    <div class="col-12">
                      <div class="alert alert-danger mb-0">
                        El pago por Nequi no esta configurado en este momento. Intenta mas tarde o contacta a soporte.
                      </div>
                    </div>
                  @endif

                  @if($activationErrors)
                    <div class="col-12">
                      <div class="alert alert-danger mb-0">
                        <strong>No pudimos recibir el comprobante.</strong>
                        <ul class="mb-0 mt-2">
                          @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                          @endforeach
                        </ul>
                      </div>
                    </div>
                  @endif

                  <div class="col-lg-7">
                      <div class="partner-signup-plan p-5 h-100 d-flex flex-column">
                      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                          <span class="badge bg-label-primary mb-2">Paquete inicial</span>
                          <h5 class="mb-1">{{ $activationPlan?->name ?: 'Activacion de cuenta' }}</h5>
                          <div class="text-muted small">Activa tu cuenta para empezar con el panel partner</div>
                        </div>
                        <span class="badge bg-label-success">One time</span>
                      </div>

                      <div class="partner-signup-plan__price mb-2">
                        ${{ number_format((int) ($activationPlan?->amount_cop ?? 0), 0, ',', '.') }}
                      </div>
                      <div class="text-muted small mb-4">COP</div>

                      <ul class="small text-muted ps-3 mb-4">
                        <li>Habilita tu acceso al panel partner</li>
                        <li>Te deja empezar con tus anuncios y tu perfil</li>
                        <li>Se revisa antes del primer ingreso</li>
                      </ul>

                      <button
                        type="button"
                        class="btn btn-primary mt-auto"
                        data-bs-toggle="modal"
                        data-bs-target="#activationPaymentModal"
                        @disabled(! $canSubmitActivation)>
                        Continuar con la activacion
                      </button>
                    </div>
                  </div>

                  <div class="col-lg-5">
                    <div class="card h-100 border shadow-none">
                      <div class="card-body">
                        <h6 class="mb-3">Resumen</h6>
                        <dl class="row mb-0 small">
                          <dt class="col-6 text-muted">Cuenta</dt>
                          <dd class="col-6 text-end">{{ $activationUser->status }}</dd>

                          <dt class="col-6 text-muted">Ultimo pago</dt>
                          <dd class="col-6 text-end">{{ $activationPayment?->statusLabel() ?: 'Sin registro' }}</dd>

                          <dt class="col-6 text-muted">Enviado</dt>
                          <dd class="col-6 text-end">{{ $activationPayment?->created_at?->format('Y-m-d H:i') ?: '-' }}</dd>

                          <dt class="col-6 text-muted">Activada</dt>
                          <dd class="col-6 text-end">{{ $activationUser->activation_paid_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                        </dl>

                        <div class="mt-4 d-flex gap-2 flex-wrap">
                          <a href="{{ route('mariachi.login') }}" class="btn btn-outline-primary btn-sm">Volver a login</a>
                          <a href="{{ route('mariachi.register') }}" class="btn btn-label-secondary btn-sm">Crear otra cuenta</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@if($isActivationStep && $activationUser)
  <div class="modal fade" id="activationPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('mariachi.activation.payments.nequi.store', ['user' => $activationUser->id, 'token' => $activationToken]) }}" enctype="multipart/form-data">
          @csrf

          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Completa tu activación</h5>
              <div class="text-muted small">
                {{ $activationPlan?->name ?: 'Activacion de cuenta' }} · ${{ number_format((int) ($activationPlan?->amount_cop ?? 0), 0, ',', '.') }} COP
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="row g-4">
              <div class="col-lg-5">
                <div class="bg-lighter rounded p-4 h-100">
                  <h6 class="mb-3">Datos de pago</h6>
                  <div class="small text-muted mb-2">Numero</div>
                  <div class="fw-semibold mb-3">{{ $nequi['phone'] ?: 'Sin configurar' }}</div>

                  <div class="small text-muted mb-2">Titular</div>
                  <div class="fw-semibold mb-4">{{ $nequi['beneficiary_name'] ?: 'Cuenta partner' }}</div>

                  @if($nequi['qr_image_url'])
                    <img src="{{ $nequi['qr_image_url'] }}" alt="QR Nequi" class="partner-signup-qr" />
                  @else
                    <div class="partner-signup-qr-placeholder d-flex align-items-center justify-content-center text-center p-4">
                      El admin aun no ha cargado un QR. Puedes pagar usando el telefono mostrado.
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-lg-7">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Comprobante de pago</label>
                    <input type="file" name="proof_image" class="form-control" accept="image/png,image/jpeg,image/webp" required />
                  </div>

                  <div class="col-12">
                    <label class="form-label">Referencia opcional</label>
                    <input type="text" name="reference_text" class="form-control" maxlength="120" placeholder="Ultimos digitos, hora o nota breve" value="{{ old('reference_text') }}" />
                  </div>

                  <div class="col-12">
                    <div class="alert alert-info mb-0">
                      Sube un comprobante claro y te avisaremos cuando tu acceso quede listo.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Enviar comprobante</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif
@endsection
