/*
 * International phone inputs (intl-tel-input): country flag + dial code
 * picker, live formatting, validation with a specific message, and the
 * number saved in E.164 form (+447700900123) so call and WhatsApp links
 * work from any country.
 *
 * Config from head.php: window.alPhone = { country: 'gb', msg: { ... } }
 */
(function () {
  if (typeof window.intlTelInput !== 'function') {
    return;
  }

  var cfg = window.alPhone || {};
  var msg = cfg.msg || {};
  var selector = 'input[name="s_phone_mobile"], input[name="s_phone_land"], input[name="sPhone"], input[name="phoneNumber"]';

  function message(iti) {
    var codes = (window.intlTelInput.utils && window.intlTelInput.utils.validationError) || {};
    var code = iti.getValidationError();
    if (code === codes.INVALID_COUNTRY_CODE) { return msg.country || msg.invalid; }
    if (code === codes.TOO_SHORT) { return msg.short || msg.invalid; }
    if (code === codes.TOO_LONG) { return msg.long || msg.invalid; }
    return msg.invalid || 'Please enter a valid phone number.';
  }

  function setup(input) {
    if (input.getAttribute('data-al-phone')) {
      return;
    }
    input.setAttribute('data-al-phone', '1');
    input.setAttribute('type', 'tel');
    input.setAttribute('inputmode', 'tel');
    input.setAttribute('autocomplete', 'tel');
    input.removeAttribute('placeholder');   // replaced by the country's example number

    // the theme draws its own phone icon over the input; the flag replaces it
    var box = input.closest('.input-box');
    if (box) {
      box.classList.add('al-phone-box');
    } else {
      input.parentNode.classList.add('al-phone-host');
    }

    // numbers saved before the picker existed have no +code: read them in the
    // user's own country, never as a US number
    var iti = window.intlTelInput(input, {
      initialCountry: cfg.country || 'us',
      separateDialCode: true,
      nationalMode: true,
      formatOnDisplay: true,
      formatAsYouType: true,
      strictMode: true,
      autoPlaceholder: 'aggressive',
      placeholderNumberType: input.name === 's_phone_land' ? 'FIXED_LINE' : 'MOBILE',
      countryOrder: ['gb', 'us', 'au', 'ca', 'lk', 'in', 'nz', 'sg', 'ae'],
      i18n: { searchPlaceholder: msg.search || 'Search country' }
    });

    // legacy theme CSS forces padding-left with !important; match the flag + dial code width
    function fitPadding() {
      var flag = input.parentNode.querySelector('.iti__country-container');
      if (flag && flag.offsetWidth) {
        input.style.setProperty('padding-left', (flag.offsetWidth + 10) + 'px', 'important');
      }
    }
    fitPadding();
    setTimeout(fitPadding, 300);
    input.addEventListener('countrychange', fitPadding);
    window.addEventListener('resize', fitPadding);

    var error = document.createElement('div');
    error.className = 'al-phone-error';
    error.setAttribute('role', 'alert');
    error.id = (input.id || input.name) + '-al-error';
    error.hidden = true;
    input.setAttribute('aria-describedby', error.id);
    (box || input.closest('.iti') || input).insertAdjacentElement('afterend', error);

    var wrap = input.closest('.iti');

    // open the country list upwards when there is no room below the field
    input.addEventListener('open:countrydropdown', function () {
      if (!wrap) { return; }
      var r = input.getBoundingClientRect();
      var below = window.innerHeight - r.bottom, above = r.top;
      wrap.classList.toggle('al-dropup', below < 280 && above > below);
    });

    function validate(show) {
      var value = input.value.trim();
      // v24: isValidNumber() only checks length; the precise check knows real number ranges
      var precise = typeof iti.isValidNumberPrecise === 'function';
      var ok = value === '' || (precise ? iti.isValidNumberPrecise() : iti.isValidNumber());
      if (show !== false) {
        error.hidden = ok;
        error.textContent = ok ? '' : message(iti);
        input.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (wrap) { wrap.classList.toggle('is-invalid', !ok); }
      }
      if (wrap) { wrap.classList.toggle('is-valid', ok && value !== ''); }
      return ok;
    }

    input.addEventListener('blur', function () { validate(); });
    // clear the error as soon as the number is fixed; never nag mid-typing
    input.addEventListener('input', function () { validate(!error.hidden); });
    input.addEventListener('countrychange', function () {
      validate(!error.hidden);
    });
    // an existing number saved without +code may belong to another of the
    // likely countries (saved country, visitor's country); otherwise flag it now
    if (input.value.trim() !== '') {
      if (!validate(false) && input.value.trim().charAt(0) !== '+') {
        var raw = input.value.trim();
        (cfg.fallback || []).some(function (cc) {
          iti.setCountry(cc);
          iti.setNumber(raw);
          return validate(false);
        }) || (iti.setCountry(cfg.country || 'us'), iti.setNumber(raw));
      }
      validate();
      fitPadding();
    }

    function guard(e) {
      if (!validate()) {
        e.preventDefault();
        e.stopImmediatePropagation();
        input.focus();
        return false;
      }
      if (input.value.trim() !== '') {
        input.value = iti.getNumber();   // E.164
      }
      return true;
    }

    var form = input.form;
    if (form) {
      // Some forms (sell/edit listing) submit through jQuery Validate's form.submit(),
      // which skips the submit event, so also check when a submit button is clicked.
      form.addEventListener('click', function (e) {
        var btn = e.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
        if (btn && btn.form === form) {
          guard(e);
        }
      }, true);
      // capture phase: runs before the theme's jQuery validation/submit handlers
      form.addEventListener('submit', guard, true);
    }
  }

  function init() {
    var inputs = Array.prototype.slice.call(document.querySelectorAll(selector));
    inputs.forEach(setup);

    // an empty second number (e.g. "Other phone") starts in the same country as the filled one
    inputs.forEach(function (input) {
      if (input.value.trim() !== '' || !input.form) { return; }
      var filled = inputs.filter(function (o) { return o !== input && o.form === input.form && o.value.trim() !== ''; })[0];
      var from = filled && window.intlTelInput.getInstance(filled);
      var to = window.intlTelInput.getInstance(input);
      if (from && to && from.getSelectedCountryData().iso2) {
        to.setCountry(from.getSelectedCountryData().iso2);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
