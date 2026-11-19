/* Service worker registration, "Install the app" prompt and the push toggle. Config: window.AL_PWA (inc.pwa.php). */
(function () {
  'use strict';
  var cfg = window.AL_PWA;
  if (!cfg || !('serviceWorker' in navigator)) { return; }

  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* private mode */ } }
  };
  var ready = navigator.serviceWorker.register(cfg.sw, { scope: cfg.scope || '/' }).then(function () { return navigator.serviceWorker.ready; });
  ready.catch(function () { /* no worker: the site works as before */ });

  /* ---------- Install prompt ---------- */
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var deferred = null;
  var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
  var mobile = window.matchMedia('(max-width: 900px)').matches;

  function snoozed() { return Date.now() < parseInt(store.get('al_install_snooze') || '0', 10); }

  function banner(ios) {
    if (standalone || snoozed() || document.querySelector('.al-install')) { return; }
    var el = document.createElement('div');
    el.className = 'al-install';
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-label', cfg.text.install);
    el.innerHTML = '<img src="" alt="" width="40" height="40"><div><strong></strong><span></span></div>'
      + (ios ? '' : '<button type="button" class="al-btn al-btn-brass" data-install></button>')
      + '<button type="button" class="al-install-close" data-close aria-label=""></button>';
    el.querySelector('img').src = (document.querySelector('link[rel="apple-touch-icon"]') || {}).href || '';
    el.querySelector('strong').textContent = cfg.text.install;
    el.querySelector('span').textContent = ios ? cfg.text.iosSub : cfg.text.installSub;
    var close = el.querySelector('[data-close]');
    close.textContent = '×';
    close.setAttribute('aria-label', cfg.text.close);
    close.addEventListener('click', function () { store.set('al_install_snooze', String(Date.now() + 14 * 86400000)); el.remove(); });
    var btn = el.querySelector('[data-install]');
    if (btn) {
      btn.textContent = cfg.text.installBtn;
      btn.addEventListener('click', function () {
        if (!deferred) { return; }
        deferred.prompt();
        deferred.userChoice.then(function (c) { if (c.outcome !== 'accepted') { store.set('al_install_snooze', String(Date.now() + 30 * 86400000)); } el.remove(); deferred = null; });
      });
    }
    document.body.appendChild(el);
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferred = e;
    // only after the visitor has looked around a little
    var visits = parseInt(store.get('al_visits') || '0', 10);
    if (mobile && visits >= 2) { setTimeout(function () { banner(false); }, 4000); }
  });
  store.set('al_visits', String(parseInt(store.get('al_visits') || '0', 10) + 1));
  if (isIos && !standalone && parseInt(store.get('al_visits') || '0', 10) >= 3) { setTimeout(function () { banner(true); }, 5000); }
  window.addEventListener('appinstalled', function () { var b = document.querySelector('.al-install'); if (b) { b.remove(); } });

  /* ---------- Push toggle ---------- */
  var box = document.querySelector('[data-al-push]');
  if (!box || !cfg.push || !cfg.key) { return; }
  var status = box.querySelector('[data-al-push-status]');
  var toggle = box.querySelector('[data-al-push-toggle]');
  box.hidden = false;

  if (!('PushManager' in window) || !('Notification' in window)) {
    status.textContent = cfg.text.unsupported;
    toggle.hidden = true;
    return;
  }

  function keyBytes(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4);
    var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) { out[i] = raw.charCodeAt(i); }
    return out;
  }
  function post(op, endpoint) {
    var body = new URLSearchParams({ op: op, endpoint: endpoint, octoken: cfg.token });
    return fetch(cfg.subscribe, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { if (!r.ok) { throw new Error('save'); } return r.json(); });
  }
  function show(on) {
    if (Notification.permission === 'denied') {
      status.textContent = cfg.text.blocked;
      toggle.hidden = true;
      return;
    }
    box.classList.toggle('is-on', on);
    status.textContent = on ? cfg.text.on : cfg.text.off;
    toggle.textContent = on ? cfg.text.turnOff : cfg.text.turnOn;
    toggle.className = on ? 'al-btn al-btn-outline' : 'al-btn al-btn-ink';
  }

  ready.then(function (reg) {
    return reg.pushManager.getSubscription().then(function (sub) {
      // keep the server copy in step (the member may have signed in on a new account)
      if (sub) { post('subscribe', sub.endpoint).catch(function () {}); }
      show(!!sub);
      toggle.addEventListener('click', function () {
        toggle.disabled = true;
        reg.pushManager.getSubscription().then(function (current) {
          if (current) {
            return post('unsubscribe', current.endpoint).catch(function () {}).then(function () { return current.unsubscribe(); }).then(function () { show(false); });
          }
          return Notification.requestPermission().then(function (perm) {
            if (perm !== 'granted') { show(false); return; }
            return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(cfg.key) })
              .then(function (s) { return post('subscribe', s.endpoint); }).then(function () { show(true); });
          });
        }).catch(function () { status.textContent = cfg.text.error; }).then(function () { toggle.disabled = false; });
      });
    });
  }).catch(function () { status.textContent = cfg.text.unsupported; toggle.hidden = true; });
})();
