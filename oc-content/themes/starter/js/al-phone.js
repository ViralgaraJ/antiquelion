/*
 * International phone inputs (intl-tel-input): country flag + dial code
 * picker, validation, and the number saved in E.164 form (+447700900123)
 * so call and WhatsApp links work from any country.
 *
 * Config from head.php: window.alPhone = { country: 'gb', invalid: '...' }
 */
(function () {
  if (typeof window.intlTelInput !== 'function') {
    return;
  }

  var cfg = window.alPhone || {};
  var selector = 'input[name="s_phone_mobile"], input[name="s_phone_land"], input[name="sPhone"]';

  function setup(input) {
    if (input.getAttribute('data-al-phone')) {
      return;
    }
    input.setAttribute('data-al-phone', '1');
    input.setAttribute('type', 'tel');
    input.setAttribute('autocomplete', 'tel');

    // the theme draws its own phone icon over the input; the flag replaces it
    var box = input.closest('.input-box');
    if (box) {
      box.classList.add('al-phone-box');
    }

    var iti = window.intlTelInput(input, {
      initialCountry: cfg.country || 'us',
      separateDialCode: true,
      nationalMode: true,
      formatOnDisplay: true,
      autoPlaceholder: 'aggressive',
      countryOrder: ['gb', 'us', 'au', 'ca', 'lk', 'in', 'nz', 'sg', 'ae']
    });

    var error = document.createElement('div');
    error.className = 'al-phone-error';
    error.setAttribute('role', 'alert');
    error.hidden = true;
    (box || input.parentNode).insertAdjacentElement('afterend', error);

    function validate() {
      var value = input.value.trim();
      var ok = value === '' || iti.isValidNumber();
      error.hidden = ok;
      error.textContent = ok ? '' : (cfg.invalid || 'Please enter a valid phone number.');
      input.setAttribute('aria-invalid', ok ? 'false' : 'true');
      return ok;
    }

    input.addEventListener('blur', validate);
    input.addEventListener('countrychange', function () {
      if (!error.hidden) {
        validate();
      }
    });

    var form = input.form;
    if (form) {
      // capture phase: runs before the theme's jQuery validation/submit handlers
      form.addEventListener('submit', function (e) {
        if (!validate()) {
          e.preventDefault();
          e.stopImmediatePropagation();
          input.focus();
          return;
        }
        if (input.value.trim() !== '') {
          input.value = iti.getNumber();   // E.164
        }
      }, true);
    }
  }

  function init() {
    Array.prototype.forEach.call(document.querySelectorAll(selector), setup);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
