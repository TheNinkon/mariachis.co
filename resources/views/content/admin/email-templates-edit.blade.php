@extends('layouts/layoutMaster')

@section('title', 'Editar plantilla de correo')

@section('page-style')
  <style>
    .email-template-shell .form-control.font-monospace {
      min-height: 34rem;
      line-height: 1.6;
      font-size: 0.9rem;
      resize: vertical;
    }

    .email-template-sidebar {
      height: 100%;
      display: flex;
      flex-direction: column;
      min-height: 46rem;
    }

    .email-template-variables {
      flex: 1 1 auto;
      overflow: auto;
      padding-right: 0.25rem;
    }

    .email-template-actions {
      display: grid;
      gap: 0.75rem;
    }

    .email-template-modal .modal-dialog {
      width: 100vw;
      max-width: 100vw;
      height: 100vh;
      margin: 0;
    }

    .email-template-modal .modal-content {
      height: 100vh;
      border: 0;
      border-radius: 0;
      overflow: hidden;
    }

    .email-template-preview-frame {
      width: min(100%, 1040px);
      height: min(100%, 78vh);
      border: 0;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18);
    }

    .email-template-preview-pane {
      min-height: 0;
      background: #f8fafc;
      border-right: 1px solid rgba(15, 23, 42, 0.08);
    }

    .email-template-preview-pane.is-hidden {
      display: none !important;
    }

    .email-template-preview-main {
      min-height: 0;
      background: #eef2f7;
      display: flex;
      flex-direction: column;
    }

    .email-template-preview-workspace {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
    }

    .email-template-status {
      min-height: 1.5rem;
    }

    @media (max-width: 1199.98px) {
      .email-template-sidebar {
        min-height: auto;
      }

      .email-template-preview-pane {
        border-right: 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      }

      .email-template-preview-frame {
        width: 100%;
        height: 72vh;
      }
    }
  </style>
@endsection

