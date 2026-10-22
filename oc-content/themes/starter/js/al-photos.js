/*
 * Listing photos on the sell / edit form. Replaces Osclass's Uppy dialog and posts
 * to the same upload endpoint, building the same photo tiles Osclass builds:
 * - "Add photos" (or dropping photos on the box) uploads straight away, no extra
 *   dialog; each photo shows a preview with progress while it uploads;
 * - large phone photos are made smaller in the browser first, so they pass the
 *   site's size limit, and formats the site does not take (WebP, HEIC) become JPEG;
 * - every photo has Edit (crop, rotate, straighten, flip, zoom: js/al-imgedit.js)
 *   and Delete, and the first photo is marked as the cover.
 * Edited photos are uploaded as a new file that takes the old one's place; the old
 * one is then deleted.
 */
(function () {
  'use strict';
  if (!document.getElementById('uppy-gallery') || !document.getElementById('uppy-upload-button')) { return; }
  var cfg = window.alPhotos || {};
  var t = cfg.t || {};
  var up = typeof uppy !== 'undefined' ? uppy : null;   // eslint-disable-line no-undef
  var list = document.getElementById('uppy-gallery');
  var box = document.getElementById('photos');
  var button = document.getElementById('uppy-upload-button');
  var MAX_BYTES = cfg.maxBytes || 4 * 1024 * 1024;
  var MAX_SIDE = 2560;
  var pending = {};                                  // pending tile id -> { li, replace }
  var seq = 0;
  var queue = [], active = 0, CONCURRENCY = 3;

  var picker = document.createElement('input');
  picker.type = 'file';
  picker.accept = 'image/*';
  picker.multiple = true;
  picker.className = 'al-photos-picker';
  picker.setAttribute('aria-hidden', 'true');
  picker.tabIndex = -1;
  box.appendChild(picker);

  var note = document.createElement('div');
  note.className = 'al-photos-note';
  note.setAttribute('role', 'status');
  note.setAttribute('aria-live', 'polite');
  box.appendChild(note);
  var noteTimer = null;
  function say(text, bad) {
    note.textContent = text || '';
    note.className = 'al-photos-note' + (text ? (bad ? ' is-error' : ' is-ok') : '');
    clearTimeout(noteTimer);
    if (text && !bad) { noteTimer = setTimeout(function () { say(''); }, 5000); }
  }

  function tiles() { return Array.prototype.filter.call(list.children, function (li) { return li.tagName === 'LI' && !li.hidden && !li.classList.contains('is-failed'); }); }
  function pendingCount() { return Object.keys(pending).length; }

  // Photos the listing can still take (asks the server how many are saved)
  function remaining() {
    var left;
    try { left = (up && typeof setUppyLimit === 'function') ? setUppyLimit(up) : null; } catch (e) { left = null; }   // eslint-disable-line no-undef
    if (left === null || isNaN(left)) {
      left = (window.maxItems || 12) - tiles().length;
    } else {
      left -= pendingCount();
    }
    return Math.max(0, left);
  }

  /* ---------- preparing photos ---------- */
  function decode(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () { resolve({ img: img, url: url }); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error(t.unreadable)); };
      img.src = url;
    });
  }
  function prep(file) {
    var okType = /^image\/(jpeg|png|gif)$/.test(file.type);
    if (okType && file.size <= MAX_BYTES * 0.9) {
      // the server goes by the file name: make it end in .jpg / .png / .gif
      var ext = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/gif': 'gif' }[file.type];
      if (!new RegExp('\\.(' + (ext === 'jpg' ? 'jpe?g' : ext) + ')$', 'i').test(file.name || '')) {
        try { file = new File([file], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.' + ext, { type: file.type }); } catch (e) { /* keep it */ }
      }
      return Promise.resolve(file);
    }
    return decode(file).then(function (d) {
      var w = d.img.naturalWidth, h = d.img.naturalHeight, k = Math.min(1, MAX_SIDE / Math.max(w, h));
      var c = document.createElement('canvas');
      c.width = Math.round(w * k); c.height = Math.round(h * k);
      var ctx = c.getContext('2d');
      ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0, 0, c.width, c.height);
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(d.img, 0, 0, c.width, c.height);
      URL.revokeObjectURL(d.url);
      return new Promise(function (resolve, reject) {
        var q = 0.9;
        (function out() {
          c.toBlob(function (b) {
            if (!b) { reject(new Error(t.unreadable)); return; }
            if (b.size > MAX_BYTES * 0.9 && q > 0.6) { q -= 0.1; out(); return; }
            resolve(new File([b], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
          }, 'image/jpeg', q);
        })();
      });
    });
  }

  /* ---------- tiles ---------- */
  var ico = {
    edit: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></svg>',
    del: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>'
  };
  function decorate(li) {
    if (li.alDecorated || li.classList.contains('al-ph-pending')) { return; }
    li.alDecorated = true;
    li.classList.add('al-ph');
    var bar = document.createElement('div');
    bar.className = 'al-ph-bar';
    bar.innerHTML = '<button type="button" class="al-ph-edit" aria-label="' + t.edit + '">' + ico.edit + '<span>' + t.edit + '</span></button>';
    // the delete link must stay a direct child of the tile: Osclass removes its parent
    var del = li.querySelector('.qq-upload-delete');
    if (del) {
      del.innerHTML = ico.del + '<span class="al-sr">' + t.del + '</span>';
      del.setAttribute('aria-label', t.del);
      del.setAttribute('title', t.del);
      del.setAttribute('role', 'button');
      del.removeAttribute('style');
    }
    li.appendChild(bar);
    var badge = document.createElement('span');
    badge.className = 'al-ph-cover';
    badge.textContent = t.cover;
    li.appendChild(badge);
    var img = li.querySelector('.ajax_preview_img img');
    if (img) { img.alt = t.photo; img.setAttribute('draggable', 'false'); }
  }
  function decorateAll() { Array.prototype.forEach.call(list.children, decorate); }
  new MutationObserver(decorateAll).observe(list, { childList: true });
  decorateAll();

  function pendingTile(file, replace) {
    var li = document.createElement('li');
    li.className = 'al-ph-pending';
    li.id = 'al-pending-' + (++seq);
    li.innerHTML = '<div class="ajax_preview_img"><img alt="" /></div><div class="al-ph-progress"><span></span></div><div class="al-ph-state">' + t.uploading + '</div>';
    li.querySelector('img').src = URL.createObjectURL(file);
    if (replace) { replace.parentNode.insertBefore(li, replace); replace.classList.add('is-replacing'); } else { list.appendChild(li); }
    return li;
  }
  function failTile(li, msg) {
    li.classList.add('is-failed');
    li.querySelector('.al-ph-state').textContent = msg || t.failed;
    var x = document.createElement('button');
    x.type = 'button'; x.className = 'al-ph-dismiss'; x.textContent = t.dismiss;
    x.addEventListener('click', function () { li.remove(); });
    li.appendChild(x);
  }

  /* ---------- adding and uploading photos ---------- */
  function addFiles(files, replace) {
    files = Array.prototype.filter.call(files || [], function (f) { return f && (/^image\//.test(f.type) || /\.(jpe?g|png|gif|webp|heic|heif)$/i.test(f.name)); });
    if (!files.length) { say(t.notImage, true); return; }
    var left = replace ? 1 : remaining();
    if (!replace && left <= 0) { say(t.full, true); return; }
    if (!replace && files.length > left) { say(t.some.replace('%d', left), true); files = files.slice(0, left); } else { say(''); }
    files.forEach(function (raw) {
      var li = pendingTile(raw, replace);
      var job = { li: li, replace: replace || null };
      pending[li.id] = job;
      prep(raw).then(function (file) {
        job.file = file;
        queue.push(job);
        pump();
      }, function (err) { fail(job, err.message); });
    });
  }
  function pump() {
    while (active < CONCURRENCY && queue.length) { send(queue.shift()); }
  }
  function fail(job, msg) {
    delete pending[job.li.id];
    failTile(job.li, msg || t.failed);
    if (job.replace) { job.replace.classList.remove('is-replacing'); }
  }
  function send(job) {
    active++;
    var fd = new FormData();
    fd.append('qquuid', 'al-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10));
    fd.append('uppyfile', job.file, job.file.name);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.upload, true);
    xhr.upload.onprogress = function (e) {
      if (e.lengthComputable) { job.li.querySelector('.al-ph-progress span').style.width = Math.min(100, Math.round(e.loaded / e.total * 100)) + '%'; }
    };
    xhr.onload = function () {
      active--;
      var res = null;
      try { res = JSON.parse(xhr.responseText); } catch (e) { res = null; }
      if (xhr.status >= 200 && xhr.status < 300 && res && res.uploadName) {
        done(job, res);
      } else {
        fail(job, (res && res.error) || t.failed);
      }
      pump();
    };
    xhr.onerror = function () { active--; fail(job, t.offline); pump(); };
    xhr.send(fd);
  }
  function esc(v) { return String(v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  // The same tile Osclass adds after an upload, so its delete, reorder and publish code work unchanged
  function done(job, res) {
    delete pending[job.li.id];
    var name = esc(res.uploadName);
    var li = document.createElement('li');
    li.className = 'qq-upload-success qq-new-img';
    li.id = res.uploadName;
    li.innerHTML = '<span class="qq-upload-file">' + esc(job.file.name) + '</span>' +
      '<a class="qq-upload-delete" href="#" ajaxfile="' + name + '">' + esc(t.del) + '</a>' +
      '<div class="ajax_preview_img">' + (cfg.reorder ? '<span class="qq-upload-move" title="' + esc(t.reorder) + '"><i class="fa fa-arrows-alt"></i></span>' : '') +
      '<img src="' + esc(res.uploadUrl || (cfg.temp + res.uploadName)) + '"></div>' +
      '<input type="hidden" name="ajax_photos[]" value="' + name + '"/>';
    job.li.replaceWith(li);
    if (job.replace) {
      if (job.replace.classList.contains('qq-img-stored')) {
        // a photo already saved on the listing: delete it only when the form is saved
        var del = job.replace.querySelector('.qq-upload-delete');
        var form = box.closest('form');
        if (form && del && del.getAttribute('photoid')) {
          var keep = document.createElement('input');
          keep.type = 'hidden'; keep.name = 'al_replaced_res[]'; keep.value = del.getAttribute('photoid');
          form.appendChild(keep);
        }
        job.replace.remove();
      } else {
        removeTile(job.replace, true);
      }
    }
    sortUpdate();
    if (up) { try { window.maxItemsUppy = setUppyLimit(up); } catch (e) { /* ignore */ } }   // eslint-disable-line no-undef
    say(job.replace ? t.updated : (pendingCount() ? '' : t.added), false);
  }

  function sortUpdate() {
    if (window.jQuery && window.jQuery.fn.sortable) { window.jQuery(list).trigger('sortupdate'); }
  }

  // Delete a photo on the server (no question asked: used after an edit)
  function deleteQuery(li) {
    var a = li.querySelector('.qq-upload-delete');
    if (a && a.getAttribute('ajaxfile')) { return 'ajax_photo=' + encodeURIComponent(a.getAttribute('ajaxfile')); }
    if (a) { return 'id=' + encodeURIComponent(a.getAttribute('photoid')) + '&item=' + encodeURIComponent(a.getAttribute('itemid')) + '&code=' + encodeURIComponent(a.getAttribute('photoname')) + '&secret=' + encodeURIComponent(a.getAttribute('photosecret') || ''); }
    return '';
  }
  function removeTile(li, quiet) {
    var q = deleteQuery(li);
    li.remove();
    sortUpdate();
    if (q) {
      fetch(cfg.base + '?page=ajax&action=delete_image&' + q, { method: 'POST', credentials: 'same-origin' })
        .then(function () { if (up) { try { window.maxItemsUppy = setUppyLimit(up); } catch (e) { /* ignore */ } } })   // eslint-disable-line no-undef
        .catch(function () { if (!quiet) { say(t.failed, true); } });
    }
  }

  /* ---------- editing ---------- */
  function sources(li) {
    var img = li.querySelector('.ajax_preview_img img');
    if (!img) { return []; }
    var src = img.getAttribute('src');
    if (/_thumbnail\./.test(src)) {
      // saved photos show a thumbnail: edit the original (or the full-size copy)
      return [src.replace('_thumbnail.', '_original.'), src.replace('_thumbnail.', '.'), src];
    }
    return [src];
  }
  function edit(li) {
    if (!window.alImageEditor) { return; }
    var srcs = sources(li);
    var tryOpen = function (i) {
      return window.alImageEditor.open(srcs[i], { title: t.editTitle, applyLabel: t.apply, max: 2400, quality: 0.9 })
        .catch(function (e) { if (i + 1 < srcs.length) { return tryOpen(i + 1); } throw e; });
    };
    tryOpen(0).then(function (file) {
      if (file) { addFiles([file], li); }
    }, function (e) { say(e.message, true); });
  }

  /* ---------- wiring ---------- */
  // "Add photos" opens the file chooser directly instead of Uppy's dialog
  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('#uppy-upload-button')) {
      e.preventDefault();
      e.stopImmediatePropagation();
      picker.click();
    }
  }, true);
  button.setAttribute('role', 'button');
  button.setAttribute('tabindex', '0');
  button.setAttribute('aria-label', t.add);
  button.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); picker.click(); }
  });
  picker.addEventListener('change', function () {
    var files = Array.prototype.slice.call(picker.files || []);
    picker.value = '';
    if (files.length) { addFiles(files); }
  });

  list.addEventListener('click', function (e) {
    var b = e.target.closest('.al-ph-edit');
    if (b) { e.preventDefault(); edit(b.closest('li')); }
  });
  // Double-click (or tap the photo) to edit as well
  list.addEventListener('dblclick', function (e) {
    var li = e.target.closest('li.al-ph');
    if (li && e.target.closest('.ajax_preview_img')) { edit(li); }
  });

  // Drop photos anywhere on the photo box
  var dragDepth = 0;
  box.addEventListener('dragenter', function (e) { if (hasFiles(e)) { e.preventDefault(); dragDepth++; box.classList.add('is-dragover'); } });
  box.addEventListener('dragover', function (e) { if (hasFiles(e)) { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; } });
  box.addEventListener('dragleave', function () { dragDepth = Math.max(0, dragDepth - 1); if (!dragDepth) { box.classList.remove('is-dragover'); } });
  box.addEventListener('drop', function (e) {
    if (!hasFiles(e)) { return; }
    e.preventDefault(); e.stopPropagation();
    dragDepth = 0; box.classList.remove('is-dragover');
    addFiles(e.dataTransfer.files);
  });
  function hasFiles(e) { return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1; }

  // Do not send the form while photos are still uploading
  var form = box.closest('form');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (pendingCount() > 0) {
        e.preventDefault();
        e.stopImmediatePropagation();
        say(t.wait, true);
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      Array.prototype.forEach.call(list.querySelectorAll('li.is-failed'), function (li) { li.remove(); });
    }, true);
  }
})();
