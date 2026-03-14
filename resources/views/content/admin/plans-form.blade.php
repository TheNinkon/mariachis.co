@extends('layouts/layoutMaster')

@section('title', $pageTitle)

@section('page-style')
  <style>
    .plan-editor-shell .card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      box-shadow: 0 1rem 2rem -1.75rem rgba(15, 23, 42, 0.26);
    }

    .plan-editor-note {
      color: #8a8d93;
      font-size: 0.92rem;
      line-height: 1.55;
    }

    .plan-pricing-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
    }

    .plan-pricing-card {
      border: 1px solid rgba(75, 70, 92, 0.12);
      border-radius: 1.15rem;
      background: linear-gradient(180deg, rgba(0, 86, 59, 0.04), rgba(255, 255, 255, 0.98));
      padding: 1rem;
      display: grid;
      gap: 0.9rem;
      min-height: 100%;
    }

    .plan-pricing-card__title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .plan-pricing-card__eyebrow {
      font-size: 0.76rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #00563b;
    }

    .plan-pricing-card__amount {
      font-size: 1.4rem;
      font-weight: 800;
      color: #2f2b3d;
      line-height: 1.05;
    }

    .plan-pricing-card__meta {
      font-size: 0.82rem;
      color: #8a8d93;
    }

    .plan-kpi-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 0.75rem;
    }

    .plan-kpi {
      border-radius: 1rem;
      border: 1px solid rgba(75, 70, 92, 0.08);
      background: rgba(75, 70, 92, 0.03);
      padding: 0.9rem 1rem;
    }

    .plan-kpi span {
      display: block;
      font-size: 0.76rem;
      color: #8a8d93;
      margin-bottom: 0.2rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-weight: 700;
    }

    .plan-kpi strong {
      font-size: 1.1rem;
      color: #2f2b3d;
    }
  </style>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const basePriceInput = document.getElementById('plan-base-price');
      const pricingCards = Array.from(document.querySelectorAll('[data-pricing-card]'));
      const formatter = new Intl.NumberFormat('es-CO');

      const updatePricingPreview = () => {
        const basePrice = Number(basePriceInput?.value || 0);

        pricingCards.forEach(card => {
          const monthsInput = card.querySelector('[data-pricing-months]');
          const discountInput = card.querySelector('[data-pricing-discount]');
          const totalTarget = card.querySelector('[data-pricing-total]');
          const monthlyTarget = card.querySelector('[data-pricing-monthly]');
          const savingsTarget = card.querySelector('[data-pricing-savings]');

          const months = Math.max(0, Number(monthsInput?.value || 0));
          const discount = Math.max(0, Math.min(100, Number(discountInput?.value || 0)));
          const subtotal = basePrice * months;
          const total = Math.max(0, Math.round(subtotal - (subtotal * (discount / 100))));
          const monthly = months > 0 ? Math.round(total / months) : 0;

          if (totalTarget) {
            totalTarget.textContent = months > 0 ? `$${formatter.format(total)}` : '$0';
          }

          if (monthlyTarget) {
            monthlyTarget.textContent = months > 0
              ? `$${formatter.format(monthly)} / mes equivalente`
              : 'Define una vigencia';
          }

          if (savingsTarget) {
            savingsTarget.textContent = discount > 0 ? `Ahorro ${discount}%` : 'Sin descuento';
          }
        });
      };

      if (basePriceInput) {
        basePriceInput.addEventListener('input', updatePricingPreview);
      }

      pricingCards.forEach(card => {
        card.querySelectorAll('input').forEach(input => {
          input.addEventListener('input', updatePricingPreview);
        });
      });

      updatePricingPreview();
    });
  </script>
@endsection