@section('content')
  @php
    $bodyValue = old('body_html', $template->body_html);
    $subjectValue = old('subject', $template->subject);
    $viewErrors = $errors ?? session('errors');
  @endphp

  @if ($viewErrors && $viewErrors->any())
    <div class="alert alert-danger">
      <strong>Hay errores de validación.</strong>
      <ul class="mb-0 mt-2">
        @foreach ($viewErrors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div
    class="email-template-shell"
    data-preview-url="{{ route('admin.email-templates.preview', $template->key) }}"
    data-test-url="{{ route('admin.email-templates.test', $template->key) }}"
    data-csrf-token="{{ csrf_token() }}"
  >
    <div class="card mb-6">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <h5 class="mb-1">{{ $template->name }}</h5>
          <p class="mb-0 text-body-secondary">{{ $template->description }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge bg-label-secondary align-self-center">{{ strtoupper($template->audience) }}</span>
          <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-primary">Volver</a>
        </div>
      </div>
    </div>

    <div class="row g-6">
      <div class="col-xl-8">
        <form id="email-template-form" method="POST" action="{{ route('admin.email-templates.update', $template->key) }}">
          @csrf
          @method('PATCH')

          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-1">Código editable</h5>
              <p class="mb-0 text-body-secondary">Usa solo variables permitidas. El HTML se renderiza en sandbox, sin procesar Blade completo.</p>
            </div>
            <div class="card-body">
              <div class="mb-4">
                <label class="form-label">Asunto</label>
                <input type="text" class="form-control" name="subject" value="{{ $subjectValue }}" required maxlength="255" />
              </div>

              <div class="mb-4">
                <input type="hidden" name="is_active" value="0" />
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} />
                  <label class="form-check-label" for="is_active">Usar esta plantilla desde base de datos</label>
                </div>
                <small class="text-body-secondary d-block mt-1">Si la desactivas, el sistema vuelve al Blade fijo actual.</small>
              </div>

              <div>
                <label class="form-label">HTML</label>
                <textarea class="form-control font-monospace" name="body_html" rows="30" required spellcheck="false">{{ $bodyValue }}</textarea>
              </div>
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit">Guardar plantilla</button>
            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-primary">Cancelar</a>
          </div>
        </form>
      </div>

      <div class="col-xl-4 d-flex">
        <div class="card h-100 w-100">
          <div class="card-header">
            <h5 class="mb-1">Variables disponibles</h5>
            <p class="mb-0 text-body-secondary">La columna se mantiene limpia y las acciones viven aquí.</p>
          </div>
          <div class="card-body email-template-sidebar">
            <div class="email-template-variables">
              @foreach ($template->variables_schema ?? [] as $variable)
                <div class="border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <strong>{{ $variable['label'] ?? $variable['key'] }}</strong>
                    <code>{{ '{'.'{'.$variable['key'].'}'.'}' }}</code>
                  </div>
                  <div class="small text-body-secondary mt-2">{{ $variable['description'] ?? 'Sin descripción.' }}</div>
                  @if (array_key_exists($variable['key'], $mockVariables))
                    <div class="small mt-2">
                      <span class="text-body-secondary">Mock:</span>
                      <span>{{ is_scalar($mockVariables[$variable['key']]) ? $mockVariables[$variable['key']] : json_encode($mockVariables[$variable['key']]) }}</span>
                    </div>
                  @endif
                </div>
              @endforeach
            </div>

            <div class="border-top pt-4 mt-4">
              <p class="small text-body-secondary mb-3">Mailer actual: <strong>{{ $mailerName }}</strong></p>
              <div class="email-template-actions">
                <button type="button" class="btn btn-primary btn-lg" data-open-preview>
                  Ver vista previa
                </button>
                <button type="button" class="btn btn-outline-primary btn-lg" data-open-test>
                  Enviar prueba
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade email-template-modal" id="emailTemplatePreviewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header border-bottom-0 pb-2">
            <div>
              <h5 class="modal-title mb-1">Vista previa renderizada</h5>
              <div class="small text-body-secondary" data-preview-subject>Asunto</div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary" data-toggle-preview-sidebar>
                Variables
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary" data-open-preview-window>
                Abrir en nueva pestaña
              </button>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
          </div>
          <div class="modal-body pt-2 h-100">
            <div class="row g-0 h-100 rounded-4 overflow-hidden border">
              <div class="col-xl-3 col-lg-4 d-flex flex-column email-template-preview-pane" data-preview-sidebar>
                <div class="p-4 border-bottom">
                  <h6 class="mb-1">Variables mock</h6>
                  <p class="mb-0 text-body-secondary small">La vista previa usa datos seguros de ejemplo.</p>
                </div>
                <div class="p-4 overflow-auto">
                  @foreach ($template->variables_schema ?? [] as $variable)
                    <div class="mb-3">
                      <div class="fw-semibold">{{ $variable['label'] ?? $variable['key'] }}</div>
                      <code class="small d-inline-block mt-1">{{ '{'.'{'.$variable['key'].'}'.'}' }}</code>
                    </div>
                  @endforeach
                </div>
              </div>
              <div class="col d-flex flex-column email-template-preview-main">
                <div class="p-3 border-bottom bg-white small text-body-secondary" data-preview-status>
                  Listo para renderizar.
                </div>
                <div class="email-template-preview-workspace">
                  <iframe
                    title="Vista previa de {{ $template->name }}"
                    class="email-template-preview-frame"
                    sandbox=""
                    referrerpolicy="no-referrer"
                    data-preview-frame
                  ></iframe>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="emailTemplateTestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Enviar prueba</h5>
              <p class="mb-0 text-body-secondary small">Se enviará usando el mailer actual: <strong>{{ $mailerName }}</strong>.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="alert d-none" data-test-alert></div>
            <form data-test-form>
              <div class="mb-4">
                <label class="form-label">Destinatario</label>
                <input type="email" class="form-control" name="recipient" value="{{ old('recipient', auth()->user()?->email) }}" required />
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" data-test-submit>Enviar prueba</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const shell = document.querySelector('.email-template-shell');

      if (!shell || typeof bootstrap === 'undefined') {
        return;
      }

      const csrfToken = shell.dataset.csrfToken;
      const previewUrl = shell.dataset.previewUrl;
      const testUrl = shell.dataset.testUrl;
      const form = document.getElementById('email-template-form');

      const previewModalEl = document.getElementById('emailTemplatePreviewModal');
      const previewModal = new bootstrap.Modal(previewModalEl);
      const previewFrame = previewModalEl.querySelector('[data-preview-frame]');
      const previewSubject = previewModalEl.querySelector('[data-preview-subject]');
      const previewStatus = previewModalEl.querySelector('[data-preview-status]');
      const previewSidebar = previewModalEl.querySelector('[data-preview-sidebar]');
      const openPreviewButton = shell.querySelector('[data-open-preview]');
      const openPreviewWindowButton = previewModalEl.querySelector('[data-open-preview-window]');
      const togglePreviewSidebarButton = previewModalEl.querySelector('[data-toggle-preview-sidebar]');

      const testModalEl = document.getElementById('emailTemplateTestModal');
      const testModal = new bootstrap.Modal(testModalEl);
      const openTestButton = shell.querySelector('[data-open-test]');
      const testForm = testModalEl.querySelector('[data-test-form]');
      const testAlert = testModalEl.querySelector('[data-test-alert]');
      const testSubmit = testModalEl.querySelector('[data-test-submit]');
      const previewButtonDefaultLabel = openPreviewButton.textContent.trim();
      const testButtonDefaultLabel = testSubmit.textContent.trim();

      let lastPreviewHtml = '';

      const collectDraftPayload = function () {
        const formData = new FormData(form);
        const params = new URLSearchParams();

        formData.forEach(function (value, key) {
          if (key === '_method') {
            return;
          }

          params.append(key, value);
        });

        if (!formData.has('is_active')) {
          params.append('is_active', '0');
        }

        return params;
      };

      const setPreviewStatus = function (message, tone) {
        previewStatus.textContent = message;
        previewStatus.classList.remove('text-danger', 'text-success', 'text-body-secondary');
        previewStatus.classList.add(tone || 'text-body-secondary');
      };

      const setTestAlert = function (message, tone) {
        testAlert.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
        testAlert.classList.add(tone);
        testAlert.textContent = message;
      };

      const clearTestAlert = function () {
        testAlert.className = 'alert d-none';
        testAlert.textContent = '';
      };

      const parseResponse = async function (response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
          return response.json();
        }

        const text = await response.text();

        if (!response.ok) {
          return {
            message: 'No pudimos procesar la solicitud con la configuración actual.',
            raw: text
          };
        }

        return { message: 'La respuesta del servidor no tuvo el formato esperado.' };
      };

      const openPreview = async function () {
        const payload = collectDraftPayload();

        openPreviewButton.disabled = true;
        openPreviewButton.textContent = 'Renderizando...';
        setPreviewStatus('Renderizando vista previa...', 'text-body-secondary');
        previewSubject.textContent = 'Generando asunto...';
        previewFrame.srcdoc = '';

        try {
          const response = await fetch(previewUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
          });

          const data = await parseResponse(response);

          if (!response.ok) {
            throw new Error(data.message || 'No pudimos renderizar la vista previa.');
          }

          lastPreviewHtml = data.html || '';
          previewSubject.textContent = data.subject || 'Sin asunto';
          previewFrame.srcdoc = lastPreviewHtml;
          setPreviewStatus('Vista previa renderizada con datos mock.', 'text-success');
          previewModal.show();
        } catch (error) {
          lastPreviewHtml = '';
          setPreviewStatus(error.message || 'No pudimos renderizar la vista previa.', 'text-danger');
          previewModal.show();
        } finally {
          openPreviewButton.disabled = false;
          openPreviewButton.textContent = previewButtonDefaultLabel;
        }
      };

      openPreviewButton.addEventListener('click', function () {
        openPreview();
      });

      openPreviewWindowButton.addEventListener('click', function () {
        if (!lastPreviewHtml) {
          return;
        }

        const blob = new Blob([lastPreviewHtml], { type: 'text/html' });
        const objectUrl = URL.createObjectURL(blob);
        window.open(objectUrl, '_blank', 'noopener,noreferrer');
        window.setTimeout(function () {
          URL.revokeObjectURL(objectUrl);
        }, 60000);
      });

      togglePreviewSidebarButton.addEventListener('click', function () {
        previewSidebar.classList.toggle('is-hidden');
      });

      openTestButton.addEventListener('click', function () {
        clearTestAlert();
        testModal.show();
      });

      testForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearTestAlert();
        testSubmit.disabled = true;
        testSubmit.textContent = 'Enviando...';

        const payload = collectDraftPayload();
        const testFormData = new FormData(testForm);

        testFormData.forEach(function (value, key) {
          payload.append(key, value);
        });

        try {
          const response = await fetch(testUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
          });

          const data = await parseResponse(response);

          if (!response.ok) {
            throw new Error(data.message || 'No pudimos enviar la prueba.');
          }

          setTestAlert(data.message || 'Correo de prueba enviado correctamente.', 'alert-success');
        } catch (error) {
          setTestAlert(error.message || 'No pudimos enviar la prueba.', 'alert-danger');
        } finally {
          testSubmit.disabled = false;
          testSubmit.textContent = testButtonDefaultLabel;
        }
      });
    });
  </script>
@endsection
