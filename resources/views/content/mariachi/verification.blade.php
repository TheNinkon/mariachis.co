@extends('layouts/layoutMaster')

@section('title', 'Verificacion')

@section('page-style')
  <style>
    .verification-plan-card {
      position: relative;
      height: 100%;
      border: 1px solid rgba(34, 41, 47, 0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .verification-plan-card:hover {
      transform: translateY(-4px);
      border-color: rgba(105, 108, 255, 0.2);
      box-shadow: 0 1rem 2rem -1.4rem rgba(34, 41, 47, 0.28);
    }

    .verification-plan-card__price {
      font-size: 1.8rem;
      font-weight: 700;
      line-height: 1;
    }
  </style>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const planButtons = Array.from(document.querySelectorAll('[data-verification-plan-trigger]'));
      const planCodeInput = document.querySelector('[data-verification-plan-code]');
      const planNameTargets = Array.from(document.querySelectorAll('[data-verification-plan-name]'));
      const planPriceTargets = Array.from(document.querySelectorAll('[data-verification-plan-price]'));
      const planDurationTargets = Array.from(document.querySelectorAll('[data-verification-plan-duration]'));
      const modalElement = document.getElementById('verificationPaymentModal');

      if (!planButtons.length || !modalElement || !window.bootstrap) {
        return;
      }

      const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
      const formatter = new Intl.NumberFormat('es-CO');

      const syncPlan = button => {
        const planCode = button.dataset.planCode || '';
        const planName = button.dataset.planName || '';
        const planAmount = Number(button.dataset.planAmount || 0);
        const planDuration = button.dataset.planDuration || '';

        if (planCodeInput) {
          planCodeInput.value = planCode;
        }

        planNameTargets.forEach(node => {
          node.textContent = planName;
        });

        planPriceTargets.forEach(node => {
          node.textContent = `$${formatter.format(planAmount)} COP`;
        });

        planDurationTargets.forEach(node => {
          node.textContent = planDuration;
        });
      };

      planButtons.forEach(button => {
        button.addEventListener('click', function () {
          syncPlan(button);
          modal.show();
        });
      });

      syncPlan(planButtons[0]);

      @if(old('plan_code'))
        const oldPlanButton = planButtons.find(button => button.dataset.planCode === @json(old('plan_code')));
        if (oldPlanButton) {
          syncPlan(oldPlanButton);
          modal.show();
        }
      @endif
    });
  </script>
@endsection

