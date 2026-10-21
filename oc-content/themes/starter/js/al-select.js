/*
 * Site dropdowns. Native <select> option lists use the operating system's
 * colours (blue highlight on macOS / Chrome) and cannot be styled, so every
 * single <select> gets a listbox in the site's own design. The real select
 * stays in the form (hidden) and remains the source of truth: forms, jQuery
 * validation and existing change handlers keep working unchanged.
 *
 * Opt out with data-al-native on the select or any ancestor.
 */
(function () {
  if (!document.querySelector || !window.getComputedStyle) {
    return;
  }

  var cfg = window.alSelect || {};
  var SEARCH_FROM = 11;          // options before a filter box is shown
  var all = [];
  var uid = 0;

  function label(o) {
    return (o.textContent || '').replace(/ /g, ' ').replace(/\s+/g, ' ').trim();
  }

  function depth(o) {
    var m = (o.textContent || '').match(/^[  ]+/);
    return m ? Math.min(3, Math.floor(m[0].length / 3)) : 0;
  }

  function signature(sel) {
    var s = sel.options.length + '|';
    for (var i = 0; i < sel.options.length; i++) {
      s += sel.options[i].value + ':' + sel.options[i].disabled + ';';
    }
    return s;
  }

  function variantOf(sel) {
    if (sel.closest('#al-header')) { return 'is-header'; }
    if (sel.closest('.price-amount-currency')) { return 'is-joined'; }
    if (sel.closest('.al-sort')) { return 'is-compact'; }
    return '';
  }

  function nameOf(sel) {
    if (sel.getAttribute('aria-label')) { return sel.getAttribute('aria-label'); }
    var lab = sel.id ? document.querySelector('label[for="' + sel.id.replace(/"/g, '') + '"]') : null;
    lab = lab || sel.closest('label');
    if (!lab) { return ''; }
    var span = lab.querySelector('span');
    return label(span || lab).replace(/\*$/, '').trim();
  }

  function enhance(sel) {
    if (sel.al || sel.multiple || sel.closest('[data-al-native], .iti')) {
      return;
    }

    var st = { sel: sel, active: -1, sig: '', open: false };
    sel.al = st;
    sel.classList.add('al-dd-native');
    sel.setAttribute('tabindex', '-1');
    sel.setAttribute('aria-hidden', 'true');

    var wrap = document.createElement('div');
    wrap.className = 'al-dd ' + variantOf(sel);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'al-dd-btn';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');

    var val = document.createElement('span');
    val.className = 'al-dd-value';
    btn.appendChild(val);

    var panel = document.createElement('div');
    panel.className = 'al-dd-panel';
    panel.hidden = true;

    var list = document.createElement('ul');
    list.className = 'al-dd-list';
    list.setAttribute('role', 'listbox');
    list.id = 'al-dd-' + (++uid);
    btn.setAttribute('aria-controls', list.id);

    var search = null;
    if (sel.options.length >= SEARCH_FROM) {
      search = document.createElement('input');
      search.type = 'search';
      search.className = 'al-dd-search';
      search.setAttribute('autocomplete', 'off');
      search.setAttribute('aria-controls', list.id);
      search.placeholder = cfg.search || 'Search';
      panel.appendChild(search);
    }
    panel.appendChild(list);
    wrap.appendChild(btn);
    wrap.appendChild(panel);
    // before the select, so validation messages placed after it sit below the dropdown
    sel.parentNode.insertBefore(wrap, sel);

    st.wrap = wrap; st.btn = btn; st.val = val; st.panel = panel; st.list = list; st.search = search;
    st.name = nameOf(sel);

    build(st);
    sync(st);
    bind(st);
    all.push(st);
  }

  function build(st) {
    var sel = st.sel, list = st.list, group = null;
    list.innerHTML = '';
    st.opts = [];
    for (var i = 0; i < sel.options.length; i++) {
      var o = sel.options[i];
      var g = o.parentNode && o.parentNode.tagName === 'OPTGROUP' ? o.parentNode : null;
      if (g && g !== group) {
        var gh = document.createElement('li');
        gh.className = 'al-dd-group';
        gh.setAttribute('role', 'presentation');
        gh.textContent = g.label;
        list.appendChild(gh);
      }
      group = g;
      if (o.hidden) { continue; }
      var li = document.createElement('li');
      li.className = 'al-dd-opt';
      li.id = list.id + '-' + i;
      li.setAttribute('role', 'option');
      li.setAttribute('data-index', i);
      li.style.setProperty('--al-dd-depth', depth(o) + (g ? 1 : 0));
      if (o.disabled) { li.setAttribute('aria-disabled', 'true'); }
      var t = document.createElement('span');
      t.className = 'al-dd-text';
      t.textContent = label(o);
      li.appendChild(t);
      list.appendChild(li);
      st.opts.push(li);
    }
    st.sig = signature(sel);
  }

  function sync(st) {
    var sel = st.sel;
    if (signature(sel) !== st.sig) { build(st); }
    var idx = sel.selectedIndex;
    var o = idx >= 0 ? sel.options[idx] : null;
    var text = o ? label(o) : '';
    if (st.val.textContent !== text) { st.val.textContent = text; }
    st.btn.classList.toggle('is-empty', !o || o.value === '');
    st.btn.setAttribute('aria-label', (st.name ? st.name + ': ' : '') + text);
    st.btn.disabled = sel.disabled;
    st.wrap.classList.toggle('is-disabled', sel.disabled);
    st.wrap.classList.toggle('is-error', sel.classList.contains('error') || sel.getAttribute('aria-invalid') === 'true');
    // follow the select when other scripts hide or show it
    var hidden = window.getComputedStyle(sel).display === 'none';
    if (st.wrap.hidden !== hidden) { st.wrap.hidden = hidden; }
    for (var i = 0; i < st.opts.length; i++) {
      var on = +st.opts[i].getAttribute('data-index') === idx;
      if ((st.opts[i].getAttribute('aria-selected') === 'true') !== on) {
        st.opts[i].setAttribute('aria-selected', on ? 'true' : 'false');
      }
    }
  }

  function visible(st) {
    return st.opts.filter(function (li) { return !li.hidden && li.getAttribute('aria-disabled') !== 'true'; });
  }

  function setActive(st, li, scroll) {
    st.opts.forEach(function (x) { x.classList.remove('is-active'); });
    if (!li) {
      st.active = -1;
      (st.search || st.btn).removeAttribute('aria-activedescendant');
      return;
    }
    li.classList.add('is-active');
    st.active = +li.getAttribute('data-index');
    (st.search || st.btn).setAttribute('aria-activedescendant', li.id);
    if (scroll !== false) {
      var lt = li.offsetTop, lb = lt + li.offsetHeight, s = st.list.scrollTop, h = st.list.clientHeight;
      if (lt < s) { st.list.scrollTop = lt - 4; } else if (lb > s + h) { st.list.scrollTop = lb - h + 4; }
    }
  }

  function move(st, step) {
    var v = visible(st);
    if (!v.length) { return; }
    var cur = v.findIndex(function (li) { return +li.getAttribute('data-index') === st.active; });
    var next = cur < 0 ? (step > 0 ? 0 : v.length - 1) : Math.max(0, Math.min(v.length - 1, cur + step));
    setActive(st, v[next]);
  }

  function filter(st) {
    var q = st.search.value.trim().toLowerCase();
    st.opts.forEach(function (li) { li.hidden = q !== '' && li.textContent.toLowerCase().indexOf(q) === -1; });
    st.list.querySelectorAll('.al-dd-group').forEach(function (g) { g.hidden = q !== ''; });
    var v = visible(st);
    setActive(st, v[0] || null);
    st.wrap.classList.toggle('is-nomatch', v.length === 0);
  }

  function open(st) {
    if (st.open || st.sel.disabled) { return; }
    all.forEach(function (o) { if (o !== st) { close(o); } });
    sync(st);
    st.open = true;
    st.panel.hidden = false;
    st.wrap.classList.add('is-open');
    st.btn.setAttribute('aria-expanded', 'true');

    var r = st.btn.getBoundingClientRect();
    var below = window.innerHeight - r.bottom, above = r.top;
    st.wrap.classList.toggle('is-up', below < 260 && above > below);
    var right = window.innerWidth - r.left;
    st.wrap.classList.toggle('is-right', right < Math.min(st.panel.offsetWidth, 360) + 16);

    if (st.search) {
      st.search.value = '';
      filter(st);
      st.search.focus();
    }
    var cur = st.opts.filter(function (li) { return +li.getAttribute('data-index') === st.sel.selectedIndex; })[0];
    setActive(st, cur || visible(st)[0] || null);
  }

  function close(st, focusBtn) {
    if (!st.open) { return; }
    st.open = false;
    st.panel.hidden = true;
    st.wrap.classList.remove('is-open', 'is-up', 'is-right', 'is-nomatch');
    st.btn.setAttribute('aria-expanded', 'false');
    setActive(st, null);
    if (focusBtn) { st.btn.focus(); }
  }

  function choose(st, i) {
    var o = st.sel.options[i];
    if (!o || o.disabled) { return; }
    var changed = st.sel.selectedIndex !== i;
    st.sel.selectedIndex = i;
    sync(st);
    close(st, true);
    if (changed) {
      st.sel.dispatchEvent(new Event('input', { bubbles: true }));
      st.sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  var typed = '', typedAt = 0;
  function typeahead(st, ch) {
    var now = Date.now();
    typed = now - typedAt > 700 ? ch : typed + ch;
    typedAt = now;
    var v = visible(st);
    var start = v.findIndex(function (li) { return +li.getAttribute('data-index') === st.active; });
    for (var k = 1; k <= v.length; k++) {
      var li = v[(start + k) % v.length];
      if (li.textContent.toLowerCase().indexOf(typed) === 0) { setActive(st, li); return; }
    }
  }

  function keys(st, e) {
    var k = e.key;
    if (!st.open) {
      if (k === 'ArrowDown' || k === 'ArrowUp' || k === 'Enter' || k === ' ') {
        e.preventDefault();
        open(st);
      } else if (k.length === 1 && /\S/.test(k) && !e.metaKey && !e.ctrlKey && !e.altKey) {
        open(st);
        if (!st.search) { typeahead(st, k.toLowerCase()); }
      }
      return;
    }
    if (k === 'ArrowDown') { e.preventDefault(); move(st, 1); }
    else if (k === 'ArrowUp') { e.preventDefault(); move(st, -1); }
    else if (k === 'PageDown') { e.preventDefault(); move(st, 8); }
    else if (k === 'PageUp') { e.preventDefault(); move(st, -8); }
    else if (k === 'Home' && !st.search) { e.preventDefault(); move(st, -100000); }
    else if (k === 'End' && !st.search) { e.preventDefault(); move(st, 100000); }
    else if (k === 'Enter' || (k === ' ' && !st.search)) {
      e.preventDefault();
      if (st.active >= 0) { choose(st, st.active); } else { close(st, true); }
    }
    else if (k === 'Escape') { e.preventDefault(); e.stopPropagation(); close(st, true); }
    else if (k === 'Tab') { close(st); }
    else if (!st.search && k.length === 1 && /\S/.test(k) && !e.metaKey && !e.ctrlKey) { typeahead(st, k.toLowerCase()); }
  }

  function bind(st) {
    st.btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (st.open) { close(st, true); } else { open(st); }
    });
    st.btn.addEventListener('keydown', function (e) { keys(st, e); });
    if (st.search) {
      st.search.addEventListener('keydown', function (e) { keys(st, e); });
      st.search.addEventListener('input', function () { filter(st); });
    }
    st.list.addEventListener('mousedown', function (e) { e.preventDefault(); });   // keep focus
    st.list.addEventListener('click', function (e) {
      var li = e.target.closest('.al-dd-opt');
      if (li) { choose(st, +li.getAttribute('data-index')); }
    });
    st.list.addEventListener('mousemove', function (e) {
      var li = e.target.closest('.al-dd-opt');
      if (li && li.getAttribute('aria-disabled') !== 'true' && +li.getAttribute('data-index') !== st.active) { setActive(st, li, false); }
    });
    // label clicks, validation and scripts focus the hidden select: hand focus to the button
    st.sel.addEventListener('focus', function () { st.btn.focus(); });
    st.sel.addEventListener('change', function () { sync(st); });
  }

  document.addEventListener('pointerdown', function (e) {
    all.forEach(function (st) { if (st.open && !st.wrap.contains(e.target)) { close(st); } });
  }, true);
  // phones fire resize when the address bar slides away while scrolling: only a width change closes
  var lastWidth = window.innerWidth;
  window.addEventListener('resize', function () {
    if (window.innerWidth === lastWidth) { return; }
    lastWidth = window.innerWidth;
    all.forEach(function (st) { close(st); });
  });

  function scan(root) {
    (root.querySelectorAll ? root : document).querySelectorAll('select').forEach(enhance);
  }

  function start() {
    scan(document);
    // selects added later (region lists, plugin fields, ajax forms)
    if (window.MutationObserver) {
      var pending = false;
      new MutationObserver(function () {
        if (pending) { return; }
        pending = true;
        setTimeout(function () { pending = false; scan(document); }, 60);
      }).observe(document.body, { childList: true, subtree: true });
    }
    // jQuery .val() / scripts change options and selection without events
    setInterval(function () {
      all = all.filter(function (st) {
        if (!document.documentElement.contains(st.sel)) { st.wrap.remove(); return false; }
        if (!st.open) { sync(st); }
        return true;
      });
    }, 350);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
