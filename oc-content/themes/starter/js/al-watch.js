/*
 * Watchlist hearts: [data-al-watch="<item id>"] buttons on cards and the item page.
 * Signed-out visitors go to the sign-in page. Config: window.alWatch (head.php).
 */
(function () {
  var cfg = window.alWatch || {};

  function setButtons(id, on) {
    document.querySelectorAll('[data-al-watch="' + id + '"]').forEach(function (b) {
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.setAttribute('aria-label', on ? cfg.remove : cfg.add);
      b.setAttribute('title', on ? cfg.remove : cfg.add);
      var t = b.querySelector('.al-watch-text');
      if (t) { t.textContent = on ? t.getAttribute('data-on') : t.getAttribute('data-off'); }
    });
  }

  function setCounts(id, count, total) {
    document.querySelectorAll('[data-al-watch-count="' + id + '"]').forEach(function (el) {
      el.textContent = (count === 1 ? cfg.one : cfg.many).replace('%d', count);
      el.hidden = count < 1;
    });
    document.querySelectorAll('[data-al-watch-total]').forEach(function (el) {
      el.textContent = total;
      el.hidden = total < 1;
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-al-watch]');
    if (!btn) { return; }
    e.preventDefault();
    e.stopPropagation();
    if (!cfg.token) { window.location.href = cfg.login; return; }
    if (btn.getAttribute('aria-busy') === 'true') { return; }

    var id = btn.getAttribute('data-al-watch');
    var was = btn.classList.contains('is-on');
    setButtons(id, !was);               // instant feedback, undone if the request fails
    btn.setAttribute('aria-busy', 'true');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onload = function () {
      btn.removeAttribute('aria-busy');
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.login) { window.location.href = cfg.login; return; }
        if (!res.success) { throw new Error(res.error || ''); }
        setButtons(id, res.watched);
        setCounts(id, res.count, res.total);
        if (res.watched) { btn.classList.add('is-pop'); setTimeout(function () { btn.classList.remove('is-pop'); }, 400); }
      } catch (err) {
        setButtons(id, was);
        window.alert(err.message || cfg.error);
      }
    };
    xhr.onerror = function () { btn.removeAttribute('aria-busy'); setButtons(id, was); window.alert(cfg.error); };
    xhr.send('itemId=' + encodeURIComponent(id) + '&octoken=' + encodeURIComponent(cfg.token));
  });
})();
