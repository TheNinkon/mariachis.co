'use strict';

(function () {
  const wizardElement = document.querySelector('[data-listing-wizard]');
  const mainForm = document.getElementById('listing-main-form');
  const autosaveBadge = document.querySelector('[data-autosave-status]');
  const autosaveTime = document.querySelector('[data-autosave-time]');
  const completionTargets = document.querySelectorAll('[data-completion-text]');

  let stepperInstance = null;
  if (wizardElement && typeof Stepper !== 'undefined') {
    stepperInstance = new Stepper(wizardElement, {
      linear: false,
      animation: true
    });
  }

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
  const stateInput = document.getElementById('listing-state-input');
  const countryInput = document.getElementById('listing-country-input');
  const addressInput = document.getElementById('listing-address-input');
  const latitudeInput = document.getElementById('listing-latitude-input');
  const longitudeInput = document.getElementById('listing-longitude-input');
  const postalCodeInput = document.getElementById('listing-postal-code-input');
  const placeIdInput = document.getElementById('listing-place-id-input');
  const payloadInput = document.getElementById('listing-google-payload-input');
  const primaryZoneIdInput = document.getElementById('listing-primary-zone-id');
  const zoneSelect = document.getElementById('zone_ids');

  let autosaveTimer = null;
  let requestVersion = 0;
  let activeController = null;

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
        throw new Error(message);
      }

      if (currentVersion !== requestVersion) {
        return;
      }

      setAutosaveState('saved', 'Guardado');
      setAutosaveTime(formatSavedAt(payload.saved_at));
      applyCompletion(payload.listing_completion);
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

  const normalize = value =>
    String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
      .replace(/\s+/g, ' ');

  const findCityByName = cityName => {
    const normalized = normalize(cityName);
    return locationCities.find(city => normalize(city.name) === normalized) || null;
  };

  const findZoneByName = (zoneName, cityId) => {
    const normalized = normalize(zoneName);
    return (
      locationZones.find(zone => zone.city_id === cityId && normalize(zone.name) === normalized) ||
      null
    );
  };

  const clearResolvedPlace = () => {
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
  };

  const resolveZoneSelection = () => {
    if (!zoneNameInput || !primaryZoneIdInput) {
      return;
    }

    const selectedCityId = Number(cityIdInput?.value || 0);
    if (!selectedCityId) {
      primaryZoneIdInput.value = '';
      return;
    }

    const matchedZone = findZoneByName(zoneNameInput.value, selectedCityId);
    primaryZoneIdInput.value = matchedZone ? String(matchedZone.id) : '';
  };

  const resolveCitySelection = () => {
    if (!cityNameInput || !cityIdInput) {
      return;
    }

    const matchedCity = findCityByName(cityNameInput.value);
    cityIdInput.value = matchedCity ? String(matchedCity.id) : '';
    syncZoneOptions();
    resolveZoneSelection();
  };

  const syncZoneOptions = () => {
    if (!zoneSelect || !cityIdInput) {
      return;
    }

    const selectedCityId = cityIdInput.value;
    Array.from(zoneSelect.options).forEach(option => {
      const optionCityId = option.getAttribute('data-city-id');
      const visible = !selectedCityId || optionCityId === selectedCityId;
      option.hidden = !visible;

      if (!visible) {
        option.selected = false;
      }
    });
  };

  syncZoneOptions();

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

  const applyPlaceSelection = place => {
    if (!place || !place.geometry || !place.geometry.location) {
      return;
    }

    const cityCandidate =
      extractComponent(place, ['locality']) ||
      extractComponent(place, ['administrative_area_level_3']) ||
      extractComponent(place, ['postal_town']) ||
      extractComponent(place, ['administrative_area_level_2']);

    const stateCandidate = extractComponent(place, ['administrative_area_level_1']);
    const zoneCandidate =
      extractComponent(place, ['neighborhood']) ||
      extractComponent(place, ['sublocality']) ||
      extractComponent(place, ['sublocality_level_1']) ||
      extractComponent(place, ['administrative_area_level_4']);
    const postalCode = extractComponent(place, ['postal_code']);
    const matchedCity = findCityByName(cityCandidate);
    const matchedZone = matchedCity ? findZoneByName(zoneCandidate, matchedCity.id) : null;

    if (addressInput) {
      addressInput.value = place.formatted_address || addressInput.value;
      addressInput.dataset.resolvedAddress = addressInput.value;
    }
    if (cityNameInput) {
      cityNameInput.value = cityCandidate || '';
    }
    if (stateInput) {
      stateInput.value = stateCandidate || '';
    }
    if (zoneNameInput) {
      zoneNameInput.value = zoneCandidate || '';
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
      latitudeInput.value = place.geometry.location.lat().toFixed(7);
    }
    if (longitudeInput) {
      longitudeInput.value = place.geometry.location.lng().toFixed(7);
    }
    if (placeIdInput) {
      placeIdInput.value = place.place_id || '';
    }
    if (countryInput) {
      countryInput.value = countryInput.value || 'Colombia';
    }
    if (payloadInput) {
      payloadInput.value = JSON.stringify({
        place_id: place.place_id || null,
        formatted_address: place.formatted_address || null,
        geometry: {
          lat: place.geometry.location.lat(),
          lng: place.geometry.location.lng()
        },
        address_components: (place.address_components || []).map(component => ({
          long_name: component.long_name,
          short_name: component.short_name,
          types: component.types
        }))
      });
    }

    syncZoneOptions();
    queueAutosave();
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
    if (!googleMapsEnabled || !addressInput || !(window.google && window.google.maps && window.google.maps.places)) {
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
  };

  mainForm.addEventListener('input', event => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (target === addressInput) {
      const resolvedAddress = addressInput?.dataset.resolvedAddress || '';
      if ((addressInput?.value || '') !== resolvedAddress) {
        clearResolvedPlace();
      }
    }

    if (target === cityNameInput) {
      resolveCitySelection();
    }

    if (target === zoneNameInput) {
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
      resolveCitySelection();
    }

    if (target === zoneNameInput) {
      resolveZoneSelection();
    }

    if (target.matches('select, input[type="checkbox"], input[type="radio"], input[type="hidden"]')) {
      if (target === cityIdInput) {
        syncZoneOptions();
      }
      queueAutosave();
    }
  });

  loadGoogleMaps()
    .then(() => {
      initializeAutocomplete();
    })
    .catch(() => {
      setAutosaveState('error', 'Google Maps no cargo');
      setAutosaveTime('Revisa la API key configurada en admin.');
    });

  resolveCitySelection();
})();
