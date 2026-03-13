@extends('layouts/layoutMaster')

@section('title', 'Plan y pago')

@section('page-style')
  <style>
    .payment-plan-card {
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(34, 41, 47, 0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .payment-plan-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 1rem 2rem -1.35rem rgba(34, 41, 47, 0.28);
      border-color: rgba(105, 108, 255, 0.24);
    }

    .payment-sheet.offcanvas-bottom {
      height: auto;
      max-height: 92vh;
      border-top-left-radius: 1.25rem;
      border-top-right-radius: 1.25rem;
    }

    .payment-sheet-qr {
      width: 100%;
      max-width: 260px;
      border-radius: 1rem;
      border: 1px solid rgba(34, 41, 47, 0.08);
      background: rgba(34, 41, 47, 0.03);
    }

    .payment-sheet-placeholder {
      min-height: 220px;
      border: 1px dashed rgba(34, 41, 47, 0.16);
      border-radius: 1rem;
      color: var(--bs-secondary-color);
    }
  </style>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const triggerButtons = Array.from(document.querySelectorAll('[data-open-payment-sheet]'));
      const planNameTargets = Array.from(document.querySelectorAll('[data-payment-plan-name]'));
      const planAmountTargets = Array.from(document.querySelectorAll('[data-payment-plan-amount]'));
      const planCodeInputs = Array.from(document.querySelectorAll('[data-payment-plan-code]'));
      const amountInputs = Array.from(document.querySelectorAll('[data-payment-plan-price]'));
      const errorBox = document.querySelector('[data-plan-selection-error]');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const sheetElement = document.getElementById('nequiPaymentSheet');
      const sheet = window.bootstrap && sheetElement ? window.bootstrap.Offcanvas.getOrCreateInstance(sheetElement) : null;

      if (!triggerButtons.length) {
        return;
      }

      const formatter = new Intl.NumberFormat('es-CO');

      const syncSheet = button => {
        const name = button.dataset.planName || '';
        const code = button.dataset.planCode || '';
        const price = Number(button.dataset.planPrice || 0);

        planNameTargets.forEach(node => {
          node.textContent = name;
        });

        planAmountTargets.forEach(node => {
          node.textContent = `$${formatter.format(price)} COP`;
        });

        planCodeInputs.forEach(input => {
          input.value = code;
        });

        amountInputs.forEach(input => {
          input.value = String(price);
        });
      };

      triggerButtons.forEach(button => {
        button.addEventListener('click', async function () {
          if (!csrfToken || !button.dataset.selectUrl) {
            return;
          }

          if (errorBox) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
          }

          const originalText = button.textContent;
          button.disabled = true;
          button.textContent = 'Preparando pago...';

          try {
            const response = await fetch(button.dataset.selectUrl, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
              },
              body: new URLSearchParams({
                plan_code: button.dataset.planCode || '',
              }).toString(),
            });

            const payload = await response.json();

            if (!response.ok || !payload.ok) {
              throw new Error(payload.message || 'No pudimos preparar el pago para este plan.');
            }

            syncSheet(button);
            sheet?.show();
          } catch (error) {
            if (errorBox) {
              errorBox.textContent = error.message || 'No pudimos preparar el pago para este plan.';
              errorBox.classList.remove('d-none');
            }
          } finally {
            button.disabled = false;
            button.textContent = originalText;
          }
        });
      });

      syncSheet(triggerButtons[0]);
    });
  </script>
@endsection

