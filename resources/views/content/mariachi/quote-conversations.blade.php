@extends('layouts/layoutMaster')

@section('title', 'Solicitudes de Presupuesto')

@section('page-style')
  @vite('resources/assets/vendor/scss/pages/app-chat.scss')
  <style>
    .mariachi-quotes-page {
      --mariachi-accent: #00563b;
      --mariachi-accent-hover: #00452f;
      --mariachi-accent-soft: rgba(0, 86, 59, .14);
      --mariachi-accent-soft-strong: rgba(0, 86, 59, .2);
    }

    .mariachi-quotes-page .app-chat {
      block-size: calc(100vh - 11.5rem);
      min-block-size: 560px;
    }

    .mariachi-quotes-page .app-chat .app-chat-history,
    .mariachi-quotes-page .app-chat .chat-history-wrapper {
      block-size: 100%;
    }

    .mariachi-quotes-page .app-chat .chat-history-wrapper {
      display: flex;
      flex-direction: column;
    }

    .mariachi-quotes-page .app-chat .chat-history-body {
      block-size: auto;
      flex: 1 1 auto;
      min-block-size: 0;
      overflow: auto;
      padding-block-end: 1rem;
    }

    .mariachi-quotes-page .app-chat .chat-history-footer {
      margin: .75rem;
      margin-block-start: 0;
      flex: 0 0 auto;
    }

    .mariachi-quotes-page .app-chat .app-chat-contacts .sidebar-body {
      block-size: calc(100% - 4.7rem);
    }

    .mariachi-quotes-page .chat-header-meta {
      font-size: .82rem;
      letter-spacing: .01em;
    }

    .mariachi-quotes-page .chat-header-main {
      gap: .75rem;
    }

    .mariachi-quotes-page .chat-header-actions .btn {
      border-radius: 999px;
    }

    .mariachi-quotes-page .chat-status-chip {
      border-radius: 999px;
      font-weight: 600;
      font-size: .74rem;
      padding: .35rem .6rem;
    }

    .mariachi-quotes-page .client-profile-trigger {
      cursor: pointer;
    }

    .mariachi-quotes-page .chat-client-sidebar .profile-avatar {
      inline-size: 88px;
      block-size: 88px;
      border-radius: 999px;
      display: grid;
      place-items: center;
      font-weight: 700;
      font-size: 1.8rem;
      color: var(--bs-primary);
      background: rgba(var(--bs-primary-rgb), .12);
      margin-inline: auto;
      margin-block-end: .85rem;
    }

    .mariachi-quotes-page .chat-client-sidebar .sidebar-header {
      position: relative;
      border-block-end: 1px solid rgba(var(--bs-secondary-rgb), .2);
    }

    .mariachi-quotes-page .chat-client-sidebar {
      display: flex;
      flex-direction: column;
    }

    .mariachi-quotes-page .chat-client-sidebar .sidebar-body {
      block-size: auto;
      flex: 1 1 auto;
      min-block-size: 0;
      overflow-y: auto;
      overscroll-behavior: contain;
      padding-block-end: 1.25rem;
    }

    .mariachi-quotes-page .chat-client-sidebar .profile-option {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: .45rem 0;
      color: var(--bs-heading-color);
    }

    .mariachi-quotes-page .text-primary {
      color: var(--mariachi-accent) !important;
    }

    .mariachi-quotes-page .btn-primary {
      background-color: var(--mariachi-accent);
      border-color: var(--mariachi-accent);
    }

    .mariachi-quotes-page .btn-primary:hover,
    .mariachi-quotes-page .btn-primary:focus,
    .mariachi-quotes-page .btn-primary:active {
      background-color: var(--mariachi-accent-hover) !important;
      border-color: var(--mariachi-accent-hover) !important;
    }

    .mariachi-quotes-page .bg-label-primary {
      background-color: var(--mariachi-accent-soft) !important;
      color: var(--mariachi-accent) !important;
    }

    .mariachi-quotes-page .avatar-initial.bg-label-primary {
      background-color: var(--mariachi-accent-soft-strong) !important;
      color: var(--mariachi-accent) !important;
    }

    .mariachi-quotes-page .chat-contact-list .chat-contact-list-item.active > a {
      background-color: var(--mariachi-accent) !important;
      box-shadow: 0 .5rem 1rem rgba(0, 86, 59, .25);
    }

    .mariachi-quotes-page .chat-contact-list .chat-contact-list-item.active .chat-contact-name,
    .mariachi-quotes-page .chat-contact-list .chat-contact-list-item.active .chat-contact-status,
    .mariachi-quotes-page .chat-contact-list .chat-contact-list-item.active .chat-contact-list-item-time {
      color: #fff !important;
    }

    .mariachi-quotes-page .chat-contact-list .chat-contact-list-item.active .avatar-initial {
      background-color: rgba(255, 255, 255, .2) !important;
      color: #fff !important;
    }

    .mariachi-quotes-page .chat-message.chat-message-right .chat-message-text {
      background-color: var(--mariachi-accent) !important;
      color: #fff !important;
    }

    @media (max-width: 1199.98px) {
      .mariachi-quotes-page .app-chat {
        block-size: calc(100vh - 10rem);
      }
    }

    @media (max-width: 991.98px) {
      .mariachi-quotes-page .app-chat {
        block-size: calc(100vh - 8.5rem);
      }

      .mariachi-quotes-page .chat-header-actions {
        inline-size: 100%;
        justify-content: space-between;
      }
    }
  </style>
