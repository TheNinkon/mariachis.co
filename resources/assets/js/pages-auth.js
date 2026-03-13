/**
 *  Pages Authentication
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  (() => {
    const formAuthentication = document.querySelector('#formAuthentication');
    const phoneCountrySelects = document.querySelectorAll('.select2-phone-country');

    // Form validation for Add new record
    if (formAuthentication && typeof FormValidation !== 'undefined') {
      FormValidation.formValidation(formAuthentication, {
        fields: {
          username: {
            validators: {
              notEmpty: {
                message: 'Ingresa tu usuario'
              },
              stringLength: {
                min: 6,
                message: 'El usuario debe tener al menos 6 caracteres'
              }
            }
          },
          email: {
            validators: {
              notEmpty: {
                message: 'Ingresa tu correo electronico'
              },
              emailAddress: {
                message: 'Ingresa un correo electronico valido'
              }
            }
          },
          'email-username': {
            validators: {
              notEmpty: {
                message: 'Ingresa tu correo o usuario'
              },
              stringLength: {
                min: 6,
                message: 'El usuario debe tener al menos 6 caracteres'
              }
            }
          },
          password: {
            validators: {
              notEmpty: {
                message: 'Ingresa tu contrasena'
              },
              stringLength: {
                min: 6,
                message: 'La contrasena debe tener al menos 6 caracteres'
              }
            }
          },
          'confirm-password': {
            validators: {
              notEmpty: {
                message: 'Confirma tu contrasena'
              },
              identical: {
                compare: () => formAuthentication.querySelector('[name="password"]').value,
                message: 'Las contrasenas no coinciden'
              },
              stringLength: {
                min: 6,
                message: 'La contrasena debe tener al menos 6 caracteres'
              }
            }
          },
          terms: {
            validators: {
              notEmpty: {
                message: 'Debes aceptar los terminos y condiciones'
              }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: '.form-control-validation'
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        },
        init: instance => {
          instance.on('plugins.message.placed', e => {
            if (e.element.parentElement.classList.contains('input-group')) {
              e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
            }
          });
        }
      });
    }

    document.addEventListener('click', event => {
      const toggle = event.target.closest('[data-password-toggle]');

      if (!toggle) {
        return;
      }

      event.preventDefault();

      const inputId = toggle.getAttribute('aria-controls');
      const passwordInput = inputId ? document.getElementById(inputId) : null;
      const icon = toggle.querySelector('i');

      if (!passwordInput || !icon) {
        return;
      }

      const shouldShow = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', shouldShow ? 'text' : 'password');
      icon.classList.toggle('tabler-eye', shouldShow);
      icon.classList.toggle('tabler-eye-off', !shouldShow);
    });

    // Two Steps Verification for numeral input mask
    const numeralMaskElements = document.querySelectorAll('.numeral-mask');

    // Format function for numeral mask
    const formatNumeral = value => value.replace(/\D/g, ''); // Only keep digits

    if (numeralMaskElements.length > 0) {
      numeralMaskElements.forEach(numeralMaskEl => {
        numeralMaskEl.addEventListener('input', event => {
          numeralMaskEl.value = formatNumeral(event.target.value);
        });
      });
    }

    if (phoneCountrySelects.length > 0 && window.jQuery && window.jQuery.fn.select2) {
      phoneCountrySelects.forEach(select => {
        const $select = window.jQuery(select);

        if ($select.hasClass('select2-hidden-accessible')) {
          return;
        }

        $select.wrap('<div class="position-relative"></div>').select2({
          width: '100%',
          dropdownAutoWidth: true,
          dropdownParent: $select.parent(),
          placeholder: 'Indicativo'
        });
      });
    }
  })();
});
