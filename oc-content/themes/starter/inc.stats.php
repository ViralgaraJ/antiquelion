<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Seller analytics: Account > Analytics (route al-stats).
 *
 * Built from data the site already records, nothing new is tracked:
 *   views          t_item_stats.i_num_views (per listing per day, Osclass core)
 *   phone reveals  t_item_stats_starter.i_num_phone_clicks (per day)
 *   enquiries      t_al_threads (new conversations about a listing)
 *   watchers       t_al_watchlist (added in the period / watching now)
 *   offers, bids   t_al_offers, t_item_bids
 */

osc_add_route('al-stats', 'account/analytics/?(?:\?.*)?$', 'account/analytics/', 'al-stats.php', true, 'custom', 'al-stats', __('Analytics', 'starter'));

function antique_stats_url($query = array()) { return antique_route_url('al-stats', $query); }

function antique_stats_periods() {
  return array(7 => __('Last 7 days', 'starter'), 30 => __('Last 30 days', 'starter'), 90 => __('Last 90 days', 'starter'));
}

/**
 * Figures for a seller over the last $days days (today included), optionally one listing.
 * Returns totals, the previous period's totals, a per-day series and per-listing rows.
 */
function antique_stats_seller($uid, $days, $itemId = 0) {
  $db = Item::newInstance()->dao;
  $p = DB_TABLE_PREFIX;
  $uid = (int)$uid;
  $days = max(1, (int)$days);
  $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
  $prevStart = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
  $mine = "i.fk_i_user_id = {$uid}" . ($itemId ? ' AND i.pk_i_id = ' . (int)$itemId : '');

  // metric => SQL returning (d DATE, k item id, n count) since :from
  $sources = array(
    'views'   => "SELECT s.dt_date AS d, s.fk_i_item_id AS k, SUM(s.i_num_views) AS n FROM `{$p}t_item_stats` s JOIN `{$p}t_item` i ON i.pk_i_id = s.fk_i_item_id WHERE {$mine} AND s.dt_date >= ':from' GROUP BY s.dt_date, s.fk_i_item_id",
    'phone'   => "SELECT s.dt_date AS d, s.fk_i_item_id AS k, SUM(s.i_num_phone_clicks) AS n FROM `{$p}t_item_stats_starter` s JOIN `{$p}t_item` i ON i.pk_i_id = s.fk_i_item_id WHERE {$mine} AND s.dt_date >= ':from' GROUP BY s.dt_date, s.fk_i_item_id",
    'enquiries' => "SELECT DATE(t.dt_created) AS d, t.fk_i_item_id AS k, COUNT(*) AS n FROM `{$p}t_al_threads` t JOIN `{$p}t_item` i ON i.pk_i_id = t.fk_i_item_id WHERE {$mine} AND t.fk_i_buyer_id <> {$uid} AND t.dt_created >= ':from' GROUP BY DATE(t.dt_created), t.fk_i_item_id",
    'watchers' => "SELECT DATE(w.dt_added) AS d, w.fk_i_item_id AS k, COUNT(*) AS n FROM `{$p}t_al_watchlist` w JOIN `{$p}t_item` i ON i.pk_i_id = w.fk_i_item_id WHERE {$mine} AND w.dt_added >= ':from' GROUP BY DATE(w.dt_added), w.fk_i_item_id",
    'offers'  => "SELECT DATE(o.dt_created) AS d, o.fk_i_item_id AS k, COUNT(*) AS n FROM `{$p}t_al_offers` o JOIN `{$p}t_item` i ON i.pk_i_id = o.fk_i_item_id WHERE {$mine} AND o.fk_i_from_id <> {$uid} AND o.dt_created >= ':from' GROUP BY DATE(o.dt_created), o.fk_i_item_id",
    'bids'    => "SELECT DATE(b.dt_date) AS d, b.fk_i_item_id AS k, COUNT(*) AS n FROM `{$p}t_item_bids` b JOIN `{$p}t_item` i ON i.pk_i_id = b.fk_i_item_id WHERE {$mine} AND b.dt_date >= ':from' GROUP BY DATE(b.dt_date), b.fk_i_item_id"
  );

  $series = array();
  for ($t = strtotime($start); $t <= strtotime(date('Y-m-d')); $t += 86400) {
    $series[date('Y-m-d', $t)] = array_fill_keys(array_keys($sources), 0);
  }
  $totals = array_fill_keys(array_keys($sources), 0);
  $prev = $totals;
  $byItem = array();
  foreach ($sources as $key => $sql) {
    $res = $db->query(str_replace(':from', $prevStart, $sql));
    if (!$res) { continue; }
    foreach ($res->result() as $r) {
      $d = substr($r['d'], 0, 10);
      $n = (int)$r['n'];
      if ($d < $start) { $prev[$key] += $n; continue; }
      $totals[$key] += $n;
      if (isset($series[$d])) { $series[$d][$key] += $n; }
      $byItem[(int)$r['k']][$key] = (isset($byItem[(int)$r['k']][$key]) ? $byItem[(int)$r['k']][$key] : 0) + $n;
    }
  }

  // every listing of the seller, with its figures for the period
  $res = $db->query("SELECT i.pk_i_id, i.b_active, i.b_enabled, i.dt_expiration, i.dt_pub_date, i.fk_c_currency_code, i.i_price,
      (SELECT d.s_title FROM `{$p}t_item_description` d WHERE d.fk_i_item_id = i.pk_i_id LIMIT 1) AS s_title,
      (SELECT COUNT(*) FROM `{$p}t_al_watchlist` w WHERE w.fk_i_item_id = i.pk_i_id) AS watching,
      (SELECT COALESCE(SUM(s.i_num_views), 0) FROM `{$p}t_item_stats` s WHERE s.fk_i_item_id = i.pk_i_id) AS views_all
    FROM `{$p}t_item` i WHERE {$mine} ORDER BY i.dt_pub_date DESC LIMIT 500");
  $items = array();
  foreach ($res ? $res->result() : array() as $r) {
    $id = (int)$r['pk_i_id'];
    $items[] = array_merge($r, array_fill_keys(array_keys($sources), 0), isset($byItem[$id]) ? $byItem[$id] : array());
  }
  usort($items, function ($a, $b) { return $b['views'] - $a['views'] ?: strcmp($b['dt_pub_date'], $a['dt_pub_date']); });

  return array('days' => $days, 'start' => $start, 'totals' => $totals, 'prev' => $prev, 'series' => $series, 'items' => $items);
}

/**
 * "+12%" / "-5%" against the previous period, or '' when there is nothing to compare.
 */
function antique_stats_change($now, $before) {
  if ($before <= 0) { return ''; }
  $pct = round(($now - $before) / $before * 100);
  return ($pct > 0 ? '+' : '') . $pct . '%';
}

/**
 * Plain-language suggestions from the figures.
 */
function antique_stats_tips($stats) {
  $tips = array();
  foreach ($stats['items'] as $r) {
    $live = (int)$r['b_active'] && (int)$r['b_enabled'];
    if (!$live) { continue; }
    if ($r['views'] >= 30 && $r['enquiries'] == 0 && $r['phone'] == 0) {
      $tips[] = sprintf(__('"%s" is seen often but nobody has asked about it yet. A clearer price or more photos usually help.', 'starter'), $r['s_title']);
    } elseif ($r['views'] < 5 && strtotime($r['dt_pub_date']) < strtotime('-7 days')) {
      $tips[] = sprintf(__('"%s" had few views. A promotion or a more specific title can bring it to more buyers.', 'starter'), $r['s_title']);
    }
    if (count($tips) >= 3) { break; }
  }
  return $tips;
}