@endsection

@section('content')
  <div class="mariachi-quotes-page">
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
      'new' => ['label' => 'Nueva', 'class' => 'warning'],
      'in_progress' => ['label' => 'En curso', 'class' => 'primary'],
      'responded' => ['label' => 'Respondida', 'class' => 'success'],
      'closed' => ['label' => 'Cerrada', 'class' => 'secondary'],
    ];

    $statusSections = [
      'new' => 'Nuevas',
      'in_progress' => 'En curso',
      'responded' => 'Respondidas',
      'closed' => 'Cerradas',
    ];

    $selectedConversation = $conversations->firstWhere('id', $selectedConversationId) ?: $conversations->first();
    $selectedConversationId = $selectedConversation ? (int) $selectedConversation->id : 0;
  @endphp

  <div class="app-chat card overflow-hidden">
    <div class="row g-0">
      <div class="col app-chat-contacts app-sidebar flex-grow-0 overflow-hidden border-end" id="app-chat-contacts">
        <div class="sidebar-header h-px-75 px-5 border-bottom d-flex align-items-center">
          <div class="flex-grow-1 input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-search icon-xs"></i></span>
            <input type="text" class="form-control chat-search-input" placeholder="Buscar..." data-chat-search />
          </div>
        </div>

        <div class="sidebar-body">
          <ul class="list-unstyled chat-contact-list py-2 mb-0" id="chat-list">
            <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
              <h5 class="text-primary mb-0">Chats</h5>
            </li>

            <li class="chat-contact-list-item chat-list-item-0 {{ $conversations->isEmpty() ? '' : 'd-none' }} px-4 py-2">
              <h6 class="text-body-secondary mb-0">No hay conversaciones.</h6>
            </li>

            @foreach($statusSections as $statusKey => $statusLabel)
              @php $sectionItems = $groupedConversations[$statusKey] ?? collect(); @endphp

              @if($sectionItems->isNotEmpty())
                <li class="chat-contact-list-item chat-contact-list-item-title mt-0" data-chat-heading="{{ $statusKey }}">
                  <h6 class="text-primary mb-0">{{ $statusLabel }} ({{ (int) ($statusTotals[$statusKey] ?? 0) }})</h6>
                </li>

                @foreach($sectionItems as $conversation)
                  @php
                    $isActive = (int) $conversation->id === $selectedConversationId;
                    $listing = $conversation->mariachiListing ?: $conversation->mariachiProfile?->resolveDefaultListing();
                    $profileName = $listing?->title ?: $listing?->business_name ?: $conversation->mariachiProfile?->business_name ?: $conversation->mariachiProfile?->user?->display_name;
                    $clientName = $conversation->clientUser?->display_name ?: 'Cliente';
                    $initials = collect(explode(' ', trim((string) $clientName)))
                      ->filter()
                      ->take(2)
                      ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                      ->implode('');
                    $lastMessage = $conversation->messages->last();
                    $summary = $lastMessage?->message ?: ($conversation->event_notes ?: 'Solicitud sin mensaje.');
                    $chatText = mb_strtolower((string) ($clientName.' '.$profileName.' '.$summary.' '.$conversation->event_city));
                  @endphp

                  <li class="chat-contact-list-item mb-1 {{ $isActive ? 'active' : '' }}" data-chat-item data-chat-status="{{ $statusKey }}" data-chat-text="{{ $chatText }}">
                    <a href="{{ route('mariachi.quotes.index', ['conversation' => $conversation->id]) }}" class="d-flex align-items-center">
                      <div class="flex-shrink-0 avatar {{ $conversation->unread_for_mariachi_count > 0 ? 'avatar-busy' : 'avatar-online' }}">
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ $initials !== '' ? $initials : 'CL' }}</span>
                      </div>

                      <div class="chat-contact-info flex-grow-1 ms-4 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                          <h6 class="chat-contact-name text-truncate m-0 fw-normal">{{ $clientName }}</h6>
                          <small class="chat-contact-list-item-time">{{ optional($conversation->last_message_at)->format('d/m H:i') ?: '-' }}</small>
                        </div>
                        <small class="chat-contact-status text-truncate d-block">{{ $profileName ?: 'Anuncio sin titulo' }}</small>
                        <small class="chat-contact-status text-truncate d-block">{{ \Illuminate\Support\Str::limit($summary, 52) }}</small>
                      </div>

                      @if($conversation->unread_for_mariachi_count > 0)
                        <span class="badge bg-warning ms-2">{{ $conversation->unread_for_mariachi_count }}</span>
                      @endif
                    </a>
                  </li>
                @endforeach
              @endif
            @endforeach
          </ul>
        </div>
      </div>

      @if($selectedConversation)
        @php
          $selectedListing = $selectedConversation->mariachiListing ?: $selectedConversation->mariachiProfile?->resolveDefaultListing();
          $selectedProfileName = $selectedListing?->title ?: $selectedListing?->business_name ?: $selectedConversation->mariachiProfile?->business_name ?: $selectedConversation->mariachiProfile?->user?->display_name;
          $selectedClientName = $selectedConversation->clientUser?->display_name ?: 'Cliente';
          $selectedStatusMeta = $statusMap[$selectedConversation->status] ?? ['label' => $selectedConversation->status, 'class' => 'secondary'];
          $hasMariachiReply = $selectedConversation->messages->contains(fn ($message): bool => (int) $message->sender_user_id === (int) auth()->id());
        @endphp

        <div class="col app-chat-history d-block" id="app-chat-history">
          <div class="chat-history-wrapper">
            <div class="chat-history-header border-bottom">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex overflow-hidden align-items-center">
                  <div
                    class="flex-shrink-0 avatar avatar-online client-profile-trigger"
                    role="button"
                    data-bs-toggle="sidebar"
                    data-overlay
                    data-target="#app-chat-sidebar-right">
                    <span class="avatar-initial rounded-circle bg-label-primary">{{ mb_strtoupper(mb_substr($selectedClientName, 0, 1)) }}</span>
                  </div>
                  <div
                    class="chat-contact-info flex-grow-1 ms-4 client-profile-trigger"
                    role="button"
                    data-bs-toggle="sidebar"
                    data-overlay
                    data-target="#app-chat-sidebar-right">
                    <h6 class="m-0 fw-normal">{{ $selectedClientName }}</h6>
                    <small class="user-status text-body">{{ $selectedProfileName ?: 'Anuncio sin titulo' }}</small>
                  </div>
                </div>

                <div class="chat-header-actions d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-label-{{ $selectedStatusMeta['class'] }} chat-status-chip">{{ $selectedStatusMeta['label'] }}</span>
                  @if(! $hasMariachiReply)
                    <span class="badge bg-label-warning chat-status-chip">Pendiente tu primera respuesta</span>
                  @endif

                  <div class="dropdown">
                    <button
                      type="button"
                      class="btn btn-sm btn-icon btn-outline-secondary"
                      data-bs-toggle="dropdown"
                      aria-expanded="false"
                      aria-label="Opciones del chat">
                      <i class="icon-base ti tabler-dots-vertical icon-18px"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <h6 class="dropdown-header">Cambiar etiqueta</h6>
                      @foreach($statusMap as $value => $meta)
                        <form action="{{ route('mariachi.quotes.status', ['conversation' => $selectedConversation->id]) }}" method="POST" class="m-0">
                          @csrf
                          @method('PATCH')
                          <input type="hidden" name="status" value="{{ $value }}" />
                          <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between">
                            <span>Marcar como {{ $meta['label'] }}</span>
                            @if($selectedConversation->status === $value)
                              <i class="icon-base ti tabler-check icon-16px text-success"></i>
                            @endif
                          </button>
                        </form>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-4 mt-3 small text-muted">
                <span><strong>Ciudad evento:</strong> {{ $selectedConversation->event_city ?: 'Sin ciudad' }}</span>
                <span><strong>Fecha evento:</strong> {{ $selectedConversation->event_date?->format('Y-m-d') ?: 'Sin fecha' }}</span>
                <span><strong>Contacto:</strong> {{ $selectedConversation->contact_phone ?: $selectedConversation->clientUser?->phone ?: 'N/A' }}</span>
              </div>
            </div>

            <div class="chat-history-body">
              <ul class="list-unstyled chat-history">
                @foreach($selectedConversation->messages as $message)
                  @php $mine = (int) $message->sender_user_id === (int) auth()->id(); @endphp
                  <li class="chat-message {{ $mine ? 'chat-message-right' : '' }}">
                    <div class="d-flex overflow-hidden">
                      @if(! $mine)
                        <div class="user-avatar flex-shrink-0 me-4">
                          <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded-circle bg-label-secondary">{{ mb_strtoupper(mb_substr($selectedClientName, 0, 1)) }}</span>
                          </div>
                        </div>
                      @endif

                      <div class="chat-message-wrapper flex-grow-1">
                        <div class="chat-message-text">
                          <p class="mb-0">{{ $message->message }}</p>
                        </div>
                        <div class="{{ $mine ? 'text-end ' : '' }}text-body-secondary mt-1">
                          <small>{{ $message->sender?->display_name ?: 'Usuario' }} · {{ $message->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                      </div>

                      @if($mine)
                        <div class="user-avatar flex-shrink-0 ms-4">
                          <div class="avatar avatar-sm">
                            <span class="avatar-initial rounded-circle bg-label-primary">YO</span>
                          </div>
                        </div>
                      @endif
                    </div>
                  </li>
                @endforeach

                @if(! $hasMariachiReply)
                  <li class="chat-message">
                    <div class="d-flex overflow-hidden">
                      <div class="chat-message-wrapper flex-grow-1">
                        <div class="chat-message-text bg-label-warning">
                          <p class="mb-0">El cliente ya envio su solicitud. Responde aqui para continuar la conversacion.</p>
                        </div>
                      </div>
                    </div>
                  </li>
                @endif
              </ul>
            </div>

            <div class="chat-history-footer shadow-xs">
              <form action="{{ route('mariachi.quotes.reply', ['conversation' => $selectedConversation->id]) }}" method="POST" class="form-send-message d-flex justify-content-between align-items-center">
                @csrf
                <input class="form-control message-input border-0 me-4 shadow-none" name="message" placeholder="Escribe tu respuesta al cliente..." required />
                <button class="btn btn-primary d-flex send-msg-btn" type="submit">
                  <span class="align-middle d-md-inline-block d-none">Enviar</span>
                  <i class="icon-base ti tabler-send icon-16px ms-md-2 ms-0"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      @else
        <div class="col app-chat-conversation d-flex align-items-center justify-content-center flex-column" id="app-chat-conversation">
          <div class="bg-label-primary p-8 rounded-circle">
            <i class="icon-base ti tabler-message-2 icon-50px"></i>
          </div>
          <h5 class="my-3">Sin conversaciones activas</h5>
          <p class="mb-0 text-muted">Cuando llegue una solicitud nueva, aparecera aqui.</p>
        </div>
      @endif
      @if($selectedConversation)
        @php
          $clientDisplayName = $selectedConversation->clientUser?->display_name ?: 'Cliente';
          $clientInitials = collect(explode(' ', trim((string) $clientDisplayName)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
          $firstMessage = $selectedConversation->messages->first();
        @endphp

        <div class="col app-chat-sidebar-right app-sidebar overflow-hidden chat-client-sidebar" id="app-chat-sidebar-right">
          <div class="sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-10 pb-6">
            <div class="profile-avatar">{{ $clientInitials !== '' ? $clientInitials : 'CL' }}</div>
            <h5 class="mb-1">{{ $clientDisplayName }}</h5>
            <span class="text-muted">{{ $selectedProfileName ?: 'Anuncio sin titulo' }}</span>
            <i class="icon-base ti tabler-x icon-lg cursor-pointer close-sidebar d-block" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-sidebar-right"></i>
          </div>
          <div class="sidebar-body p-6 pt-0">
            <div class="mb-4">
              <p class="text-uppercase text-body-secondary mb-2 small">Contacto</p>
              <div class="d-grid gap-2">
                <div class="profile-option"><i class="icon-base ti tabler-mail icon-18px"></i> {{ $selectedConversation->clientUser?->email ?: 'Sin email' }}</div>
                <div class="profile-option"><i class="icon-base ti tabler-phone icon-18px"></i> {{ $selectedConversation->contact_phone ?: $selectedConversation->clientUser?->phone ?: 'Sin telefono' }}</div>
              </div>
            </div>

            <div class="mb-4">
              <p class="text-uppercase text-body-secondary mb-2 small">Evento</p>
              <div class="d-grid gap-2">
                <div class="profile-option"><i class="icon-base ti tabler-map-pin icon-18px"></i> {{ $selectedConversation->event_city ?: 'Sin ciudad' }}</div>
                <div class="profile-option"><i class="icon-base ti tabler-calendar icon-18px"></i> {{ $selectedConversation->event_date?->format('Y-m-d') ?: 'Sin fecha' }}</div>
                <div class="profile-option"><i class="icon-base ti tabler-clock icon-18px"></i> Ultimo mensaje: {{ optional($selectedConversation->last_message_at)->format('Y-m-d H:i') ?: '-' }}</div>
              </div>
            </div>

            <div class="mb-4">
              <p class="text-uppercase text-body-secondary mb-2 small">Resumen</p>
              <div class="alert alert-secondary mb-0">
                {{ $firstMessage?->message ?: ($selectedConversation->event_notes ?: 'Sin descripcion inicial.') }}
              </div>
            </div>

            <div class="mb-3">
              <p class="text-uppercase text-body-secondary mb-2 small">Opciones</p>
              <div class="d-grid gap-1">
                <button type="button" class="btn btn-sm text-start btn-outline-secondary">Agregar etiqueta interna</button>
                <button type="button" class="btn btn-sm text-start btn-outline-secondary">Marcar contacto importante</button>
                <button type="button" class="btn btn-sm text-start btn-outline-secondary">Ver medios compartidos</button>
              </div>
            </div>
          </div>
        </div>
      @endif

      <div class="app-overlay"></div>
    </div>
  </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const searchInput = document.querySelector('[data-chat-search]');
      const items = Array.from(document.querySelectorAll('[data-chat-item]'));
      const headings = Array.from(document.querySelectorAll('[data-chat-heading]'));
      const emptyState = document.querySelector('.chat-list-item-0');

      const applyFilter = term => {
        let visibleCount = 0;
        const visibleByStatus = {};

        items.forEach(item => {
          const haystack = item.dataset.chatText || '';
          const status = item.dataset.chatStatus || '';
          const isVisible = term === '' || haystack.includes(term);

          item.classList.toggle('d-none', !isVisible);

          if (isVisible) {
            visibleCount += 1;
            visibleByStatus[status] = (visibleByStatus[status] || 0) + 1;
          }
        });

        headings.forEach(heading => {
          const status = heading.dataset.chatHeading || '';
          const hasVisibleItems = (visibleByStatus[status] || 0) > 0;
          heading.classList.toggle('d-none', !hasVisibleItems);
        });

        if (emptyState) {
          emptyState.classList.toggle('d-none', visibleCount > 0);
        }
      };

      applyFilter('');

      if (!searchInput) {
        return;
      }

      searchInput.addEventListener('input', event => {
        const term = (event.target.value || '').toLowerCase().trim();
        applyFilter(term);
      });
    });
  </script>
@endpush
