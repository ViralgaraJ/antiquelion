<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Watchlist: signed-in visitors save listings with the heart button on cards and on
 * the item page, see them under Account > Watchlist, and get an email shortly before
 * a watched auction ends. Rows live in t_al_watchlist (created on first use).
 */

define('ANTIQUE_WATCH_SCHEMA', '1');
define('ANTIQUE_WATCH_ENDING_HOURS', 3);     // "ending soon" email this long before the end

function antique_watch_table() {
  return DB_TABLE_PREFIX . 't_al_watchlist';
}

function antique_watch_db() {
  return Item::newInstance()->dao;
}

function antique_watch_schema() {
  if (osc_get_preference('watch_schema', 'starter_theme') == ANTIQUE_WATCH_SCHEMA) { return; }
  $db = antique_watch_db();
  if (!$db) { return; }
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_watch_table() . "` (
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `dt_added` datetime NOT NULL,
    `b_ending_sent` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`fk_i_user_id`, `fk_i_item_id`),
    KEY `idx_item` (`fk_i_item_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) {
    osc_set_preference('watch_schema', ANTIQUE_WATCH_SCHEMA, 'starter_theme');
  }
}
osc_add_hook('init', 'antique_watch_schema', 1);

// IDs the signed-in visitor watches (one query per request)
function antique_watch_ids() {
  static $ids = null;
  if ($ids !== null) { return $ids; }
  $ids = array();
  if (!osc_is_web_user_logged_in()) { return $ids; }
  $db = antique_watch_db();
  $res = $db ? $db->query("SELECT fk_i_item_id FROM `" . antique_watch_table() . "` WHERE fk_i_user_id = " . (int)osc_logged_user_id() . " ORDER BY dt_added DESC") : false;
  if ($res) {
    foreach ($res->result() as $r) { $ids[] = (int)$r['fk_i_item_id']; }
  }
  return $ids;
}

function antique_is_watched($itemId) {
  return in_array((int)$itemId, antique_watch_ids(), true);
}

function antique_watch_count($itemId) {
  $db = antique_watch_db();
  $res = $db ? $db->query("SELECT COUNT(*) AS n FROM `" . antique_watch_table() . "` WHERE fk_i_item_id = " . (int)$itemId) : false;
  return $res ? (int)$res->row()['n'] : 0;
}

/**
 * Heart button. $variant 'card' (icon over the photo) or 'item' (labelled button).
 */
function antique_watch_button($itemId, $variant = 'card') {
  $on = antique_is_watched($itemId);
  $label = $on ? __('Remove from watchlist', 'starter') : __('Add to watchlist', 'starter');
  $icon = '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/></svg>';
  $html = '<button type="button" class="al-watch al-watch-' . $variant . ($on ? ' is-on' : '') . '" data-al-watch="' . (int)$itemId . '" aria-pressed="' . ($on ? 'true' : 'false') . '" aria-label="' . osc_esc_html($label) . '" title="' . osc_esc_html($label) . '">' . $icon;
  if ($variant == 'item') {
    $html .= '<span class="al-watch-text" data-on="' . osc_esc_html(__('Watching', 'starter')) . '" data-off="' . osc_esc_html(__('Watch', 'starter')) . '">' . ($on ? __('Watching', 'starter') : __('Watch', 'starter')) . '</span>';
  }
  return $html . '</button>';
}

function antique_watchlist_url() {
  return osc_route_url('al-watchlist');
}

// Account > Watchlist (user_menu route: Osclass sends signed-out visitors to the login page)
osc_add_route('al-watchlist', 'account/watchlist/?(?:\?.*)?$', 'account/watchlist/', 'al-watchlist.php', true, 'custom', 'al-watchlist', __('Watchlist', 'starter'));

// AJAX toggle: index.php?page=ajax&action=runhook&hook=al_watch (POST itemId, octoken)
function antique_watch_ajax() {
  ob_clean();
  header('Content-Type: application/json');
  if (!osc_is_web_user_logged_in()) {
    echo json_encode(array('success' => false, 'login' => true));
    exit;
  }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    echo json_encode(array('success' => false, 'error' => __('Your session expired. Please reload the page.', 'starter')));
    exit;
  }
  $itemId = (int)Params::getParam('itemId');
  $item = Item::newInstance()->findByPrimaryKey($itemId);
  if (!$item) {
    echo json_encode(array('success' => false, 'error' => __('This listing no longer exists.', 'starter')));
    exit;
  }
  $uid = (int)osc_logged_user_id();
  $db = antique_watch_db();
  $table = antique_watch_table();
  $exists = $db->query("SELECT 1 FROM `{$table}` WHERE fk_i_user_id = {$uid} AND fk_i_item_id = {$itemId}");
  if ($exists && $exists->numRows() > 0) {
    $db->query("DELETE FROM `{$table}` WHERE fk_i_user_id = {$uid} AND fk_i_item_id = {$itemId}");
    $on = false;
  } else {
    $db->query("INSERT IGNORE INTO `{$table}` (fk_i_user_id, fk_i_item_id, dt_added) VALUES ({$uid}, {$itemId}, '" . date('Y-m-d H:i:s') . "')");
    $on = true;
  }
  $res = $db->query("SELECT COUNT(*) AS n FROM `{$table}` WHERE fk_i_user_id = {$uid}");
  echo json_encode(array(
    'success' => true,
    'watched' => $on,
    'total'   => $res ? (int)$res->row()['n'] : 0,
    'count'   => antique_watch_count($itemId)
  ));
  exit;
}
osc_add_hook('ajax_al_watch', 'antique_watch_ajax');

function antique_watch_delete_item($itemId) {
  $db = antique_watch_db();
  if ($db) { $db->query("DELETE FROM `" . antique_watch_table() . "` WHERE fk_i_item_id = " . (int)$itemId); }
}
osc_add_hook('delete_item', 'antique_watch_delete_item');

/**
 * Watched listings for the watchlist page, newest first, as Osclass item rows.
 */
function antique_watch_items() {
  $ids = antique_watch_ids();
  if (empty($ids)) { return array(); }
  $db = antique_watch_db();
  $res = $db->query("SELECT * FROM `" . DB_TABLE_PREFIX . "t_item` WHERE pk_i_id IN (" . implode(',', array_map('intval', $ids)) . ")");
  $rows = array();
  if ($res) {
    foreach ($res->result() as $r) { $rows[(int)$r['pk_i_id']] = $r; }
  }
  $ordered = array();
  foreach ($ids as $id) {
    if (isset($rows[$id])) { $ordered[] = $rows[$id]; }
  }
  return Item::newInstance()->extendData($ordered);
}

/**
 * "Ending soon" emails for watched auctions. Runs at most every 10 minutes.
 */
function antique_watch_ending_soon() {
  if (!function_exists('antique_bids_mail') || !class_exists('ModelBids')) { return; }
  $last = (int)osc_get_preference('watch_ending_cron', 'starter_theme');
  if (time() - $last < 600) { return; }
  osc_set_preference('watch_ending_cron', time(), 'starter_theme', 'INTEGER');

  $db = antique_watch_db();
  $p = DB_TABLE_PREFIX;
  $res = $db->query("SELECT w.fk_i_user_id, w.fk_i_item_id, a.dt_end_date
    FROM `" . antique_watch_table() . "` w
    JOIN `{$p}t_item_auctions` a ON a.fk_i_item_id = w.fk_i_item_id
    JOIN `{$p}t_item` i ON i.pk_i_id = w.fk_i_item_id
    WHERE w.b_ending_sent = 0 AND a.b_active = 1 AND a.dt_end_date > NOW()
      AND a.dt_end_date <= DATE_ADD(NOW(), INTERVAL " . (int)ANTIQUE_WATCH_ENDING_HOURS . " HOUR)
      AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0 AND i.fk_i_user_id <> w.fk_i_user_id
    LIMIT 50");
  if (!$res) { return; }
  foreach ($res->result() as $r) {
    // claim first so a parallel request never sends it twice
    $claim = $db->query("UPDATE `" . antique_watch_table() . "` SET b_ending_sent = 1 WHERE fk_i_user_id = " . (int)$r['fk_i_user_id'] . " AND fk_i_item_id = " . (int)$r['fk_i_item_id'] . " AND b_ending_sent = 0");
    if (!$claim || $db->affectedRows() < 1) { continue; }
    $user = User::newInstance()->findByPrimaryKey($r['fk_i_user_id']);
    $item = Item::newInstance()->findByPrimaryKey($r['fk_i_item_id']);
    if (!$user || !$item) { continue; }
    $state = function_exists('antique_bids_state') ? antique_bids_state($item['pk_i_id']) : array('amount' => 0, 'hasBids' => false);
    $left = antique_bids_time_left(max(60, strtotime($r['dt_end_date']) - time()));
    antique_bids_mail(
      $user['s_email'], $user['s_name'],
      sprintf(__('Ending soon: %s', 'starter'), $item['s_title']),
      __('An auction you are watching ends soon', 'starter'),
      array(
        sprintf(__('%1$s ends in %2$s.', 'starter'), '<strong>' . osc_esc_html($item['s_title']) . '</strong>', '<strong>' . osc_esc_html($left) . '</strong>'),
        sprintf($state['hasBids'] ? __('Current bid: %s.', 'starter') : __('Starting bid: %s. No bids yet.', 'starter'), '<strong>' . osc_esc_html(antique_bids_money($state['amount'], $item['fk_c_currency_code'])) . '</strong>')
      ),
      osc_item_url_from_item($item), __('View the auction', 'starter')
    );
  }
}
osc_add_hook('init', 'antique_watch_ending_soon');
