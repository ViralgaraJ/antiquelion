<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Installable web app (PWA) and browser/phone push notifications.
 *
 * - Web app manifest and service worker are served by PHP (ajax hooks al_manifest,
 *   al_sw). The worker's URL is /index.php?..., so its scope can be the whole site.
 *   It caches theme assets and photos, and shows an offline page (route al-offline)
 *   when there is no connection. Account pages, admin and AJAX are never cached.
 *
 * - Push uses the standard Web Push protocol with VAPID (keys generated on first use,
 *   kept in preferences). Pushes carry no payload, so no payload encryption is needed:
 *   the worker wakes up, asks al_push_inbox (identified by its subscription endpoint)
 *   for the waiting notifications and shows them.
 *
 * - Every email sent through antique_mail() to a member (messages, offers, outbid,
 *   auction results, payments...) is also queued as a notification for that member's
 *   subscribed devices, so email opt-outs apply to push too.
 */

define('ANTIQUE_PUSH_SCHEMA', '1');

function antique_push_subs_table() { return DB_TABLE_PREFIX . 't_al_push_subs'; }
function antique_push_queue_table() { return DB_TABLE_PREFIX . 't_al_push_queue'; }
function antique_pwa_db() { return User::newInstance()->dao; }

function antique_push_schema() {
  if (osc_get_preference('push_schema', 'starter_theme') == ANTIQUE_PUSH_SCHEMA) { return; }
  $db = antique_pwa_db();
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_push_subs_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_hash` char(40) NOT NULL,
    `s_endpoint` text NOT NULL,
    `s_agent` varchar(120) DEFAULT NULL,
    `dt_created` datetime NOT NULL,
    `dt_last_ok` datetime DEFAULT NULL,
    PRIMARY KEY (`pk_i_id`),
    UNIQUE KEY `uq_hash` (`s_hash`),
    KEY `idx_user` (`fk_i_user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  $ok = $ok && $db->query("CREATE TABLE IF NOT EXISTS `" . antique_push_queue_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_title` varchar(150) NOT NULL,
    `s_body` varchar(300) NOT NULL DEFAULT '',
    `s_url` varchar(500) NOT NULL DEFAULT '',
    `dt_created` datetime NOT NULL,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_user` (`fk_i_user_id`, `pk_i_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('push_schema', ANTIQUE_PUSH_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_push_schema', 1);

/* ---------- Page wiring ---------- */

function antique_pwa_version() {
  $f = __DIR__ . '/js/al-pwa.js';
  return substr(md5(__FILE__ . filemtime(__FILE__) . (is_file($f) ? filemtime($f) : 0)), 0, 8);
}

function antique_pwa_manifest_url() { return osc_ajax_hook_url('al_manifest'); }
function antique_pwa_sw_url() { return osc_base_url() . 'index.php?page=ajax&action=runhook&hook=al_sw'; }

osc_add_route('al-offline', 'offline/?(?:\?.*)?$', 'offline/', 'al-offline.php', false, 'custom', 'al-offline', __('You are offline', 'starter'));

function antique_pwa_assets() {
  osc_enqueue_style('al-extras', osc_current_web_theme_url('css/al-extras.css') . '?v=' . filemtime(__DIR__ . '/css/al-extras.css'));
}
osc_add_hook('init', 'antique_pwa_assets');

function antique_pwa_head() {
  $cfg = array(
    'sw' => antique_pwa_sw_url(),
    'scope' => (string)parse_url(osc_base_url(), PHP_URL_PATH),
    'push' => antique_push_ready() && osc_is_web_user_logged_in(),
    'key' => antique_push_ready() ? antique_vapid_public() : '',
    'subscribe' => osc_ajax_hook_url('al_push_sub'),
    'token' => osc_is_web_user_logged_in() ? osc_csrfguard_generate_token() : '',
    'text' => array(
      'install' => __('Install the AntiqueLion app', 'starter'),
      'installSub' => __('Faster access, and alerts for messages and bids.', 'starter'),
      'installBtn' => __('Install', 'starter'),
      'iosSub' => __('Tap Share, then "Add to Home Screen".', 'starter'),
      'close' => __('Not now', 'starter'),
      'on' => __('Notifications are on for this device.', 'starter'),
      'off' => __('Notifications are off for this device.', 'starter'),
      'blocked' => __('Notifications are blocked in your browser settings for this site.', 'starter'),
      'unsupported' => __('This browser does not support notifications. On iPhone, install the app to your Home Screen first.', 'starter'),
      'turnOn' => __('Turn on notifications', 'starter'),
      'turnOff' => __('Turn off', 'starter'),
      'error' => __('Something went wrong. Please try again.', 'starter')
    )
  );
  echo '<script>window.AL_PWA=' . json_encode($cfg) . ';</script>' . PHP_EOL;
  echo '<script src="' . osc_esc_html(osc_current_web_theme_url('js/al-pwa.js') . '?v=' . antique_pwa_version()) . '" defer></script>' . PHP_EOL;
}
osc_add_hook('header', 'antique_pwa_head');

/* ---------- Manifest and service worker ---------- */

function antique_pwa_manifest() {
  if (ob_get_length()) { ob_clean(); }
  header('Content-Type: application/manifest+json; charset=utf-8');
  header('Cache-Control: public, max-age=86400');
  $icons = osc_current_web_theme_url('images/favicons/');
  echo json_encode(array(
    'id' => '/',
    'name' => osc_page_title(),
    'short_name' => 'AntiqueLion',
    'description' => __('Buy, sell and bid on antiques and collectibles.', 'starter'),
    'start_url' => osc_base_url() . '?source=pwa',
    'scope' => osc_base_url(),
    'display' => 'standalone',
    'background_color' => '#F7F4EE',
    'theme_color' => '#14213D',
    'categories' => array('shopping', 'lifestyle'),
    'icons' => array(
      array('src' => $icons . 'android-chrome-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
      array('src' => $icons . 'android-chrome-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any')
    ),
    'shortcuts' => array(
      array('name' => __('Sell an item', 'starter'), 'url' => osc_item_post_url()),
      array('name' => __('Messages', 'starter'), 'url' => antique_inbox_url()),
      array('name' => __('Watchlist', 'starter'), 'url' => antique_watchlist_url()),
      array('name' => __('Live auctions', 'starter'), 'url' => osc_search_url(array('page' => 'search', 'sAuction' => 1)))
    )
  ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}
osc_add_hook('ajax_al_manifest', 'antique_pwa_manifest');

function antique_pwa_sw() {
  if (ob_get_length()) { ob_clean(); }
  header('Content-Type: application/javascript; charset=utf-8');
  header('Cache-Control: no-cache');
  header('Service-Worker-Allowed: ' . (string)parse_url(osc_base_url(), PHP_URL_PATH));
  $cfg = array(
    'v' => 'al-' . antique_pwa_version(),
    'offline' => osc_route_url('al-offline'),
    'inbox' => osc_base_url() . 'index.php?page=ajax&action=runhook&hook=al_push_inbox',
    'home' => osc_base_url(),
    'icon' => osc_current_web_theme_url('images/favicons/android-chrome-192x192.png'),
    'badge' => osc_current_web_theme_url('images/favicons/favicon-32x32.png'),
    'title' => osc_page_title(),
    'generic' => __('You have new activity on AntiqueLion.', 'starter')
  );
  echo 'var AL=' . json_encode($cfg, JSON_UNESCAPED_SLASHES) . ";\n";
  readfile(__DIR__ . '/js/al-sw.js');
  exit;
}
osc_add_hook('ajax_al_sw', 'antique_pwa_sw');

/* ---------- VAPID (Web Push sender identity) ---------- */

function antique_b64url($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }

function antique_vapid_keys() {
  static $keys = null;
  if ($keys !== null) { return $keys; }
  $pem = (string)osc_get_preference('push_vapid_private', 'starter_theme');
  $pub = (string)osc_get_preference('push_vapid_public', 'starter_theme');
  if ($pem === '' || $pub === '') {
    if (!function_exists('openssl_pkey_new')) { return $keys = array(); }
    $res = @openssl_pkey_new(array('curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC));
    if (!$res || !openssl_pkey_export($res, $pem)) { return $keys = array(); }
    $d = openssl_pkey_get_details($res);
    if (empty($d['ec']['x']) || empty($d['ec']['y'])) { return $keys = array(); }
    $pub = antique_b64url("\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT));
    osc_set_preference('push_vapid_private', $pem, 'starter_theme');
    osc_set_preference('push_vapid_public', $pub, 'starter_theme');
  }
  return $keys = array('private' => $pem, 'public' => $pub);
}

function antique_vapid_public() { $k = antique_vapid_keys(); return isset($k['public']) ? $k['public'] : ''; }

function antique_push_ready() {
  return osc_get_preference('push_enabled', 'starter_theme') !== '0' && function_exists('curl_init') && antique_vapid_public() !== '';
}

// ES256 JWT for the push service at $endpoint
function antique_vapid_jwt($endpoint) {
  $k = antique_vapid_keys();
  $u = parse_url($endpoint);
  $aud = $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
  $contact = osc_contact_email() ?: 'admin@' . parse_url(osc_base_url(), PHP_URL_HOST);
  $input = antique_b64url(json_encode(array('typ' => 'JWT', 'alg' => 'ES256'))) . '.' . antique_b64url(json_encode(array('aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => 'mailto:' . $contact)));
  if (!openssl_sign($input, $der, $k['private'], OPENSSL_ALGO_SHA256)) { return ''; }
  return $input . '.' . antique_b64url(antique_der_to_raw($der));
}

// DER ECDSA signature -> 64-byte r||s (JWS format)
function antique_der_to_raw($der) {
  $pos = 2;
  if (ord($der[1]) & 0x80) { $pos += ord($der[1]) & 0x7f; }
  $parts = array();
  for ($i = 0; $i < 2; $i++) {
    $len = ord($der[$pos + 1]);
    $int = substr($der, $pos + 2, $len);
    $parts[] = str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
    $pos += 2 + $len;
  }
  return $parts[0] . $parts[1];
}

/* ---------- Subscriptions ---------- */

function antique_push_json($data, $code = 200) {
  if (ob_get_length()) { ob_clean(); }
  http_response_code($code);
  header('Content-Type: application/json');
  header('Cache-Control: no-store');
  echo json_encode($data);
  exit;
}

// POST hook=al_push_sub: op=subscribe|unsubscribe, endpoint, octoken
function antique_push_subscribe() {
  if (!osc_is_web_user_logged_in()) { antique_push_json(array('success' => false, 'login' => true), 401); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) { antique_push_json(array('success' => false, 'error' => 'token'), 403); }
  $endpoint = trim((string)Params::getParam('endpoint', false, false));
  if (!preg_match('#^https://[A-Za-z0-9.\-]+(:\d+)?/\S{10,1500}$#', $endpoint)) { antique_push_json(array('success' => false, 'error' => 'endpoint'), 400); }
  $db = antique_pwa_db();
  $hash = sha1($endpoint);
  if (Params::getParam('op') === 'unsubscribe') {
    $db->query(sprintf("DELETE FROM `%s` WHERE s_hash = %s AND fk_i_user_id = %d", antique_push_subs_table(), $db->escape($hash), (int)osc_logged_user_id()));
  } else {
    $db->query(sprintf("INSERT INTO `%s` (fk_i_user_id, s_hash, s_endpoint, s_agent, dt_created) VALUES (%d, %s, %s, %s, %s)
      ON DUPLICATE KEY UPDATE fk_i_user_id = VALUES(fk_i_user_id), s_agent = VALUES(s_agent)",
      antique_push_subs_table(), (int)osc_logged_user_id(), $db->escape($hash), $db->escape($endpoint),
      $db->escape(mb_substr(strip_tags((string)(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')), 0, 120)), $db->escape(date('Y-m-d H:i:s'))));
  }
  antique_push_json(array('success' => true));
}
osc_add_hook('ajax_al_push_sub', 'antique_push_subscribe');

/**
 * POST hook=al_push_inbox (from the service worker): notifications for the member who
 * owns this subscription, newer than the last one this device showed (after=ID).
 * Each device keeps its own position, so a member's phone and laptop both get them.
 */
function antique_push_inbox() {
  $endpoint = trim((string)Params::getParam('endpoint', false, false));
  $after = (int)Params::getParam('after');
  $db = antique_pwa_db();
  $res = $endpoint !== '' ? $db->query(sprintf("SELECT fk_i_user_id FROM `%s` WHERE s_hash = %s", antique_push_subs_table(), $db->escape(sha1($endpoint)))) : false;
  if (!$res || $res->numRows() < 1) { antique_push_json(array('items' => array())); }
  $uid = (int)$res->row()['fk_i_user_id'];
  $res = $db->query("SELECT pk_i_id, s_title, s_body, s_url FROM `" . antique_push_queue_table() . "` WHERE fk_i_user_id = {$uid} AND pk_i_id > {$after}
    AND dt_created > DATE_SUB(NOW(), INTERVAL 2 DAY) ORDER BY pk_i_id DESC LIMIT " . ($after > 0 ? 5 : 1));
  $items = array();
  foreach ($res ? $res->result() : array() as $r) {
    $items[] = array('id' => (int)$r['pk_i_id'], 'title' => $r['s_title'], 'body' => $r['s_body'], 'url' => $r['s_url']);
  }
  antique_push_json(array('items' => array_reverse($items)));
}
osc_add_hook('ajax_al_push_inbox', 'antique_push_inbox');

function antique_push_count($uid) {
  $db = antique_pwa_db();
  $res = $db->query(sprintf("SELECT COUNT(*) AS n FROM `%s` WHERE fk_i_user_id = %d", antique_push_subs_table(), (int)$uid));
  return $res ? (int)$res->row()['n'] : 0;
}

/* ---------- Sending ---------- */

/**
 * Queue a notification for a member and wake their subscribed devices.
 */
function antique_push_notify($uid, $title, $body = '', $url = '') {
  $uid = (int)$uid;
  if ($uid < 1 || !antique_push_ready()) { return 0; }
  $db = antique_pwa_db();
  $subs = $db->query(sprintf("SELECT pk_i_id, s_endpoint FROM `%s` WHERE fk_i_user_id = %d", antique_push_subs_table(), $uid));
  if (!$subs || $subs->numRows() < 1) { return 0; }
  $clean = function ($t, $max) { return mb_substr(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string)$t), ENT_QUOTES, 'UTF-8'))), 0, $max); };
  $url = ($url !== '' && strpos($url, osc_base_url()) === 0) ? $url : osc_base_url();
  $db->query(sprintf("INSERT INTO `%s` (fk_i_user_id, s_title, s_body, s_url, dt_created) VALUES (%d, %s, %s, %s, %s)",
    antique_push_queue_table(), $uid, $db->escape($clean($title, 150)), $db->escape($clean($body, 300)), $db->escape(mb_substr($url, 0, 500)), $db->escape(date('Y-m-d H:i:s'))));
  $sent = 0;
  foreach ($subs->result() as $s) {
    $code = antique_push_send($s['s_endpoint']);
    if ($code >= 200 && $code < 300) {
      $sent++;
      $db->query(sprintf("UPDATE `%s` SET dt_last_ok = %s WHERE pk_i_id = %d", antique_push_subs_table(), $db->escape(date('Y-m-d H:i:s')), (int)$s['pk_i_id']));
    } elseif ($code == 404 || $code == 410) {
      // the browser dropped this subscription
      $db->query(sprintf("DELETE FROM `%s` WHERE pk_i_id = %d", antique_push_subs_table(), (int)$s['pk_i_id']));
    }
  }
  return $sent;
}

// Empty push (no payload) to one endpoint; returns the HTTP status
function antique_push_send($endpoint) {
  $jwt = antique_vapid_jwt($endpoint);
  if ($jwt === '') { return 0; }
  $ch = curl_init($endpoint);
  curl_setopt_array($ch, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HTTPHEADER => array('TTL: 86400', 'Urgency: normal', 'Content-Length: 0', 'Authorization: vapid t=' . $jwt . ', k=' . antique_vapid_public())
  ));
  curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $code;
}

/**
 * Called by antique_mail() after the topic opt-out check: the same news, as a push.
 */
function antique_push_from_mail($to, $heading, $paragraphs, $url) {
  if (!antique_push_ready()) { return; }
  $user = User::newInstance()->findByEmail(trim((string)$to));
  if (!$user) { return; }
  $first = '';
  foreach ((array)$paragraphs as $p) { if (trim(strip_tags((string)$p)) !== '') { $first = $p; break; } }
  antique_push_notify($user['pk_i_id'], $heading, $first, (string)$url);
}

function antique_push_cleanup() {
  if (!antique_job_due('push_cleanup', 86400)) { return; }
  antique_pwa_db()->query("DELETE FROM `" . antique_push_queue_table() . "` WHERE dt_created < DATE_SUB(NOW(), INTERVAL 7 DAY)");
}
osc_add_hook('cron', 'antique_push_cleanup');

function antique_push_delete_user($uid) {
  $db = antique_pwa_db();
  $db->query("DELETE FROM `" . antique_push_subs_table() . "` WHERE fk_i_user_id = " . (int)$uid);
  $db->query("DELETE FROM `" . antique_push_queue_table() . "` WHERE fk_i_user_id = " . (int)$uid);
}
osc_add_hook('delete_user', 'antique_push_delete_user');

/**
 * "Notifications on this device" control (Account > Email settings, Messages).
 */
function antique_push_control($compact = false) {
  if (!antique_push_ready() || !osc_is_web_user_logged_in()) { return ''; }
  return '<div class="al-push-control' . ($compact ? ' is-compact' : '') . '" data-al-push hidden>'
    . '<div><strong>' . osc_esc_html(__('Notifications on this device', 'starter')) . '</strong>'
    . '<p data-al-push-status>' . osc_esc_html(__('Get an alert on this phone or computer for new messages, offers and bids, even when the site is closed.', 'starter')) . '</p></div>'
    . '<button type="button" class="al-btn al-btn-ink" data-al-push-toggle>' . osc_esc_html(__('Turn on notifications', 'starter')) . '</button></div>';
}
