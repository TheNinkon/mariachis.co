'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableElement = document.querySelector('.datatables-users');
  const roleFilter = document.getElementById('MariachiRoleFilter');
  const planFilter = document.getElementById('MariachiPlanFilter');
  const statusFilter = document.getElementById('MariachiStatusFilter');

  if (window.jQuery && window.jQuery.fn.select2) {
    window.jQuery('.select2').each(function () {
      const $select = window.jQuery(this);
      $select.wrap('<div class="position-relative"></div>').select2({
        placeholder: $select.find('option:first').text(),
        allowClear: false,
        width: '100%',
        dropdownParent: $select.parent(),
      });
    });
  }

  let dataTable = null;
  if (tableElement && window.DataTable) {
    dataTable = new window.DataTable(tableElement, {
      pageLength: 10,
      order: [[2, 'asc']],
      responsive: true,
      columnDefs: [
        { targets: 0, searchable: false, orderable: false, className: 'control' },
        { targets: 1, searchable: false, orderable: false },
        { targets: 7, searchable: false, orderable: false },
      ],
      language: {
        search: '',
        searchPlaceholder: 'Buscar mariachi',
        emptyTable: 'No hay mariachis registrados.',
        lengthMenu: '_MENU_',
        paginate: {
          next: '<i class="icon-base ti tabler-chevron-right icon-18px"></i>',
          previous: '<i class="icon-base ti tabler-chevron-left icon-18px"></i>',
        },
      },
    });
  }

  const applyColumnFilter = (columnIndex, value) => {
    if (!dataTable) {
      return;
    }

    dataTable.column(columnIndex).search(value || '').draw();
  };

  roleFilter?.addEventListener('change', event => {
    applyColumnFilter(3, event.target.value);
  });

  planFilter?.addEventListener('change', event => {
    applyColumnFilter(4, event.target.value);
  });

  statusFilter?.addEventListener('change', event => {
    applyColumnFilter(6, event.target.value);
  });

  document.querySelectorAll('.js-toggle-status-form').forEach(form => {
    form.addEventListener('submit', function (event) {
      const button = form.querySelector('button[type="submit"]');
      const actionLabel = button?.textContent?.trim() || 'actualizar';

      if (!window.Swal) {
        if (!window.confirm(`Vas a ${actionLabel.toLowerCase()} este mariachi.`)) {
          event.preventDefault();
        }

        return;
      }

      event.preventDefault();

      window.Swal.fire({
        title: 'Confirmar accion',
        text: `Vas a ${actionLabel.toLowerCase()} este mariachi.`,
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
