@extends('layouts/layoutMaster')

@section('title', 'Facturacion y planes')

@section('content')
  @php
    $entitlements = $planSummary['entitlements'] ?? [];
    $verificationActive = $profile->hasActiveVerification();
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

  <div class="row g-6 mb-6">
    <div class="col-lg-7">
      <div class="card h-100">
        <h5 class="card-header">Plan actual del partner</h5>
        <div class="card-body">
          <div class="row g-4 align-items-start">
            <div class="col-md-7">
              <div class="mb-4">
                <h4 class="mb-1">{{ $planSummary['name'] }}</h4>
                <p class="mb-1 text-muted">{{ $planSummary['description'] ?: 'Resumen de capacidades generales del perfil partner.' }}</p>
                <div class="small text-muted">
                  Codigo: <strong>{{ $planSummary['code'] ?: 'legacy' }}</strong>
                  @if($planSummary['badge_text'])
                    · <span class="badge bg-label-primary">{{ $planSummary['badge_text'] }}</span>
                  @endif
                </div>
              </div>

              <div>
                <h6 class="mb-1">
                  {{ $planSummary['price_cop'] > 0 ? '$'.number_format((int) $planSummary['price_cop'], 0, ',', '.').' COP' : 'Sin precio publico' }}
                </h6>
                <div class="text-muted small">Ciclo: {{ $planSummary['billing_cycle'] ?: 'monthly' }}</div>
              </div>
            </div>

            <div class="col-md-5">
              <div class="bg-lighter rounded p-4">
                <div class="fw-semibold mb-3">Resumen de capacidades</div>
                <ul class="small text-muted ps-3 mb-0">
                  <li>{{ (int) ($entitlements['max_cities_covered'] ?? 1) }} ciudad(es) incluidas</li>
                  <li>{{ (int) ($entitlements['max_zones_covered'] ?? 5) }} zona(s) por anuncio</li>
                  <li>{{ (int) ($entitlements['max_photos_per_listing'] ?? 0) }} foto(s) por anuncio</li>
                  <li>{{ (bool) ($entitlements['can_add_video'] ?? false) ? (int) ($entitlements['max_videos_per_listing'] ?? 0).' video(s)' : 'Sin videos' }}</li>
                  <li>{{ (bool) ($entitlements['can_show_whatsapp'] ?? false) ? 'WhatsApp visible' : 'Sin WhatsApp publico' }}</li>
                  <li>{{ (bool) ($entitlements['can_show_phone'] ?? false) ? 'Telefono visible' : 'Sin telefono publico' }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card h-100">
        <h5 class="card-header">Producto de verificacion</h5>
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-label-{{ $verificationActive ? 'success' : ($profile->verification_status === 'payment_pending' ? 'warning' : 'secondary') }}">
              {{ $verificationActive ? 'Activa' : ($profile->verification_status === 'payment_pending' ? 'En revision' : 'Sin activar') }}
            </span>
            @if($profile->slug_locked)
              <span class="badge bg-label-info">Handle premium</span>
            @endif
          </div>

          <p class="text-muted mb-3">
            La verificacion es un producto pago separado del plan del anuncio. Activa insignia + /@handle custom.
          </p>

          <div class="small text-muted mb-3">
            @if($verificationActive)
              Vigente hasta <strong>{{ $profile->verification_expires_at?->format('Y-m-d') ?: 'sin fecha definida' }}</strong>
            @elseif($profile->verification_status === 'payment_pending')
              Tu comprobante esta pendiente de revision.
            @else
              Precio base desde <strong>$18.900 COP</strong>.
            @endif
          </div>

          <div class="d-flex gap-2 flex-wrap mb-4">
            @foreach($verificationPlans as $plan)
              <span class="badge bg-label-primary">{{ $plan['duration_months'] }}m · ${{ number_format((int) $plan['amount_cop'], 0, ',', '.') }}</span>
            @endforeach
          </div>

          <a href="{{ route('mariachi.verification.edit') }}" class="btn btn-primary">Gestionar verificacion</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <h5 class="card-header">Facturacion reciente de verificacion</h5>
    <div class="card-body">
      @if($verificationPayments->isEmpty())
        <p class="mb-0 text-muted">Aun no tienes pagos de verificacion registrados.</p>
      @else
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Comprobante</th>
                <th>Revisado</th>
                <th>Vigencia</th>
              </tr>
            </thead>
            <tbody>
              @foreach($verificationPayments as $payment)
                <tr>
                  <td>
                    <div class="fw-medium">{{ $payment->plan_code }}</div>
                    <div class="small text-muted">{{ $payment->duration_months }} mes(es)</div>
                  </td>
                  <td>${{ number_format((int) $payment->amount_cop, 0, ',', '.') }} COP</td>
                  <td>
                    @php
                      $paymentClass = match ($payment->status) {
                        \App\Models\ProfileVerificationPayment::STATUS_APPROVED => 'success',
                        \App\Models\ProfileVerificationPayment::STATUS_REJECTED => 'danger',
                        default => 'warning',
                      };
                    @endphp
                    <span class="badge bg-label-{{ $paymentClass }}">{{ $payment->statusLabel() }}</span>
                  </td>
                  <td>
                    @if($payment->proof_path)
                      <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                    @else
                      -
                    @endif
                  </td>
                  <td>{{ $payment->reviewed_at?->format('Y-m-d H:i') ?: '-' }}</td>
                  <td>
                    @if($payment->starts_at || $payment->ends_at)
                      {{ $payment->starts_at?->format('Y-m-d') ?: '-' }} a {{ $payment->ends_at?->format('Y-m-d') ?: '-' }}
                    @else
                      -
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <div class="card">
    <h5 class="card-header">Que controla cada producto</h5>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-6">
          <div class="bg-lighter rounded p-4 h-100">
            <h6 class="mb-2">Plan del anuncio</h6>
            <p class="mb-0 text-muted small">
              Define fotos, videos, ciudades, zonas, WhatsApp, llamada, ranking y otros limites funcionales por anuncio.
            </p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="bg-lighter rounded p-4 h-100">
            <h6 class="mb-2">Verificacion</h6>
            <p class="mb-0 text-muted small">
              Es un producto aparte. Solo habilita insignia verificada, confianza extra y el handle premium /@custom.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
