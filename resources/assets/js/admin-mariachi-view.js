'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableInstances = {};
  const tableConfigs = [
    {
      key: 'listings',
      selector: '.datatable-listings',
      searchPlaceholder: 'Buscar anuncio',
      emptyTable: 'Este mariachi aun no tiene anuncios.',
    },
    {
      key: 'reviews',
      selector: '.datatable-reviews',
      searchPlaceholder: 'Buscar opinion',
      emptyTable: 'No hay opiniones registradas para este mariachi.',
    },
  ];

  const getTableConfig = section => tableConfigs.find(config => config.key === section) || null;

  const initTable = section => {
    if (!window.DataTable || tableInstances[section]) {
      return;
    }

    const config = getTableConfig(section);
    if (!config) {
      return;
    }

    const tableElement = document.querySelector(config.selector);
    if (!tableElement) {
      return;
    }

    tableInstances[section] = new window.DataTable(tableElement, {
      pageLength: 5,
      order: [],
      responsive: true,
      columnDefs: [
        { targets: 0, searchable: false, orderable: false, className: 'control' },
        { targets: 1, searchable: false, orderable: false },
      ],
      language: {
        search: '',
        searchPlaceholder: config.searchPlaceholder,
        emptyTable: config.emptyTable,
        lengthMenu: '_MENU_',
        paginate: {
          next: '<i class="icon-base ti tabler-chevron-right icon-18px"></i>',
          previous: '<i class="icon-base ti tabler-chevron-left icon-18px"></i>',
        },
      },
    });
  };

  const refreshTableLayout = section => {
    const instance = tableInstances[section];
    if (!instance) {
      return;
    }

    instance.columns?.adjust?.();
    instance.responsive?.recalc?.();
  };

  const navLinks = Array.from(document.querySelectorAll('[data-admin-profile-nav] .nav-link[data-section-target]'));
  const sections = Array.from(document.querySelectorAll('[data-admin-profile-section]'));

  const activateSection = (section, syncHash = true) => {
    const sectionExists = sections.some(element => element.dataset.adminProfileSection === section);
    const nextSection = sectionExists ? section : 'account';

    navLinks.forEach(link => {
      link.classList.toggle('active', link.dataset.sectionTarget === nextSection);
    });

    sections.forEach(element => {
      const isActive = element.dataset.adminProfileSection === nextSection;
      element.classList.toggle('d-none', !isActive);
      element.hidden = !isActive;
    });

    initTable(nextSection);
    window.requestAnimationFrame(() => refreshTableLayout(nextSection));

    if (syncHash) {
      window.history.replaceState(null, '', `#${nextSection}`);
    }
  };

  navLinks.forEach(link => {
    link.addEventListener('click', event => {
      event.preventDefault();
      activateSection(link.dataset.sectionTarget);
    });
  });

  const initialSection = window.location.hash ? window.location.hash.replace('#', '') : 'account';
  activateSection(initialSection, false);

  document.querySelectorAll('.js-toggle-status-form').forEach(form => {
    form.addEventListener('submit', function (event) {
      if (!window.Swal) {
        return;
      }

      event.preventDefault();

      const button = form.querySelector('button[type="submit"]');
      const buttonLabel = button?.textContent?.trim() || 'actualizar';

      window.Swal.fire({
        title: 'Confirmar accion',
        text: `Vas a ${buttonLabel.toLowerCase()} esta cuenta de mariachi.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-label-secondary',
        },
        buttonsStyling: false,
      }).then(result => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});