@section('content')
  @php
    use Illuminate\Support\Str;
    use App\Support\Entitlements\EntitlementKey;

    $displayEntitlementGroups = collect($entitlementGroups)->except('pricing')->all();
    $pricingCards = [
      [
        'title' => 'Opción 1',
        'subtitle' => 'Tarifa base visible primero',
        'months_key' => EntitlementKey::LISTING_TERM_PRIMARY_MONTHS,
        'discount_key' => EntitlementKey::LISTING_TERM_PRIMARY_DISCOUNT_PERCENT,
      ],
      [
        'title' => 'Opción 2',
        'subtitle' => 'Plazo de ahorro intermedio',
        'months_key' => EntitlementKey::LISTING_TERM_SECONDARY_MONTHS,
        'discount_key' => EntitlementKey::LISTING_TERM_SECONDARY_DISCOUNT_PERCENT,
      ],
      [
        'title' => 'Opción 3',
        'subtitle' => 'Plazo largo o anual',
        'months_key' => EntitlementKey::LISTING_TERM_TERTIARY_MONTHS,
        'discount_key' => EntitlementKey::LISTING_TERM_TERTIARY_DISCOUNT_PERCENT,
      ],
    ];
  @endphp

  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validacion.</strong>
      <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">{{ $pageTitle }}</h5>
        <p class="mb-0 text-body-secondary">Controla capacidades reales, cuotas y visibilidad para cada paquete.</p>
      </div>
      <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-primary">Volver a paquetes</a>
    </div>
  </div>

  <form method="POST" action="{{ $formAction }}" class="plan-editor-shell">
    @csrf
    @if ($formMethod !== 'POST')
      @method($formMethod)
    @endif

    <div class="row g-6">
      <div class="col-xl-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Datos base</h5>
          </div>
          <div class="card-body">
            <div class="mb-4">
              <label class="form-label">Nombre</label>
              <input class="form-control" name="name" value="{{ old('name', $plan->name) }}" required maxlength="140" />
            </div>

            <div class="mb-4">
              <label class="form-label">Codigo interno</label>
              <input class="form-control" name="code" value="{{ old('code', $plan->code) }}" required maxlength="40" @readonly($plan->exists) />
            </div>

            <div class="mb-4">
              <label class="form-label">Slug</label>
              <input class="form-control" name="slug" value="{{ old('slug', $plan->slug) }}" maxlength="140" placeholder="Se genera si lo dejas vacio" />
            </div>

            <div class="mb-4">
              <label class="form-label">Descripcion comercial</label>
              <textarea class="form-control" name="description" rows="4" maxlength="2000">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div class="mb-4">
              <label class="form-label">Badge</label>
              <input class="form-control" name="badge_text" value="{{ old('badge_text', $plan->badge_text) }}" maxlength="80" placeholder="Ej: Pro, Top, Privado" />
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">Precio base mensual COP</label>
                <input id="plan-base-price" type="number" min="0" class="form-control" name="price_cop" value="{{ old('price_cop', $plan->price_cop ?? 0) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Base de cobro</label>
                <input class="form-control" name="billing_cycle" value="{{ old('billing_cycle', $plan->billing_cycle ?: 'monthly') }}" required maxlength="40" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Orden</label>
                <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" />
              </div>
            </div>

            <div class="plan-kpi-strip mt-4">
              <div class="plan-kpi">
                <span>Base mensual</span>
                <strong>${{ number_format((int) old('price_cop', $plan->price_cop ?? 0), 0, ',', '.') }}</strong>
              </div>
              <div class="plan-kpi">
                <span>Estado</span>
                <strong>{{ old('is_active', $plan->exists ? $plan->is_active : true) ? 'Activo' : 'Inactivo' }}</strong>
              </div>
              <div class="plan-kpi">
                <span>Visibilidad</span>
                <strong>{{ old('is_public', $plan->exists ? $plan->is_public : true) ? 'Publico' : 'Privado' }}</strong>
              </div>
            </div>

            <div class="mt-4">
              <input type="hidden" name="is_public" value="0" />
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" {{ old('is_public', $plan->exists ? $plan->is_public : true) ? 'checked' : '' }} />
                <label class="form-check-label" for="is_public">Visible para autoseleccion del mariachi</label>
              </div>

              <input type="hidden" name="is_active" value="0" />
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $plan->exists ? $plan->is_active : true) ? 'checked' : '' }} />
                <label class="form-check-label" for="is_active">Paquete activo</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card mb-6">
          <div class="card-header">
            <h5 class="mb-1">Precio y vigencia del anuncio</h5>
            <p class="mb-0 plan-editor-note">Esto es exactamente lo que verá el mariachi al pagar un anuncio. Aquí decides cuántos meses ofreces y qué descuento aplica en cada opción.</p>
          </div>
          <div class="card-body">
            <div class="plan-pricing-grid">
              @foreach ($pricingCards as $pricingCard)
                @php
                  $monthsValue = old('entitlements.'.$pricingCard['months_key'], $entitlementValues[$pricingCard['months_key']] ?? 0);
                  $discountValue = old('entitlements.'.$pricingCard['discount_key'], $entitlementValues[$pricingCard['discount_key']] ?? 0);
                @endphp
                <div class="plan-pricing-card" data-pricing-card>
                  <div class="plan-pricing-card__title">
                    <div>
                      <div class="plan-pricing-card__eyebrow">{{ $pricingCard['title'] }}</div>
                      <div class="fw-semibold">{{ $pricingCard['subtitle'] }}</div>
                    </div>
                    <span class="badge bg-label-primary">Wizard</span>
                  </div>

                  <div class="row g-3">
                    <div class="col-6">
                      <label class="form-label">Meses</label>
                      <input
                        type="number"
                        min="0"
                        max="36"
                        class="form-control"
                        name="entitlements[{{ $pricingCard['months_key'] }}]"
                        value="{{ $monthsValue }}"
                        data-pricing-months />
                      <small class="text-muted d-block mt-1">Pon `0` si no quieres mostrar esta opción.</small>
                    </div>
                    <div class="col-6">
                      <label class="form-label">Descuento %</label>
                      <input
                        type="number"
                        min="0"
                        max="100"
                        class="form-control"
                        name="entitlements[{{ $pricingCard['discount_key'] }}]"
                        value="{{ $discountValue }}"
                        data-pricing-discount />
                      <small class="text-muted d-block mt-1">Se aplica sobre el total del plazo.</small>
                    </div>
                  </div>

                  <div>
                    <div class="plan-pricing-card__amount" data-pricing-total>$0</div>
                    <div class="plan-pricing-card__meta" data-pricing-monthly>Define una vigencia</div>
                    <div class="plan-pricing-card__meta mt-1" data-pricing-savings>Sin descuento</div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        @foreach ($displayEntitlementGroups as $category => $definitions)
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-1">{{ $categoryLabels[$category] ?? Str::headline($category) }}</h5>
              <p class="mb-0 plan-editor-note">Entitlements configurables por admin.</p>
            </div>
            <div class="card-body">
              <div class="row g-4">
                @foreach ($definitions as $key => $definition)
                  @php
                    $value = old('entitlements.'.$key, $entitlementValues[$key] ?? $definition['default']);
                  @endphp

                  @if ($definition['type'] === 'boolean')
                    <div class="col-md-6">
                      <input type="hidden" name="entitlements[{{ $key }}]" value="0" />
                      <div class="form-check form-switch border rounded p-4 h-100">
                        <input class="form-check-input" type="checkbox" id="entitlement_{{ $key }}" name="entitlements[{{ $key }}]" value="1" {{ (bool) $value ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="entitlement_{{ $key }}">{{ $definition['label'] }}</label>
                        <div class="small text-muted mt-2">{{ $definition['description'] }}</div>
                      </div>
                    </div>
                  @else
                    <div class="col-md-6">
                      <label class="form-label" for="entitlement_{{ $key }}">{{ $definition['label'] }}</label>
                      <input
                        id="entitlement_{{ $key }}"
                        type="{{ $definition['type'] === 'integer' ? 'number' : 'text' }}"
                        min="{{ $definition['type'] === 'integer' ? '0' : '' }}"
                        class="form-control"
                        name="entitlements[{{ $key }}]"
                        value="{{ $value }}"
                      />
                      <small class="text-muted d-block mt-1">{{ $definition['description'] }}</small>
                    </div>
                  @endif
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="mt-4 d-flex gap-2 flex-wrap">
      <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
      <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-primary">Cancelar</a>
    </div>
  </form>
@endsection