@section('content')
  @php
    $selectedPlan = $listing->selected_plan_code ? ($plans[$listing->selected_plan_code] ?? null) : null;
    $defaultPlan = $selectedPlan ?: (count($plans) ? reset($plans) : null);
    $latestPayment = $listing->latestPayment;
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Comprobante en revision', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
    $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
  @endphp

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Paso final: plan y pago</h5>
        <p class="mb-1">
          Anuncio: <strong>{{ $listing->title }}</strong>
          · Completitud: <strong>{{ $listing->listing_completion }}%</strong>
          · Pago: <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
        </p>
        <small class="text-muted">
          El anuncio no se publicará hasta que el admin valide el comprobante y apruebe el pago.
        </small>
      </div>
      @if($listing->isPaymentPending())
        <a href="{{ route('mariachi.listings.index') }}" class="btn btn-outline-secondary">Volver al panel</a>
      @else
        <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}" class="btn btn-outline-primary">Volver al editor</a>
      @endif
    </div>
  </div>

  <div class="alert alert-danger d-none" data-plan-selection-error></div>

  @if(! $listing->listing_completed)
    <div class="alert alert-warning">
      Este anuncio aún no cumple el mínimo para publicarse. Completa datos, ubicación, filtros y fotos antes de pagar.
    </div>
  @endif

  @if(! $nequi['is_configured'])
    <div class="alert alert-danger">
      El pago por Nequi no está configurado en este momento. No podrás enviar comprobantes hasta que el admin cargue los datos.
    </div>
  @endif

  @if($listing->isPaymentPending())
    <div class="alert alert-warning">
      <strong>Comprobante enviado.</strong> El anuncio quedó bloqueado mientras el equipo valida tu pago.
    </div>
  @elseif($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED)
    <div class="alert alert-success">
      <strong>Pago aprobado.</strong> La suscripción ya está activa y el anuncio puede publicarse con este plan.
    </div>
  @elseif($listing->isPaymentRejected())
    <div class="alert alert-danger">
      <strong>Pago rechazado.</strong>
      {{ $latestPayment?->rejection_reason ?: 'Revisa el comprobante y vuelve a intentar.' }}
    </div>
  @endif

  <div class="row g-4 mb-6">
    <div class="col-lg-8">
      <div class="row g-4">
        @foreach($plans as $code => $plan)
          @php
            $buttonLabel = 'Pagar con Nequi';
            if ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $listing->selected_plan_code === $code) {
              $buttonLabel = 'Plan aprobado';
            } elseif ($listing->isPaymentPending()) {
              $buttonLabel = 'Comprobante en revision';
            } elseif ($listing->isPaymentRejected() && $listing->selected_plan_code === $code) {
              $buttonLabel = 'Reintentar pago con Nequi';
            } elseif ($listing->selected_plan_code === $code) {
              $buttonLabel = 'Continuar con este plan';
            }

            $isDisabled = ! $listing->listing_completed
              || $listing->isPaymentPending()
              || ! $nequi['is_configured']
              || ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $listing->selected_plan_code === $code);
          @endphp

          <div class="col-md-6 col-xl-4">
            <div class="card h-100 payment-plan-card">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <h5 class="mb-0">
                    {{ $plan['name'] }}
                    @if($plan['badge_text'])
                      <span class="badge bg-label-primary">{{ $plan['badge_text'] }}</span>
                    @endif
                  </h5>
                  @if($listing->selected_plan_code === $code)
                    <span class="badge bg-label-info">Seleccionado</span>
                  @endif
                </div>

                <p class="text-muted mb-2">{{ $plan['description'] }}</p>
                <p class="mb-3"><strong>${{ number_format((int) $plan['price_cop'], 0, ',', '.') }} COP / mes</strong></p>

                <ul class="small text-muted mb-4 ps-3">
                  <li>Este plan aplica solo a este anuncio</li>
                  <li>Hasta {{ $plan['included_cities'] }} ciudad(es)</li>
                  <li>Hasta {{ $plan['max_zones_covered'] }} zona(s)</li>
                  <li>{{ $plan['max_photos_per_listing'] }} foto(s) por anuncio</li>
                  <li>{{ $plan['can_add_video'] ? $plan['max_videos_per_listing'].' video(s) por anuncio' : 'Sin videos incluidos' }}</li>
                  <li>WhatsApp visible: {{ $plan['show_whatsapp'] ? 'Sí' : 'No' }}</li>
                  <li>Teléfono visible: {{ $plan['show_phone'] ? 'Sí' : 'No' }}</li>
                </ul>

                <button
                  type="button"
                  class="btn btn-primary w-100 mt-auto"
                  data-open-payment-sheet
                  data-select-url="{{ route('mariachi.listings.plans.select', ['listing' => $listing->id]) }}"
                  data-plan-code="{{ $code }}"
                  data-plan-name="{{ $plan['name'] }}"
                  data-plan-price="{{ (int) $plan['price_cop'] }}"
                  @disabled($isDisabled)>
                  {{ $buttonLabel }}
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Estado del anuncio</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Plan solicitado</small>
            <div class="fw-medium">{{ $selectedPlan['name'] ?? 'Sin seleccionar' }}</div>
          </div>

          <div class="mb-3">
            <small class="text-body-secondary d-block mb-1">Estado del pago</small>
            <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
          </div>

          @if($latestPayment)
            <div class="mb-3">
              <small class="text-body-secondary d-block mb-1">Ultimo comprobante</small>
              <div class="fw-medium">{{ $latestPayment->created_at?->format('d/m/Y H:i') ?: 'Sin fecha' }}</div>
            </div>

            <div class="mb-3">
              <small class="text-body-secondary d-block mb-1">Monto enviado</small>
              <div class="fw-medium">${{ number_format((int) $latestPayment->amount_cop, 0, ',', '.') }} COP</div>
            </div>
          @endif

          @if($latestPayment?->rejection_reason)
            <div class="alert alert-danger py-2 px-3 mb-3">
              <p class="mb-1 fw-semibold">Motivo del rechazo</p>
              <p class="mb-0 small">{{ $latestPayment->rejection_reason }}</p>
            </div>
          @endif

          <div class="alert alert-secondary mb-0">
            <p class="mb-1 fw-semibold">Flujo actual</p>
            <p class="mb-0 small">1. Eliges plan. 2. Pagas por Nequi. 3. Subes el comprobante. 4. Admin valida. 5. Solo entonces se activa y publica.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="offcanvas offcanvas-bottom payment-sheet" tabindex="-1" id="nequiPaymentSheet" aria-labelledby="nequiPaymentSheetLabel">
    <div class="offcanvas-header">
      <div>
        <h5 id="nequiPaymentSheetLabel" class="offcanvas-title">Pagar con Nequi</h5>
        <small class="text-muted">Sube el comprobante para dejar el anuncio en revisión de pago.</small>
      </div>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body">
      <form method="POST" action="{{ route('mariachi.listings.payments.nequi.store', ['listing' => $listing->id]) }}" enctype="multipart/form-data" class="row g-4">
        @csrf
        <input type="hidden" name="listing_id" value="{{ $listing->id }}" />
        <input type="hidden" name="plan_code" value="{{ $defaultPlan['code'] ?? array_key_first($plans) }}" data-payment-plan-code />
        <input type="hidden" name="amount_cop" value="{{ (int) ($defaultPlan['price_cop'] ?? 0) }}" data-payment-plan-price />

        <div class="col-lg-7">
          <div class="card border shadow-none h-100">
            <div class="card-body">
              <div class="mb-3">
                <small class="text-body-secondary d-block mb-1">Plan seleccionado</small>
                <div class="fw-semibold" data-payment-plan-name>{{ $defaultPlan['name'] ?? 'Plan' }}</div>
              </div>

              <div class="mb-3">
                <small class="text-body-secondary d-block mb-1">Monto a pagar</small>
                <div class="fw-semibold" data-payment-plan-amount>
                  ${{ number_format((int) ($defaultPlan['price_cop'] ?? 0), 0, ',', '.') }} COP
                </div>
              </div>

              <div class="mb-3">
                <small class="text-body-secondary d-block mb-1">Telefono Nequi</small>
                <div class="fw-semibold">{{ $nequi['phone'] ?: 'Pendiente de configurar' }}</div>
                @if($nequi['beneficiary_name'])
                  <div class="text-body-secondary">Beneficiario: {{ $nequi['beneficiary_name'] }}</div>
                @endif
              </div>

              <div class="alert alert-info mb-0">
                Paga por Nequi, toma una captura clara del comprobante y súbela aquí. Tu anuncio quedará en revisión hasta validación del admin.
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border shadow-none h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              @if($nequi['qr_image_url'])
                <img src="{{ $nequi['qr_image_url'] }}" alt="QR de Nequi" class="payment-sheet-qr img-fluid" />
              @else
                <div class="payment-sheet-placeholder w-100 d-flex align-items-center justify-content-center text-center px-4">
                  El admin aún no ha cargado un QR. Puedes pagar usando el teléfono Nequi mostrado.
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="proof_image">Captura del comprobante</label>
          <input
            id="proof_image"
            type="file"
            name="proof_image"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            class="form-control"
            required />
        </div>

        <div class="col-md-6">
          <label class="form-label" for="reference_text">Referencia opcional</label>
          <input
            id="reference_text"
            type="text"
            name="reference_text"
            class="form-control"
            maxlength="120"
            placeholder="Últimos dígitos, hora o nota breve" />
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
          <button type="submit" class="btn btn-primary">Ya pagué, enviar comprobante</button>
        </div>
      </form>
    </div>
  </div>
@endsection
