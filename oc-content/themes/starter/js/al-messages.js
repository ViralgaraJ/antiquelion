/*
 * Conversation page: send without reloading, new messages appear while the page is
 * open (checked every few seconds while the tab is visible), "Seen" under your last
 * message, counter-offer forms, report dialog. Without JavaScript the forms still post.
 */
(function () {
  var root = document.querySelector('[data-al-thread]');
  if (!root) { return; }
  var cfg = window.alMsg || {};
  var list = root.querySelector('.al-thread-list');
  var form = root.querySelector('.al-thread-compose');
  var seenNote = root.querySelector('.al-msg-seen-note');
  var after = +root.getAttribute('data-after') || 0;
  var pollUrl = root.getAttribute('data-poll');
  var busy = false;

  // desktop: the message list scrolls; phones: the page scrolls and the reply box stays above the dock
  function pageMode() { return list.scrollHeight <= list.clientHeight + 1 || getComputedStyle(list).overflowY === 'visible'; }
  function toBottom() {
    list.scrollTop = list.scrollHeight;
    if (pageMode()) {
      // bring the end of the conversation (not the footer) to the bottom of the screen, above the dock
      var dock = document.querySelector('.mobile-bottom-dock');
      var limit = form && getComputedStyle(form).position === 'fixed' ? form.getBoundingClientRect().top
        : (dock && getComputedStyle(dock).display !== 'none' ? dock.getBoundingClientRect().top : window.innerHeight);
      var end = (seenNote && !seenNote.hidden ? seenNote : list).getBoundingClientRect().bottom;
      window.scrollTo({ top: Math.max(0, end + window.scrollY - limit + 8), behavior: 'instant' });
    }
  }
  toBottom();
  window.addEventListener('load', toBottom);

  function setBadges(n) {
    document.querySelectorAll('[data-al-msg-total]').forEach(function (el) {
      el.textContent = n;
      el.hidden = n < 1;
    });
  }

  function setSeen(id) {
    var mine = list.querySelectorAll('.al-msg-row[data-mine]');
    var last = mine.length ? mine[mine.length - 1] : null;
    var show = !!(last && id && +last.getAttribute('data-id') === id);
    if (seenNote) { seenNote.hidden = !show; }
  }

  function apply(res) {
    if (res.reload) { window.location.reload(); return; }
    if (res.html) {
      var near = pageMode() ? (list.getBoundingClientRect().bottom < window.innerHeight + 260) : (list.scrollHeight - list.scrollTop - list.clientHeight < 120);
      var tmp = document.createElement('div');
      tmp.innerHTML = res.html;
      while (tmp.firstChild) { list.appendChild(tmp.firstChild); }
      if (near) { toBottom(); }
    }
    if (res.last) { after = Math.max(after, res.last); }
    setSeen(res.seen || 0);
    if (typeof res.unread === 'number') { setBadges(res.unread); }
  }

  function poll() {
    if (busy || document.hidden) { return; }
    busy = true;
    fetch(pollUrl + '&after=' + after, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (res && res.success) { apply(res); } })
      .catch(function () {})
      .then(function () { busy = false; });
  }
  setInterval(poll, 8000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });

  if (form) {
    var ta = form.querySelector('textarea');
    var err = form.querySelector('.al-thread-error');
    var btn = form.querySelector('button[type="submit"]');
    // the old theme forces textarea height with !important, so set ours the same way
    function grow() { ta.style.setProperty('height', '46px', 'important'); ta.style.setProperty('height', Math.min(Math.max(ta.scrollHeight + 2, 46), 180) + 'px', 'important'); }
    ta.addEventListener('input', grow);
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
    });
    form.addEventListener('submit', function (e) {
      if (!window.fetch || !window.FormData) { return; }
      e.preventDefault();
      if (ta.value.trim() === '' || btn.disabled) { return; }
      var data = new FormData(form);
      data.append('ajax', '1');
      data.append('after', after);
      btn.disabled = true;
      err.hidden = true;
      busy = true;
      fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.success) {
            if (res && res.login) { window.location.reload(); return; }
            err.textContent = (res && res.error) || cfg.error;
            err.hidden = false;
            return;
          }
          ta.value = '';
          grow();
          apply(res);
          toBottom();
        })
        .catch(function () { err.textContent = cfg.error; err.hidden = false; })
        .then(function () { btn.disabled = false; busy = false; ta.focus(); });
    });
  }

  // counter-offer: show the amount form under the offer
  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-al-counter]');
    if (!b) { return; }
    var f = root.querySelector('[data-al-counter-form="' + b.getAttribute('data-al-counter') + '"]');
    if (f) { f.hidden = !f.hidden; if (!f.hidden) { f.querySelector('input[name="amount"]').focus(); } }
  });

  // report dialog
  var rep = root.querySelector('.al-report-dialog');
  var repOpen = root.querySelector('[data-al-report-open]');
  if (rep && repOpen && typeof rep.showModal === 'function') {
    repOpen.addEventListener('click', function () {
      var menu = repOpen.closest('details');
      if (menu) { menu.open = false; }
      rep.showModal();
    });
    rep.querySelector('[data-al-dialog-close]').addEventListener('click', function () { rep.close(); });
    rep.addEventListener('click', function (e) { if (e.target === rep) { rep.close(); } });
  }
  // close the "more" menu when clicking elsewhere
  document.addEventListener('click', function (e) {
    root.querySelectorAll('.al-thread-menu[open]').forEach(function (d) { if (!d.contains(e.target)) { d.open = false; } });
  });
})();
