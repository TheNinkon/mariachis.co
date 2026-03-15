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

  </style>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const triggerButtons = Array.from(document.querySelectorAll('[data-open-payment-sheet]'));
      const planNameTargets = Array.from(document.querySelectorAll('[data-payment-plan-name]'));
      const planAmountTargets = Array.from(document.querySelectorAll('[data-payment-plan-amount]'));
      const planDurationTargets = Array.from(document.querySelectorAll('[data-payment-plan-duration]'));
      const planCodeInputs = Array.from(document.querySelectorAll('[data-payment-plan-code]'));
      const termInputs = Array.from(document.querySelectorAll('[data-payment-plan-term-months]'));
      const amountInputs = Array.from(document.querySelectorAll('[data-payment-plan-price]'));
      const checkoutForm = document.getElementById('wompiCheckoutForm');
      const errorBox = document.querySelector('[data-plan-selection-error]');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      if (!triggerButtons.length) {
        return;
      }

      const formatter = new Intl.NumberFormat('es-CO');

      const syncSheet = (button, checkout = {}) => {
        const name = checkout.plan_name || button.dataset.planName || '';
        const code = checkout.plan_code || button.dataset.planCode || '';
        const price = Number(checkout.amount_cop || button.dataset.planPrice || 0);
        const termMonths = Number(checkout.term_months || button.dataset.planTermMonths || 1);
        const termLabel = checkout.term_label || button.dataset.planTermLabel || '1 mes';

        planNameTargets.forEach(node => {
          node.textContent = name;
        });

        planAmountTargets.forEach(node => {
          node.textContent = `$${formatter.format(price)} COP`;
        });

        planDurationTargets.forEach(node => {
          node.textContent = termLabel;
        });

        planCodeInputs.forEach(input => {
          input.value = code;
        });

        termInputs.forEach(input => {
          input.value = String(termMonths);
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

            syncSheet(button, payload.checkout || {});
            checkoutForm?.submit();
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
    $defaultPlanTerm = $defaultPlan ? ($defaultPlan['terms'][$defaultPlan['default_term_months']] ?? reset($defaultPlan['terms'])) : null;
    $latestPayment = $listing->latestPayment;
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago pendiente', 'class' => 'warning'],
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
          El anuncio no se publicará hasta que el cobro quede aprobado en Wompi y luego lo envíes a revisión.
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

  @if(! $wompi['is_configured'])
    <div class="alert alert-danger">
      Wompi no está configurado en este momento. No podrás continuar al checkout hasta completar las llaves en el entorno.
    </div>
  @endif

  @if($listing->isPaymentPending())
    <div class="alert alert-warning">
      <strong>Pago pendiente.</strong> Ya existe un checkout Wompi abierto para este anuncio. Puedes retomarlo con el plan seleccionado.
    </div>
  @elseif($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED)
    <div class="alert alert-success">
      <strong>Pago aprobado.</strong> Si el anuncio ya está activo, también puedes renovar o subir de plan sin bajarlo del aire.
    </div>
  @elseif($listing->isPaymentRejected())
    <div class="alert alert-danger">
      <strong>Pago rechazado.</strong>
      {{ $latestPayment?->rejection_reason ?: 'Wompi no aprobó la transacción. Revisa el cobro y vuelve a intentar.' }}
    </div>
  @endif

  <div class="row g-4 mb-6">
    <div class="col-lg-8">
      <div class="row g-4">
        @foreach($plans as $code => $plan)
          @php
            $defaultTerm = $plan['terms'][$plan['default_term_months']] ?? reset($plan['terms']);
            $isCurrentSelection = $listing->selected_plan_code === $code;
            $canRenewCurrentPlan = $listing->status === \App\Models\MariachiListing::STATUS_ACTIVE
              && $listing->review_status === \App\Models\MariachiListing::REVIEW_APPROVED
              && $listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED
              && $isCurrentSelection;
            $buttonLabel = 'Pagar con Wompi';
            if ($canRenewCurrentPlan) {
              $buttonLabel = 'Renovar con Wompi';
            } elseif ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $listing->selected_plan_code === $code) {
              $buttonLabel = 'Plan aprobado';
            } elseif ($listing->isPaymentPending()) {
              $buttonLabel = $isCurrentSelection ? 'Continuar pago en Wompi' : 'Pago pendiente en otro plan';
            } elseif ($listing->isPaymentRejected() && $listing->selected_plan_code === $code) {
              $buttonLabel = 'Reintentar con Wompi';
            } elseif ($listing->selected_plan_code === $code) {
              $buttonLabel = 'Continuar con este plan';
            }

            $isDisabled = ! $listing->listing_completed
              || (! $isCurrentSelection && $listing->isPaymentPending())
              || ! $wompi['is_configured']
              || ($listing->payment_status === \App\Models\MariachiListing::PAYMENT_APPROVED && $listing->selected_plan_code === $code && ! $canRenewCurrentPlan);
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
                <p class="mb-3">
                  <strong>${{ number_format((int) ($defaultTerm['total_price_cop'] ?? $plan['price_cop']), 0, ',', '.') }} COP</strong>
                  <small class="text-muted d-block">Total {{ $defaultTerm['label'] ?? '1 mes' }}</small>
                </p>

                <ul class="small text-muted mb-4 ps-3">
                  <li>Este plan aplica solo a este anuncio</li>
                  <li>Borradores abiertos: {{ (int) ($plan['max_open_drafts'] ?? 0) === 0 ? 'sin tope' : ($plan['max_open_drafts'].' max.') }}</li>
                  <li>Publicados simultaneos: {{ (int) ($plan['max_published_listings'] ?? 0) === 0 ? 'ilimitados' : $plan['max_published_listings'] }}</li>
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
                  data-plan-price="{{ (int) ($defaultTerm['total_price_cop'] ?? $plan['price_cop']) }}"
                  data-plan-term-months="{{ (int) ($defaultTerm['months'] ?? 1) }}"
                  data-plan-term-label="{{ $defaultTerm['label'] ?? '1 mes' }}"
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
            <p class="mb-0 small">1. Eliges plan. 2. Pagas en Wompi. 3. Wompi confirma el cobro. 4. Vuelves al editor. 5. Envías el anuncio a revisión.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <form id="wompiCheckoutForm" method="POST" action="{{ route('mariachi.listings.payments.wompi.checkout', ['listing' => $listing->id]) }}" class="d-none">
    @csrf
    <input type="hidden" name="listing_id" value="{{ $listing->id }}" />
    <input type="hidden" name="plan_code" value="{{ $defaultPlan['code'] ?? array_key_first($plans) }}" data-payment-plan-code />
    <input type="hidden" name="term_months" value="{{ (int) ($defaultPlanTerm['months'] ?? 1) }}" data-payment-plan-term-months />
    <input type="hidden" name="amount_cop" value="{{ (int) ($defaultPlanTerm['total_price_cop'] ?? 0) }}" data-payment-plan-price />
  </form>
@endsection
