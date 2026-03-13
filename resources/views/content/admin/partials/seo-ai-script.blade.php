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

    const extractHeadings = function (value) {
      const div = document.createElement('div');
      div.innerHTML = value || '';

      return Array.from(div.querySelectorAll('h1, h2, h3')).map(function (heading) {
        return (heading.textContent || '').replace(/\s+/g, ' ').trim();
      }).filter(Boolean);
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

    const csvKeywords = function (value) {
      if (Array.isArray(value)) {
        return value.filter(Boolean).join(', ');
      }

      return typeof value === 'string' ? value.trim() : '';
    };

    const updateStatus = function (target, message, state) {
      if (!target) {
        return;
      }

      target.classList.remove('text-muted', 'text-success', 'text-danger');
      target.classList.add(state === 'error' ? 'text-danger' : (state === 'success' ? 'text-success' : 'text-muted'));
      target.textContent = message;
    };

    const postJson = async function (endpoint, payload) {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'La accion no pudo completarse.');
      }

      return data;
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
          if (key === 'headings') {
            const element = document.querySelector(selectors[key]);
            dynamicContext[key] = element ? extractHeadings(element.value || '') : [];
            return;
          }

          dynamicContext[key] = collectFieldValue(selectors[key]);
        });

        return Object.assign({}, baseContext, dynamicContext);
      };

      buttons.forEach(function (button) {
        button.addEventListener('click', async function () {
          buttons.forEach(function (item) { item.disabled = true; });
          updateStatus(status, 'Generando propuesta SEO con IA...', 'loading');

          try {
            const keywordsTarget = toolbar.dataset.seoAiKeywordsTarget;
            const storedKeywords = keywordsTarget ? (document.querySelector(keywordsTarget)?.value || '').trim() : '';
            const payload = await postJson(endpoint, {
              type: type,
              language: language,
              keywords_target: keywordsInput?.value?.trim() || storedKeywords || '',
              raw_context: buildContext()
            });

            const action = button.dataset.seoAiAction;
            const templateTarget = toolbar.dataset.seoAiTemplateTarget;
            const twitterTarget = toolbar.dataset.seoAiTwitterTarget;

            if (action === 'title' || action === 'all') {
              setFieldValue(titleTarget, payload.meta_title || '');
            }

            if (action === 'description' || action === 'all') {
              setFieldValue(descriptionTarget, payload.meta_description || '');
            }

            if ((action === 'keywords' || action === 'all') && keywordsTarget) {
              setFieldValue(keywordsTarget, csvKeywords(payload.keywords));
            }

            if (action === 'all' && templateTarget && payload.title_template_suggestion) {
              setFieldValue(templateTarget, payload.title_template_suggestion);
            }

            if (action === 'all' && twitterTarget && payload.twitter_site_suggestion) {
              const twitterElement = document.querySelector(twitterTarget);
              if (twitterElement && !(twitterElement.value || '').trim()) {
                setFieldValue(twitterTarget, payload.twitter_site_suggestion);
              }
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

    document.querySelectorAll('[data-seo-rule-tool]').forEach(function (tool) {
      const endpoint = tool.dataset.seoRuleEndpoint;
      const type = tool.dataset.seoRuleType;
      const fieldTarget = tool.dataset.seoRuleFieldTarget;
      const mode = tool.dataset.seoRuleMode;
      const status = tool.querySelector('[data-seo-rule-status]');
      const trigger = tool.querySelector('[data-seo-rule-action]');
      const baseContext = parseJsonData(tool.dataset.seoRuleContext, {});
      const selectors = parseJsonData(tool.dataset.seoRuleSelectors, {});

      if (!trigger) {
        return;
      }

      const buildContext = function () {
        const dynamicContext = {};

        Object.keys(selectors).forEach(function (key) {
          if (key === 'headings') {
            const element = document.querySelector(selectors[key]);
            dynamicContext[key] = element ? extractHeadings(element.value || '') : [];
            return;
          }

          dynamicContext[key] = collectFieldValue(selectors[key]);
        });

        return Object.assign({}, baseContext, dynamicContext);
      };

      trigger.addEventListener('click', async function () {
        trigger.disabled = true;
        updateStatus(status, mode === 'canonical' ? 'Calculando canonical recomendado...' : 'Generando JSON-LD recomendado...', 'loading');

        try {
          const payload = await postJson(endpoint, {
            type: type,
            raw_context: buildContext()
          });

          if (mode === 'canonical') {
            setFieldValue(fieldTarget, payload.canonical || '');
            updateStatus(status, payload.canonical ? 'Canonical sugerido aplicado.' : 'No hay canonical recomendado para este tipo de página.', payload.canonical ? 'success' : 'error');
          }

          if (mode === 'jsonld') {
            setFieldValue(fieldTarget, payload.jsonld || '');
            updateStatus(status, payload.jsonld ? 'JSON-LD recomendado generado.' : 'No hay un template JSON-LD para este contexto.', payload.jsonld ? 'success' : 'error');
          }
        } catch (error) {
          updateStatus(status, error.message || 'La sugerencia no pudo generarse.', 'error');
        } finally {
          trigger.disabled = false;
        }
      });
    });

    const globalAiButton = document.querySelector('[data-seo-ai-global-generate]');
    const globalAiStatus = document.querySelector('[data-seo-ai-global-status]');

    if (globalAiButton) {
      globalAiButton.addEventListener('click', async function () {
        globalAiButton.disabled = true;
        updateStatus(globalAiStatus, 'Generando sugerencias globales con IA...', 'loading');

        try {
          const payload = await postJson(globalAiButton.dataset.seoAiGlobalGenerate, {
            type: 'global_settings',
            language: 'es',
            keywords_target: document.getElementById('seo_default_keywords_target')?.value || '',
            raw_context: {
              site_name: document.getElementById('seo_site_name')?.value || '',
              title_template: document.getElementById('seo_default_title_template')?.value || '',
              meta_description: document.getElementById('seo_default_meta_description')?.value || '',
              twitter_site: document.getElementById('seo_twitter_site')?.value || ''
            }
          });

          setFieldValue('#seo_default_meta_description', payload.meta_description || '');

          if (payload.title_template_suggestion) {
            setFieldValue('#seo_default_title_template', payload.title_template_suggestion);
          }

          const twitterInput = document.getElementById('seo_twitter_site');
          if (twitterInput && !twitterInput.value.trim() && payload.twitter_site_suggestion) {
            setFieldValue('#seo_twitter_site', payload.twitter_site_suggestion);
          }

          updateStatus(globalAiStatus, 'Sugerencias globales generadas. Revisa el copy antes de guardar.', 'success');
        } catch (error) {
          updateStatus(globalAiStatus, error.message || 'No fue posible generar sugerencias globales.', 'error');
        } finally {
          globalAiButton.disabled = false;
        }
      });
    }

    const testButton = document.querySelector('[data-seo-ai-test]');
    const testStatus = document.querySelector('[data-seo-ai-test-status]');

    if (testButton) {
      testButton.addEventListener('click', async function () {
        const apiKey = document.getElementById('seo_gemini_api_key')?.value || '';
        const model = document.getElementById('seo_gemini_model')?.value || '';

        testButton.disabled = true;
        updateStatus(testStatus, 'Probando conexion con Gemini...', 'loading');

        try {
          const payload = await postJson(testButton.dataset.seoAiTest, {
            seo_gemini_api_key: apiKey,
            seo_gemini_model: model
          });

          if (!payload.ok) {
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
