'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const selectAll = document.querySelector('[data-listings-select-all]');
  const rows = Array.from(document.querySelectorAll('[data-listing-row]'));
  const stageTabs = Array.from(document.querySelectorAll('[data-listings-stage-tab]'));
  const emptyState = document.querySelector('[data-listings-empty-state]');
  const editTrigger = document.querySelector('[data-listings-edit-trigger]');
  const toggleForm = document.querySelector('[data-listings-toggle-form]');
  const toggleTrigger = document.querySelector('[data-listings-toggle-trigger]');
  const toggleLabel = document.querySelector('[data-listings-toggle-label]');
  const toggleIcon = document.querySelector('[data-listings-toggle-icon]');
  const editorOverlay = document.querySelector('[data-editor-overlay]');
  const editorFrame = document.querySelector('[data-editor-iframe]');
  const editorLaunchers = Array.from(document.querySelectorAll('[data-editor-launch]'));

  if (!selectAll || rows.length === 0 || !editTrigger || !toggleForm || !toggleTrigger || !toggleLabel || !toggleIcon) {
    return;
  }

  let currentStage = 'all';

  const openEditor = url => {
    if (!editorOverlay || !editorFrame || !url) {
      return;
    }

    editorFrame.src = url;
    editorOverlay.hidden = false;
    editorOverlay.classList.add('is-active');
    document.body.style.overflow = 'hidden';
  };

  const closeEditor = options => {
    if (!editorOverlay || !editorFrame) {
      return;
    }

    const shouldRefresh = options && options.refresh === true;

    editorOverlay.classList.remove('is-active');
    editorOverlay.hidden = true;
    editorFrame.src = 'about:blank';
    document.body.style.overflow = '';

    if (shouldRefresh) {
      window.location.reload();
    }
  };

  const getSelectedRows = () =>
    rows.filter(row => {
      const input = row.querySelector('[data-listing-select]');

      return input && input.checked;
    });

  const getVisibleRows = () => rows.filter(row => !row.hidden);

  const matchesStage = (row, stage) => {
    if (stage === 'review') {
      return row.dataset.stageReview === '1';
    }

    if (stage === 'published') {
      return row.dataset.stagePublished === '1';
    }

    return true;
  };

  const setDisabledState = (element, disabled) => {
    element.classList.toggle('is-disabled', disabled);
    element.setAttribute('aria-disabled', disabled ? 'true' : 'false');

    if (element.tagName === 'BUTTON') {
      element.disabled = disabled;
    }
  };

  const syncToolbar = () => {
    const selectedRows = getSelectedRows();
    const visibleRows = getVisibleRows();
    const singleSelection = selectedRows.length === 1 ? selectedRows[0] : null;

    rows.forEach(row => {
      const input = row.querySelector('[data-listing-select]');
      row.classList.toggle('is-selected', Boolean(input && input.checked));
    });

    selectAll.disabled = visibleRows.length === 0;

    if (selectedRows.length === 0 || visibleRows.length === 0) {
      selectAll.indeterminate = false;
      selectAll.checked = false;
    } else if (selectedRows.length === visibleRows.length) {
      selectAll.indeterminate = false;
      selectAll.checked = true;
    } else {
      selectAll.checked = false;
      selectAll.indeterminate = true;
    }

    if (singleSelection) {
      editTrigger.href = singleSelection.dataset.editUrl || '#';
      editTrigger.dataset.editorUrl = singleSelection.dataset.editorUrl || '';
      setDisabledState(editTrigger, false);

      const actionUrl = singleSelection.dataset.toggleUrl || '';
      const actionLabel = singleSelection.dataset.toggleLabel || 'Pausar';
      const actionIcon = singleSelection.dataset.toggleIcon || 'tabler-player-pause';

      if (actionUrl) {
        toggleForm.action = actionUrl;
        toggleLabel.textContent = actionLabel;
        toggleIcon.className = `icon-base ti ${actionIcon} me-1`;
        setDisabledState(toggleTrigger, false);
      } else {
        toggleForm.action = '#';
        toggleLabel.textContent = 'Pausar';
        toggleIcon.className = 'icon-base ti tabler-player-pause me-1';
        setDisabledState(toggleTrigger, true);
      }
    } else {
      editTrigger.href = '#';
      editTrigger.dataset.editorUrl = '';
      setDisabledState(editTrigger, true);
      toggleForm.action = '#';
      toggleLabel.textContent = 'Pausar';
      toggleIcon.className = 'icon-base ti tabler-player-pause me-1';
      setDisabledState(toggleTrigger, true);
    }
  };

  const applyStageFilter = stage => {
    currentStage = stage;

    let visibleCount = 0;

    rows.forEach(row => {
      const shouldShow = matchesStage(row, stage);
      row.hidden = !shouldShow;
      row.style.display = shouldShow ? '' : 'none';

      const input = row.querySelector('[data-listing-select]');

      if (!shouldShow && input) {
        input.checked = false;
      }

      if (shouldShow) {
        visibleCount += 1;
      }
    });

    stageTabs.forEach(tab => {
      const isActive = tab.dataset.listingsStageTab === stage;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (emptyState) {
      emptyState.hidden = visibleCount !== 0;
    }

    syncToolbar();
  };

  selectAll.addEventListener('change', function () {
    getVisibleRows().forEach(row => {
      const input = row.querySelector('[data-listing-select]');

      if (input) {
        input.checked = selectAll.checked;
      }
    });

    syncToolbar();
  });

  rows.forEach(row => {
    const input = row.querySelector('[data-listing-select]');

    if (!input) {
      return;
    }

    input.addEventListener('change', syncToolbar);
  });

  stageTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const stage = tab.dataset.listingsStageTab || 'all';
      applyStageFilter(stage);
    });
  });

  editTrigger.addEventListener('click', function (event) {
    if (editTrigger.classList.contains('is-disabled')) {
      event.preventDefault();
      return;
    }

    const editorUrl = editTrigger.dataset.editorUrl || '';

    if (editorUrl) {
      event.preventDefault();
      openEditor(editorUrl);
    }
  });

  toggleForm.addEventListener('submit', function (event) {
    if (toggleTrigger.classList.contains('is-disabled') || toggleForm.action.endsWith('#')) {
      event.preventDefault();
    }
  });

  editorLaunchers.forEach(link => {
    link.addEventListener('click', event => {
      if (link.classList.contains('is-disabled')) {
        event.preventDefault();
        return;
      }

      const href = link.getAttribute('href') || '';
      const editorUrl = link.dataset.editorUrl || href;

      if (!editorUrl || editorUrl === '#') {
        return;
      }

      event.preventDefault();
      openEditor(editorUrl);
    });
  });

  window.addEventListener('message', event => {
    if (event.origin !== window.location.origin || !event.data || typeof event.data !== 'object') {
      return;
    }

    if (event.data.type === 'listing-editor-close') {
      closeEditor({ refresh: Boolean(event.data.refresh) });
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && editorOverlay && editorOverlay.classList.contains('is-active')) {
      closeEditor({ refresh: false });
    }
  });

  applyStageFilter(currentStage);
});
