'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const table = document.querySelector('.datatables-partner-listings');

  if (!table || typeof DataTable === 'undefined') {
    return;
  }

  new DataTable(table, {
    pageLength: 10,
    order: [[6, 'desc']],
    responsive: true,
    columnDefs: [
      {
        className: 'control',
        searchable: false,
        orderable: false,
        responsivePriority: 1,
        targets: 0,
        render: function () {
          return '';
        }
      },
      {
        responsivePriority: 2,
        targets: 1
      },
      {
        responsivePriority: 3,
        targets: -1,
        orderable: false,
        searchable: false
      }
    ],
    layout: {
      topStart: {
        search: {
          placeholder: 'Buscar anuncio',
          text: '_INPUT_'
        }
      },
      topEnd: {
        pageLength: {
          menu: [10, 25, 50, 100],
          text: '_MENU_'
        }
      }
    },
    language: {
      emptyTable: 'No tienes anuncios para mostrar.',
      info: 'Mostrando _START_ a _END_ de _TOTAL_ anuncios',
      infoEmpty: 'Mostrando 0 a 0 de 0 anuncios',
      lengthMenu: '_MENU_',
      paginate: {
        first: 'Primero',
        last: 'Último',
        next: 'Siguiente',
        previous: 'Anterior'
      },
      search: '',
      searchPlaceholder: 'Buscar anuncio',
      zeroRecords: 'No encontramos anuncios con ese criterio'
    }
  });
});
