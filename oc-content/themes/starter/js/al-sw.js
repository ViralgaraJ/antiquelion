/* AntiqueLion service worker. Served by inc.pwa.php, which defines AL above this line. */
'use strict';

var STATIC = AL.v + '-static';
var PHOTOS = AL.v + '-photos';
var PHOTO_LIMIT = 150;

self.addEventListener('install', function (event) {
  event.waitUntil(caches.open(STATIC).then(function (c) { return c.add(new Request(AL.offline, { cache: 'reload' })); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (event) {
  event.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k.indexOf(AL.v) !== 0 && k.indexOf('al-') === 0; }).map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

function isStatic(url) {
  return /\/oc-content\/themes\/[^/]+\/(css|js|fonts|images)\//.test(url.pathname) || /\/oc-includes\/osclass\/assets\//.test(url.pathname) || url.hostname === 'fonts.gstatic.com';
}
function isPhoto(url) { return /\/oc-content\/uploads\/.+\.(jpe?g|png|webp|gif)$/i.test(url.pathname) && !/al-private/.test(url.pathname); }
function isPrivate(url) {
  return /\/oc-admin\//.test(url.pathname) || /[?&]page=(ajax|login|register|user)\b/.test(url.search) || /\/(account|user|oauth|unsubscribe)\//.test(url.pathname);
}

function trim(cacheName, max) {
  caches.open(cacheName).then(function (c) {
    c.keys().then(function (keys) { if (keys.length > max) { c.delete(keys[0]).then(function () { trim(cacheName, max); }); } });
  });
}

// cached copy now, fresh copy in the background
function staleWhileRevalidate(request, cacheName) {
  return caches.open(cacheName).then(function (cache) {
    return cache.match(request).then(function (hit) {
      var fresh = fetch(request).then(function (res) {
        if (res && (res.ok || res.type === 'opaque')) { cache.put(request, res.clone()); if (cacheName === PHOTOS) { trim(PHOTOS, PHOTO_LIMIT); } }
        return res;
      }).catch(function () { return hit; });
      return hit || fresh;
    });
  });
}

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET') { return; }
  var url = new URL(req.url);
  if (req.mode === 'navigate') {
    if (url.origin !== self.location.origin || isPrivate(url)) { return; }
    event.respondWith(fetch(req).catch(function () { return caches.match(AL.offline); }));
    return;
  }
  if (url.origin === self.location.origin && isPrivate(url)) { return; }
  if (isStatic(url)) { event.respondWith(staleWhileRevalidate(req, STATIC)); return; }
  if (url.origin === self.location.origin && isPhoto(url)) { event.respondWith(staleWhileRevalidate(req, PHOTOS)); }
});

/* ---------- Push ---------- */

var POS_KEY = '/__al_push_position';

function readPosition() {
  return caches.open('al-push').then(function (c) { return c.match(POS_KEY); }).then(function (r) { return r ? r.text() : '0'; }).then(function (t) { return parseInt(t, 10) || 0; });
}
function savePosition(id) {
  return caches.open('al-push').then(function (c) { return c.put(POS_KEY, new Response(String(id))); });
}

self.addEventListener('push', function (event) {
  event.waitUntil(self.registration.pushManager.getSubscription().then(function (sub) {
    if (!sub) { return []; }
    return readPosition().then(function (after) {
      var body = new URLSearchParams({ endpoint: sub.endpoint, after: String(after) });
      return fetch(AL.inbox, { method: 'POST', body: body, credentials: 'omit' }).then(function (r) { return r.json(); }).then(function (d) { return d.items || []; });
    });
  }).catch(function () { return []; }).then(function (items) {
    if (!items.length) {
      // a push must always show something
      return self.registration.showNotification(AL.title, { body: AL.generic, icon: AL.icon, badge: AL.badge, data: { url: AL.home }, tag: 'al-generic' });
    }
    var last = items[items.length - 1].id;
    return Promise.all(items.map(function (n) {
      return self.registration.showNotification(n.title, { body: n.body, icon: AL.icon, badge: AL.badge, data: { url: n.url || AL.home }, tag: 'al-' + n.id });
    })).then(function () { return savePosition(last); });
  }));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var target = (event.notification.data && event.notification.data.url) || AL.home;
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
    for (var i = 0; i < list.length; i++) {
      if (list[i].url === target && 'focus' in list[i]) { return list[i].focus(); }
    }
    return self.clients.openWindow(target);
  }));
});
