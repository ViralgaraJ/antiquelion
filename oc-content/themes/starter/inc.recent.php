<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Recently viewed: the item page shows the last few listings this browser opened.
 * The list lives only in the al_recent cookie (item IDs, newest first), so it works
 * for guests and needs no table. The cookie is visitor-controlled input: every ID is
 * cast to int and checked before it goes near SQL.
 */

define('ANTIQUE_RECENT_COOKIE', 'al_recent');
define('ANTIQUE_RECENT_MAX', 12);        // IDs kept in the cookie
define('ANTIQUE_RECENT_DAYS', 30);

/** Cookie value -> list of positive int IDs, unique, newest first, at most ANTIQUE_RECENT_MAX. */
function antique_recent_parse($raw) {
  $ids = array();
  if (!is_string($raw) || $raw === '') { return $ids; }
  foreach (explode(',', substr($raw, 0, 400)) as $part) {
    $part = trim($part);
    if ($part === '' || !ctype_digit($part)) { continue; }
    $id = (int)$part;
    if ($id <= 0 || in_array($id, $ids, true)) { continue; }
    $ids[] = $id;
    if (count($ids) >= ANTIQUE_RECENT_MAX) { break; }
  }
  return $ids;
}

/** Put $itemId at the front of $ids (moving it if already there) and trim the list. */
function antique_recent_push($ids, $itemId) {
  $itemId = (int)$itemId;
  $out = $itemId > 0 ? array($itemId) : array();
  foreach ((array)$ids as $id) {
    $id = (int)$id;
    if ($id > 0 && !in_array($id, $out, true)) { $out[] = $id; }
  }
  return array_slice($out, 0, ANTIQUE_RECENT_MAX);
}

/** IDs from the visitor's cookie. */
function antique_recent_ids() {
  return antique_recent_parse(isset($_COOKIE[ANTIQUE_RECENT_COOKIE]) ? $_COOKIE[ANTIQUE_RECENT_COOKIE] : '');
}

/*
 * show_item runs in the item controller before any output, so the cookie can still be
 * set. Owners looking at their own listing, popup (contentOnly) renders and bots are
 * skipped. This only writes the visitor's own cookie: no server state changes.
 */
function antique_recent_record($item) {
  if (headers_sent() || empty($item['pk_i_id'])) { return; }
  if (Params::getParam('contentOnly') == 1 || !osc_visitor_is_real_user()) { return; }
  if (osc_is_web_user_logged_in() && (int)$item['fk_i_user_id'] === (int)osc_logged_user_id()) { return; }
  $ids = antique_recent_push(antique_recent_ids(), (int)$item['pk_i_id']);
  setcookie(ANTIQUE_RECENT_COOKIE, implode(',', $ids), array(
    'expires' => time() + ANTIQUE_RECENT_DAYS * 86400,
    'path' => '/',
    'secure' => osc_is_ssl(),
    'httponly' => true,
    'samesite' => 'Lax'
  ));
}
osc_add_hook('show_item', 'antique_recent_record');

/**
 * Live listings from the visitor's recently viewed list, in cookie order, without
 * $excludeId (the listing being shown). Same "live" rules as antique_promo_items().
 */
function antique_recent_items($excludeId, $limit = 4) {
  $ids = array();
  foreach (antique_recent_ids() as $id) {
    if ($id !== (int)$excludeId) { $ids[] = (int)$id; }
  }
  if (empty($ids)) { return array(); }

  $db = Item::newInstance()->dao;
  $p = DB_TABLE_PREFIX;
  $in = implode(',', array_map('intval', $ids));
  $res = $db->query("SELECT i.* FROM `{$p}t_item` i
    LEFT JOIN `{$p}t_item_starter` s ON s.fk_i_item_id = i.pk_i_id
    WHERE i.pk_i_id IN ({$in})
      AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
      AND (i.dt_expiration IS NULL OR i.dt_expiration >= NOW())
      AND COALESCE(s.i_sold, '') NOT IN ('1', '2')");
  if (!$res) { return array(); }

  // back into cookie order (IN () returns them in any order)
  $byId = array();
  foreach ($res->result() as $row) { $byId[(int)$row['pk_i_id']] = $row; }
  $rows = array();
  foreach ($ids as $id) {
    if (isset($byId[$id])) { $rows[] = $byId[$id]; }
    if (count($rows) >= (int)$limit) { break; }
  }
  return $rows ? Item::newInstance()->extendData($rows) : array();
}
