import Sortable from 'sortablejs';

'use strict';

(function () {
  const wizardElement = document.querySelector('[data-listing-wizard]');
  const mainForm = document.getElementById('listing-main-form');
  const autosaveBadge = document.querySelector('[data-autosave-status]');
  const autosaveTime = document.querySelector('[data-autosave-time]');
  const completionTargets = document.querySelectorAll('[data-completion-text]');
  const photoInput = document.querySelector('[data-photo-input]');
  const photoTrigger = document.querySelector('[data-photo-trigger]');
  const preserveForms = Array.from(document.querySelectorAll('form[data-preserve-step]'));
  const stepLinks = Array.from(document.querySelectorAll('[data-step-link]'));
  const upgradeToFinalTriggers = Array.from(document.querySelectorAll('[data-upgrade-to-final="true"]'));
  const listingId = wizardElement?.dataset.listingId || '';
  const stepStorageKey = listingId ? `listing_wizard_step_${listingId}` : null;
  const stepOrder = ['basic', 'location', 'filters', 'faqs', 'photos', 'videos', 'final'];
  const legacyStepAliases = {
    media: 'photos',
    review: 'final'
  };

  let stepperInstance = null;
  if (wizardElement && typeof Stepper !== 'undefined') {
    stepperInstance = new Stepper(wizardElement, {
      linear: false,
      animation: true
    });
  }

  function normalizeWizardStep(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return legacyStepAliases[normalized] || normalized;
  }

  const normalizeStepKey = value => normalizeWizardStep(value);
  const initialStep = normalizeWizardStep(wizardElement?.dataset.initialStep || '');

  const persistStep = stepKey => {
    if (!stepStorageKey) {
      return;
    }

    const normalized = normalizeStepKey(stepKey);
    if (!stepOrder.includes(normalized)) {
      return;
    }

    window.sessionStorage.setItem(stepStorageKey, normalized);
  };

  const getStoredStep = () => {
    if (!stepStorageKey) {
      return null;
    }

    return normalizeStepKey(window.sessionStorage.getItem(stepStorageKey) || '');
  };

  const getStepKeyByIndex = index => {
    const contents = Array.from(wizardElement?.querySelectorAll('.bs-stepper-content > .content') || []);
    return contents[index]?.dataset.stepKey || null;
  };

  const getCurrentStepKey = () => {
    const activeContent = wizardElement?.querySelector('.bs-stepper-content > .content.active');
    if (activeContent instanceof HTMLElement && activeContent.dataset.stepKey) {
      return normalizeStepKey(activeContent.dataset.stepKey);
    }

    const activeHeader = wizardElement?.querySelector('.bs-stepper-header .step.active');
    if (activeHeader instanceof HTMLElement && activeHeader.dataset.stepKey) {
      return normalizeStepKey(activeHeader.dataset.stepKey);
    }

    return 'basic';
  };

  const showStep = stepKey => {
    if (!stepperInstance) {
      return;
    }

    const normalized = normalizeStepKey(stepKey);
    const index = stepOrder.indexOf(normalized);
    if (index === -1) {
      return;
    }

    stepperInstance.to(index + 1);
  };

  const showFinalStep = () => {
    persistStep('final');
    showStep('final');
  };

  const nextButtons = Array.from(document.querySelectorAll('[data-step-next]'));
  const prevButtons = Array.from(document.querySelectorAll('[data-step-prev]'));

  const validateStep = step => {
    if (!step) {
      return true;
    }

    const requiredFields = Array.from(step.querySelectorAll('input[required], textarea[required], select[required]'));
    for (const field of requiredFields) {
      if (!field.reportValidity()) {
        return false;
      }
    }

    return true;
  };

  nextButtons.forEach(button => {
    button.addEventListener('click', () => {
      if (!stepperInstance) {
        return;
      }

      const currentStep = button.closest('.content');
      if (!validateStep(currentStep)) {
        return;
      }

      stepperInstance.next();
    });
  });

  prevButtons.forEach(button => {
    button.addEventListener('click', () => {
      if (!stepperInstance) {
        return;
      }

      stepperInstance.previous();
    });
  });

  if (wizardElement && stepperInstance) {
    wizardElement.addEventListener('shown.bs-stepper', event => {
      const stepKey = getStepKeyByIndex(event.detail.indexStep) || getCurrentStepKey();
      persistStep(stepKey);
    });

    window.setTimeout(() => {
      if (initialStep && stepOrder.includes(initialStep)) {
        showStep(initialStep);
        persistStep(initialStep);
        return;
      }

      const storedStep = getStoredStep();
      if (storedStep && storedStep !== getCurrentStepKey()) {
        showStep(storedStep);
      } else {
        persistStep(getCurrentStepKey());
      }
    }, 0);
  }

  preserveForms.forEach(form => {
    form.addEventListener('submit', () => {
      const requestedStep = form.dataset.preserveStep || form.querySelector('[name="return_step"]')?.value || getCurrentStepKey();
      persistStep(requestedStep);
    });
  });

  stepLinks.forEach(link => {
    link.addEventListener('click', () => {
      persistStep(link.getAttribute('data-step-link') || getCurrentStepKey());
    });
  });

  upgradeToFinalTriggers.forEach(trigger => {
    trigger.addEventListener('click', event => {
      event.preventDefault();
      showFinalStep();
    });
  });

  if (photoTrigger && photoInput instanceof HTMLInputElement) {
    photoTrigger.addEventListener('click', () => {
      if (photoTrigger.hasAttribute('disabled')) {
        return;
      }

      photoInput.click();
    });

    photoInput.addEventListener('change', () => {
      if (!photoInput.files || photoInput.files.length === 0 || !photoInput.form) {
        return;
      }

      persistStep('photos');
      if (typeof photoInput.form.requestSubmit === 'function') {
        photoInput.form.requestSubmit();
        return;
      }

      photoInput.form.submit();
    });
  }

  if (!mainForm || !mainForm.dataset.autosaveUrl) {
    return;
  }

  const autosaveUrl = mainForm.dataset.autosaveUrl;
  const autosaveSync = mainForm.dataset.autosaveSync === 'true';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const googleMapsEnabled = mainForm.dataset.googleMapsEnabled === 'true';
  const googleMapsKey = mainForm.dataset.googleMapsKey || '';
  const googleCountry = (mainForm.dataset.googleCountry || 'co').toLowerCase();
  const locationCities = JSON.parse(mainForm.dataset.locationCities || '[]');
  const locationZones = JSON.parse(mainForm.dataset.locationZones || '[]');

  const cityIdInput = document.getElementById('listing-city-id');
  const cityNameInput = document.getElementById('listing-city-name-input');
  const zoneNameInput = document.getElementById('listing-zone-name-input');
  const neighborhoodInput = document.getElementById('listing-neighborhood-input');
  const suggestZoneInput = document.getElementById('listing-suggest-zone');
  const localityStatus = document.querySelector('[data-locality-status]');
  const stateInput = document.getElementById('listing-state-input');
  const countryInput = document.getElementById('listing-country-input');
  const addressInput = document.getElementById('listing-address-input');
  const latitudeInput = document.getElementById('listing-latitude-input');
  const longitudeInput = document.getElementById('listing-longitude-input');
  const postalCodeInput = document.getElementById('listing-postal-code-input');
  const placeIdInput = document.getElementById('listing-place-id-input');
  const payloadInput = document.getElementById('listing-google-payload-input');
  const primaryZoneIdInput = document.getElementById('listing-primary-zone-id');
  const mapPickerOpenButton = document.getElementById('listing-map-picker-open');
  const mapPickerModalElement = document.getElementById('listing-map-picker-modal');
  const mapPickerCanvas = document.getElementById('listing-map-picker-canvas');
  const mapPickerAddress = document.getElementById('listing-map-picker-address');
  const mapPickerCoordinates = document.getElementById('listing-map-picker-coordinates');
  const mapPickerConfirmButton = document.getElementById('listing-map-picker-confirm');

  const zonePicker = document.querySelector('[data-zone-picker]');
  const zoneAvailableContainer = zonePicker?.querySelector('[data-zone-available]');
  const zoneSelectedContainer = zonePicker?.querySelector('[data-zone-selected]');
  const zoneSearchInput = zonePicker?.querySelector('[data-zone-search]');
  const zoneCountTarget = zonePicker?.querySelector('[data-zone-count]');
  const zoneHiddenInputsContainer = zonePicker?.querySelector('[data-zone-hidden-inputs]');
  const zoneSelectedCopy = zonePicker?.querySelector('[data-zone-selected-copy]');
  const zoneLimitBadge = zonePicker?.querySelector('[data-zone-limit-badge]');
  const zoneUpgradeTile = zonePicker?.querySelector('[data-zone-upgrade]');
  const zoneFeedback = document.querySelector('[data-zone-feedback]');
  const maxZones = Number(zonePicker?.getAttribute('data-max-zones') || 0);
  const basePriceRange = document.querySelector('[data-base-price-range]');
  const basePriceHidden = document.getElementById('listing-base-price-hidden');
  const basePriceDisplay = document.querySelector('[data-base-price-display]');
  const faqList = document.querySelector('[data-faq-list]');
  const faqAddButton = document.querySelector('[data-faq-add]');
  const faqCountTarget = document.querySelector('[data-faq-count]');
  const faqTemplate = document.getElementById('listing-faq-item-template');
  const maxFaqItems = Number(faqList?.getAttribute('data-faq-max') || 10);
  const richEditor = document.querySelector('[data-rich-editor]');
  const richSurface = richEditor?.querySelector('[data-rich-surface]');
  const richInput = richEditor?.querySelector('[data-rich-input]');
  const richCommandButtons = Array.from(richEditor?.querySelectorAll('[data-rich-command]') || []);
  const richLinkPanel = richEditor?.querySelector('[data-rich-link-panel]');
  const richLinkInput = richEditor?.querySelector('[data-rich-link-input]');
  const richLinkError = richEditor?.querySelector('[data-rich-link-error]');
  const richLinkApplyButton = richEditor?.querySelector('[data-rich-link-apply]');
  const richLinkCancelButtons = Array.from(richEditor?.querySelectorAll('[data-rich-link-cancel]') || []);
  const filterGroups = Array.from(document.querySelectorAll('[data-filter-group]'));
  const billingTermButtons = Array.from(document.querySelectorAll('[data-billing-term-button]'));
  const paymentPlanCards = Array.from(document.querySelectorAll('[data-plan-card]'));
  const paymentPlanButtons = Array.from(document.querySelectorAll('[data-open-payment-sheet]'));
  const wompiCheckoutForm = document.getElementById('wompiCheckoutForm');
  const paymentPlanNameTargets = Array.from(document.querySelectorAll('[data-payment-plan-name]'));
  const paymentPlanAmountTargets = Array.from(document.querySelectorAll('[data-payment-plan-amount]'));
  const paymentPlanDurationTargets = Array.from(document.querySelectorAll('[data-payment-plan-duration]'));
  const paymentPlanCodeInputs = Array.from(document.querySelectorAll('[data-payment-plan-code]'));
  const paymentPlanTermInputs = Array.from(document.querySelectorAll('[data-payment-plan-term-months]'));
  const paymentPlanPriceInputs = Array.from(document.querySelectorAll('[data-payment-plan-price]'));
  const planSelectionError = document.querySelector('[data-plan-selection-error]');
  const zonesById = new Map(
    locationZones.map(zone => [Number(zone.id), { id: Number(zone.id), city_id: Number(zone.city_id), name: zone.name }])
  );
  const selectedZoneIds = new Set(
    Array.from(zoneHiddenInputsContainer?.querySelectorAll('input[name="zone_ids[]"]') || [])
      .map(input => Number(input.value || 0))
      .filter(id => id > 0)
  );

  let autosaveTimer = null;
  let requestVersion = 0;
  let activeController = null;
  let autocompleteInitialized = false;
  let mapPickerBindingsReady = false;
  let geocoder = null;
  let mapPickerModal = null;
  let mapPickerMap = null;
  let mapPickerMarker = null;
  let mapPickerLocation = null;
  let mapPickerPlace = null;
  let geocodeRequestVersion = 0;
  let savedRichSelection = null;
  let activeBillingTermMonths = Number(
    document.querySelector('[data-billing-term-picker]')?.getAttribute('data-active-term-months') || 1
  );

  const setAutosaveState = (state, text) => {
    if (!autosaveBadge) {
      return;
    }

    autosaveBadge.textContent = text;
    autosaveBadge.classList.remove('bg-label-secondary', 'bg-label-info', 'bg-label-success', 'bg-label-danger');

    if (state === 'saving') {
      autosaveBadge.classList.add('bg-label-info');
      return;
    }

    if (state === 'saved') {
      autosaveBadge.classList.add('bg-label-success');
      return;
    }

    if (state === 'error') {
      autosaveBadge.classList.add('bg-label-danger');
      return;
    }

    autosaveBadge.classList.add('bg-label-secondary');
  };

  const setAutosaveTime = text => {
    if (!autosaveTime) {
      return;
    }

    autosaveTime.textContent = text;
  };

  const formatSavedAt = isoDate => {
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    return `Ultimo guardado: ${date.toLocaleTimeString()}`;
  };

  const applyCompletion = completion => {
    if (typeof completion !== 'number') {
      return;
    }

    completionTargets.forEach(target => {
      target.textContent = `${completion}%`;
    });
  };

  const autosave = async () => {
    const currentVersion = ++requestVersion;

    if (activeController) {
      activeController.abort();
    }

    activeController = new AbortController();

    const formData = new FormData(mainForm);
    formData.set('_method', 'PATCH');
    formData.set('autosave_sync', autosaveSync ? '1' : '0');

    setAutosaveState('saving', 'Guardando...');
    setAutosaveTime('');

    try {
      const response = await fetch(autosaveUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData,
        signal: activeController.signal
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        const message = payload.message || 'No se pudo guardar el borrador.';
        if (zoneFeedback) {
          zoneFeedback.textContent = /(zona|localidad)/i.test(message) ? message : '';
        }
        throw new Error(message);
      }

      if (currentVersion !== requestVersion) {
        return;
      }

      setAutosaveState('saved', 'Guardado');
      setAutosaveTime(formatSavedAt(payload.saved_at));
      applyCompletion(payload.listing_completion);
      if (zoneFeedback) {
        zoneFeedback.textContent = '';
      }
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return;
      }

      setAutosaveState('error', 'Error al guardar');
      setAutosaveTime(error instanceof Error ? error.message : 'Error de red');
    }
  };

  const queueAutosave = () => {
    window.clearTimeout(autosaveTimer);
    autosaveTimer = window.setTimeout(() => {
      autosave().catch(() => {
        // handled inside autosave
      });
    }, 1200);
  };

  const copFormatter = new Intl.NumberFormat('es-CO');
  const basePriceRangeMax = Number(basePriceRange?.getAttribute('max') || 4000000);
  const basePriceRangeStep = Number(basePriceRange?.getAttribute('step') || 5000);

  const normalizePriceValue = value => {
    const parsed = Number.parseFloat(String(value ?? '').replace(/[^\d.-]/g, ''));
    if (!Number.isFinite(parsed) || parsed < 0) {
      return null;
    }

    const rounded = Math.round(parsed / Math.max(basePriceRangeStep, 1)) * Math.max(basePriceRangeStep, 1);
    return Math.min(basePriceRangeMax, Math.max(0, rounded));
  };

  const renderBasePrice = value => {
    if (!basePriceDisplay) {
      return;
    }

    if (value === null) {
      basePriceDisplay.textContent = '$—';
      return;
    }

    basePriceDisplay.textContent = `$${copFormatter.format(value)}`;
  };

  const syncBasePriceControls = rawValue => {
    if (!basePriceRange || !basePriceHidden) {
      return;
    }

    const normalized = normalizePriceValue(rawValue);
    basePriceRange.value = String(normalized ?? 0);
    basePriceHidden.value = normalized === null ? '' : String(normalized);
    renderBasePrice(normalized);
  };

  const updateFaqCount = () => {
    if (!faqList) {
      return;
    }

    const count = faqList.querySelectorAll('[data-faq-item]').length;

    if (faqCountTarget) {
      faqCountTarget.textContent = String(count);
    }

    if (faqAddButton instanceof HTMLButtonElement) {
      faqAddButton.disabled = count >= maxFaqItems;
    }
  };

  const bindFaqItem = item => {
    if (!(item instanceof HTMLElement) || item.dataset.faqBound === 'true') {
      return;
    }

    item.dataset.faqBound = 'true';

    const removeButton = item.querySelector('[data-faq-remove]');
    if (removeButton instanceof HTMLButtonElement) {
      removeButton.addEventListener('click', () => {
        item.remove();
        updateFaqCount();
        queueAutosave();
      });
    }
  };

  const addFaqItem = () => {
    if (!faqList || !faqTemplate || faqList.querySelectorAll('[data-faq-item]').length >= maxFaqItems) {
      return;
    }

    const fragment = faqTemplate.content.cloneNode(true);
    const item = fragment.firstElementChild;
    if (!(item instanceof HTMLElement)) {
      return;
    }

    bindFaqItem(item);
    faqList.appendChild(item);
    updateFaqCount();

    const firstField = item.querySelector('input[name="faq_question[]"]');
    if (firstField instanceof HTMLInputElement) {
      firstField.focus();
    }
  };

  const sanitizeRichEditorHtml = html => {
    const template = document.createElement('template');
    template.innerHTML = String(html || '');
    const allowedTags = new Set(['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h2', 'h3']);
    const isAllowedLink = href => /^(https?:|mailto:|tel:)/i.test(String(href || '').trim());

    const unwrapElement = element => {
      const parent = element.parentNode;
      if (!parent) {
        return;
      }

      while (element.firstChild) {
        parent.insertBefore(element.firstChild, element);
      }

      parent.removeChild(element);
    };

    const replaceElement = (element, tagName) => {
      const replacement = document.createElement(tagName);

      while (element.firstChild) {
        replacement.appendChild(element.firstChild);
      }

      element.parentNode?.replaceChild(replacement, element);

      return replacement;
    };

    const walk = node => {
      Array.from(node.childNodes).forEach(childNode => {
        if (childNode.nodeType === Node.COMMENT_NODE) {
          childNode.parentNode?.removeChild(childNode);
          return;
        }

        if (childNode.nodeType !== Node.ELEMENT_NODE) {
          return;
        }

        let element = childNode;
        let tag = element.tagName.toLowerCase();

        if (tag === 'b') {
          element = replaceElement(element, 'strong');
          tag = 'strong';
        } else if (tag === 'i') {
          element = replaceElement(element, 'em');
          tag = 'em';
        } else if (tag === 'div') {
          element = replaceElement(element, 'p');
          tag = 'p';
        }

        if (!allowedTags.has(tag)) {
          unwrapElement(element);
          return;
        }

        Array.from(element.attributes).forEach(attribute => {
          if (tag === 'a' && attribute.name === 'href' && isAllowedLink(attribute.value)) {
            return;
          }

          element.removeAttribute(attribute.name);
        });

        if (tag === 'a') {
          const href = String(element.getAttribute('href') || '').trim();
          if (!isAllowedLink(href)) {
            unwrapElement(element);
            return;
          }

          element.setAttribute('rel', 'nofollow noopener noreferrer');
          element.setAttribute('target', '_blank');
        }

        walk(element);
      });
    };

    walk(template.content);

    return template.innerHTML
      .replace(/<(p|h2|h3)>\s*<\/\1>/gi, '')
      .trim();
  };

  const syncRichEditorInput = () => {
    if (!(richSurface instanceof HTMLElement) || !(richInput instanceof HTMLTextAreaElement)) {
      return;
    }

    const sanitized = sanitizeRichEditorHtml(richSurface.innerHTML);
    if (richSurface.innerHTML !== sanitized) {
      richSurface.innerHTML = sanitized;
    }

    richInput.value = sanitized;
  };

  const getRichSelectionRange = () => {
    if (!(richSurface instanceof HTMLElement)) {
      return null;
    }

    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
      return null;
    }

    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer.nodeType === Node.TEXT_NODE
      ? range.commonAncestorContainer.parentNode
      : range.commonAncestorContainer;

    if (!(container instanceof Node) || !richSurface.contains(container)) {
      return null;
    }

    return range;
  };

  const captureRichSelection = () => {
    const range = getRichSelectionRange();
    savedRichSelection = range ? range.cloneRange() : null;
  };

  const restoreRichSelection = () => {
    if (!(richSurface instanceof HTMLElement)) {
      return;
    }

    richSurface.focus();

    const selection = window.getSelection();
    if (!selection) {
      return;
    }

    selection.removeAllRanges();

    if (savedRichSelection instanceof Range) {
      selection.addRange(savedRichSelection);
    }
  };

  const clearRichLinkError = () => {
    if (richLinkError instanceof HTMLElement) {
      richLinkError.textContent = '';
    }
  };

  const normalizeRichLinkUrl = value => {
    let url = String(value || '').trim();
    if (!url) {
      return '';
    }

    if (!/^(https?:|mailto:|tel:)/i.test(url)) {
      url = `https://${url.replace(/^\/+/, '')}`;
    }

    return /^(https?:|mailto:|tel:)/i.test(url) ? url : '';
  };

  const closeRichLinkPanel = (shouldFocusEditor = true) => {
    if (!(richLinkPanel instanceof HTMLElement)) {
      return;
    }

    richLinkPanel.hidden = true;
    clearRichLinkError();

    if (richLinkInput instanceof HTMLInputElement) {
      richLinkInput.value = '';
    }

    if (shouldFocusEditor && richSurface instanceof HTMLElement) {
      restoreRichSelection();
    }
  };

  const openRichLinkPanel = () => {
    if (!(richLinkPanel instanceof HTMLElement) || !(richLinkInput instanceof HTMLInputElement)) {
      return;
    }

    captureRichSelection();
    clearRichLinkError();
    richLinkPanel.hidden = false;

    const selection = window.getSelection();
    const anchor = selection?.anchorNode instanceof Node && selection.anchorNode.parentElement
      ? selection.anchorNode.parentElement.closest('a')
      : null;

    richLinkInput.value = anchor?.getAttribute('href') || '';
    window.setTimeout(() => {
      richLinkInput.focus();
      richLinkInput.select();
    }, 0);
  };

  const applyRichLink = () => {
    if (!(richSurface instanceof HTMLElement) || !(richLinkInput instanceof HTMLInputElement)) {
      return;
    }

    const normalizedUrl = normalizeRichLinkUrl(richLinkInput.value);
    if (!normalizedUrl) {
      if (richLinkError instanceof HTMLElement) {
        richLinkError.textContent = 'Escribe un enlace válido: https://, mailto: o tel:.';
      }
      richLinkInput.focus();
      return;
    }

    restoreRichSelection();

    const selection = window.getSelection();
    const selectedText = selection ? String(selection).trim() : '';

    if (selectedText !== '') {
      document.execCommand('createLink', false, normalizedUrl);
    } else {
      const safeUrl = normalizedUrl
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

      document.execCommand(
        'insertHTML',
        false,
        `<a href="${safeUrl}" target="_blank" rel="nofollow noopener noreferrer">${safeUrl}</a>`
      );
    }

    syncRichEditorInput();
    captureRichSelection();
    closeRichLinkPanel(false);
    queueAutosave();
    richSurface.focus();
  };

  const executeRichCommand = button => {
    if (!(richSurface instanceof HTMLElement)) {
      return;
    }

    const command = button.dataset.richCommand || '';
    const commandValue = button.dataset.richValue || null;
    if (command === 'createLink') {
      openRichLinkPanel();
      return;
    }

    restoreRichSelection();

    document.execCommand(command, false, commandValue);

    syncRichEditorInput();
    captureRichSelection();
    queueAutosave();
  };

  const updateFilterGroup = group => {
    const limit = Number(group.getAttribute('data-limit') || 0);
    const checkboxes = Array.from(group.querySelectorAll('input[type="checkbox"]'));
    const countTarget = group.querySelector('[data-filter-count]');
    const upgradeBox = group.querySelector('[data-filter-upgrade]');
    const selectedCount = checkboxes.filter(checkbox => checkbox.checked).length;
    const reachedLimit = limit <= 0 || selectedCount >= limit;
    const hasBlockedOptions = checkboxes.some(checkbox => !checkbox.checked);

    if (countTarget) {
      countTarget.textContent = String(selectedCount);
    }

    if (upgradeBox instanceof HTMLElement) {
      upgradeBox.hidden = !(reachedLimit && hasBlockedOptions);
    }

    checkboxes.forEach(checkbox => {
      const shouldDisable = !checkbox.checked && reachedLimit;
      checkbox.disabled = shouldDisable;
      checkbox.closest('.form-check')?.classList.toggle('is-disabled', shouldDisable);
    });
  };

  const syncPaymentSheet = (button, checkout = {}) => {
    const price = Number(checkout.amount_cop || button.dataset.planPrice || 0);
    const termLabel = checkout.term_label || button.dataset.planTermLabel || '1 mes';
    const termMonths = String(checkout.term_months || button.dataset.planTermMonths || '1');
    const planCode = checkout.plan_code || button.dataset.planCode || '';
    const planName = checkout.plan_name || button.dataset.planName || '';

    paymentPlanNameTargets.forEach(target => {
      target.textContent = planName;
    });

    paymentPlanAmountTargets.forEach(target => {
      target.textContent = `$${copFormatter.format(price)} COP`;
    });

    paymentPlanDurationTargets.forEach(target => {
      target.textContent = termLabel;
    });

    paymentPlanCodeInputs.forEach(input => {
      input.value = planCode;
    });

    paymentPlanTermInputs.forEach(input => {
      input.value = termMonths;
    });

    paymentPlanPriceInputs.forEach(input => {
      input.value = String(price);
    });
  };

  const parsePlanTerms = rawValue => {
    try {
      const parsed = JSON.parse(rawValue || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  };

  const resolvePlanTerm = (terms, requestedMonths) => {
    const normalizedMonths = Number(requestedMonths || 0);
    return terms.find(term => Number(term.months || 0) === normalizedMonths) || terms[0] || null;
  };

  const updateBillingButtons = months => {
    billingTermButtons.forEach(button => {
      button.classList.toggle('is-active', Number(button.dataset.termMonths || 0) === months);
    });
  };

  const updatePlanCardForTerm = card => {
    const terms = parsePlanTerms(card.getAttribute('data-plan-terms'));
    const activeTerm = resolvePlanTerm(terms, activeBillingTermMonths);

    if (!activeTerm) {
      return;
    }

    const totalTarget = card.querySelector('[data-plan-total]');
    const periodTarget = card.querySelector('[data-plan-period-label]');
    const monthlyTarget = card.querySelector('[data-plan-monthly-equivalent]');
    const savingsTarget = card.querySelector('[data-plan-savings]');
    const actionButton = card.querySelector('[data-open-payment-sheet]');

    if (totalTarget) {
      totalTarget.textContent = `$${copFormatter.format(Number(activeTerm.total_price_cop || 0))}`;
    }

    if (periodTarget) {
      periodTarget.textContent = `Total ${activeTerm.label || '1 mes'}`;
    }

    if (monthlyTarget) {
      monthlyTarget.textContent = `$${copFormatter.format(Number(activeTerm.monthly_equivalent_cop || 0))} / mes equivalente`;
    }

    if (savingsTarget instanceof HTMLElement) {
      savingsTarget.textContent = activeTerm.savings_copy || 'Precio regular';
      savingsTarget.classList.toggle('is-muted', Number(activeTerm.discount_percent || 0) === 0);
    }

    if (actionButton instanceof HTMLElement) {
      actionButton.dataset.planPrice = String(Number(activeTerm.total_price_cop || 0));
      actionButton.dataset.planTermMonths = String(Number(activeTerm.months || 1));
      actionButton.dataset.planTermLabel = activeTerm.label || '1 mes';
    }
  };

  const applyBillingTermSelection = months => {
    activeBillingTermMonths = Number(months || 1);
    updateBillingButtons(activeBillingTermMonths);
    paymentPlanCards.forEach(updatePlanCardForTerm);

    if (paymentPlanButtons[0]) {
      syncPaymentSheet(paymentPlanButtons[0]);
    }
  };

  const normalize = value =>
    String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
      .replace(/\s+/g, ' ');

  const getLocationCoords = place => {
    const location = place?.geometry?.location;
    if (!location) {
      return null;
    }

    const lat = typeof location.lat === 'function' ? Number(location.lat()) : Number(location.lat);
    const lng = typeof location.lng === 'function' ? Number(location.lng()) : Number(location.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return null;
    }

    return { lat, lng };
  };

  const buildVisibleAddress = place => {
    const route = extractComponent(place, ['route']);
    const streetNumber = extractComponent(place, ['street_number']).replace(/^#\s*/, '').trim();
    const premise = extractComponent(place, ['premise']);

    if (route && streetNumber) {
      return `${route} #${streetNumber}`;
    }

    if (route) {
      return route;
    }

    if (premise) {
      return premise;
    }

    const firstSegment = String(place?.formatted_address || '')
      .split(',')
      .map(segment => segment.trim())
      .find(Boolean);

    return firstSegment || String(addressInput?.value || '').trim();
  };

  const setLocalityStatus = (message = '', tone = 'muted') => {
    if (!localityStatus) {
      return;
    }

    localityStatus.hidden = message === '';
    localityStatus.textContent = message;
    localityStatus.classList.remove('text-muted', 'text-warning', 'text-success');
    localityStatus.classList.add(tone === 'warning' ? 'text-warning' : tone === 'success' ? 'text-success' : 'text-muted');
  };

  const syncDetectedLocationState = ({ localityName = '', neighborhoodName = '', matchedCity = null, matchedZone = null }) => {
    if (neighborhoodInput) {
      neighborhoodInput.value = neighborhoodName || '';
    }

    if (matchedCity && localityName && !matchedZone) {
      setLocalityStatus(
        `Localidad detectada pendiente de catalogo: ${localityName}. La enviaremos como sugerencia para aprobacion admin.`,
        'warning'
      );
      return;
    }

    if (matchedCity && matchedZone) {
      setLocalityStatus(`Localidad detectada en catalogo: ${matchedZone.name}.`, 'success');
      return;
    }

    if (localityName) {
      setLocalityStatus(`Localidad detectada: ${localityName}.`, 'muted');
      return;
    }

    if (neighborhoodName) {
      setLocalityStatus(`Barrio detectado: ${neighborhoodName}.`, 'muted');
      return;
    }

    setLocalityStatus('');
  };

  const setMapPickerSummary = (addressText, coordinates = null) => {
    if (mapPickerAddress) {
      mapPickerAddress.textContent = addressText || 'Mueve el pin para resolver la direccion exacta.';
    }

    if (!mapPickerCoordinates) {
      return;
    }

    if (!coordinates) {
      mapPickerCoordinates.textContent = 'Sin coordenadas todavia.';
      return;
    }

    mapPickerCoordinates.textContent = `${coordinates.lat.toFixed(6)}, ${coordinates.lng.toFixed(6)}`;
  };

  const getCurrentLatLng = () => {
    const lat = Number.parseFloat(latitudeInput?.value || '');
    const lng = Number.parseFloat(longitudeInput?.value || '');

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return null;
    }

    return { lat, lng };
  };

  const findCityByName = cityName => {
    const normalized = normalize(cityName);
    return locationCities.find(city => normalize(city.name) === normalized) || null;
  };

  const findZoneByName = (zoneName, cityId) => {
    const normalized = normalize(zoneName);
    return locationZones.find(zone => zone.city_id === cityId && normalize(zone.name) === normalized) || null;
  };

  const clearResolvedPlace = () => {
    if (cityIdInput) {
      cityIdInput.value = '';
    }
    if (cityNameInput) {
      cityNameInput.value = '';
    }
    if (zoneNameInput) {
      zoneNameInput.value = '';
    }
    if (primaryZoneIdInput) {
      primaryZoneIdInput.value = '';
    }
    if (stateInput) {
      stateInput.value = '';
    }
    if (latitudeInput) {
      latitudeInput.value = '';
    }
    if (longitudeInput) {
      longitudeInput.value = '';
    }
    if (postalCodeInput) {
      postalCodeInput.value = '';
    }
    if (placeIdInput) {
      placeIdInput.value = '';
    }
    if (payloadInput) {
      payloadInput.value = '';
    }
    if (suggestZoneInput) {
      suggestZoneInput.value = '';
    }
    if (neighborhoodInput) {
      neighborhoodInput.value = '';
    }
    if (addressInput) {
      delete addressInput.dataset.resolvedAddress;
      delete addressInput.dataset.displayAddress;
    }

    mapPickerLocation = null;
    mapPickerPlace = null;
    setLocalityStatus('');
    setMapPickerSummary('', null);
    renderZonePicker();
  };

  const syncZoneHiddenInputs = () => {
    if (!zoneHiddenInputsContainer) {
      return;
    }

    zoneHiddenInputsContainer.innerHTML = '';
    Array.from(selectedZoneIds).forEach(zoneId => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'zone_ids[]';
      input.value = String(zoneId);
      zoneHiddenInputsContainer.appendChild(input);
    });
  };

  const createZoneMeta = (zone, subtitle) => {
    const meta = document.createElement('div');
    meta.className = 'listing-zone-item__meta';

    const name = document.createElement('div');
    name.className = 'listing-zone-item__name';
    name.textContent = zone.name;

    const subline = document.createElement('div');
    subline.className = 'listing-zone-item__city';
    subline.textContent = subtitle;

    meta.appendChild(name);
    meta.appendChild(subline);

    return meta;
  };

  const createZoneEmpty = (title, copy) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'listing-zone-empty';

    const icon = document.createElement('span');
    icon.className = 'avatar avatar-lg bg-label-secondary mb-3';
    icon.innerHTML = '<span class="avatar-initial rounded"><i class="icon-base ti tabler-map-pin icon-md"></i></span>';

    const strong = document.createElement('strong');
    strong.className = 'text-heading';
    strong.textContent = title;

    const text = document.createElement('span');
    text.className = 'text-muted small mt-1';
    text.textContent = copy;

    wrapper.append(icon, strong, text);
    return wrapper;
  };

  const renderZonePicker = () => {
    if (!zonePicker || !zoneAvailableContainer || !zoneSelectedContainer) {
      return;
    }

    const selectedCityId = Number(cityIdInput?.value || 0);
    const searchTerm = normalize(zoneSearchInput?.value || '');
    let primaryZoneId = Number(primaryZoneIdInput?.value || 0);
    const primaryZone = primaryZoneId > 0 ? zonesById.get(primaryZoneId) : null;

    Array.from(selectedZoneIds).forEach(zoneId => {
      const zone = zonesById.get(zoneId);
      if (!zone || (selectedCityId > 0 && zone.city_id !== selectedCityId)) {
        selectedZoneIds.delete(zoneId);
      }
    });

    if (primaryZone && selectedCityId > 0 && primaryZone.city_id !== selectedCityId) {
      primaryZoneId = 0;
      if (primaryZoneIdInput) {
        primaryZoneIdInput.value = '';
      }
    }

    const selectedZones = Array.from(selectedZoneIds)
      .map(zoneId => zonesById.get(zoneId))
      .filter(Boolean);

    const countedSelected = selectedZones.length + (primaryZoneId > 0 ? 1 : 0);
    const hasExtraCoverage = zonePicker?.getAttribute('data-has-extra-coverage') !== 'false';
    const atLimit = !hasExtraCoverage || maxZones <= 0 || countedSelected >= maxZones;
    if (zoneCountTarget) {
      zoneCountTarget.textContent = String(countedSelected);
    }
    if (zoneSelectedCopy) {
      zoneSelectedCopy.textContent = atLimit
        ? 'Llegaste al límite de tu plan actual. Quita una localidad o mejora a Plan Pro para ampliar cobertura.'
        : 'La localidad principal se detecta automáticamente y cuenta dentro del límite.';
      zoneSelectedCopy.classList.toggle('is-limit', atLimit);
    }
    if (zoneLimitBadge) {
      zoneLimitBadge.textContent = atLimit ? 'Límite alcanzado' : `Máx ${maxZones}`;
      zoneLimitBadge.classList.toggle('bg-label-primary', !atLimit);
      zoneLimitBadge.classList.toggle('bg-label-warning', atLimit);
    }
    if (zoneUpgradeTile) {
      zoneUpgradeTile.hidden = !(atLimit && hasExtraCoverage);
    }

    zoneSelectedContainer.innerHTML = '';
    if (primaryZoneId > 0 && primaryZone) {
      const card = document.createElement('div');
      card.className = 'listing-zone-item listing-zone-item--primary';
      card.appendChild(createZoneMeta(primaryZone, 'Localidad principal detectada'));

      const badge = document.createElement('span');
      badge.className = 'badge bg-success';
      badge.textContent = 'Principal';
      card.appendChild(badge);
      zoneSelectedContainer.appendChild(card);
    }

    if (selectedZones.length > 0) {
      selectedZones.forEach(zone => {
        const card = document.createElement('div');
        card.className = 'listing-zone-item listing-zone-item--selected';
        card.appendChild(createZoneMeta(zone, 'Cobertura adicional'));

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-danger';
        button.textContent = 'Quitar';
        button.addEventListener('click', () => {
          selectedZoneIds.delete(zone.id);
          syncZoneHiddenInputs();
          queueAutosave();
          renderZonePicker();
        });

        card.appendChild(button);
        zoneSelectedContainer.appendChild(card);
      });
    }

    if (zoneSelectedContainer.children.length === 0) {
      zoneSelectedContainer.appendChild(
        createZoneEmpty('Aun no seleccionas localidades', 'La localidad principal aparecera aqui cuando la direccion quede bien detectada.')
      );
    }

    zoneAvailableContainer.innerHTML = '';

    if (!selectedCityId) {
      zoneAvailableContainer.appendChild(
        createZoneEmpty('Primero confirma la ciudad', 'Cuando la ciudad principal este detectada, veras aqui las localidades disponibles.')
      );
      syncZoneHiddenInputs();
      return;
    }

    const filteredZones = locationZones.filter(zone => {
      if (zone.city_id !== selectedCityId) {
        return false;
      }

      if (zone.id === primaryZoneId || selectedZoneIds.has(zone.id)) {
        return false;
      }

      return !searchTerm || normalize(zone.name).includes(searchTerm);
    });

    if (filteredZones.length === 0) {
      zoneAvailableContainer.appendChild(
        createZoneEmpty('No hay mas localidades disponibles', atLimit ? 'Alcanzaste el límite actual. Quita una localidad o revisa Plan Pro para ampliar cobertura.' : 'Ajusta la búsqueda o cambia la ciudad principal.')
      );
      syncZoneHiddenInputs();
      return;
    }

    filteredZones.forEach(zone => {
      const card = document.createElement('div');
      card.className = 'listing-zone-item';
      card.appendChild(createZoneMeta(zone, 'Disponible para agregar'));

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-sm btn-outline-primary';
      button.textContent = '+';
      button.disabled = atLimit;
      button.addEventListener('click', () => {
        if (maxZones <= 0 || selectedZoneIds.has(zone.id)) {
          return;
        }

        const activePrimaryZoneId = Number(primaryZoneIdInput?.value || 0);
        const currentCount = selectedZoneIds.size + (activePrimaryZoneId > 0 ? 1 : 0);
        if (currentCount >= maxZones) {
          renderZonePicker();
          return;
        }

        selectedZoneIds.add(zone.id);
        syncZoneHiddenInputs();
        queueAutosave();
        renderZonePicker();
      });

      card.appendChild(button);
      zoneAvailableContainer.appendChild(card);
    });

    syncZoneHiddenInputs();
  };

  const resolveZoneSelection = () => {
    if (!zoneNameInput || !primaryZoneIdInput) {
      renderZonePicker();
      return;
    }

    const selectedCityId = Number(cityIdInput?.value || 0);
    const matchedCity = selectedCityId > 0
      ? locationCities.find(city => Number(city.id) === selectedCityId) || null
      : null;

    if (!selectedCityId) {
      primaryZoneIdInput.value = '';
      syncDetectedLocationState({
        localityName: zoneNameInput.value,
        neighborhoodName: neighborhoodInput?.value || '',
        matchedCity: null,
        matchedZone: null
      });
      renderZonePicker();
      return;
    }

    const matchedZone = findZoneByName(zoneNameInput.value, selectedCityId);
    primaryZoneIdInput.value = matchedZone ? String(matchedZone.id) : '';
    syncDetectedLocationState({
      localityName: zoneNameInput.value,
      neighborhoodName: neighborhoodInput?.value || '',
      matchedCity,
      matchedZone
    });
    renderZonePicker();
  };

  const resolveCitySelection = () => {
    if (!cityNameInput || !cityIdInput) {
      renderZonePicker();
      return;
    }

    const matchedCity = findCityByName(cityNameInput.value);
    cityIdInput.value = matchedCity ? String(matchedCity.id) : '';
    resolveZoneSelection();
  };

  const extractComponent = (place, candidates) => {
    if (!place.address_components) {
      return '';
    }

    for (const candidate of candidates) {
      const match = place.address_components.find(component => component.types.includes(candidate));
      if (match) {
        return match.long_name || '';
      }
    }

    return '';
  };

  const applyPlaceSelection = (place, options = {}) => {
    const coordinates = getLocationCoords(place);
    if (!place || !coordinates) {
      return;
    }

    const cityCandidate =
      extractComponent(place, ['locality']) ||
      extractComponent(place, ['administrative_area_level_3']) ||
      extractComponent(place, ['postal_town']) ||
      extractComponent(place, ['administrative_area_level_2']);

    const stateCandidate = extractComponent(place, ['administrative_area_level_1']);
    const localityCandidate =
      extractComponent(place, ['sublocality_level_1']) ||
      extractComponent(place, ['sublocality']) ||
      extractComponent(place, ['neighborhood']) ||
      extractComponent(place, ['administrative_area_level_4']);
    const neighborhoodCandidate =
      extractComponent(place, ['neighborhood']) ||
      extractComponent(place, ['sublocality_level_2']) ||
      extractComponent(place, ['administrative_area_level_5']);
    const postalCode = extractComponent(place, ['postal_code']);
    const countryCandidate = extractComponent(place, ['country']);
    const matchedCity = findCityByName(cityCandidate);
    const matchedZone = matchedCity ? findZoneByName(localityCandidate, matchedCity.id) : null;
    const visibleAddress = buildVisibleAddress(place);
    const resolvedCityName = matchedCity?.name || cityCandidate || '';
    const resolvedLocalityName = matchedZone?.name || localityCandidate || '';

    if (addressInput) {
      addressInput.value = visibleAddress || place.formatted_address || addressInput.value;
      addressInput.dataset.displayAddress = addressInput.value;
      addressInput.dataset.resolvedAddress = place.formatted_address || addressInput.value;
    }
    if (cityNameInput) {
      cityNameInput.value = resolvedCityName;
    }
    if (stateInput) {
      stateInput.value = stateCandidate || '';
    }
    if (zoneNameInput) {
      zoneNameInput.value = resolvedLocalityName;
    }
    if (suggestZoneInput) {
      suggestZoneInput.value = matchedCity && localityCandidate && !matchedZone ? localityCandidate : '';
    }
    if (cityIdInput) {
      cityIdInput.value = matchedCity ? String(matchedCity.id) : '';
    }
    if (primaryZoneIdInput) {
      primaryZoneIdInput.value = matchedZone ? String(matchedZone.id) : '';
    }
    if (postalCodeInput) {
      postalCodeInput.value = postalCode || '';
    }
    if (latitudeInput) {
      latitudeInput.value = coordinates.lat.toFixed(7);
    }
    if (longitudeInput) {
      longitudeInput.value = coordinates.lng.toFixed(7);
    }
    if (placeIdInput) {
      placeIdInput.value = place.place_id || '';
    }
    if (countryInput) {
      countryInput.value = countryCandidate || countryInput.value || 'Colombia';
    }
    if (payloadInput) {
      payloadInput.value = JSON.stringify({
        place_id: place.place_id || null,
        formatted_address: place.formatted_address || null,
        geometry: coordinates,
        address_components: (place.address_components || []).map(component => ({
          long_name: component.long_name,
          short_name: component.short_name,
          types: component.types
        }))
      });
    }

    mapPickerLocation = coordinates;
    mapPickerPlace = place;
    setMapPickerSummary(place.formatted_address || visibleAddress, coordinates);
    syncDetectedLocationState({
      localityName: resolvedLocalityName,
      neighborhoodName: neighborhoodCandidate,
      matchedCity,
      matchedZone
    });
    renderZonePicker();

    if (options.queue !== false) {
      queueAutosave();
    }
  };

  const buildStoredPlace = payload => {
    const lat = Number(payload?.geometry?.lat);
    const lng = Number(payload?.geometry?.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return null;
    }

    return {
      place_id: payload?.place_id || '',
      formatted_address: payload?.formatted_address || '',
      address_components: Array.isArray(payload?.address_components) ? payload.address_components : [],
      geometry: {
        location: {
          lat: () => lat,
          lng: () => lng
        }
      }
    };
  };

  const parseStoredPlace = () => {
    if (!payloadInput?.value) {
      return null;
    }

    try {
      return buildStoredPlace(JSON.parse(payloadInput.value));
    } catch (error) {
      return null;
    }
  };

  const resolveMapPickerOrigin = () => getCurrentLatLng() || getLocationCoords(mapPickerPlace) || { lat: 4.711, lng: -74.0721 };

  const setMapPickerLocation = coordinates => {
    if (!coordinates) {
      return;
    }

    mapPickerLocation = {
      lat: Number(coordinates.lat),
      lng: Number(coordinates.lng)
    };

    if (!Number.isFinite(mapPickerLocation.lat) || !Number.isFinite(mapPickerLocation.lng)) {
      mapPickerLocation = null;
      setMapPickerSummary('', null);
      return;
    }

    if (mapPickerMarker) {
      mapPickerMarker.setPosition(mapPickerLocation);
    }

    if (mapPickerMap) {
      mapPickerMap.setCenter(mapPickerLocation);
    }

    setMapPickerSummary(mapPickerPlace?.formatted_address || addressInput?.dataset.resolvedAddress || '', mapPickerLocation);
  };

  const buildReverseGeocodedPlace = (result, coordinates) => ({
    ...result,
    geometry: {
      ...(result.geometry || {}),
      location: {
        lat: () => coordinates.lat,
        lng: () => coordinates.lng
      }
    }
  });

  const reverseGeocodeLocation = coordinates =>
    new Promise(resolve => {
      if (!geocoder || !coordinates) {
        resolve(null);
        return;
      }

      geocoder.geocode({ location: coordinates }, (results, status) => {
        if (status !== 'OK' || !Array.isArray(results) || !results[0]) {
          resolve(null);
          return;
        }

        resolve(buildReverseGeocodedPlace(results[0], coordinates));
      });
    });

  const previewMapPickerLocation = async coordinates => {
    const requestId = ++geocodeRequestVersion;
    setMapPickerSummary('Buscando direccion...', coordinates);

    const place = await reverseGeocodeLocation(coordinates);
    if (requestId !== geocodeRequestVersion) {
      return null;
    }

    mapPickerPlace = place;
    if (place) {
      setMapPickerSummary(place.formatted_address || buildVisibleAddress(place), coordinates);
      return place;
    }

    setMapPickerSummary('No pudimos resolver esta ubicacion. Ajusta el pin y vuelve a intentar.', coordinates);
    return null;
  };

  const loadGoogleMaps = () => {
    if (!googleMapsEnabled || !googleMapsKey || !addressInput) {
      return Promise.resolve();
    }

    if (window.google && window.google.maps && window.google.maps.places) {
      return Promise.resolve();
    }

    const existingScript = document.querySelector('script[data-google-maps-loader="listing"]');
    if (existingScript) {
      return new Promise(resolve => {
        existingScript.addEventListener('load', resolve, { once: true });
      });
    }

    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(googleMapsKey)}&libraries=places`;
      script.async = true;
      script.defer = true;
      script.dataset.googleMapsLoader = 'listing';
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  };

  const initializeAutocomplete = () => {
    if (
      autocompleteInitialized ||
      !googleMapsEnabled ||
      !addressInput ||
      !(window.google && window.google.maps && window.google.maps.places)
    ) {
      return;
    }

    const autocomplete = new window.google.maps.places.Autocomplete(addressInput, {
      fields: ['address_components', 'formatted_address', 'geometry', 'place_id'],
      componentRestrictions: { country: googleCountry },
      types: ['address']
    });

    autocomplete.addListener('place_changed', () => {
      applyPlaceSelection(autocomplete.getPlace());
    });

    autocompleteInitialized = true;
  };

  const ensureMapPickerReady = () => {
    if (!mapPickerCanvas || !(window.google && window.google.maps)) {
      return false;
    }

    geocoder = geocoder || new window.google.maps.Geocoder();

    if (!mapPickerMap) {
      const origin = resolveMapPickerOrigin();
      mapPickerMap = new window.google.maps.Map(mapPickerCanvas, {
        center: origin,
        zoom: getCurrentLatLng() ? 17 : 14,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
      });

      mapPickerMarker = new window.google.maps.Marker({
        map: mapPickerMap,
        position: origin,
        draggable: true
      });

      mapPickerMarker.addListener('dragend', event => {
        if (!event.latLng) {
          return;
        }

        const coordinates = { lat: event.latLng.lat(), lng: event.latLng.lng() };
        setMapPickerLocation(coordinates);
        previewMapPickerLocation(coordinates).catch(() => {
          setMapPickerSummary('No pudimos resolver esta ubicacion. Ajusta el pin y vuelve a intentar.', coordinates);
        });
      });

      mapPickerMap.addListener('click', event => {
        if (!event.latLng) {
          return;
        }

        const coordinates = { lat: event.latLng.lat(), lng: event.latLng.lng() };
        setMapPickerLocation(coordinates);
        previewMapPickerLocation(coordinates).catch(() => {
          setMapPickerSummary('No pudimos resolver esta ubicacion. Ajusta el pin y vuelve a intentar.', coordinates);
        });
      });
    }

    return true;
  };

  const initializeMapPicker = () => {
    if (
      mapPickerBindingsReady ||
      !mapPickerOpenButton ||
      !mapPickerModalElement ||
      !mapPickerConfirmButton ||
      !window.bootstrap?.Modal
    ) {
      return;
    }

    mapPickerModal = new window.bootstrap.Modal(mapPickerModalElement);

    mapPickerModalElement.addEventListener('shown.bs.modal', () => {
      if (!ensureMapPickerReady()) {
        return;
      }

      const origin = resolveMapPickerOrigin();
      mapPickerPlace = parseStoredPlace() || mapPickerPlace;
      setMapPickerLocation(origin);
      mapPickerMap.setZoom(getCurrentLatLng() ? 17 : 14);

      window.setTimeout(() => {
        if (!mapPickerMap) {
          return;
        }

        mapPickerMap.setCenter(origin);
        if (!mapPickerPlace) {
          previewMapPickerLocation(origin).catch(() => {
            setMapPickerSummary('No pudimos resolver esta ubicacion. Ajusta el pin y vuelve a intentar.', origin);
          });
        }
      }, 120);
    });

    mapPickerOpenButton.addEventListener('click', () => {
      loadGoogleMaps()
        .then(() => {
          initializeAutocomplete();
          initializeMapPicker();
          if (!ensureMapPickerReady()) {
            return;
          }

          const origin = resolveMapPickerOrigin();
          mapPickerPlace = parseStoredPlace() || mapPickerPlace;
          setMapPickerLocation(origin);
          mapPickerModal?.show();
        })
        .catch(() => {
          setAutosaveState('error', 'Google Maps no cargo');
          setAutosaveTime('No pudimos abrir el mapa.');
        });
    });

    mapPickerConfirmButton.addEventListener('click', async () => {
      if (!mapPickerLocation) {
        return;
      }

      mapPickerConfirmButton.disabled = true;

      try {
        const place = mapPickerPlace || (await previewMapPickerLocation(mapPickerLocation));
        if (!place) {
          setAutosaveState('error', 'Ubicacion no resuelta');
          setAutosaveTime('Ajusta el pin y vuelve a intentar.');
          return;
        }

        applyPlaceSelection(place);
        mapPickerModal?.hide();
      } finally {
        mapPickerConfirmButton.disabled = false;
      }
    });

    mapPickerBindingsReady = true;
  };

  if (zoneSearchInput) {
    zoneSearchInput.addEventListener('input', () => {
      renderZonePicker();
    });
  }

  if (basePriceRange) {
    syncBasePriceControls(basePriceHidden?.value ?? basePriceRange.value);

    basePriceRange.addEventListener('input', event => {
      const target = event.currentTarget;
      if (!(target instanceof HTMLInputElement)) {
        return;
      }

      syncBasePriceControls(target.value);
    });

    basePriceRange.addEventListener('change', event => {
      const target = event.currentTarget;
      if (!(target instanceof HTMLInputElement)) {
        return;
      }

      syncBasePriceControls(target.value);
    });
  }

  if (faqList) {
    Array.from(faqList.querySelectorAll('[data-faq-item]')).forEach(bindFaqItem);
    updateFaqCount();

    Sortable.create(faqList, {
      animation: 180,
      handle: '[data-faq-handle]',
      ghostClass: 'sortable-ghost',
      onEnd: () => {
        updateFaqCount();
        queueAutosave();
      }
    });
  }

  if (faqAddButton instanceof HTMLButtonElement) {
    faqAddButton.addEventListener('click', () => {
      addFaqItem();
    });
  }

  if (richSurface instanceof HTMLElement && richInput instanceof HTMLTextAreaElement) {
    syncRichEditorInput();

    richCommandButtons.forEach(button => {
      button.addEventListener('mousedown', event => {
        event.preventDefault();
        captureRichSelection();
      });

      button.addEventListener('click', () => {
        executeRichCommand(button);
      });
    });

    if (richLinkApplyButton instanceof HTMLButtonElement) {
      richLinkApplyButton.addEventListener('click', () => {
        applyRichLink();
      });
    }

    if (richLinkInput instanceof HTMLInputElement) {
      richLinkInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
          event.preventDefault();
          applyRichLink();
          return;
        }

        if (event.key === 'Escape') {
          event.preventDefault();
          closeRichLinkPanel();
        }
      });

      richLinkInput.addEventListener('input', () => {
        clearRichLinkError();
      });
    }

    richLinkCancelButtons.forEach(button => {
      button.addEventListener('click', () => {
        closeRichLinkPanel();
      });
    });

    richSurface.addEventListener('input', () => {
      syncRichEditorInput();
      captureRichSelection();
      queueAutosave();
    });

    richSurface.addEventListener('focus', () => {
      captureRichSelection();
    });

    richSurface.addEventListener('mouseup', () => {
      captureRichSelection();
    });

    richSurface.addEventListener('keyup', () => {
      captureRichSelection();
    });

    richSurface.addEventListener('blur', () => {
      syncRichEditorInput();
    });

    richSurface.addEventListener('paste', event => {
      event.preventDefault();
      const text = event.clipboardData?.getData('text/plain') || '';
      document.execCommand('insertText', false, text);
      syncRichEditorInput();
      queueAutosave();
    });

    richSurface.addEventListener('drop', event => {
      if (event.dataTransfer?.files?.length) {
        event.preventDefault();
      }
    });

    document.addEventListener('mousedown', event => {
      if (!(richLinkPanel instanceof HTMLElement) || richLinkPanel.hidden) {
        return;
      }

      const target = event.target;
      if (target instanceof Node && richEditor instanceof HTMLElement && richEditor.contains(target)) {
        return;
      }

      closeRichLinkPanel(false);
    });
  }

  filterGroups.forEach(group => {
    Array.from(group.querySelectorAll('input[type="checkbox"]')).forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        updateFilterGroup(group);
      });
    });

    updateFilterGroup(group);
  });

  if (paymentPlanButtons.length && wompiCheckoutForm instanceof HTMLFormElement && csrfToken) {
    if (billingTermButtons.length) {
      billingTermButtons.forEach(button => {
        button.addEventListener('click', () => {
          applyBillingTermSelection(Number(button.dataset.termMonths || 1));
        });
      });

      applyBillingTermSelection(activeBillingTermMonths);
    } else {
      paymentPlanCards.forEach(updatePlanCardForTerm);
    }

    paymentPlanButtons.forEach(button => {
      button.addEventListener('click', async () => {
        if (!button.dataset.selectUrl) {
          return;
        }

        if (planSelectionError instanceof HTMLElement) {
          planSelectionError.classList.add('d-none');
          planSelectionError.textContent = '';
        }

        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.textContent = 'Preparando pago...';

        try {
          const response = await fetch(button.dataset.selectUrl, {
            method: 'POST',
            headers: {
              Accept: 'application/json',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
              plan_code: button.dataset.planCode || '',
              term_months: button.dataset.planTermMonths || '1'
            }).toString()
          });

          const payload = await response.json().catch(() => ({}));

          if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'No pudimos preparar el pago para este plan.');
          }

          syncPaymentSheet(button, payload.checkout || {});
          showFinalStep();
          wompiCheckoutForm.submit();
        } catch (error) {
          if (planSelectionError instanceof HTMLElement) {
            planSelectionError.textContent = error instanceof Error ? error.message : 'No pudimos preparar el pago para este plan.';
            planSelectionError.classList.remove('d-none');
          }
        } finally {
          button.disabled = false;
          button.innerHTML = originalHtml;
        }
      });
    });

    syncPaymentSheet(paymentPlanButtons[0]);
  }

  mainForm.addEventListener('input', event => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (target === addressInput) {
      const displayAddress = addressInput?.dataset.displayAddress || '';
      if ((addressInput?.value || '') !== displayAddress) {
        clearResolvedPlace();
      }
    }

    if (target === cityNameInput) {
      if (suggestZoneInput) {
        suggestZoneInput.value = '';
      }
      resolveCitySelection();
    }

    if (target === zoneNameInput) {
      if (suggestZoneInput) {
        suggestZoneInput.value = '';
      }
      resolveZoneSelection();
    }

    if (target.matches('input, textarea')) {
      queueAutosave();
    }
  });

  mainForm.addEventListener('change', event => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (target === cityNameInput) {
      if (suggestZoneInput) {
        suggestZoneInput.value = '';
      }
      resolveCitySelection();
    }

    if (target === zoneNameInput) {
      if (suggestZoneInput) {
        suggestZoneInput.value = '';
      }
      resolveZoneSelection();
    }

    if (target.matches('select, input[type="checkbox"], input[type="radio"], input[type="hidden"]')) {
      queueAutosave();
    }
  });

  initializeMapPicker();

  const storedPlace = parseStoredPlace();
  if (storedPlace) {
    applyPlaceSelection(storedPlace, { queue: false });
  } else {
    setMapPickerSummary(addressInput?.dataset.resolvedAddress || '', getCurrentLatLng());
    resolveCitySelection();
  }

  loadGoogleMaps()
    .then(() => {
      initializeAutocomplete();
      initializeMapPicker();
    })
    .catch(() => {
      setAutosaveState('error', 'Google Maps no cargo');
      setAutosaveTime('Revisa la API key configurada en admin.');
    });
})();