@section('content')
  @php
    $statusMeta = match ((string) $profile->verification_status) {
      'verified' => $profile->hasActiveVerification()
        ? ['label' => 'Verificado', 'class' => 'success', 'description' => 'Tu insignia esta activa y ya puedes usar handle premium y foto de perfil.']
        : ['label' => 'Vencida', 'class' => 'warning', 'description' => 'Tu verificacion vencio. Compra una nueva vigencia para recuperar insignia, handle y foto.'],
      'payment_pending' => ['label' => 'En revision', 'class' => 'warning', 'description' => 'Tu pago y tus documentos estan en proceso. Si Wompi ya aprobo el cobro, ahora solo falta la revision manual del equipo.'],
      'rejected' => ['label' => 'Rechazada', 'class' => 'danger', 'description' => 'La ultima solicitud fue rechazada. Revisa el motivo y vuelve a intentarlo.'],
      default => ['label' => 'Sin verificacion', 'class' => 'secondary', 'description' => 'Activa la verificacion para obtener insignia, handle personalizado y foto de perfil.'],
    };
    $publicHandle = $profile->slug ?: 'm-xxxxxxx';
    $suggestedHandle = \Illuminate\Support\Str::slug((string) ($profile->business_name ?: auth()->user()?->display_name ?: 'tu-grupo')) ?: 'tu-grupo';
    $baseAmount = (int) (collect($verificationPlans)->min('amount_cop') ?? 0);
    $hasPendingVerificationRequest = $latestRequest?->status === \App\Models\VerificationRequest::STATUS_PENDING;
    $hasPendingCheckout = $hasPendingVerificationRequest && $latestPayment?->isPending();
    $hasApprovedPaymentAwaitingReview = $hasPendingVerificationRequest && $latestPayment?->status === \App\Models\ProfileVerificationPayment::STATUS_APPROVED;
    $purchaseLocked = ! $canSubmitVerification || ! $wompi['is_configured'] || $hasApprovedPaymentAwaitingReview;
    $lastReviewedBy = $latestRequest?->reviewedBy?->display_name ?? $latestPayment?->reviewedBy?->display_name;
    $verificationErrors = $errors->hasAny([
      'verification',
      'plan_code',
      'id_document',
      'identity_proof',
      'notes',
    ]);
  @endphp

  @include('content.mariachi.partials.account-settings-nav')

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any() && ! $verificationErrors)
    <div class="alert alert-danger">
      <strong>Hay errores de validacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-6 mb-6">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <h4 class="mb-0">Verificacion del proveedor</h4>
                <span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
              </div>
              <p class="mb-1 text-muted">{{ $statusMeta['description'] }}</p>
              <div class="small text-muted">
                URL publica actual:
                <strong>/&#64;{{ $publicHandle }}</strong>
                @if($profile->hasActiveVerification())
                  · Vigente hasta {{ $profile->verification_expires_at?->format('Y-m-d') ?: 'sin fecha definida' }}
                @endif
              </div>
            </div>
            <div class="text-start text-md-end">
              <div class="text-muted small mb-1">Precio base</div>
              <div class="verification-plan-card__price">${{ number_format($baseAmount, 0, ',', '.') }}</div>
              <div class="small text-muted">COP desde el plan más corto</div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="bg-lighter rounded p-4 h-100">
                <div class="fw-semibold mb-1">Incluye insignia</div>
                <div class="text-muted small">Se muestra en tu perfil publico cuando el admin aprueba pago y documentos.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="bg-lighter rounded p-4 h-100">
                <div class="fw-semibold mb-1">Handle premium + foto</div>
                <div class="text-muted small">Solo los perfiles verificados pueden elegir un /@handle corto y subir su foto de perfil.</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="bg-lighter rounded p-4 h-100">
                <div class="fw-semibold mb-1">Revision manual</div>
                <div class="text-muted small">Subes cedula y prueba visual del grupo. Wompi confirma el cobro y luego el admin valida identidad y marca.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="mb-3">Estado reciente</h5>
          <dl class="row mb-0 small">
            <dt class="col-6 text-muted">Ultimo pago</dt>
            <dd class="col-6 text-end">{{ $latestPayment?->statusLabel() ?: 'Sin registro' }}</dd>

            <dt class="col-6 text-muted">Ultima solicitud</dt>
            <dd class="col-6 text-end">{{ $latestRequest?->status ?: 'Sin solicitud' }}</dd>

            <dt class="col-6 text-muted">Enviado</dt>
            <dd class="col-6 text-end">{{ $latestRequest?->submitted_at?->format('Y-m-d H:i') ?: '-' }}</dd>

            <dt class="col-6 text-muted">Revisado por</dt>
            <dd class="col-6 text-end">{{ $lastReviewedBy ?: '-' }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  @if(! $wompi['is_configured'])
    <div class="alert alert-danger">
      Wompi no esta configurado en este momento. No podras continuar al checkout hasta completar las llaves del entorno.
    </div>
  @elseif($profile->hasActiveVerification())
    <div class="alert alert-success">
      Tu verificacion ya esta activa. La insignia, el handle premium y la foto de perfil seguiran disponibles hasta {{ $profile->verification_expires_at?->format('Y-m-d') ?: 'nuevo aviso' }}.
    </div>
  @elseif($hasApprovedPaymentAwaitingReview)
    <div class="alert alert-warning">
      Wompi ya aprobo el cobro de tu verificacion. Ahora tu solicitud y tus documentos estan en revision manual.
    </div>
  @elseif($hasPendingCheckout)
    <div class="alert alert-warning">
      Ya existe un checkout Wompi pendiente para esta verificacion. Puedes retomarlo con el mismo plan o esperar a que termine antes de cambiarlo.
    </div>
  @elseif($latestRequest?->status === \App\Models\VerificationRequest::STATUS_REJECTED)
    <div class="alert alert-danger">
      La ultima verificacion fue rechazada.
      {{ $latestRequest->rejection_reason ?: 'Revisa los documentos y vuelve a intentar.' }}
    </div>
  @endif

  @if($verificationErrors)
    <div class="alert alert-danger">
      <strong>No pudimos enviar la verificacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-header d-flex flex-wrap justify-content-between gap-3 align-items-center">
      <div>
        <h5 class="mb-1">Planes de verificacion</h5>
        <div class="text-muted small">Elige duracion, carga tus documentos y continua con Wompi para completar el cobro.</div>
      </div>
      <span class="badge bg-label-info">Producto separado del plan del anuncio</span>
    </div>
    <div class="card-body">
      <div class="row g-4">
        @foreach($verificationPlans as $plan)
          <div class="col-md-6 col-xl-4">
            <div class="card verification-plan-card">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <h5 class="mb-1">{{ $plan['name'] }}</h5>
                    <div class="small text-muted">{{ $plan['duration_months'] }} mes(es)</div>
                  </div>
                  @if($plan['duration_months'] === 12)
                    <span class="badge bg-label-primary">Mayor vigencia</span>
                  @endif
                </div>

                <div class="verification-plan-card__price mb-2">${{ number_format((int) $plan['amount_cop'], 0, ',', '.') }}</div>
                <div class="text-muted small mb-4">COP pago unico por el periodo seleccionado</div>

                <ul class="small text-muted ps-3 mb-4">
                  <li>Insignia verificada en tu perfil publico</li>
                  <li>Edicion del handle premium /@custom</li>
                  <li>Desbloqueo de foto de perfil</li>
                  <li>Revision manual de identidad y marca</li>
                </ul>

                @php
                  $isCurrentPendingPlan = $hasPendingCheckout && $latestPayment?->plan_code === $plan['code'];
                  $buttonLabel = 'Pagar con Wompi';

                  if ($hasApprovedPaymentAwaitingReview) {
                    $buttonLabel = 'Documentos en revision';
                  } elseif ($hasPendingCheckout) {
                    $buttonLabel = $isCurrentPendingPlan ? 'Continuar pago en Wompi' : 'Pago pendiente en otro plan';
                  } elseif ($latestRequest?->status === \App\Models\VerificationRequest::STATUS_REJECTED || $latestPayment?->status === \App\Models\ProfileVerificationPayment::STATUS_REJECTED) {
                    $buttonLabel = 'Reintentar con Wompi';
                  }

                  $isDisabled = $purchaseLocked || ($hasPendingCheckout && ! $isCurrentPendingPlan);
                @endphp
                <button
                  type="button"
                  class="btn btn-primary mt-auto"
                  data-verification-plan-trigger
                  data-plan-code="{{ $plan['code'] }}"
                  data-plan-name="{{ $plan['name'] }}"
                  data-plan-amount="{{ $plan['amount_cop'] }}"
                  data-plan-duration="{{ $plan['duration_months'] }}"
                  @disabled($isDisabled)
                >
                  {{ $buttonLabel }}
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Handle personalizado</h5>
          <div class="text-muted small">Disponible solo cuando la verificacion este aprobada y vigente.</div>
        </div>
        <div class="card-body">
          @if($profile->hasActiveVerification())
            <form method="POST" action="{{ route('mariachi.verification.handle.update') }}">
              @csrf
              @method('PATCH')

              <div class="mb-3">
                <label class="form-label" for="handle">Handle /@custom</label>
                <div class="input-group">
                  <span class="input-group-text">/@</span>
                  <input
                    id="handle"
                    name="handle"
                    class="form-control"
                    value="{{ old('handle', $profile->slug) }}"
                    placeholder="mariachi-vargas"
                    maxlength="60"
                    required
                  />
                </div>
                <div class="form-text">Solo minusculas, numeros y guiones. Ejemplo: <code>mariachi-vargas</code>.</div>
              </div>

              <button type="submit" class="btn btn-primary">Guardar handle premium</button>
            </form>
          @else
            <p class="mb-3 text-muted">
              Tu perfil hoy usa una URL automática. Cuando la verificación quede aprobada podrás cambiarla por una personalizada.
            </p>
            <div class="bg-lighter rounded p-4">
              <div class="small text-muted mb-1">URL actual</div>
              <strong>/&#64;{{ $publicHandle }}</strong>
              <div class="small text-muted mt-2">Ejemplo premium: /&#64;{{ $suggestedHandle }}</div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Historial rapido</h5>
          <div class="text-muted small">Resumen de pago y revision mas recientes.</div>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-5 text-muted">Plan enviado</dt>
            <dd class="col-sm-7">{{ $latestPayment?->plan_code ?: '-' }}</dd>

            <dt class="col-sm-5 text-muted">Monto</dt>
            <dd class="col-sm-7">{{ $latestPayment ? '$'.number_format((int) $latestPayment->amount_cop, 0, ',', '.').' COP' : '-' }}</dd>

            <dt class="col-sm-5 text-muted">Checkout</dt>
            <dd class="col-sm-7">{{ $latestPayment?->checkout_reference ?: '-' }}</dd>

            <dt class="col-sm-5 text-muted">Transaccion</dt>
            <dd class="col-sm-7">{{ $latestPayment?->provider_transaction_id ?: '-' }}</dd>

            <dt class="col-sm-5 text-muted">Observaciones</dt>
            <dd class="col-sm-7">{{ $latestRequest?->notes ?: '-' }}</dd>

            <dt class="col-sm-5 text-muted">Motivo rechazo</dt>
            <dd class="col-sm-7 text-danger">{{ $latestRequest?->rejection_reason ?: $latestPayment?->rejection_reason ?: '-' }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="verificationPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('mariachi.verification.store') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="plan_code" value="{{ old('plan_code') }}" data-verification-plan-code />

          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Completar verificacion con Wompi</h5>
              <div class="text-muted small">
                <span data-verification-plan-name></span>
                · <span data-verification-plan-price></span>
                · <span data-verification-plan-duration></span> mes(es)
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="row g-4">
              <div class="col-lg-5">
                <div class="bg-lighter rounded p-4 h-100">
                  <h6 class="mb-3">Que va a pasar</h6>
                  <ol class="small text-muted ps-3 mb-4">
                    <li>Subes tu cedula y la prueba visual del grupo.</li>
                    <li>Te redirigimos a Wompi para completar el pago.</li>
                    <li>Cuando Wompi confirme el cobro, tu solicitud pasa a revision manual.</li>
                  </ol>

                  <div class="border rounded p-3 bg-white">
                    <div class="small text-muted mb-1">Entorno actual</div>
                    <div class="fw-semibold text-uppercase">{{ $wompi['environment'] }}</div>
                    <div class="small text-muted mt-3 mb-1">Moneda</div>
                    <div class="fw-semibold">{{ $wompi['currency'] }}</div>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Foto de cedula</label>
                    <input type="file" name="id_document" class="form-control" accept="image/png,image/jpeg,image/webp" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Prueba visual del grupo</label>
                    <input type="file" name="identity_proof" class="form-control" accept="image/png,image/jpeg,image/webp" required />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Notas para revision</label>
                    <textarea name="notes" rows="4" class="form-control" placeholder="Describe la marca, quien aparece en la prueba visual o cualquier contexto util.">{{ old('notes') }}</textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" @disabled($purchaseLocked)>
              {{ $hasPendingCheckout ? 'Actualizar documentos y continuar a Wompi' : 'Guardar documentos e ir a Wompi' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
