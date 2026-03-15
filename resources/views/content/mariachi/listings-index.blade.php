@extends('layouts/layoutMaster')

@section('title', 'Mis anuncios')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/mariachi-listings-index.js'])
@endsection

@section('page-style')
  <style>
    .partner-ads-shell-card {
      --partner-select-col-width: 3.25rem;
      --partner-toggle-col-width: 5.75rem;
      --partner-name-col-width: 19.5rem;
    }

    .partner-ads-shell-card {
      overflow: hidden;
    }

    .partner-ads-stage {
      display: grid;
      gap: 0;
      padding: 0.8rem 0.8rem 0;
      background:
        linear-gradient(180deg, rgba(242, 246, 247, 0.95) 0%, rgba(255, 255, 255, 0.98) 78%),
        radial-gradient(circle at top left, rgba(0, 86, 59, 0.08), transparent 28%);
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }

    .partner-ads-stage__tabs {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.55rem;
    }

    .partner-ads-stage__tab {
      appearance: none;
      border: 1px solid transparent;
      border-bottom: 0;
      border-radius: 1rem 1rem 0 0;
      background: transparent;
      padding: 0.9rem 1rem 0.8rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      color: #667588;
      text-align: center;
      transition:
        background-color 0.18s ease,
        border-color 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
    }

    .partner-ads-stage__tab.is-active {
      background: #fff;
      border-color: rgba(15, 23, 42, 0.1);
      color: #132033;
      transform: translateY(1px);
      box-shadow: 0 -18px 38px -36px rgba(15, 23, 42, 0.35);
    }

    .partner-ads-stage__tab-icon {
      width: 2.3rem;
      height: 2.3rem;
      border-radius: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(15, 23, 42, 0.06);
      color: #5b6a7c;
      font-size: 1.1rem;
    }

    .partner-ads-stage__tab.is-active .partner-ads-stage__tab-icon {
      background: rgba(0, 86, 59, 0.1);
      color: #00563b;
    }

    .partner-ads-stage__tab-label {
      font-size: 0.98rem;
      font-weight: 800;
      line-height: 1.1;
    }

    .partner-ads-stage__tab-meta {
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      color: #7b8898;
    }

    .partner-ads-header {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.75rem;
      padding-block: 0.85rem;
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }

    .partner-ads-create-btn {
      min-width: auto;
      padding-inline: 1rem;
    }

    .partner-ads-create-btn .icon-base {
      font-size: 1rem;
    }

    .partner-ads-toolbar-btn {
      min-width: auto;
      padding-inline: 0.95rem;
    }

    .partner-ads-toolbar-icon-btn {
      width: 2.6rem;
      height: 2.6rem;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .partner-ads-toolbar-btn.is-disabled,
    .partner-ads-toolbar-icon-btn.is-disabled {
      opacity: 0.48;
      pointer-events: none;
    }

    .partner-editor-overlay {
      position: fixed;
      inset: 0;
      z-index: 1600;
      display: none;
      background: #eef2f6;
    }

    .partner-editor-overlay.is-active {
      display: block;
    }

    .partner-editor-overlay__frame {
      width: 100%;
      height: 100%;
      border: 0;
      background: #eef2f6;
    }

    .partner-listing-thumb {
      width: 56px;
      height: 56px;
      border-radius: 0.9rem;
      object-fit: cover;
      flex-shrink: 0;
    }

    .partner-listing-thumb-fallback {
      width: 56px;
      height: 56px;
      border-radius: 0.9rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(115, 103, 240, 0.12);
      color: #7367f0;
      flex-shrink: 0;
    }

    .partner-listing-progress {
      min-width: 120px;
    }

    .partner-listing-progress .progress {
      height: 6px;
    }

    .partner-listing-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .partner-listing-note {
      max-width: 420px;
    }

    .partner-listing-inline-tools {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem;
      margin-top: 0.45rem;
    }

    .partner-listing-inline-tools a,
    .partner-listing-inline-tools button {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      padding: 0;
      border: 0;
      background: transparent;
      color: #4a5a6d;
      font-size: 0.8rem;
      font-weight: 700;
      line-height: 1;
      text-decoration: none;
    }

    .partner-listing-inline-tools a:hover,
    .partner-listing-inline-tools button:hover {
      color: #00563b;
    }

    .partner-listing-inline-tools .is-disabled {
      color: #97a3b1;
      cursor: default;
      pointer-events: none;
    }

    .card-datatable .dataTables_wrapper .dataTables_filter,
    .card-datatable .dataTables_wrapper .dataTables_length {
      display: none;
    }

    .card-datatable .dataTables_wrapper .dataTables_filter input {
      min-width: 14rem;
      border-radius: 0.55rem;
    }

    .card-datatable .dataTables_wrapper .dataTables_length select {
      min-width: 4.5rem;
      border-radius: 0.55rem;
    }

    .card-datatable.table-responsive {
      overflow-x: auto;
      overflow-y: hidden;
    }

    .datatables-partner-listings {
      width: max-content !important;
      min-width: 86rem;
      border-collapse: separate !important;
      border-spacing: 0 !important;
      table-layout: fixed;
    }

    .datatables-partner-listings thead th {
      position: sticky;
      top: 0;
      box-sizing: border-box;
      white-space: nowrap;
      border-bottom: 1px solid rgba(15, 23, 42, 0.12) !important;
      border-right: 1px solid rgba(15, 23, 42, 0.08);
      background: #fbfcfd;
      z-index: 3;
    }

    .datatables-partner-listings tbody td {
      box-sizing: border-box;
      border-bottom: 1px solid rgba(15, 23, 42, 0.08) !important;
      border-right: 1px solid rgba(15, 23, 42, 0.08);
      vertical-align: top;
      background: #fff;
    }

    .datatables-partner-listings thead th:last-child,
    .datatables-partner-listings tbody td:last-child {
      border-right: 0;
    }

    .partner-listing-select-col {
      width: var(--partner-select-col-width) !important;
      min-width: var(--partner-select-col-width) !important;
      max-width: var(--partner-select-col-width) !important;
      text-align: center;
      padding-inline: 0.75rem !important;
      overflow: hidden;
    }

    .partner-listing-toggle-col {
      width: var(--partner-toggle-col-width) !important;
      min-width: var(--partner-toggle-col-width) !important;
      max-width: var(--partner-toggle-col-width) !important;
      text-align: center;
      padding-inline: 0.75rem !important;
      overflow: hidden;
    }

    .partner-listing-name-col {
      width: var(--partner-name-col-width) !important;
      min-width: var(--partner-name-col-width) !important;
      max-width: var(--partner-name-col-width) !important;
      overflow: hidden;
    }

    .partner-listing-status-col,
    .partner-listing-payment-col,
    .partner-listing-plan-col,
    .partner-listing-progress-col,
    .partner-listing-updated-col {
      width: 8.75rem !important;
      min-width: 8.75rem !important;
    }

    .partner-listing-progress-col {
      width: 9.5rem !important;
      min-width: 9.5rem !important;
    }

    .partner-listing-updated-col {
      width: 8.5rem !important;
      min-width: 8.5rem !important;
    }

    .partner-listing-select {
      width: 1.45rem;
      height: 1.45rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.4rem;
      border: 1px solid rgba(15, 23, 42, 0.18);
      background: #fff;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .partner-listing-select::after {
      content: '';
      width: 0.55rem;
      height: 0.55rem;
      border-radius: 0.16rem;
      background: rgba(15, 23, 42, 0.08);
    }

    .partner-listing-select.is-header::after {
      width: 0.7rem;
      height: 0.12rem;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.28);
    }

    .partner-listing-select-input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .partner-listing-select-label {
      position: relative;
      display: inline-flex;
      cursor: pointer;
    }

    .partner-listing-select-label .partner-listing-select {
      transition:
        border-color 0.18s ease,
        background-color 0.18s ease,
        box-shadow 0.18s ease;
    }

    .partner-listing-select-label .partner-listing-select::after {
      background: transparent;
      width: 0.38rem;
      height: 0.7rem;
      border-right: 2px solid transparent;
      border-bottom: 2px solid transparent;
      border-radius: 0;
      transform: rotate(45deg) translate(-1px, -1px);
    }

    .partner-listing-select-input:checked + .partner-listing-select {
      border-color: rgba(0, 86, 59, 0.38);
      background: rgba(0, 86, 59, 0.12);
      box-shadow: 0 0 0 3px rgba(0, 86, 59, 0.08);
    }

    .partner-listing-select-input:checked + .partner-listing-select::after {
      border-right-color: #00563b;
      border-bottom-color: #00563b;
    }

    .partner-listing-select-input:indeterminate + .partner-listing-select::after {
      width: 0.72rem;
      height: 0.12rem;
      border-radius: 999px;
      border-right: 0;
      border-bottom: 0;
      background: rgba(15, 23, 42, 0.28);
      transform: none;
    }

    .partner-listing-switch-form {
      display: inline-flex;
      margin: 0;
    }

    .partner-listing-switch {
      width: 2.9rem;
      height: 1.65rem;
      display: inline-flex;
      align-items: center;
      padding: 0.14rem;
      border: 1px solid rgba(15, 23, 42, 0.12);
      border-radius: 999px;
      background: #dbe3ec;
      transition:
        background-color 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
    }

    .partner-listing-switch::after {
      content: '';
      width: 1.1rem;
      height: 1.1rem;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18);
      transition: transform 0.18s ease;
    }

    .partner-listing-switch:hover {
      box-shadow: 0 0 0 3px rgba(0, 86, 59, 0.08);
    }

    .partner-listing-switch.is-on {
      background: rgba(40, 199, 111, 0.22);
      border-color: rgba(40, 199, 111, 0.42);
    }

    .partner-listing-switch.is-on::after {
      transform: translateX(1.22rem);
    }

    .partner-listing-switch.is-disabled {
      opacity: 0.62;
      cursor: not-allowed;
      box-shadow: none;
    }

    .partner-listing-title {
      display: block;
      max-width: 100%;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }

    .partner-listing-name-col .d-flex.align-items-start.gap-3,
    .partner-listing-name-col .d-flex.flex-column {
      min-width: 0;
    }

    .partner-listing-name-col .d-flex.align-items-start.gap-3 {
      width: 100%;
    }

    .partner-listing-name-col .d-flex.flex-column {
      flex: 1 1 auto;
    }

    .partner-listing-row.is-selected td {
      background: #eef9f2;
    }

    .partner-listing-row.is-selected td.partner-listing-select-col,
    .partner-listing-row.is-selected td.partner-listing-toggle-col,
    .partner-listing-row.is-selected td.partner-listing-name-col {
      background: #e6f6ec;
    }

    .partner-listing-row.is-selected td:first-child {
      box-shadow: inset 3px 0 0 #28c76f;
    }

    .datatables-partner-listings thead th.partner-listing-select-col,
    .datatables-partner-listings tbody td.partner-listing-select-col {
      position: sticky;
      left: 0;
      z-index: 4;
      background: #fff;
    }

    .datatables-partner-listings thead th.partner-listing-toggle-col,
    .datatables-partner-listings tbody td.partner-listing-toggle-col {
      position: sticky;
      left: var(--partner-select-col-width);
      z-index: 4;
      background: #fff;
    }

    .datatables-partner-listings thead th.partner-listing-name-col,
    .datatables-partner-listings tbody td.partner-listing-name-col {
      position: sticky;
      left: calc(var(--partner-select-col-width) + var(--partner-toggle-col-width));
      z-index: 4;
      background: #fff;
      overflow: hidden;
      text-align: left;
      box-shadow:
        inset -1px 0 0 rgba(15, 23, 42, 0.08),
        10px 0 18px -18px rgba(15, 23, 42, 0.45);
    }

    .datatables-partner-listings thead th.partner-listing-name-col {
      text-align: left !important;
      padding-left: 1rem !important;
      padding-right: 1rem !important;
    }

    .datatables-partner-listings thead th.partner-listing-name-col .dt-column-title {
      display: block;
      width: 100%;
      text-align: left !important;
    }

    .datatables-partner-listings thead th.partner-listing-select-col,
    .datatables-partner-listings thead th.partner-listing-toggle-col,
    .datatables-partner-listings thead th.partner-listing-name-col {
      z-index: 8;
      background: #fbfcfd;
    }

    @media (max-width: 767.98px) {
      .partner-ads-stage__tabs {
        grid-template-columns: 1fr;
      }

      .partner-ads-stage__tab {
        border-radius: 1rem;
        border-bottom: 1px solid transparent;
      }

      .partner-ads-stage__tab.is-active {
        transform: none;
      }

      .partner-listing-title {
        max-width: 14rem;
      }

      .datatables-partner-listings thead th.partner-listing-select-col,
      .datatables-partner-listings thead th.partner-listing-toggle-col,
      .datatables-partner-listings thead th.partner-listing-name-col,
      .datatables-partner-listings tbody td.partner-listing-select-col,
      .datatables-partner-listings tbody td.partner-listing-toggle-col,
      .datatables-partner-listings tbody td.partner-listing-name-col {
        position: static;
        left: auto;
        box-shadow: none;
      }

      .datatables-partner-listings thead th {
        top: auto;
      }
    }
  </style>
