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

  const normalizeStepKey = value => {
    const normalized = String(value || '').trim().toLowerCase();
    return legacyStepAliases[normalized] || normalized;
  };

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
    const atLimit = maxZones <= 0 || countedSelected >= maxZones;
    if (zoneCountTarget) {
      zoneCountTarget.textContent = String(countedSelected);
    }
    if (zoneUpgradeTile) {
      zoneUpgradeTile.hidden = !atLimit;
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
        createZoneEmpty('No hay mas localidades disponibles', atLimit ? 'Alcanzaste el limite de tu plan para este anuncio.' : 'Ajusta la busqueda o cambia la ciudad principal.')
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
