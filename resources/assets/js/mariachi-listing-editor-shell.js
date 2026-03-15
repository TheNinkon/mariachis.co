'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const shell = document.querySelector('[data-listing-editor-shell]');
  const closeButton = document.querySelector('[data-editor-close]');
  const navToggle = document.querySelector('[data-editor-nav-toggle]');

  if (!shell || !closeButton || !navToggle) {
    return;
  }

  const storageKey = 'listing_editor_nav_collapsed';
  const indexUrl = shell.dataset.editorIndexUrl || '/anuncios';
  const stepContents = Array.from(document.querySelectorAll('.bs-stepper-content .content[data-step-key]'));
  const globalFooter = document.querySelector('[data-editor-global-footer]');
  const globalPrev = document.querySelector('[data-editor-footer-prev]');
  const globalNext = document.querySelector('[data-editor-footer-next]');

  const normalizeStepLayouts = () => {
    stepContents.forEach(step => {
      if (step.querySelector('.listing-editor-step-layout')) {
        return;
      }

      const actions = step.querySelector('.listing-step-actions');

      if (!actions) {
        return;
      }

      const layout = document.createElement('div');
      layout.className = 'listing-editor-step-layout';

      const scrollArea = document.createElement('div');
      scrollArea.className = 'listing-editor-step-scroll';

      const frame = document.createElement('div');
      frame.className = 'listing-editor-step-frame';

      Array.from(step.childNodes).forEach(node => {
        if (node === actions) {
          return;
        }

        frame.appendChild(node);
      });

      scrollArea.appendChild(frame);
      layout.appendChild(scrollArea);
      layout.appendChild(actions);
      step.appendChild(layout);
    });
  };

  const getActiveStep = () => {
    const activeStep = document.querySelector('.bs-stepper-content .content.active[data-step-key]');

    if (activeStep instanceof HTMLElement) {
      return activeStep;
    }

    const visibleStep =
      stepContents.find(step => step.offsetParent !== null && step.classList.contains('active')) ||
      stepContents.find(step => step.offsetParent !== null) ||
      stepContents[0] ||
      null;

    return visibleStep instanceof HTMLElement ? visibleStep : null;
  };

  const syncGlobalFooter = () => {
    if (!globalFooter || !globalPrev || !globalNext) {
      return;
    }

    const activeStep = getActiveStep();
    const prevButton = activeStep?.querySelector('[data-step-prev]') || null;
    const nextButton = activeStep?.querySelector('[data-step-next]') || null;

    if (prevButton) {
      globalPrev.hidden = false;
      globalPrev.innerHTML = prevButton.innerHTML;
      globalPrev.disabled = prevButton.disabled;
    } else {
      globalPrev.hidden = true;
    }

    if (nextButton) {
      globalNext.hidden = false;
      globalNext.innerHTML = nextButton.innerHTML;
      globalNext.disabled = nextButton.disabled;
    } else {
      globalNext.hidden = true;
    }
  };

  const syncToggleIcon = () => {
    const icon = navToggle.querySelector('i');
    const collapsed = shell.classList.contains('is-nav-collapsed');

    if (!icon) {
      return;
    }

    icon.className = collapsed
      ? 'icon-base ti tabler-layout-sidebar-right-expand'
      : 'icon-base ti tabler-layout-sidebar-left-collapse';

    navToggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    navToggle.setAttribute('title', collapsed ? 'Mostrar estructura del anuncio' : 'Ocultar estructura del anuncio');
  };

  const closeEditor = refresh => {
    const shouldRefresh = refresh !== false;

    if (window.parent && window.parent !== window) {
      window.parent.postMessage(
        {
          type: 'listing-editor-close',
          refresh: shouldRefresh
        },
        window.location.origin
      );

      return;
    }

    window.location.href = indexUrl;
  };

  const storedCollapsed = window.sessionStorage.getItem(storageKey) === 'true';

  normalizeStepLayouts();
  syncGlobalFooter();
  window.setTimeout(syncGlobalFooter, 0);
  window.setTimeout(syncGlobalFooter, 120);

  if (storedCollapsed) {
    shell.classList.add('is-nav-collapsed');
  }

  syncToggleIcon();

  closeButton.addEventListener('click', function () {
    closeEditor(true);
  });

  navToggle.addEventListener('click', function () {
    shell.classList.toggle('is-nav-collapsed');
    window.sessionStorage.setItem(storageKey, shell.classList.contains('is-nav-collapsed') ? 'true' : 'false');
    syncToggleIcon();
  });

  if (globalPrev) {
    globalPrev.addEventListener('click', function () {
      const activeStep = document.querySelector('.bs-stepper-content .content.active[data-step-key]');
      const prevButton = activeStep?.querySelector('[data-step-prev]');

      if (prevButton) {
        prevButton.click();
      }
    });
  }

  if (globalNext) {
    globalNext.addEventListener('click', function () {
      const activeStep = document.querySelector('.bs-stepper-content .content.active[data-step-key]');
      const nextButton = activeStep?.querySelector('[data-step-next]');

      if (nextButton) {
        nextButton.click();
      }
    });
  }

  const wizard = document.querySelector('[data-listing-wizard]');
  if (wizard) {
    wizard.addEventListener('shown.bs-stepper', function () {
      syncGlobalFooter();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeEditor(false);
    }
  });
});
