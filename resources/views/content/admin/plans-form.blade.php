@extends('layouts/layoutMaster')

@section('title', $pageTitle)

@section('content')
  @php
    use Illuminate\Support\Str;
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

  <form method="POST" action="{{ $formAction }}">
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
                <label class="form-label">Precio COP</label>
                <input type="number" min="0" class="form-control" name="price_cop" value="{{ old('price_cop', $plan->price_cop ?? 0) }}" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Ciclo</label>
                <input class="form-control" name="billing_cycle" value="{{ old('billing_cycle', $plan->billing_cycle ?: 'monthly') }}" required maxlength="40" />
              </div>
              <div class="col-md-6">
                <label class="form-label">Orden</label>
                <input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" />
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
        @foreach ($entitlementGroups as $category => $definitions)
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-1">{{ $categoryLabels[$category] ?? Str::headline($category) }}</h5>
              <p class="mb-0 text-body-secondary">Entitlements configurables por admin.</p>
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
