/*
 * Photo editor used everywhere a photo is added (listing photos, profile photo, shop
 * banner, verification documents): preview, crop (free or fixed shape), rotate,
 * straighten, flip and zoom, then hands back an edited JPEG.
 *
 *   alImageEditor.open(fileOrUrl, { title, aspect, aspects, round, max, quality })
 *     -> Promise resolving to a File (edited) or null (cancelled); rejects if the
 *        image cannot be read (e.g. HEIC in a browser that cannot show it).
 *
 * Cropper.js (MIT, js/vendor/cropper) is loaded the first time the editor opens.
 */
(function () {
  'use strict';
  var cfg = window.alImg || {};
  var t = cfg.t || {};
  var tx = function (k, d) { return t[k] || d; };
  var loading = null;
  var ui = null;
  var state = null;

  function loadCropper() {
    if (window.Cropper) { return Promise.resolve(); }
    if (loading) { return loading; }
    loading = new Promise(function (resolve, reject) {
      if (cfg.css && !document.querySelector('link[data-al-cropper]')) {
        var l = document.createElement('link');
        l.rel = 'stylesheet'; l.href = cfg.css; l.setAttribute('data-al-cropper', '1');
        document.head.appendChild(l);
      }
      var s = document.createElement('script');
      s.src = cfg.js; s.async = true;
      s.onload = function () { resolve(); };
      s.onerror = function () { loading = null; reject(new Error(tx('loadFail', 'The photo editor could not load. Check your connection and try again.'))); };
      document.head.appendChild(s);
    });
    return loading;
  }

  var icon = {
    rotl: '<path d="M4 4v5h5"/><path d="M4.6 13a8 8 0 1 0 2.1-6.9L4 9"/>',
    rotr: '<path d="M20 4v5h-5"/><path d="M19.4 13a8 8 0 1 1-2.1-6.9L20 9"/>',
    fliph: '<path d="M12 3v18"/><path d="M8 7 3 12l5 5V7z"/><path d="M16 7l5 5-5 5V7z"/>',
    flipv: '<path d="M3 12h18"/><path d="M7 8l5-5 5 5H7z"/><path d="M7 16l5 5 5-5H7z"/>',
    zoomin: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/>',
    zoomout: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M8 11h6"/>',
    reset: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    close: '<path d="M6 6l12 12M18 6 6 18"/>'
  };
  function svg(name) {
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + icon[name] + '</svg>';
  }

  function build() {
    if (ui) { return ui; }
    var el = document.createElement('div');
    el.className = 'al-ie';
    el.hidden = true;
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-labelledby', 'al-ie-title');
    var tool = function (act, label) { return '<button type="button" class="al-ie-tool" data-act="' + act + '" title="' + label + '" aria-label="' + label + '">' + svg(act) + '</button>'; };
    el.innerHTML =
      '<div class="al-ie-box">' +
        '<header class="al-ie-head"><h3 id="al-ie-title"></h3>' +
          '<button type="button" class="al-ie-x" data-act="cancel" aria-label="' + tx('cancel', 'Cancel') + '">' + svg('close') + '</button></header>' +
        '<div class="al-ie-stage"><img alt="" /><div class="al-ie-wait"><span></span></div></div>' +
        '<div class="al-ie-tools">' +
          '<div class="al-ie-aspects" role="radiogroup" aria-label="' + tx('shape', 'Crop shape') + '"></div>' +
          '<div class="al-ie-row">' +
            tool('rotl', tx('rotl', 'Rotate left')) + tool('rotr', tx('rotr', 'Rotate right')) +
            tool('fliph', tx('fliph', 'Flip horizontally')) + tool('flipv', tx('flipv', 'Flip vertically')) +
            tool('zoomout', tx('zoomout', 'Zoom out')) + tool('zoomin', tx('zoomin', 'Zoom in')) +
            '<button type="button" class="al-ie-tool al-ie-reset" data-act="reset">' + svg('reset') + '<span>' + tx('reset', 'Reset') + '</span></button>' +
          '</div>' +
          '<label class="al-ie-straight"><span>' + tx('straighten', 'Straighten') + '</span>' +
            '<input type="range" min="-45" max="45" step="0.5" value="0" /><output>0°</output></label>' +
        '</div>' +
        '<footer class="al-ie-foot"><p class="al-ie-tip">' + tx('tip', 'Drag the photo to move it, drag the corners to crop, scroll or pinch to zoom.') + '</p>' +
          '<div class="al-ie-btns"><button type="button" class="al-btn al-btn-outline" data-act="cancel">' + tx('cancel', 'Cancel') + '</button>' +
          '<button type="button" class="al-btn al-btn-ink" data-act="apply">' + tx('apply', 'Use photo') + '</button></div></footer>' +
      '</div>';
    document.body.appendChild(el);
    ui = {
      el: el,
      title: el.querySelector('#al-ie-title'),
      img: el.querySelector('.al-ie-stage img'),
      aspects: el.querySelector('.al-ie-aspects'),
      range: el.querySelector('.al-ie-straight input'),
      out: el.querySelector('.al-ie-straight output'),
      apply: el.querySelector('[data-act=apply]')
    };
    el.addEventListener('click', function (e) {
      var b = e.target.closest('[data-act]');
      if (!b || !state || !state.cropper) {
        if (b && b.getAttribute('data-act') === 'cancel') { finish(null); }
        return;
      }
      act(b.getAttribute('data-act'), b);
    });
    ui.range.addEventListener('input', function () {
      if (!state || !state.cropper) { return; }
      state.fine = parseFloat(ui.range.value) || 0;
      ui.out.textContent = (state.fine > 0 ? '+' : '') + state.fine + '°';
      state.cropper.rotateTo(state.base + state.fine);
    });
    document.addEventListener('keydown', function (e) {
      if (!state || ui.el.hidden) { return; }
      if (e.key === 'Escape') { e.preventDefault(); finish(null); }
      else if (e.key === 'Tab') {
        // keep keyboard focus inside the editor
        var f = Array.prototype.filter.call(ui.el.querySelectorAll('button, input'), function (x) { return !x.disabled && x.offsetParent !== null; });
        if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    return ui;
  }

  function act(a, btn) {
    var c = state.cropper;
    switch (a) {
      case 'rotl': state.base -= 90; c.rotateTo(state.base + state.fine); break;
      case 'rotr': state.base += 90; c.rotateTo(state.base + state.fine); break;
      case 'fliph': state.sx = -state.sx; c.scaleX(state.sx); break;
      case 'flipv': state.sy = -state.sy; c.scaleY(state.sy); break;
      case 'zoomin': c.zoom(0.1); break;
      case 'zoomout': c.zoom(-0.1); break;
      case 'reset':
        state.base = 0; state.fine = 0; state.sx = 1; state.sy = 1;
        ui.range.value = 0; ui.out.textContent = '0°';
        c.reset();
        if (!state.fixed) { setAspect(state.aspect0); }
        break;
      case 'aspect': setAspect(parseFloat(btn.getAttribute('data-ratio'))); break;
      case 'apply': apply(); break;
      case 'cancel': finish(null); break;
    }
  }

  function setAspect(r) {
    state.cropper.setAspectRatio(isNaN(r) ? NaN : r);
    Array.prototype.forEach.call(ui.aspects.querySelectorAll('button'), function (b) {
      var on = (isNaN(r) && b.getAttribute('data-ratio') === 'NaN') || parseFloat(b.getAttribute('data-ratio')) === r;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  function apply() {
    var o = state.opts;
    ui.apply.disabled = true;
    var canvas = state.cropper.getCroppedCanvas({
      maxWidth: o.max, maxHeight: o.max, fillColor: '#FFFFFF',
      imageSmoothingEnabled: true, imageSmoothingQuality: 'high'
    });
    if (!canvas || !canvas.width) { ui.apply.disabled = false; return; }
    canvas.toBlob(function (blob) {
      ui.apply.disabled = false;
      if (!blob) { return; }
      var name = (state.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
      var file;
      try { file = new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }); }
      catch (e) { file = blob; file.name = name; }
      finish(file);
    }, 'image/jpeg', o.quality);
  }

  function finish(result) {
    if (!state) { return; }
    var s = state;
    state = null;
    if (s.cropper) { s.cropper.destroy(); }
    if (s.url) { URL.revokeObjectURL(s.url); }
    ui.img.removeAttribute('src');
    ui.el.hidden = true;
    document.documentElement.classList.remove('al-ie-open');
    if (s.focus && s.focus.focus) { try { s.focus.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }
    s.resolve(result);
  }

  function open(src, opts) {
    opts = opts || {};
    var o = {
      title: opts.title || tx('title', 'Edit photo'),
      aspect: typeof opts.aspect === 'number' ? opts.aspect : NaN,
      aspects: opts.aspects || [[tx('free', 'Free'), NaN], [tx('square', 'Square'), 1], ['4:3', 4 / 3], ['3:4', 3 / 4], ['16:9', 16 / 9]],
      round: !!opts.round,
      max: opts.max || 2400,
      quality: opts.quality || 0.9,
      apply: opts.applyLabel || tx('apply', 'Use photo')
    };
    if (state) { finish(null); }
    build();
    return new Promise(function (resolve, reject) {
      var isBlob = typeof src !== 'string';
      state = {
        opts: o, resolve: resolve, reject: reject, cropper: null,
        base: 0, fine: 0, sx: 1, sy: 1,
        fixed: !isNaN(o.aspect), aspect0: o.aspect,
        url: isBlob ? URL.createObjectURL(src) : null,
        name: isBlob && src.name ? src.name : 'photo.jpg',
        focus: document.activeElement
      };
      var my = state;
      ui.title.textContent = o.title;
      ui.apply.textContent = o.apply;
      ui.range.value = 0; ui.out.textContent = '0°';
      ui.el.classList.toggle('is-round', o.round);
      ui.el.classList.add('is-loading');
      ui.aspects.innerHTML = '';
      ui.aspects.hidden = my.fixed;
      if (!my.fixed) {
        o.aspects.forEach(function (a) {
          var b = document.createElement('button');
          b.type = 'button'; b.className = 'al-ie-chip'; b.textContent = a[0];
          b.setAttribute('role', 'radio'); b.setAttribute('data-act', 'aspect'); b.setAttribute('data-ratio', String(a[1]));
          ui.aspects.appendChild(b);
        });
      }
      ui.el.hidden = false;
      document.documentElement.classList.add('al-ie-open');
      ui.apply.focus({ preventScroll: true });

      var fail = function (msg) {
        if (state !== my) { return; }
        state = null;
        if (my.url) { URL.revokeObjectURL(my.url); }
        ui.el.hidden = true;
        ui.el.classList.remove('is-loading');
        document.documentElement.classList.remove('al-ie-open');
        reject(new Error(msg));
      };
      loadCropper().then(function () {
        if (state !== my) { return; }
        ui.img.onload = function () {
          if (state !== my) { return; }
          ui.img.onload = ui.img.onerror = null;
          my.cropper = new window.Cropper(ui.img, {
            viewMode: 1,
            dragMode: 'move',
            aspectRatio: o.aspect,
            autoCropArea: my.fixed ? 0.92 : 1,
            background: false,
            checkOrientation: true,
            toggleDragModeOnDblclick: false,
            responsive: true,
            restore: false,
            ready: function () {
              ui.el.classList.remove('is-loading');
              if (!my.fixed) { setAspect(NaN); }
            }
          });
        };
        ui.img.onerror = function () { fail(tx('unreadable', 'This photo could not be opened. Choose a JPG, PNG or WebP photo, or take the photo again with your camera.')); };
        ui.img.src = my.url || src;
      }, function (e) { fail(e.message); });
    });
  }

  window.alImageEditor = { open: open, load: loadCropper };

  // Shared helper: put a File into an <input type=file> (where the browser allows it)
  window.alImageEditor.setInputFile = function (input, file) {
    try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; return true; } catch (e) { return false; }
  };
})();
