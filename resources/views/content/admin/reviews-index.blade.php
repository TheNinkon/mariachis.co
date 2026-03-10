@extends('layouts/layoutMaster')

@section('title', 'Moderacion de Resenas')

@section('content')
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

@php
  $statusMap = [
    'pending' => ['label' => 'Pendiente', 'class' => 'warning'],
    'approved' => ['label' => 'Aprobada', 'class' => 'success'],
    'rejected' => ['label' => 'Rechazada', 'class' => 'danger'],
    'reported' => ['label' => 'Reportada', 'class' => 'info'],
    'hidden' => ['label' => 'Oculta', 'class' => 'secondary'],
  ];

  $verificationMap = [
    'basic' => ['label' => 'Opinion basica', 'class' => 'secondary'],
    'manual_validated' => ['label' => 'Validada manualmente', 'class' => 'primary'],
    'evidence_attached' => ['label' => 'Con foto/prueba', 'class' => 'info'],
  ];
@endphp

<div class="card mb-6">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h5 class="mb-1">Moderacion de opiniones</h5>
      <p class="mb-0 text-muted">Aprobar, rechazar, ocultar y marcar spam. Tambien puedes moderar respuestas del mariachi.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('admin.reviews.index', ['status' => 'all', 'verification' => $verificationFilter]) }}" class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Todas ({{ $statusTotals->sum() }})</a>
      @foreach($statuses as $status)
        @php $meta = $statusMap[$status] ?? ['label' => $status, 'class' => 'secondary']; @endphp
        <a href="{{ route('admin.reviews.index', ['status' => $status, 'verification' => $verificationFilter]) }}" class="btn btn-sm {{ $statusFilter === $status ? 'btn-'.$meta['class'] : 'btn-outline-'.$meta['class'] }}">{{ $meta['label'] }} ({{ (int) ($statusTotals[$status] ?? 0) }})</a>
      @endforeach
    </div>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body d-flex flex-wrap align-items-center gap-2">
    <span class="small text-muted">Verificacion:</span>
    <a href="{{ route('admin.reviews.index', ['status' => $statusFilter, 'verification' => 'all']) }}" class="btn btn-sm {{ $verificationFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Todas</a>
    @foreach($verificationStatuses as $verification)
      @php $meta = $verificationMap[$verification] ?? ['label' => $verification, 'class' => 'secondary']; @endphp
      <a href="{{ route('admin.reviews.index', ['status' => $statusFilter, 'verification' => $verification]) }}" class="btn btn-sm {{ $verificationFilter === $verification ? 'btn-'.$meta['class'] : 'btn-outline-'.$meta['class'] }}">{{ $meta['label'] }} ({{ (int) ($verificationTotals[$verification] ?? 0) }})</a>
    @endforeach
  </div>
</div>

@if($reviews->isEmpty())
  <div class="card">
    <div class="card-body">
      <p class="mb-0 text-muted">No hay resenas con este filtro.</p>
    </div>
  </div>
