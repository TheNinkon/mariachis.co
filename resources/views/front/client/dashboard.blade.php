@extends('front.layouts.public-clean')

@section('title', 'Panel Cliente | Mariachis.co')
@section('meta_description', 'Gestiona tu perfil, seguridad, privacidad, favoritos, vistos recientes y solicitudes.')
@section('page_id', 'client-panel')

@section('auth_header_link')
  <a href="#" onclick="event.preventDefault();document.getElementById('client-logout-form').submit();">Cerrar sesión</a>
@endsection

@section('content')
  @php
    $statusMap = [
      'new' => ['label' => 'Nueva', 'color' => 'status-new'],
      'in_progress' => ['label' => 'En curso', 'color' => 'status-in-progress'],
      'responded' => ['label' => 'Respondida', 'color' => 'status-responded'],
      'closed' => ['label' => 'Cerrada', 'color' => 'status-closed'],
    ];
    $reviewStatusMap = [
      'pending' => ['label' => 'Pendiente', 'color' => 'status-pending'],
      'approved' => ['label' => 'Aprobada', 'color' => 'status-approved'],
      'rejected' => ['label' => 'Rechazada', 'color' => 'status-rejected'],
      'reported' => ['label' => 'Reportada', 'color' => 'status-reported'],
      'hidden' => ['label' => 'Oculta', 'color' => 'status-hidden'],
    ];
    $verificationMap = [
      'basic' => 'Opinion basica',
      'manual_validated' => 'Validada manualmente',
      'evidence_attached' => 'Con foto/prueba',
    ];
  @endphp

  <main class="client-panel-shell">
    @if(session('status'))
      <div class="client-panel-alert success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="client-panel-alert error">
        {{ $errors->first() }}
      </div>
    @endif

    <section class="client-panel-hero">
      <div>
        <p class="client-panel-eyebrow">Mi cuenta</p>
        <h1 class="client-panel-title">Hola, {{ $user->display_name }}</h1>
        <p class="client-panel-meta">Solicitudes: {{ $summaryConversationsCount }} · No leídos: {{ $summaryUnreadCount }} · Favoritos: {{ $summaryFavoritesCount }} · Vistos: {{ $summaryRecentViewsCount }}</p>
      </div>
      <form id="client-logout-form" action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="client-panel-logout" type="submit">Cerrar sesión</button>
      </form>
    </section>

    <div class="client-panel-layout">
      <aside class="client-panel-sidebar">
        <nav>
          <a href="{{ route('client.dashboard') }}" class="{{ $activeSection === 'solicitudes' ? 'is-active' : '' }}">Solicitudes y mensajería</a>
          <a href="{{ route('client.account.favorites') }}" class="{{ $activeSection === 'favoritos' ? 'is-active' : '' }}">Lista de deseos</a>
          <a href="{{ route('client.account.recent') }}" class="{{ $activeSection === 'vistos' ? 'is-active' : '' }}">Vistos recientemente</a>
          <a href="{{ route('client.account.profile') }}" class="{{ $activeSection === 'perfil' ? 'is-active' : '' }}">Perfil</a>
          <a href="{{ route('client.account.security') }}" class="{{ $activeSection === 'seguridad' ? 'is-active' : '' }}">Seguridad</a>
          <a href="{{ route('client.account.privacy') }}" class="{{ $activeSection === 'privacidad' ? 'is-active' : '' }}">Privacidad</a>
        </nav>
      </aside>

      <section class="client-panel-content">
        @if($activeSection === 'solicitudes')
          <article class="client-panel-card">
            <div class="client-panel-card-head">
              <div>
                <h2>Solicitudes de presupuesto / mensajería</h2>
                <p>Historial de conversaciones con mariachis y estado de cada solicitud.</p>
              </div>
              <div class="client-panel-filters">
                <a href="{{ route('client.dashboard', ['status' => 'all']) }}" class="{{ $statusFilter === 'all' ? 'is-active' : '' }}">Todas ({{ $statusTotals->sum() }})</a>
                @foreach($statusMap as $key => $meta)
                  <a href="{{ route('client.dashboard', ['status' => $key]) }}" class="{{ $statusFilter === $key ? 'is-active' : '' }}">{{ $meta['label'] }} ({{ (int) ($statusTotals[$key] ?? 0) }})</a>
                @endforeach
              </div>
            </div>

            @if($conversations->isEmpty())
              <p class="client-panel-empty">No hay conversaciones para este filtro.</p>
            @else
              <div class="client-panel-conversations">
                @foreach($conversations as $conversation)
                  @php
                    $profile = $conversation->mariachiListing ?: $conversation->mariachiProfile?->resolveDefaultListing() ?: $conversation->mariachiProfile;
                    $profileName = $profile?->title ?: $profile?->business_name ?: $profile?->user?->display_name;
                    $statusMeta = $statusMap[$conversation->status] ?? ['label' => $conversation->status, 'color' => 'status-closed'];
                    $review = $conversation->review;
                    $canCreateReview = ! $review && (int) $conversation->mariachi_messages_count > 0;
                    $awaitingMariachiReply = (int) $conversation->mariachi_messages_count === 0;
                  @endphp
                  <article class="client-conversation {{ $conversation->unread_for_client_count > 0 ? 'has-unread' : '' }}">
                    <div class="client-conversation-head">
                      <h3>{{ $profileName }}</h3>
                      <div class="client-conversation-flags">
                        @if($conversation->unread_for_client_count > 0)
                          <span class="client-flag unread">{{ $conversation->unread_for_client_count }} no leídos</span>
                        @endif
                        <span class="client-flag {{ $statusMeta['color'] }}">{{ $statusMeta['label'] }}</span>
                      </div>
                    </div>
                    <p class="client-conversation-meta">{{ $conversation->event_city ?: 'Sin ciudad' }} · {{ $conversation->event_date?->format('Y-m-d') ?: 'Sin fecha' }} · Último: {{ optional($conversation->last_message_at)->format('Y-m-d H:i') ?: 'N/A' }}</p>

                    <div class="client-thread">
                      @foreach($conversation->messages as $message)
                        @php $mine = $message->sender_user_id === auth()->id(); @endphp
                        <div class="client-message-row {{ $mine ? 'mine' : 'other' }}">
                          <div class="client-message">
                            <p class="client-message-author">{{ $message->sender?->display_name ?: 'Usuario' }}</p>
                            <p>{{ $message->message }}</p>
                            <p class="client-message-time">{{ $message->created_at->format('Y-m-d H:i') }}</p>
                          </div>
                        </div>
                      @endforeach

                      @if($awaitingMariachiReply)
                        <div class="client-message-row other">
                          <div class="client-message">
                            <p class="client-message-author">Sistema</p>
                            <p>Solicitud enviada. En espera de respuesta del mariachi.</p>
                          </div>
                        </div>
                      @endif
                    </div>

                    <form action="{{ route('client.quotes.reply', ['conversation' => $conversation->id]) }}" method="POST" class="client-reply-form">
                      @csrf
                      <textarea name="message" rows="2" placeholder="Responder al mariachi..." required></textarea>
                      <button type="submit">Enviar mensaje</button>
                    </form>

                    <div class="client-review-panel">
                      @if($review)
                        @php
                          $reviewMeta = $reviewStatusMap[$review->moderation_status] ?? ['label' => $review->moderation_status, 'color' => 'status-hidden'];
                          $verificationLabel = $verificationMap[$review->verification_status] ?? $review->verification_status;
                        @endphp
                        <div class="client-review-summary">
                          <div class="client-review-summary-head">
                            <p class="client-review-title">Tu opinion para {{ $profileName }}</p>
                            <div class="client-conversation-flags">
                              <span class="client-flag {{ $reviewMeta['color'] }}">{{ $reviewMeta['label'] }}</span>
                              <span class="client-flag status-verification">{{ $verificationLabel }}</span>
                            </div>
                          </div>
                          <p class="client-review-rating">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                          @if($review->title)
                            <p class="client-review-headline">{{ $review->title }}</p>
                          @endif
                          <p class="client-review-comment">{{ $review->comment }}</p>
                          @if($review->photos->isNotEmpty())
                            <p class="client-review-photos">{{ $review->photos->count() }} foto(s) adjuntas.</p>
                          @endif
                          @if($review->rejection_reason)
                            <p class="client-review-note">Motivo de moderacion: {{ $review->rejection_reason }}</p>
                          @endif
                        </div>
                      @elseif($canCreateReview)
                        <form action="{{ route('client.reviews.store', ['conversation' => $conversation->id]) }}" method="POST" enctype="multipart/form-data" class="client-review-form">
                          @csrf
                          <p class="client-review-form-title">Dejar opinion</p>
                          <div class="client-review-grid">
                            <label>
                              <span>Calificacion (1 a 5)</span>
                              <select name="rating" required>
                                <option value="">Selecciona</option>
                                @for($rating = 5; $rating >= 1; $rating--)
                                  <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} estrella{{ $rating > 1 ? 's' : '' }}</option>
                                @endfor
                              </select>
                            </label>
                            <label>
                              <span>Titulo (opcional)</span>
                              <input type="text" name="title" maxlength="120" value="{{ old('title') }}" placeholder="Excelente puntualidad" />
                            </label>
                            <label>
                              <span>Fecha del evento (opcional)</span>
                              <input type="date" name="event_date" value="{{ old('event_date', optional($conversation->event_date)->format('Y-m-d')) }}" />
                            </label>
                            <label>
                              <span>Tipo de evento (opcional)</span>
                              <input type="text" name="event_type" maxlength="120" value="{{ old('event_type') }}" placeholder="Cumpleaños, boda, serenata..." />
                            </label>
                            <label class="span-2">
                              <span>Comentario</span>
                              <textarea name="comment" rows="3" minlength="10" maxlength="3000" placeholder="Comparte tu experiencia con este mariachi..." required>{{ old('comment') }}</textarea>
                            </label>
                            <label class="span-2">
                              <span>Fotos (opcional, maximo 4)</span>
                              <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple />
                            </label>
                          </div>
                          <button type="submit">Enviar opinion para moderacion</button>
                        </form>
                      @else
                        <p class="client-review-hint">La opcion para opinar se habilita cuando el mariachi responda esta conversacion.</p>
                      @endif
                    </div>
                  </article>
                @endforeach
              </div>
            @endif
          </article>
        @endif

        @if($activeSection === 'favoritos')
          <article class="client-panel-card">
            <h2>Lista de deseos / favoritos</h2>
            @if($favorites->isEmpty())
              <p class="client-panel-empty">Aún no guardas mariachis como favoritos.</p>
            @else
              <div class="client-favorites-grid">
                @foreach($favorites as $profile)
                  @php $photo = $profile->photos->firstWhere('is_featured', true) ?? $profile->photos->first(); @endphp
                  <article class="client-favorite-card">
                    <a href="{{ route('mariachi.public.show', ['slug' => $profile->slug]) }}" class="client-favorite-title">{{ $profile->business_name ?: $profile->user?->display_name }}</a>
                    <p class="client-favorite-city">{{ $profile->city_name ?: 'Colombia' }}</p>
                    @if($photo)
                      <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $profile->business_name }}" />
                    @endif
                    <form action="{{ route('client.favorites.destroy', ['slug' => $profile->slug]) }}" method="POST">
                      @csrf
                      @method('DELETE')
                      <button type="submit">Quitar de favoritos</button>
                    </form>
                  </article>
                @endforeach
              </div>
            @endif
          </article>
        @endif

        @if($activeSection === 'vistos')
          <article class="client-panel-card">
            <h2>Vistos recientemente</h2>
            @if($recentViews->isEmpty())
              <p class="client-panel-empty">No hay actividad reciente.</p>
            @else
              <div class="client-recent-list">
                @foreach($recentViews as $view)
                  @php
                    $recentListing = $view->mariachiListing ?: $view->mariachiProfile?->resolveDefaultListing();
                  @endphp
                  <a href="{{ $recentListing?->slug ? route('mariachi.public.show', ['slug' => $recentListing->slug]) : '#' }}" class="client-recent-item">
                    <strong>{{ $recentListing?->title ?: $recentListing?->business_name ?: $recentListing?->user?->display_name }}</strong>
                    <span>{{ $view->last_viewed_at?->format('Y-m-d H:i') }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </article>
        @endif

        @if($activeSection === 'perfil')
          <article class="client-panel-card">
            <h2>Perfil / información personal</h2>
            <form action="{{ route('client.profile.update') }}" method="POST" class="client-form-grid">
              @csrf
              @method('PATCH')
              <input name="first_name" value="{{ old('first_name', $user->first_name) }}" placeholder="Nombre" required />
              <input name="last_name" value="{{ old('last_name', $user->last_name) }}" placeholder="Apellido" required />
              <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Teléfono" />
              <input name="city_name" value="{{ old('city_name', $user->clientProfile?->city_name) }}" placeholder="Ciudad" />
              <input name="zone_name" value="{{ old('zone_name', $user->clientProfile?->zone_name) }}" placeholder="Zona/Barrio" class="span-2" />
              <button type="submit" class="span-2">Guardar perfil</button>
            </form>
          </article>
        @endif

        @if($activeSection === 'seguridad')
          <article class="client-panel-card">
            <h2>Inicio de sesión y seguridad</h2>
            <form action="{{ route('client.security.update') }}" method="POST" class="client-form-grid">
              @csrf
              @method('PATCH')
              <input type="password" name="password" placeholder="Nueva contraseña" required />
              <input type="password" name="password_confirmation" placeholder="Confirmar contraseña" required />
              <button type="submit" class="span-2">Actualizar contraseña</button>
            </form>
          </article>
        @endif

        @if($activeSection === 'privacidad')
          <article class="client-panel-card">
            <h2>Uso compartido de datos / privacidad</h2>
            <form action="{{ route('client.privacy.update') }}" method="POST" class="client-privacy-form">
              @csrf
              @method('PATCH')
              <label><input type="checkbox" name="share_data_for_recommendations" value="1" @checked($user->clientProfile?->preferences['share_data_for_recommendations'] ?? false) /> Permitir recomendaciones personalizadas</label>
              <label><input type="checkbox" name="share_data_for_marketing" value="1" @checked($user->clientProfile?->preferences['share_data_for_marketing'] ?? false) /> Permitir comunicaciones comerciales</label>
              <button type="submit">Guardar privacidad</button>
            </form>
            <form action="{{ route('client.deactivate') }}" method="POST" class="client-deactivate-form" onsubmit="return confirm('¿Seguro que deseas desactivar tu cuenta?');">
              @csrf
              @method('DELETE')
              <button type="submit">Desactivar cuenta</button>
            </form>
          </article>
        @endif
      </section>
    </div>
  </main>
@endsection
