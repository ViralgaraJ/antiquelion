<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Email preferences. Optional emails (new messages, offers, outbid, watchlist...)
 * carry a topic; members turn topics off under Account > Email settings or with the
 * unsubscribe link in each email, which works without signing in (signed with a
 * per-site secret) and also as a one-click List-Unsubscribe header (RFC 8058).
 * Emails about a deal (auction won or sold, offer accepted, verification) are
 * always sent. Opted-out topics are rows in t_al_email_optout.
 */

define('ANTIQUE_NOTIFY_SCHEMA', 1);

// Account > Email settings, and the public page an unsubscribe link opens
osc_add_route('al-notify', 'account/emails/?(?:\?.*)?$', 'account/emails/', 'al-notify.php', true, 'custom', 'al-notify', __('Email settings', 'starter'));
osc_add_route('al-unsubscribe', 'unsubscribe/?(?:\?.*)?$', 'unsubscribe/', 'al-unsubscribe.php', false, 'custom', 'al-unsubscribe', __('Unsubscribe', 'starter'));

function antique_notify_table() { return DB_TABLE_PREFIX . 't_al_email_optout'; }

function antique_notify_schema() {
  if ((int)osc_get_preference('notify_schema', 'starter_theme') >= ANTIQUE_NOTIFY_SCHEMA) { return; }
  $ok = antique_msg_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_notify_table() . "` (
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_topic` varchar(24) NOT NULL,
    `dt_date` datetime NOT NULL,
    PRIMARY KEY (`fk_i_user_id`, `s_topic`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('notify_schema', ANTIQUE_NOTIFY_SCHEMA, 'starter_theme', 'INTEGER'); }
}
osc_add_hook('init', 'antique_notify_schema', 1);

/**
 * Optional email topics: id => array(label, description).
 */
function antique_notify_topics() {
  return array(
    'messages'        => array(__('New messages', 'starter'), __('When a buyer or seller sends you a message.', 'starter')),
    'offers'          => array(__('Offers', 'starter'), __('New offers, counter-offers and declined offers. Accepted offers are always emailed.', 'starter')),
    'outbid'          => array(__('Outbid alerts', 'starter'), __('When another bidder outbids you on a running auction.', 'starter')),
    'auction_updates' => array(__('Auction results', 'starter'), __('When an auction you bid on ends and you did not win. If you win, we always email you.', 'starter')),
    'watchlist'       => array(__('Watchlist', 'starter'), __('When an auction you are watching ends soon, or a watched item sells.', 'starter')),
    'reviews'         => array(__('Reviews', 'starter'), __('When a buyer reviews your shop.', 'starter'))
  );
}

function antique_notify_url() { return osc_route_url('al-notify'); }

function antique_notify_off_topics($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if (!isset($cache[$uid])) {
    $res = antique_msg_db()->query(sprintf("SELECT s_topic FROM `%s` WHERE fk_i_user_id = %d", antique_notify_table(), $uid));
    $cache[$uid] = $res ? array_column($res->result(), 's_topic') : array();
  }
  return $cache[$uid];
}

function antique_notify_on($uid, $topic) {
  return !in_array($topic, antique_notify_off_topics($uid), true);
}

/**
 * Saves which topics are on. $on: topic ids to keep on; every other known topic is turned off.
 */
function antique_notify_save($uid, $on) {
  $uid = (int)$uid;
  $db = antique_msg_db();
  foreach (array_keys(antique_notify_topics()) as $t) {
    if (in_array($t, $on, true)) {
      $db->query(sprintf("DELETE FROM `%s` WHERE fk_i_user_id = %d AND s_topic = %s", antique_notify_table(), $uid, $db->escape($t)));
    } else {
      $db->query(sprintf("INSERT IGNORE INTO `%s` (fk_i_user_id, s_topic, dt_date) VALUES (%d, %s, '%s')", antique_notify_table(), $uid, $db->escape($t), date('Y-m-d H:i:s')));
    }
  }
}

function antique_notify_secret() {
  // cached: osc_get_preference() does not see a value set earlier in the same request
  static $s = null;
  if ($s === null) {
    $s = (string)osc_get_preference('notify_secret', 'starter_theme');
    if (strlen($s) < 32) {
      $s = bin2hex(random_bytes(32));
      osc_set_preference('notify_secret', $s, 'starter_theme');
    }
  }
  return $s;
}

// $topic is a topic id, or 'all' for every optional email
function antique_notify_token($uid, $topic) {
  return substr(hash_hmac('sha256', (int)$uid . '|' . $topic, antique_notify_secret()), 0, 32);
}

function antique_notify_check($uid, $topic, $token) {
  return (int)$uid > 0 && ($topic === 'all' || isset(antique_notify_topics()[$topic]))
    && hash_equals(antique_notify_token($uid, $topic), (string)$token);
}

// The page an email's unsubscribe link opens (asks to confirm; mail scanners only GET)
function antique_unsubscribe_url($uid, $topic) {
  return antique_route_url('al-unsubscribe', array('u' => (int)$uid, 't' => $topic, 'k' => antique_notify_token($uid, $topic)));
}

// POST target for that page and for one-click List-Unsubscribe
function antique_unsubscribe_post_url($uid, $topic) {
  return osc_ajax_hook_url('al_unsub', array('u' => (int)$uid, 't' => $topic, 'k' => antique_notify_token($uid, $topic)));
}

/**
 * Footer lines and List-Unsubscribe URL for an email to $to.
 * Returns array('skip' => true) when the recipient turned $topic off.
 */
function antique_notify_mail_info($to, $topic) {
  $user = User::newInstance()->findByEmail($to);
  if (!$user) { return array('skip' => false, 'footer' => '', 'unsub' => ''); }
  $uid = (int)$user['pk_i_id'];
  if ($topic !== '' && !antique_notify_on($uid, $topic)) { return array('skip' => true); }

  $link = 'color:#8A6420;';
  $footer = '<br/><a href="' . osc_esc_html(antique_notify_url()) . '" style="' . $link . '">' . __('Email settings', 'starter') . '</a>';
  $unsub = '';
  if ($topic !== '' && isset(antique_notify_topics()[$topic])) {
    $footer .= ' &middot; <a href="' . osc_esc_html(antique_unsubscribe_url($uid, $topic)) . '" style="' . $link . '">'
      . __('Unsubscribe from these emails', 'starter') . '</a>';
    $unsub = antique_unsubscribe_post_url($uid, $topic);
  }
  return array('skip' => false, 'footer' => $footer, 'unsub' => $unsub);
}

// One-click unsubscribe headers (Gmail / Yahoo show an "Unsubscribe" button)
function antique_notify_mail_headers($mail, $params = array()) {
  if (is_object($mail) && !empty($params['al_unsub'])) {
    $mail->addCustomHeader('List-Unsubscribe', '<' . $params['al_unsub'] . '>');
    $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
  }
  return $mail;
}
osc_add_filter('init_send_mail', 'antique_notify_mail_headers');

/**
 * POST hook=al_unsub&u=&t=&k=  From the unsubscribe page (confirm=1, redirects back)
 * or a mail client's one-click request (plain 200 reply).
 */
function antique_notify_unsub_post() {
  $uid = (int)Params::getParam('u');
  $topic = (string)Params::getParam('t');
  $token = (string)Params::getParam('k');
  $fromPage = Params::getParam('confirm') == '1';
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // opened as a plain link: show the confirm page instead
    osc_redirect_to(antique_route_url('al-unsubscribe', array('u' => $uid, 't' => $topic, 'k' => $token)));
  }
  $valid = antique_notify_check($uid, $topic, $token) && User::newInstance()->findByPrimaryKey($uid);

  if ($valid) {
    $off = antique_notify_off_topics($uid);
    $on = $topic === 'all' ? array() : array_values(array_diff(array_keys(antique_notify_topics()), array_merge($off, array($topic))));
    antique_notify_save($uid, $on);
  }
  if ($fromPage) {
    $query = array('u' => $uid, 't' => $topic, 'k' => $token);
    if ($valid) { $query['done'] = 1; }
    osc_redirect_to(antique_route_url('al-unsubscribe', $query));
  }
  http_response_code($valid ? 200 : 400);
  header('Content-Type: text/plain; charset=utf-8');
  echo $valid ? 'Unsubscribed' : 'Invalid unsubscribe link';
  exit;
}
osc_add_hook('ajax_al_unsub', 'antique_notify_unsub_post');

/**
 * POST hook=al_notify: Account > Email settings (topics[] = topics to keep on).
 */
function antique_notify_settings_post() {
  $back = antique_notify_url();
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_login_url()); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $on = Params::getParam('topics');
  $on = is_array($on) ? array_values(array_intersect(array_keys(antique_notify_topics()), $on)) : array();
  antique_notify_save(osc_logged_user_id(), $on);
  antique_msg_reply(true, __('Your email settings have been saved.', 'starter'), $back);
}
osc_add_hook('ajax_al_notify', 'antique_notify_settings_post');

function antique_notify_delete_user($uid) {
  antique_msg_db()->query(sprintf("DELETE FROM `%s` WHERE fk_i_user_id = %d", antique_notify_table(), (int)$uid));
}
osc_add_hook('delete_user', 'antique_notify_delete_user');