@else
  <div class="row g-4">
    @foreach($reviews as $review)
      @php
        $statusMeta = $statusMap[$review->moderation_status] ?? ['label' => $review->moderation_status, 'class' => 'secondary'];
        $verificationMeta = $verificationMap[$review->verification_status] ?? ['label' => $review->verification_status, 'class' => 'secondary'];
        $mariachiName = $review->mariachiProfile?->business_name ?: $review->mariachiProfile?->user?->display_name;
      @endphp
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
              <h6 class="mb-1">{{ $review->clientUser?->display_name ?: 'Cliente' }} -> {{ $mariachiName ?: 'Mariachi' }}</h6>
              <div class="d-flex flex-wrap gap-1 mb-1">
                <span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                <span class="badge bg-label-{{ $verificationMeta['class'] }}">{{ $verificationMeta['label'] }}</span>
                @if($review->is_visible)
                  <span class="badge bg-label-success">Visible</span>
                @else
                  <span class="badge bg-label-secondary">No visible</span>
                @endif
                @if($review->is_spam)
                  <span class="badge bg-label-danger">Spam</span>
                @endif
                @if($review->has_offensive_language)
                  <span class="badge bg-label-warning">Lenguaje sensible</span>
                @endif
              </div>
              <p class="mb-0 small text-muted">
                {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} ·
                {{ $review->event_type ?: 'Sin tipo de evento' }} ·
                {{ $review->event_date?->format('Y-m-d') ?: 'Sin fecha de evento' }}
              </p>
            </div>
            <div class="text-end">
              <p class="mb-1 small text-muted">Creada: {{ $review->created_at->format('Y-m-d H:i') }}</p>
              <p class="mb-0 small text-muted">Spam score: {{ $review->spam_score }} · Reportes: {{ $review->reports_count }}</p>
            </div>
          </div>

          <div class="card-body">
            @if($review->title)
              <p class="fw-semibold mb-1">{{ $review->title }}</p>
            @endif
            <p class="mb-3">{{ $review->comment }}</p>

            @if($review->photos->isNotEmpty())
              <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($review->photos as $photo)
                  <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" rel="noopener noreferrer" class="d-inline-block border rounded overflow-hidden">
                    <img src="{{ asset('storage/'.$photo->path) }}" alt="Foto de resena" style="width:80px;height:80px;object-fit:cover;" />
                  </a>
                @endforeach
              </div>
            @endif

            @if($review->latest_report_reason)
              <div class="alert alert-warning py-2 px-3 mb-3">
                <p class="mb-1 fw-semibold">Ultimo motivo de reporte</p>
                <p class="mb-0">{{ $review->latest_report_reason }}</p>
              </div>
            @endif

            @if($review->rejection_reason)
              <div class="alert alert-danger py-2 px-3 mb-3">
                <p class="mb-1 fw-semibold">Motivo de rechazo</p>
                <p class="mb-0">{{ $review->rejection_reason }}</p>
              </div>
            @endif

            @if($review->mariachi_reply)
              <div class="alert alert-secondary py-2 px-3 mb-3">
                <p class="mb-1 fw-semibold">Respuesta del mariachi</p>
                <p class="mb-1">{{ $review->mariachi_reply }}</p>
                <p class="mb-0 small text-muted">
                  {{ $review->mariachi_replied_at?->format('Y-m-d H:i') ?: '' }} ·
                  {{ $review->mariachi_reply_visible ? 'Visible' : 'Oculta' }}
                </p>
                @if($review->mariachi_reply_moderation_note)
                  <p class="mb-0 mt-1 small text-muted"><strong>Nota moderacion:</strong> {{ $review->mariachi_reply_moderation_note }}</p>
                @endif
              </div>
            @endif

            <div class="row g-3">
              <div class="col-xl-8">
                <div class="border rounded p-3 h-100">
                  <p class="fw-semibold mb-2">Acciones de moderacion</p>

                  <div class="d-flex flex-wrap gap-2 mb-2">
                    <form action="{{ route('admin.reviews.moderate', ['review' => $review->id]) }}" method="POST">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="action" value="approve" />
                      <button type="submit" class="btn btn-sm btn-success">Aprobar</button>
                    </form>

                    <form action="{{ route('admin.reviews.moderate', ['review' => $review->id]) }}" method="POST">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="action" value="hide" />
                      <button type="submit" class="btn btn-sm btn-outline-secondary">Ocultar</button>
                    </form>
                  </div>

                  <form action="{{ route('admin.reviews.moderate', ['review' => $review->id]) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject" />
                    <label class="form-label mb-1">Rechazar con motivo</label>
                    <textarea name="reason" rows="2" class="form-control" placeholder="Motivo obligatorio para rechazo" required></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Rechazar</button>
                  </form>

                  <form action="{{ route('admin.reviews.moderate', ['review' => $review->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="spam" />
                    <label class="form-label mb-1">Marcar spam</label>
                    <textarea name="reason" rows="2" class="form-control" placeholder="Motivo opcional"></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-warning mt-2">Marcar spam</button>
                  </form>
                </div>
              </div>

              <div class="col-xl-4">
                <div class="border rounded p-3 h-100 d-grid gap-3 align-content-start">
                  <form action="{{ route('admin.reviews.verification', ['review' => $review->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <label class="form-label mb-1">Estado de verificacion</label>
                    <select name="verification_status" class="form-select form-select-sm">
                      @foreach($verificationStatuses as $verification)
                        @php $meta = $verificationMap[$verification] ?? ['label' => $verification]; @endphp
                        <option value="{{ $verification }}" @selected($review->verification_status === $verification)>{{ $meta['label'] }}</option>
                      @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Guardar verificacion</button>
                  </form>

                  @if($review->mariachi_reply)
                    <form action="{{ route('admin.reviews.reply', ['review' => $review->id]) }}" method="POST">
                      @csrf
                      @method('PATCH')
                      <label class="form-label mb-1">Moderar respuesta mariachi</label>
                      <select name="reply_visible" class="form-select form-select-sm">
                        <option value="1" @selected($review->mariachi_reply_visible)>Visible</option>
                        <option value="0" @selected(! $review->mariachi_reply_visible)>Oculta</option>
                      </select>
                      <textarea name="note" rows="2" class="form-control mt-2" placeholder="Nota interna">{{ $review->mariachi_reply_moderation_note }}</textarea>
                      <button type="submit" class="btn btn-sm btn-outline-primary mt-2">Guardar moderacion</button>
                    </form>
                  @else
                    <p class="mb-0 small text-muted">Esta resena aun no tiene respuesta del mariachi.</p>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="mt-4">
    {{ $reviews->links() }}
  </div>
@endif
@endsection