@endsection

@section('content')
  @php
    $totalListingsCount = $listings->count();
    $reviewMap = [
      'draft' => ['label' => 'Borrador de revisión', 'class' => 'secondary'],
      'pending' => ['label' => 'En revisión', 'class' => 'warning'],
      'approved' => ['label' => 'Aprobado', 'class' => 'success'],
      'rejected' => ['label' => 'Rechazado', 'class' => 'danger'],
    ];
    $paymentMap = [
      \App\Models\MariachiListing::PAYMENT_NONE => ['label' => 'Sin pago', 'class' => 'secondary'],
      \App\Models\MariachiListing::PAYMENT_PENDING => ['label' => 'Pago en revisión', 'class' => 'warning'],
      \App\Models\MariachiListing::PAYMENT_APPROVED => ['label' => 'Pago aprobado', 'class' => 'success'],
      \App\Models\MariachiListing::PAYMENT_REJECTED => ['label' => 'Pago rechazado', 'class' => 'danger'],
    ];
    $statusMap = [
      \App\Models\MariachiListing::STATUS_DRAFT => ['label' => 'Borrador', 'class' => 'secondary'],
      \App\Models\MariachiListing::STATUS_AWAITING_PLAN => ['label' => 'Sin plan', 'class' => 'warning'],
      \App\Models\MariachiListing::STATUS_AWAITING_PAYMENT => ['label' => 'Esperando pago', 'class' => 'warning'],
      \App\Models\MariachiListing::STATUS_ACTIVE => ['label' => 'Activo', 'class' => 'success'],
      \App\Models\MariachiListing::STATUS_PAUSED => ['label' => 'Pausado', 'class' => 'danger'],
    ];
  @endphp

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  @if(! $canCreateListingDraft)
    <div class="alert alert-warning">
      <strong>Tope de borradores alcanzado.</strong> Publica o elimina uno para liberar espacio antes de crear otro.
    </div>
  @endif

  @if($planIssues !== [])
    <div class="alert alert-warning">
      <strong>Tu configuración actual requiere ajuste.</strong>
      <ul class="mb-0 mt-2 ps-3">
        @foreach($planIssues as $issue)
          <li>{{ $issue }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card partner-ads-shell-card">
    <div class="partner-ads-stage">
      <div class="partner-ads-stage__tabs" role="tablist" aria-label="Vistas del administrador de anuncios">
        <button type="button" class="partner-ads-stage__tab is-active" aria-selected="true" data-listings-stage-tab="all">
          <span class="partner-ads-stage__tab-icon">
            <i class="icon-base ti tabler-folders"></i>
          </span>
          <span class="partner-ads-stage__tab-label">Todos los anuncios</span>
          <span class="partner-ads-stage__tab-meta">{{ $totalListingsCount }} en cartera</span>
        </button>

        <button type="button" class="partner-ads-stage__tab" aria-selected="false" data-listings-stage-tab="review">
          <span class="partner-ads-stage__tab-icon">
            <i class="icon-base ti tabler-hourglass-high"></i>
          </span>
          <span class="partner-ads-stage__tab-label">En revisión</span>
          <span class="partner-ads-stage__tab-meta">{{ $pendingReviewCount }} pendientes</span>
        </button>

        <button type="button" class="partner-ads-stage__tab" aria-selected="false" data-listings-stage-tab="published">
          <span class="partner-ads-stage__tab-icon">
            <i class="icon-base ti tabler-rosette-discount-check"></i>
          </span>
          <span class="partner-ads-stage__tab-label">Publicados</span>
          <span class="partner-ads-stage__tab-meta">{{ $activeCount }} visibles</span>
        </button>
      </div>
    </div>

    <div class="card-header partner-ads-header">

      <div class="d-flex flex-wrap gap-2">
        @if($canCreateListingDraft)
          <a href="{{ route('mariachi.listings.create', ['editor' => 'fullscreen']) }}" class="btn btn-primary partner-ads-create-btn" data-editor-launch>
            <i class="icon-base ti tabler-plus me-1"></i>Crear
          </a>
        @else
          <button type="button" class="btn btn-primary partner-ads-create-btn" disabled>
            <i class="icon-base ti tabler-plus me-1"></i>Crear
          </button>
        @endif

        <a href="#" class="btn btn-outline-secondary partner-ads-toolbar-icon-btn is-disabled" aria-label="Editar anuncio seleccionado" data-listings-edit-trigger data-editor-launch>
          <i class="icon-base ti tabler-pencil"></i>
        </a>

        <form method="POST" action="#" class="m-0" data-listings-toggle-form>
          @csrf
          <button type="submit" class="btn btn-outline-secondary partner-ads-toolbar-btn is-disabled" data-listings-toggle-trigger>
            <i class="icon-base ti tabler-player-pause me-1" data-listings-toggle-icon></i><span data-listings-toggle-label>Pausar</span>
          </button>
        </form>

        <input type="hidden" value="Pausar" data-listings-pause-label>
        <input type="hidden" value="Activar" data-listings-resume-label>
        <input type="hidden" value="tabler-player-pause" data-listings-pause-icon>
        <input type="hidden" value="tabler-player-play" data-listings-resume-icon>
      </div>
    </div>

    @if($listings->isEmpty())
      <div class="card-body">
        <p class="mb-0 text-muted">Aún no has creado anuncios. Empieza con un borrador, complétalo y activa el plan solo en el anuncio que quieras publicar.</p>
      </div>
    @else
      <div class="card-datatable table-responsive">
        <table class="table datatables-partner-listings border-top">
          <colgroup>
            <col style="width: 3.25rem;">
            <col style="width: 5.75rem;">
            <col style="width: 19.5rem;">
            <col style="width: 8.75rem;">
            <col style="width: 8.75rem;">
            <col style="width: 8.75rem;">
            <col style="width: 9.5rem;">
            <col style="width: 8.5rem;">
          </colgroup>
          <thead>
            <tr>
              <th class="partner-listing-select-col">
                <label class="partner-listing-select-label" aria-label="Seleccionar todos los anuncios">
                  <input type="checkbox" class="partner-listing-select-input" data-listings-select-all>
                  <span class="partner-listing-select" aria-hidden="true"></span>
                </label>
              </th>
              <th class="partner-listing-toggle-col">Act.</th>
              <th class="partner-listing-name-col">Campaña</th>
              <th class="partner-listing-status-col">Estado</th>
              <th class="partner-listing-payment-col">Pago</th>
              <th class="partner-listing-plan-col">Plan</th>
              <th class="partner-listing-progress-col">Completitud</th>
              <th class="partner-listing-updated-col">Actualizado</th>
            </tr>
          </thead>
          <tbody>
            @foreach($listings as $listing)
              @php
                $photo = $listing->photos->firstWhere('is_featured', true) ?? $listing->photos->first();
                $reviewMeta = $reviewMap[$listing->review_status] ?? ['label' => $listing->reviewStatusLabel(), 'class' => 'secondary'];
                $paymentMeta = $paymentMap[$listing->payment_status] ?? ['label' => $listing->paymentStatusLabel(), 'class' => 'secondary'];
                $statusMeta = $statusMap[$listing->status] ?? ['label' => \Illuminate\Support\Str::headline($listing->status), 'class' => 'secondary'];
                $canSubmit = $listing->canBeSubmittedForReview();
                $submitLabel = $listing->review_status === \App\Models\MariachiListing::REVIEW_REJECTED ? 'Reenviar' : 'Enviar';
                $currentListingIssues = $listingIssues->get($listing->id, []);
              @endphp
              <tr class="partner-listing-row"
                  data-listing-row
                  data-stage-review="{{ $listing->isPendingReview() ? '1' : '0' }}"
                  data-stage-published="{{ $listing->isApprovedForMarketplace() ? '1' : '0' }}"
                  data-edit-url="{{ route('mariachi.listings.edit', ['listing' => $listing->id]) }}"
                  data-editor-url="{{ route('mariachi.listings.edit', ['listing' => $listing->id, 'editor' => 'fullscreen']) }}"
                  data-toggle-url="{{ $listing->canOwnerPause() ? route('mariachi.listings.pause', ['listing' => $listing->id]) : ($listing->canOwnerResume() ? route('mariachi.listings.resume', ['listing' => $listing->id]) : '') }}"
                  data-toggle-label="{{ $listing->canOwnerPause() ? 'Pausar' : ($listing->canOwnerResume() ? 'Activar' : '') }}"
                  data-toggle-icon="{{ $listing->canOwnerPause() ? 'tabler-player-pause' : ($listing->canOwnerResume() ? 'tabler-player-play' : '') }}">
                <td class="partner-listing-select-col">
                  <label class="partner-listing-select-label" aria-label="Seleccionar anuncio {{ $listing->title }}">
                    <input type="checkbox" class="partner-listing-select-input" data-listing-select>
                    <span class="partner-listing-select" aria-hidden="true"></span>
                  </label>
                </td>
                <td class="partner-listing-toggle-col">
                  @if($listing->canOwnerPause())
                    <form method="POST" action="{{ route('mariachi.listings.pause', ['listing' => $listing->id]) }}" class="partner-listing-switch-form">
                      @csrf
                      <button type="submit" class="partner-listing-switch is-on" aria-label="Pausar anuncio {{ $listing->title }}"></button>
                    </form>
                  @elseif($listing->canOwnerResume())
                    <form method="POST" action="{{ route('mariachi.listings.resume', ['listing' => $listing->id]) }}" class="partner-listing-switch-form">
                      @csrf
                      <button type="submit" class="partner-listing-switch" aria-label="Activar anuncio {{ $listing->title }}"></button>
                    </form>
                  @else
                    <button type="button" class="partner-listing-switch {{ $listing->status === \App\Models\MariachiListing::STATUS_ACTIVE ? 'is-on' : '' }} is-disabled" aria-label="Estado actual {{ $statusMeta['label'] }}" disabled></button>
                  @endif
                </td>
                <td class="partner-listing-name-col">
                  <div class="d-flex align-items-start gap-3">
                    @if($photo)
                      <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $listing->title }}" class="partner-listing-thumb" />
                    @else
                      <span class="partner-listing-thumb-fallback">
                        <i class="icon-base ti tabler-speakerphone"></i>
                      </span>
                    @endif

                    <div class="d-flex flex-column">
                      <span class="fw-semibold text-heading partner-listing-title">{{ $listing->title }}</span>
                      <small class="text-muted">{{ $listing->city_name ?: 'Sin ciudad definida' }}</small>

                      <div class="partner-listing-inline-tools">
                        @if($listing->isPendingReview() || $listing->isPaymentPending())
                          <span class="is-disabled">
                            <i class="icon-base ti {{ $listing->isPaymentPending() ? 'tabler-credit-card' : 'tabler-hourglass-high' }}"></i>
                            {{ $listing->isPaymentPending() ? 'Cobro' : 'Revisión' }}
                          </span>
                        @else
                          <a href="{{ route('mariachi.listings.edit', ['listing' => $listing->id, 'editor' => 'fullscreen']) }}" data-editor-launch>
                            <i class="icon-base ti tabler-pencil"></i>Editar
                          </a>
                        @endif

                        @if($canSubmit && $currentListingIssues === [])
                          <form method="POST" action="{{ route('mariachi.listings.submit-review', ['listing' => $listing->id]) }}" class="m-0">
                            @csrf
                            <button type="submit">
                              <i class="icon-base ti tabler-send"></i>{{ $submitLabel }}
                            </button>
                          </form>
                        @elseif($canSubmit)
                          <span class="is-disabled">
                            <i class="icon-base ti tabler-alert-circle"></i>Requiere ajuste
                          </span>
                        @endif

                        @if($listing->isApprovedForMarketplace() && $listing->slug)
                          <a href="{{ route('mariachi.public.show', ['slug' => $listing->slug]) }}" target="_blank">
                            <i class="icon-base ti tabler-external-link"></i>Ver
                          </a>
                        @endif
                      </div>

                      @if($listing->submitted_for_review_at)
                        <small class="text-body-secondary mt-1">Enviado {{ $listing->submitted_for_review_at->diffForHumans() }}</small>
                      @endif

                      @if($listing->rejection_reason)
                        <small class="text-danger mt-1">{{ \Illuminate\Support\Str::limit($listing->rejection_reason, 110) }}</small>
                      @elseif($listing->isPaymentRejected() && $listing->latestPayment?->rejection_reason)
                        <small class="text-danger mt-1">{{ \Illuminate\Support\Str::limit($listing->latestPayment->rejection_reason, 110) }}</small>
                      @elseif($currentListingIssues !== [])
                        <small class="text-warning mt-1">{{ \Illuminate\Support\Str::limit(implode(' ', $currentListingIssues), 110) }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td class="partner-listing-status-col">
                  <div class="d-flex flex-column gap-2">
                    <span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                    <span class="badge bg-label-{{ $reviewMeta['class'] }}">{{ $reviewMeta['label'] }}</span>
                  </div>
                </td>
                <td class="partner-listing-payment-col">
                  <span class="badge bg-label-{{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                </td>
                <td class="partner-listing-plan-col">
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ \Illuminate\Support\Str::headline($listing->selected_plan_code ?: 'sin seleccionar') }}</span>
                    <small class="text-muted">Efectivo: {{ \Illuminate\Support\Str::headline($listing->effectivePlanCode() ?: 'sin plan') }}</small>
                  </div>
                </td>
                <td class="partner-listing-progress-col" data-order="{{ (int) $listing->listing_completion }}">
                  <div class="partner-listing-progress">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="small text-body-secondary">Progreso</span>
                      <span class="fw-semibold">{{ (int) $listing->listing_completion }}%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" role="progressbar" style="width: {{ (int) $listing->listing_completion }}%;"></div>
                    </div>
                  </div>
                </td>
                <td class="partner-listing-updated-col" data-order="{{ optional($listing->updated_at)->timestamp ?: 0 }}">
                  <div class="d-flex flex-column">
                    <span>{{ $listing->updated_at?->diffForHumans() ?: '-' }}</span>
                    <small class="text-muted">{{ $listing->updated_at?->format('d/m/Y H:i') ?: '-' }}</small>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="card-body border-top" data-listings-empty-state hidden>
        <p class="mb-0 text-muted">No hay anuncios en esta vista todavía.</p>
      </div>
    @endif
  </div>

  <div class="partner-editor-overlay" data-editor-overlay hidden>
    <iframe class="partner-editor-overlay__frame" title="Editor de anuncios" data-editor-iframe></iframe>
  </div>
@endsection
