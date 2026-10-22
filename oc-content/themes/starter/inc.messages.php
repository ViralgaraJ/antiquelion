<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * On-site messaging between buyers and sellers (Account > Messages).
 * One conversation per buyer, seller and listing (listing 0 = a message from the
 * seller's shop page). The other person gets an email when a conversation they had
 * read gets a new message, so a burst of messages sends one email, not ten.
 * Offers (inc.offers.php) live inside these conversations.
 */

define('ANTIQUE_MSG_SCHEMA', '1');
define('ANTIQUE_MSG_MAX', 2000);            // characters per message
define('ANTIQUE_MSG_RATE', 40);             // messages per person per 10 minutes
define('ANTIQUE_MSG_NEW_RATE', 15);         // new conversations per person per hour

function antique_msg_threads_table() { return DB_TABLE_PREFIX . 't_al_threads'; }
function antique_msg_table() { return DB_TABLE_PREFIX . 't_al_messages'; }
function antique_msg_db() { return Item::newInstance()->dao; }

function antique_msg_schema() {
  if (osc_get_preference('msg_schema', 'starter_theme') == ANTIQUE_MSG_SCHEMA) { return; }
  $db = antique_msg_db();
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_msg_threads_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_item_id` int(10) unsigned NOT NULL DEFAULT 0,
    `fk_i_buyer_id` int(10) unsigned NOT NULL,
    `fk_i_seller_id` int(10) unsigned NOT NULL,
    `dt_created` datetime NOT NULL,
    `dt_last` datetime NOT NULL,
    `fk_i_last_sender` int(10) unsigned NOT NULL DEFAULT 0,
    `s_last_preview` varchar(200) NOT NULL DEFAULT '',
    `i_buyer_unread` smallint(5) unsigned NOT NULL DEFAULT 0,
    `i_seller_unread` smallint(5) unsigned NOT NULL DEFAULT 0,
    `dt_buyer_read` datetime DEFAULT NULL,
    `dt_seller_read` datetime DEFAULT NULL,
    `b_buyer_archived` tinyint(1) NOT NULL DEFAULT 0,
    `b_seller_archived` tinyint(1) NOT NULL DEFAULT 0,
    `b_reported` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_i_id`),
    UNIQUE KEY `uq_thread` (`fk_i_item_id`, `fk_i_buyer_id`, `fk_i_seller_id`),
    KEY `idx_buyer` (`fk_i_buyer_id`, `dt_last`),
    KEY `idx_seller` (`fk_i_seller_id`, `dt_last`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")
  && $db->query("CREATE TABLE IF NOT EXISTS `" . antique_msg_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_thread_id` int(10) unsigned NOT NULL,
    `fk_i_sender_id` int(10) unsigned NOT NULL DEFAULT 0,
    `s_kind` varchar(12) NOT NULL DEFAULT 'text',
    `fk_i_offer_id` int(10) unsigned DEFAULT NULL,
    `s_body` text NOT NULL,
    `dt_sent` datetime NOT NULL,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_thread` (`fk_i_thread_id`, `pk_i_id`),
    KEY `idx_sender` (`fk_i_sender_id`, `dt_sent`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if ($ok) { osc_set_preference('msg_schema', ANTIQUE_MSG_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_msg_schema', 1);

function antique_msg_open_read() {
  if (Params::getParam('page') != 'custom' || Params::getParam('route') != 'al-thread' || !osc_is_web_user_logged_in()) { return; }
  $thread = antique_msg_thread((int)Params::getParam('thread'));
  $role = antique_msg_role($thread, osc_logged_user_id());
  if ($role !== null) { antique_msg_mark_read($thread, $role); }
}
osc_add_hook('init', 'antique_msg_open_read', 6);

// Account > Messages and one conversation (signed-in only)
osc_add_route('al-inbox', 'account/messages/?(?:\?.*)?$', 'account/messages/', 'al-inbox.php', true, 'custom', 'al-inbox', __('Messages', 'starter'));
osc_add_route('al-thread', 'account/messages/([0-9]+)/?(?:\?.*)?$', 'account/messages/{thread}/', 'al-thread.php', true, 'custom', 'al-thread', __('Conversation', 'starter'));

/**
 * Route URL with query parameters. osc_route_url() builds "account/x/?&a=b" when
 * friendly URLs are on, which does not match the route (404).
 */
function antique_route_url($id, $query = array()) {
  $url = osc_route_url($id);
  return $query ? $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($query) : $url;
}

function antique_inbox_url($filter = '') {
  return antique_route_url('al-inbox', $filter !== '' ? array('f' => $filter) : array());
}

function antique_thread_url($id) {
  return osc_route_url('al-thread', array('thread' => (int)$id));
}

function antique_msg_now() { return date('Y-m-d H:i:s'); }

/**
 * Name, avatar and shop link of a member (cached per request).
 */
function antique_msg_user($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if (!isset($cache[$uid])) {
    $u = $uid > 0 ? User::newInstance()->findByPrimaryKey($uid) : null;
    $cache[$uid] = $u ? array(
      'id' => $uid,
      'name' => antique_seller_display_name($uid, $u['s_name']),
      'email' => $u['s_email'],
      'avatar' => starter_profile_picture($uid, 'small', true),
      'url' => osc_user_public_profile_url($uid)
    ) : array('id' => 0, 'name' => __('Former member', 'starter'), 'email' => '', 'avatar' => osc_current_web_theme_url('images/profile-256.png'), 'url' => '');
  }
  return $cache[$uid];
}

function antique_msg_thread($id) {
  $res = antique_msg_db()->query("SELECT * FROM `" . antique_msg_threads_table() . "` WHERE pk_i_id = " . (int)$id);
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

// 'buyer', 'seller' or null when the person is not part of the conversation
function antique_msg_role($thread, $uid) {
  if (!$thread || (int)$uid <= 0) { return null; }
  if ((int)$thread['fk_i_buyer_id'] === (int)$uid) { return 'buyer'; }
  if ((int)$thread['fk_i_seller_id'] === (int)$uid) { return 'seller'; }
  return null;
}

function antique_msg_other_id($thread, $uid) {
  return antique_msg_role($thread, $uid) == 'buyer' ? (int)$thread['fk_i_seller_id'] : (int)$thread['fk_i_buyer_id'];
}

function antique_msg_find_thread($itemId, $buyerId, $sellerId) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_item_id = %d AND fk_i_buyer_id = %d AND fk_i_seller_id = %d",
    antique_msg_threads_table(), (int)$itemId, (int)$buyerId, (int)$sellerId));
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_msg_open_thread($itemId, $buyerId, $sellerId) {
  $t = antique_msg_find_thread($itemId, $buyerId, $sellerId);
  if ($t) { return $t; }
  $now = antique_msg_now();
  antique_msg_db()->query(sprintf("INSERT IGNORE INTO `%s` (fk_i_item_id, fk_i_buyer_id, fk_i_seller_id, dt_created, dt_last) VALUES (%d, %d, %d, '%s', '%s')",
    antique_msg_threads_table(), (int)$itemId, (int)$buyerId, (int)$sellerId, $now, $now));
  return antique_msg_find_thread($itemId, $buyerId, $sellerId);
}

/**
 * Too many messages in a short time (spam protection). Returns an error or ''.
 */
function antique_msg_rate_error($uid, $newThread = false) {
  $db = antique_msg_db();
  $res = $db->query(sprintf("SELECT COUNT(*) AS n FROM `%s` WHERE fk_i_sender_id = %d AND dt_sent > '%s'", antique_msg_table(), (int)$uid, date('Y-m-d H:i:s', time() - 600)));
  if ($res && (int)$res->row()['n'] >= ANTIQUE_MSG_RATE) {
    return __('You are sending messages very quickly. Please wait a few minutes and try again.', 'starter');
  }
  if ($newThread) {
    $res = $db->query(sprintf("SELECT COUNT(*) AS n FROM `%s` WHERE fk_i_buyer_id = %d AND dt_created > '%s'", antique_msg_threads_table(), (int)$uid, date('Y-m-d H:i:s', time() - 3600)));
    if ($res && (int)$res->row()['n'] >= ANTIQUE_MSG_NEW_RATE) {
      return __('You have started a lot of conversations in the last hour. Please try again later.', 'starter');
    }
  }
  return '';
}

function antique_msg_clean($body) {
  $body = str_replace(array("\r\n", "\r"), "\n", (string)$body);
  $body = trim(strip_tags($body));
  $body = preg_replace("/\n{3,}/", "\n\n", $body);
  return mb_substr($body, 0, ANTIQUE_MSG_MAX);
}

/**
 * Adds a message and updates the conversation. $kind: text, offer (an offer card,
 * $offerId set) or event (a short note such as "Offer accepted"). $senderId 0 = the site.
 * Emails the other person when this is the first message they have not read.
 */
function antique_msg_add($threadId, $senderId, $body, $kind = 'text', $offerId = null, $notify = true) {
  $db = antique_msg_db();
  $thread = antique_msg_thread($threadId);
  if (!$thread) { return 0; }
  $now = antique_msg_now();
  $db->query(sprintf("INSERT INTO `%s` (fk_i_thread_id, fk_i_sender_id, s_kind, fk_i_offer_id, s_body, dt_sent) VALUES (%d, %d, %s, %s, %s, '%s')",
    antique_msg_table(), (int)$threadId, (int)$senderId, $db->escape($kind), $offerId ? (int)$offerId : 'NULL', $db->escape($body), $now));
  $msgId = (int)$db->insertedId();

  $role = antique_msg_role($thread, $senderId);
  $preview = mb_substr(preg_replace('/\s+/', ' ', $body), 0, 190);
  $set = "dt_last = '{$now}', fk_i_last_sender = " . (int)$senderId . ", s_last_preview = " . $db->escape($preview) . ", b_buyer_archived = 0, b_seller_archived = 0";
  if ($role == 'buyer') {
    $set .= ", i_seller_unread = i_seller_unread + 1, i_buyer_unread = 0, dt_buyer_read = '{$now}'";
  } elseif ($role == 'seller') {
    $set .= ", i_buyer_unread = i_buyer_unread + 1, i_seller_unread = 0, dt_seller_read = '{$now}'";
  } else {
    $set .= ", i_buyer_unread = i_buyer_unread + 1, i_seller_unread = i_seller_unread + 1";
  }
  $db->query("UPDATE `" . antique_msg_threads_table() . "` SET {$set} WHERE pk_i_id = " . (int)$threadId);

  if ($notify && $role !== null && $kind == 'text') {
    $otherUnread = $role == 'buyer' ? (int)$thread['i_seller_unread'] : (int)$thread['i_buyer_unread'];
    if ($otherUnread == 0) {
      antique_msg_email($thread, $senderId, $body);
    }
  }
  return $msgId;
}

function antique_msg_item_title($itemId) {
  if ((int)$itemId <= 0) { return ''; }
  $item = Item::newInstance()->findByPrimaryKey((int)$itemId);
  return $item ? $item['s_title'] : '';
}

function antique_msg_email($thread, $senderId, $body) {
  $to = antique_msg_user(antique_msg_other_id($thread, $senderId));
  $from = antique_msg_user($senderId);
  if ($to['email'] == '') { return; }
  $title = antique_msg_item_title($thread['fk_i_item_id']);
  $subject = $title !== '' ? sprintf(__('%1$s sent you a message about %2$s', 'starter'), $from['name'], $title) : sprintf(__('%s sent you a message', 'starter'), $from['name']);
  antique_mail($to['email'], $to['name'], $subject, __('You have a new message', 'starter'), array(
    $title !== ''
      ? sprintf(__('%1$s wrote about %2$s:', 'starter'), '<strong>' . osc_esc_html($from['name']) . '</strong>', '<strong>' . osc_esc_html($title) . '</strong>')
      : sprintf(__('%s wrote:', 'starter'), '<strong>' . osc_esc_html($from['name']) . '</strong>'),
    antique_mail_quote($body),
    __('Reply on the site so you both keep a record of what was agreed. Never send money or bank details to someone you have not checked.', 'starter')
  ), antique_thread_url($thread['pk_i_id']), __('Read and reply', 'starter'), 'messages');
}

function antique_msg_mark_read($thread, $role) {
  if (!$thread || !in_array($role, array('buyer', 'seller'), true)) { return; }
  antique_msg_db()->query(sprintf("UPDATE `%s` SET i_%s_unread = 0, dt_%s_read = '%s' WHERE pk_i_id = %d",
    antique_msg_threads_table(), $role, $role, antique_msg_now(), (int)$thread['pk_i_id']));
}

/**
 * Unread messages across all conversations of the signed-in person.
 */
function antique_msg_unread_total($refresh = false) {
  static $n = null;
  if ($n !== null && !$refresh) { return $n; }
  $n = 0;
  if (!osc_is_web_user_logged_in()) { return $n; }
  $uid = (int)osc_logged_user_id();
  $res = antique_msg_db()->query(sprintf("SELECT
      COALESCE(SUM(CASE WHEN fk_i_buyer_id = %d THEN i_buyer_unread ELSE 0 END), 0) + COALESCE(SUM(CASE WHEN fk_i_seller_id = %d THEN i_seller_unread ELSE 0 END), 0) AS n
    FROM `%s` WHERE fk_i_buyer_id = %d OR fk_i_seller_id = %d", $uid, $uid, antique_msg_threads_table(), $uid, $uid));
  if ($res) { $n = (int)$res->row()['n']; }
  return $n;
}

/**
 * Conversations for the inbox. $filter: '' (all), buying, selling, unread, archived.
 */
function antique_msg_threads($uid, $filter = '') {
  $uid = (int)$uid;
  $notArchived = "((t.fk_i_buyer_id = {$uid} AND t.b_buyer_archived = 0) OR (t.fk_i_seller_id = {$uid} AND t.b_seller_archived = 0))";
  switch ($filter) {
    case 'buying':   $where = "t.fk_i_buyer_id = {$uid} AND t.b_buyer_archived = 0"; break;
    case 'selling':  $where = "t.fk_i_seller_id = {$uid} AND t.b_seller_archived = 0"; break;
    case 'unread':   $where = "((t.fk_i_buyer_id = {$uid} AND t.i_buyer_unread > 0) OR (t.fk_i_seller_id = {$uid} AND t.i_seller_unread > 0))"; break;
    case 'archived': $where = "((t.fk_i_buyer_id = {$uid} AND t.b_buyer_archived = 1) OR (t.fk_i_seller_id = {$uid} AND t.b_seller_archived = 1))"; break;
    default:         $where = $notArchived;
  }
  // a conversation shows once its first message exists
  $res = antique_msg_db()->query("SELECT t.* FROM `" . antique_msg_threads_table() . "` t WHERE {$where} AND t.fk_i_last_sender IS NOT NULL AND t.s_last_preview <> '' ORDER BY t.dt_last DESC LIMIT 200");
  return $res ? $res->result() : array();
}

function antique_msg_messages($threadId, $afterId = 0) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM (SELECT * FROM `%s` WHERE fk_i_thread_id = %d AND pk_i_id > %d ORDER BY pk_i_id DESC LIMIT 400) x ORDER BY pk_i_id ASC",
    antique_msg_table(), (int)$threadId, (int)$afterId));
  return $res ? $res->result() : array();
}

/**
 * Listing summary for a conversation header / inbox row.
 */
function antique_msg_item($itemId) {
  static $cache = array();
  $itemId = (int)$itemId;
  if ($itemId <= 0) { return null; }
  if (!array_key_exists($itemId, $cache)) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) { return $cache[$itemId] = null; }
    $res = ItemResource::newInstance()->getAllResourcesFromItem($itemId);
    $thumb = '';
    if (!empty($res[0])) {
      $r = $res[0];
      $thumb = osc_base_url() . $r['s_path'] . $r['pk_i_id'] . '_thumbnail.' . $r['s_extension'];
    }
    $extra = starter_item_extra($itemId);
    $cache[$itemId] = array(
      'id' => $itemId,
      'title' => $item['s_title'],
      'url' => osc_item_url_from_item($item),
      'thumb' => $thumb,
      'price' => antique_price_html($item['i_price'], $item['fk_c_currency_code']),
      'i_price' => $item['i_price'],
      'currency' => $item['fk_c_currency_code'],
      'sold' => (int)$extra['i_sold'],
      'user' => (int)$item['fk_i_user_id'],
      'live' => $item['b_enabled'] == 1 && $item['b_active'] == 1 && $item['b_spam'] == 0 && !osc_isExpired($item['dt_expiration'])
    );
  }
  return $cache[$itemId];
}

function antique_msg_day_label($date) {
  $d = date('Y-m-d', strtotime($date));
  if ($d == date('Y-m-d')) { return __('Today', 'starter'); }
  if ($d == date('Y-m-d', strtotime('-1 day'))) { return __('Yesterday', 'starter'); }
  return date(date('Y', strtotime($date)) == date('Y') ? 'j F' : 'j F Y', strtotime($date));
}

function antique_msg_short_time($date) {
  $t = strtotime($date);
  if (date('Y-m-d', $t) == date('Y-m-d')) { return date('H:i', $t); }
  if (time() - $t < 6 * 86400) { return date('D', $t); }
  return date(date('Y', $t) == date('Y') ? 'j M' : 'j M Y', $t);
}

/**
 * HTML for messages. $prevDate: date of the message before the first one (for day breaks).
 */
function antique_msg_render_list($messages, $thread, $uid, $prevDate = '') {
  $html = '';
  $lastDay = $prevDate !== '' ? date('Y-m-d', strtotime($prevDate)) : '';
  foreach ($messages as $m) {
    $day = date('Y-m-d', strtotime($m['dt_sent']));
    if ($day !== $lastDay) {
      $html .= '<div class="al-msg-day"><span>' . osc_esc_html(antique_msg_day_label($m['dt_sent'])) . '</span></div>';
      $lastDay = $day;
    }
    $html .= antique_msg_render($m, $thread, $uid);
  }
  return $html;
}

function antique_msg_render($m, $thread, $uid) {
  $id = (int)$m['pk_i_id'];
  if ($m['s_kind'] == 'offer' && function_exists('antique_offer_card')) {
    return '<div class="al-msg-row is-offer" data-id="' . $id . '">' . antique_offer_card((int)$m['fk_i_offer_id'], $thread, $uid) . '</div>';
  }
  if ($m['s_kind'] != 'text') {
    return '<div class="al-msg-event" data-id="' . $id . '"><span>' . osc_esc_html($m['s_body']) . ' · ' . date('H:i', strtotime($m['dt_sent'])) . '</span></div>';
  }
  $mine = (int)$m['fk_i_sender_id'] === (int)$uid;
  return '<div class="al-msg-row ' . ($mine ? 'is-mine' : 'is-theirs') . '" data-id="' . $id . '"' . ($mine ? ' data-mine' : '') . '>'
    . '<div class="al-msg-bubble">' . nl2br(osc_esc_html($m['s_body'])) . '</div>'
    . '<div class="al-msg-meta"><time datetime="' . osc_esc_html(date('c', strtotime($m['dt_sent']))) . '">' . date('H:i', strtotime($m['dt_sent'])) . '</time></div>'
    . '</div>';
}

// Has the other person read everything up to this message?
function antique_msg_seen($thread, $uid, $sentAt) {
  $otherRole = antique_msg_role($thread, $uid) == 'buyer' ? 'seller' : 'buyer';
  $read = $thread['dt_' . $otherRole . '_read'];
  return $read && strtotime($read) >= strtotime($sentAt);
}

// A return address from a form: on this site only, with HTML entities (&amp;) decoded
function antique_msg_back_url($url) {
  $url = html_entity_decode((string)$url, ENT_QUOTES, 'UTF-8');
  return ($url !== '' && strpos($url, osc_base_url()) === 0) ? $url : antique_inbox_url();
}

function antique_msg_json($ok, $data = array()) {
  if (ob_get_length()) { ob_clean(); }
  header('Content-Type: application/json');
  echo json_encode(array_merge(array('success' => $ok), $data));
  exit;
}

function antique_msg_is_ajax() {
  return Params::getParam('ajax') == '1';
}

// Answers a form post: JSON for scripts, otherwise a flash message and a redirect
function antique_msg_reply($ok, $message, $url, $data = array()) {
  if (antique_msg_is_ajax()) {
    antique_msg_json($ok, array_merge($data, $ok ? array() : array('error' => $message)));
  }
  if ($message !== '') {
    $ok ? osc_add_flash_ok_message($message) : osc_add_flash_error_message(osc_esc_html($message));
  }
  osc_redirect_to($url);
}

/**
 * POST hook=al_msg: op=start (item or seller + body), send (thread + body),
 * archive / unarchive / report (thread).
 */
function antique_msg_post() {
  $back = antique_msg_back_url(Params::getParam('back'));
  if (!osc_is_web_user_logged_in()) {
    if (antique_msg_is_ajax()) { antique_msg_json(false, array('login' => true, 'error' => __('Please sign in again.', 'starter'))); }
    Session::newInstance()->_set('al_after_login', $back);
    osc_redirect_to(osc_user_login_url());
  }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  $op = Params::getParam('op');

  if ($op == 'start') {
    $itemId = (int)Params::getParam('item');
    $sellerId = (int)Params::getParam('seller');
    if ($itemId > 0) {
      $item = Item::newInstance()->findByPrimaryKey($itemId);
      if (!$item || (int)$item['fk_i_user_id'] <= 0) {
        antique_msg_reply(false, __('This seller cannot receive messages on the site.', 'starter'), $back);
      }
      $sellerId = (int)$item['fk_i_user_id'];
    } else {
      $itemId = 0;
      if (!User::newInstance()->findByPrimaryKey($sellerId)) {
        antique_msg_reply(false, __('This member no longer exists.', 'starter'), $back);
      }
    }
    if ($sellerId == $uid) {
      antique_msg_reply(false, __('This is your own listing.', 'starter'), $back);
    }
    $body = antique_msg_clean(Params::getParam('body'));
    if (mb_strlen($body) < 2) {
      antique_msg_reply(false, __('Please write a message.', 'starter'), $back);
    }
    $existing = antique_msg_find_thread($itemId, $uid, $sellerId);
    $err = antique_msg_rate_error($uid, !$existing);
    if ($err !== '') { antique_msg_reply(false, $err, $back); }
    $thread = antique_msg_open_thread($itemId, $uid, $sellerId);
    if (!$thread) { antique_msg_reply(false, __('Your message could not be sent. Please try again.', 'starter'), $back); }
    antique_msg_add($thread['pk_i_id'], $uid, $body);
    antique_msg_reply(true, __('Message sent. You will get an email when the seller replies.', 'starter'), antique_thread_url($thread['pk_i_id']), array('url' => antique_thread_url($thread['pk_i_id'])));
  }

  $thread = antique_msg_thread((int)Params::getParam('thread'));
  $role = antique_msg_role($thread, $uid);
  if ($role === null) {
    antique_msg_reply(false, __('This conversation is not available.', 'starter'), antique_inbox_url());
  }
  $url = antique_thread_url($thread['pk_i_id']);

  if ($op == 'send') {
    $body = antique_msg_clean(Params::getParam('body'));
    if ($body === '') { antique_msg_reply(false, __('Please write a message.', 'starter'), $url); }
    if (antique_msg_other_id($thread, $uid) <= 0 || !User::newInstance()->findByPrimaryKey(antique_msg_other_id($thread, $uid))) {
      antique_msg_reply(false, __('This member has closed their account, so they cannot reply.', 'starter'), $url);
    }
    $err = antique_msg_rate_error($uid);
    if ($err !== '') { antique_msg_reply(false, $err, $url); }
    $after = (int)Params::getParam('after');
    antique_msg_add($thread['pk_i_id'], $uid, $body);
    if (antique_msg_is_ajax()) {
      $thread = antique_msg_thread($thread['pk_i_id']);
      antique_msg_json(true, antique_msg_poll_data($thread, $uid, $after));
    }
    osc_redirect_to($url);
  }

  if ($op == 'archive' || $op == 'unarchive') {
    antique_msg_db()->query(sprintf("UPDATE `%s` SET b_%s_archived = %d WHERE pk_i_id = %d", antique_msg_threads_table(), $role, $op == 'archive' ? 1 : 0, (int)$thread['pk_i_id']));
    antique_msg_reply(true, $op == 'archive' ? __('Conversation archived. It comes back if a new message arrives.', 'starter') : __('Conversation moved back to your inbox.', 'starter'), $op == 'archive' ? antique_inbox_url() : $url);
  }

  if ($op == 'report') {
    antique_msg_report($thread, $uid, trim(mb_substr(strip_tags((string)Params::getParam('reason')), 0, 500)));
    antique_msg_reply(true, __('Thank you. Our team will review this conversation. Do not send money or personal details while we check.', 'starter'), $url);
  }

  antique_msg_reply(false, __('Something went wrong. Please try again.', 'starter'), $url);
}
osc_add_hook('ajax_al_msg', 'antique_msg_post');

function antique_msg_report($thread, $uid, $reason) {
  antique_msg_db()->query("UPDATE `" . antique_msg_threads_table() . "` SET b_reported = 1 WHERE pk_i_id = " . (int)$thread['pk_i_id']);
  $admin = osc_contact_email();
  if ($admin == '') { return; }
  $by = antique_msg_user($uid);
  $other = antique_msg_user(antique_msg_other_id($thread, $uid));
  $lines = array();
  foreach (array_slice(antique_msg_messages($thread['pk_i_id']), -15) as $m) {
    $who = (int)$m['fk_i_sender_id'] > 0 ? antique_msg_user($m['fk_i_sender_id'])['name'] : osc_page_title();
    $lines[] = '[' . $m['dt_sent'] . '] ' . $who . ': ' . $m['s_body'];
  }
  antique_mail($admin, osc_page_title(), sprintf(__('Conversation reported by %s', 'starter'), $by['name']), __('A member reported a conversation', 'starter'), array(
    sprintf(__('%1$s (%2$s) reported their conversation with %3$s (%4$s), conversation #%5$d.', 'starter'), osc_esc_html($by['name']), osc_esc_html($by['email']), osc_esc_html($other['name']), osc_esc_html($other['email']), (int)$thread['pk_i_id']),
    $reason !== '' ? sprintf(__('Reason: %s', 'starter'), osc_esc_html($reason)) : __('No reason given.', 'starter'),
    antique_mail_quote(implode("\n", $lines), 6000)
  ), $other['url'], __('View the reported member', 'starter'));
}

/**
 * New messages after $afterId plus state for the conversation page.
 */
function antique_msg_poll_data($thread, $uid, $afterId) {
  $new = antique_msg_messages($thread['pk_i_id'], $afterId);
  $prev = '';
  if ($afterId > 0) {
    $res = antique_msg_db()->query("SELECT dt_sent FROM `" . antique_msg_table() . "` WHERE pk_i_id = " . (int)$afterId);
    if ($res && $res->numRows() > 0) { $prev = $res->row()['dt_sent']; }
  }
  $reload = false;
  foreach ($new as $m) { if ($m['s_kind'] != 'text') { $reload = true; } }
  $role = antique_msg_role($thread, $uid);
  if ($new) { antique_msg_mark_read($thread, $role); }
  $last = $new ? (int)end($new)['pk_i_id'] : (int)$afterId;
  // "Seen" under my latest message
  $res = antique_msg_db()->query(sprintf("SELECT pk_i_id, dt_sent FROM `%s` WHERE fk_i_thread_id = %d AND fk_i_sender_id = %d AND s_kind = 'text' ORDER BY pk_i_id DESC LIMIT 1", antique_msg_table(), (int)$thread['pk_i_id'], (int)$uid));
  $mine = ($res && $res->numRows() > 0) ? $res->row() : null;
  return array(
    'html' => antique_msg_render_list($new, $thread, $uid, $prev),
    'last' => $last,
    'reload' => $reload,
    'seen' => $mine ? (antique_msg_seen($thread, $uid, $mine['dt_sent']) ? (int)$mine['pk_i_id'] : 0) : 0,
    'unread' => antique_msg_unread_total(true)
  );
}

// GET hook=al_msg_poll&thread=&after=  (read-only; marks new messages read)
function antique_msg_poll() {
  if (!osc_is_web_user_logged_in()) { antique_msg_json(false, array('login' => true)); }
  $uid = (int)osc_logged_user_id();
  $thread = antique_msg_thread((int)Params::getParam('thread'));
  if (antique_msg_role($thread, $uid) === null) { antique_msg_json(false); }
  antique_msg_json(true, antique_msg_poll_data($thread, $uid, (int)Params::getParam('after')));
}
osc_add_hook('ajax_al_msg_poll', 'antique_msg_poll');

/**
 * Message form for a listing or a shop page (signed-in visitors who are not the seller).
 */
function antique_msg_compose($itemId, $sellerId, $sellerName) {
  $uid = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
  $back = $itemId > 0 ? osc_item_url() : osc_user_public_profile_url($sellerId);
  $thread = $uid ? antique_msg_find_thread($itemId, $uid, $sellerId) : null;
  $ideas = $itemId > 0
    ? array(__('Is this still available?', 'starter'), __('Could you send more photos?', 'starter'), __('Are there any marks, repairs or damage?', 'starter'), __('Can you deliver to my country?', 'starter'))
    : array(__('Hello, do you have any similar pieces?', 'starter'), __('Do you accept requests for particular items?', 'starter'));
  ob_start(); ?>
  <?php if ($thread && $thread['s_last_preview'] !== '') { ?>
    <a class="al-msg-existing" href="<?php echo antique_thread_url($thread['pk_i_id']); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <span><?php echo sprintf(__('You already have a conversation with %s. Open it', 'starter'), osc_esc_html($sellerName)); ?> &rarr;</span>
    </a>
  <?php } ?>
  <form class="al-form al-msg-compose" method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_msg')); ?>">
    <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
    <input type="hidden" name="op" value="start" />
    <input type="hidden" name="item" value="<?php echo (int)$itemId; ?>" />
    <input type="hidden" name="seller" value="<?php echo (int)$sellerId; ?>" />
    <input type="hidden" name="back" value="<?php echo osc_esc_html($back); ?>" />
    <div class="al-msg-ideas" aria-label="<?php echo osc_esc_html(__('Suggested questions', 'starter')); ?>">
      <?php foreach ($ideas as $q) { ?><button type="button" class="al-chip" data-al-idea><?php echo osc_esc_html($q); ?></button><?php } ?>
    </div>
    <label class="al-form-full"><span class="al-sr-only"><?php _e('Message', 'starter'); ?></span>
      <textarea name="body" rows="4" maxlength="<?php echo ANTIQUE_MSG_MAX; ?>" required placeholder="<?php echo osc_esc_html(sprintf(__('Write to %s…', 'starter'), $sellerName)); ?>"></textarea>
    </label>
    <div class="al-msg-compose-foot">
      <p class="al-hint"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><?php _e('Messages stay on the site. Your email address is never shown.', 'starter'); ?></p>
      <button type="submit" class="al-btn al-btn-brass"><?php _e('Send message', 'starter'); ?></button>
    </div>
  </form>
  <script>
    (function () {
      var f = document.currentScript.previousElementSibling, ta = f.querySelector('textarea');
      f.querySelectorAll('[data-al-idea]').forEach(function (b) {
        b.addEventListener('click', function () { ta.value = (ta.value.trim() ? ta.value.trim() + '\n' : '') + b.textContent + ' '; ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); });
      });
    })();
  </script>
  <?php return ob_get_clean();
}

function antique_msg_delete_user($uid) {
  $uid = (int)$uid;
  $db = antique_msg_db();
  $res = $db->query("SELECT pk_i_id FROM `" . antique_msg_threads_table() . "` WHERE fk_i_buyer_id = {$uid} OR fk_i_seller_id = {$uid}");
  if (!$res) { return; }
  $ids = array_map(function ($r) { return (int)$r['pk_i_id']; }, $res->result());
  if (!$ids) { return; }
  $in = implode(',', $ids);
  $db->query("DELETE FROM `" . antique_msg_table() . "` WHERE fk_i_thread_id IN ({$in})");
  if (function_exists('antique_offers_table')) { $db->query("DELETE FROM `" . antique_offers_table() . "` WHERE fk_i_thread_id IN ({$in})"); }
  $db->query("DELETE FROM `" . antique_msg_threads_table() . "` WHERE pk_i_id IN ({$in})");
}
osc_add_hook('delete_user', 'antique_msg_delete_user');
