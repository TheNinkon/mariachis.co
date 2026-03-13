<script>
  document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const parseJsonData = function (value, fallback) {
      try {
        return JSON.parse(value || '');
      } catch (error) {
        return fallback;
      }
    };

    const stripHtml = function (value) {
      const div = document.createElement('div');
      div.innerHTML = value || '';
      return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
    };

    const collectFieldValue = function (selector) {
      const element = document.querySelector(selector);

      if (!element) {
        return null;
      }

      if (element.tagName === 'SELECT' && element.multiple) {
        return Array.from(element.selectedOptions).map(function (option) {
          return option.textContent.trim();
        }).filter(Boolean);
      }

      if (element.tagName === 'SELECT') {
        return element.selectedOptions[0]?.textContent.trim() || element.value || null;
      }

      if ((element.type || '').toLowerCase() === 'checkbox') {
        return element.checked;
      }

      let value = (element.value || '').trim();

      if (element.id === 'content' || element.name === 'content') {
        value = stripHtml(value);
      }

      return value;
    };

    const setFieldValue = function (selector, value) {
      const element = document.querySelector(selector);

      if (!element || typeof value !== 'string' || value.trim() === '') {
        return;
      }

      element.value = value.trim();
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const updateStatus = function (target, message, state) {
      if (!target) {
        return;
      }

      target.classList.remove('text-muted', 'text-success', 'text-danger');
      target.classList.add(state === 'error' ? 'text-danger' : (state === 'success' ? 'text-success' : 'text-muted'));
      target.textContent = message;
    };

    document.querySelectorAll('[data-seo-ai-toolbar]').forEach(function (toolbar) {
      const endpoint = toolbar.dataset.seoAiEndpoint;
      const titleTarget = toolbar.dataset.seoAiTitleTarget;
      const descriptionTarget = toolbar.dataset.seoAiDescriptionTarget;
      const type = toolbar.dataset.seoAiType || 'page';
      const language = toolbar.dataset.seoAiLanguage || 'es';
      const baseContext = parseJsonData(toolbar.dataset.seoAiContext, {});
      const selectors = parseJsonData(toolbar.dataset.seoAiSelectors, {});
      const status = toolbar.querySelector('[data-seo-ai-status]');
      const keywordsInput = toolbar.querySelector('[data-seo-ai-keywords]');
      const buttons = Array.from(toolbar.querySelectorAll('[data-seo-ai-action]'));

      const buildContext = function () {
        const dynamicContext = {};

        Object.keys(selectors).forEach(function (key) {
          dynamicContext[key] = collectFieldValue(selectors[key]);
        });

        return Object.assign({}, baseContext, dynamicContext);
      };

      buttons.forEach(function (button) {
        button.addEventListener('click', async function () {
          buttons.forEach(function (item) { item.disabled = true; });
          updateStatus(status, 'Generando propuesta SEO con IA...', 'loading');

          try {
            const response = await fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
              },
              body: JSON.stringify({
                type: type,
                language: language,
                keywords_target: keywordsInput?.value?.trim() || '',
                raw_context: buildContext()
              })
            });

            const payload = await response.json();

            if (!response.ok) {
              throw new Error(payload.message || 'No fue posible generar el contenido SEO.');
            }

            if (button.dataset.seoAiAction === 'title' || button.dataset.seoAiAction === 'all') {
              setFieldValue(titleTarget, payload.meta_title || '');
            }

            if (button.dataset.seoAiAction === 'description' || button.dataset.seoAiAction === 'all') {
              setFieldValue(descriptionTarget, payload.meta_description || '');
            }

            updateStatus(
              status,
              'Propuesta generada con ' + (payload.model || 'Gemini') + '. Revisa el copy antes de guardar.',
              'success'
            );
          } catch (error) {
            updateStatus(status, error.message || 'No fue posible generar el contenido SEO.', 'error');
          } finally {
            buttons.forEach(function (item) { item.disabled = false; });
          }
        });
      });
    });

    const testButton = document.querySelector('[data-seo-ai-test]');
    const testStatus = document.querySelector('[data-seo-ai-test-status]');

    if (testButton) {
      testButton.addEventListener('click', async function () {
        const apiKey = document.getElementById('seo_gemini_api_key')?.value || '';
        const model = document.getElementById('seo_gemini_model')?.value || '';

        testButton.disabled = true;
        updateStatus(testStatus, 'Probando conexion con Gemini...', 'loading');

        try {
          const response = await fetch(testButton.dataset.seoAiTest, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
              seo_gemini_api_key: apiKey,
              seo_gemini_model: model
            })
          });

          const payload = await response.json();

          if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'No se pudo validar la conexion con Gemini.');
          }

          updateStatus(testStatus, payload.message || 'Conexion exitosa.', 'success');
        } catch (error) {
          updateStatus(testStatus, error.message || 'No se pudo validar la conexion con Gemini.', 'error');
        } finally {
          testButton.disabled = false;
        }
      });
    }
  });
</script>
