<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Seller plans and listing limits (like the memberships on big classifieds sites).
 *
 *   Free         up to N live listings (Marketplace > Payments, default 5)
 *   Seller Plus  more listings, a discount on promotions, a "Plus" badge
 *   Dealer Pro   unlimited listings, a bigger discount, a "Pro" badge
 *   Listing pack +N live listings for 30 days, on top of any plan
 *
 * Plans are prepaid for 1, 3 or 12 months through PayHere (inc.payments.php
 * creates the order; antique_member_fulfil() starts it). Buying the same plan again
 * adds the time after the current one; another plan replaces it from today.
 * We email 3 days before a plan ends and when it has ended.
 *
 * A "live listing" is enabled, active, not spam, not expired and not sold or
 * reserved. Limits stop new listings only; nothing already live is hidden.
 * Limits apply only while the seller can actually buy (payments on for them).
 */

define('ANTIQUE_MEMBER_SCHEMA', '1');
define('ANTIQUE_MEMBER_REMIND_DAYS', 3);
define('ANTIQUE_MEMBER_PACK_DAYS', 30);

function antique_members_table() { return DB_TABLE_PREFIX . 't_al_memberships'; }

function antique_member_schema() {
  if (osc_get_preference('member_schema', 'starter_theme') == ANTIQUE_MEMBER_SCHEMA) { return; }
  $db = antique_pay_db();
  if (!$db) { return; }
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_members_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `fk_i_order_id` int(10) unsigned DEFAULT NULL,
    `s_plan` varchar(20) NOT NULL,
    `i_slots` smallint(5) unsigned NOT NULL DEFAULT 0,
    `dt_start` datetime NOT NULL,
    `dt_end` datetime NOT NULL,
    `b_cancelled` tinyint(1) NOT NULL DEFAULT 0,
    `b_remind_sent` tinyint(1) NOT NULL DEFAULT 0,
    `b_end_sent` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_user` (`fk_i_user_id`, `dt_end`),
    KEY `idx_end` (`dt_end`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('member_schema', ANTIQUE_MEMBER_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_member_schema', 1);
osc_add_hook('init_admin', 'antique_member_schema', 1);

/* ---------- Settings ---------- */

function antique_member_setting($key, $default = '') {
  $v = osc_get_preference('member_' . $key, 'starter_theme');
  return ($v === '' || $v === null) ? $default : $v;
}

// Plans and listing limits switched on in Marketplace > Payments
function antique_member_on() {
  return antique_member_setting('enabled', '0') === '1';
}

function antique_member_free_limit() {
  return max(1, (int)antique_member_setting('free_limit', 5));
}

/**
 * Paid plans. Prices are USD per number of days (30 / 90 / 365); limit 0 = unlimited.
 * Admin overrides (prices, limits, discounts) come from Marketplace > Payments.
 */
function antique_member_plans() {
  $plans = array(
    'plus' => array(
      'name' => __('Seller Plus', 'starter'),
      'badge' => __('Plus', 'starter'),
      'desc' => __('For collectors who sell regularly.', 'starter'),
      'limit' => 50, 'discount' => 10,
      'prices' => array(30 => 9, 90 => 24, 365 => 84)
    ),
    'pro' => array(
      'name' => __('Dealer Pro', 'starter'),
      'badge' => __('Pro', 'starter'),
      'desc' => __('For antique dealers and shops.', 'starter'),
      'limit' => 0, 'discount' => 20,
      'prices' => array(30 => 29, 90 => 79, 365 => 279)
    )
  );
  $saved = json_decode((string)antique_member_setting('plans'), true);
  if (is_array($saved)) {
    foreach ($plans as $key => $p) {
      if (!isset($saved[$key])) { continue; }
      if (isset($saved[$key]['limit'])) { $plans[$key]['limit'] = max(0, (int)$saved[$key]['limit']); }
      if (isset($saved[$key]['discount'])) { $plans[$key]['discount'] = max(0, min(90, (int)$saved[$key]['discount'])); }
      foreach ($p['prices'] as $days => $usd) {
        if (isset($saved[$key]['prices'][$days]) && (float)$saved[$key]['prices'][$days] > 0) { $plans[$key]['prices'][$days] = (float)$saved[$key]['prices'][$days]; }
      }
    }
  }
  return $plans;
}

function antique_member_pack() {
  return array(
    'slots' => max(1, (int)antique_member_setting('pack_slots', 10)),
    'usd' => max(0.5, (float)antique_member_setting('pack_usd', 5))
  );
}

// "1 month", "3 months", "12 months"
function antique_member_period($days) {
  $months = $days >= 360 ? 12 : max(1, (int)round($days / 30));
  return sprintf(_n('%d month', '%d months', $months, 'starter'), $months);
}

/* ---------- Who has what ---------- */

/**
 * Running plan rows and packs of a member (cached per request).
 */
function antique_member_rows($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if (isset($cache[$uid])) { return $cache[$uid]; }
  $db = antique_pay_db();
  $now = $db->escape(antique_pay_now());
  $res = $uid > 0 ? $db->query("SELECT * FROM `" . antique_members_table() . "` WHERE fk_i_user_id = {$uid} AND b_cancelled = 0 AND dt_start <= {$now} AND dt_end > {$now} ORDER BY dt_end DESC") : false;
  return $cache[$uid] = $res ? $res->result() : array();
}

// Current plan key ('plus', 'pro') or '' for free
function antique_member_plan($uid) {
  $plans = antique_member_plans();
  $best = '';
  foreach (antique_member_rows($uid) as $r) {
    if ($r['s_plan'] === 'pro') { return 'pro'; }
    if (isset($plans[$r['s_plan']])) { $best = $r['s_plan']; }
  }
  return $best;
}

// When the current plan (including time bought in advance) ends
function antique_member_plan_end($uid, $plan) {
  $db = antique_pay_db();
  $res = $db->query(sprintf("SELECT MAX(dt_end) AS e FROM `%s` WHERE fk_i_user_id = %d AND s_plan = %s AND b_cancelled = 0 AND dt_end > %s",
    antique_members_table(), (int)$uid, $db->escape($plan), $db->escape(antique_pay_now())));
  return $res ? $res->row()['e'] : null;
}

function antique_member_discount($uid) {
  if (!antique_member_on()) { return 0; }
  $plan = antique_member_plan($uid);
  $plans = antique_member_plans();
  return $plan !== '' ? (int)$plans[$plan]['discount'] : 0;
}

// Extra listings from running packs
function antique_member_pack_slots($uid) {
  $n = 0;
  foreach (antique_member_rows($uid) as $r) {
    if ($r['s_plan'] === 'pack') { $n += (int)$r['i_slots']; }
  }
  return $n;
}

/**
 * Live listing limit for a member: 0 means unlimited.
 */
function antique_member_limit($uid) {
  $plan = antique_member_plan($uid);
  $plans = antique_member_plans();
  $base = $plan !== '' ? (int)$plans[$plan]['limit'] : antique_member_free_limit();
  return $base === 0 ? 0 : $base + antique_member_pack_slots($uid);
}

function antique_member_live_count($uid) {
  $db = antique_pay_db();
  $p = DB_TABLE_PREFIX;
  $res = $db->query(sprintf("SELECT COUNT(*) AS n FROM `{$p}t_item` i LEFT JOIN `{$p}t_item_starter` s ON s.fk_i_item_id = i.pk_i_id
    WHERE i.fk_i_user_id = %d AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
      AND (i.dt_expiration IS NULL OR i.dt_expiration >= %s) AND COALESCE(s.i_sold, '') NOT IN ('1', '2')", (int)$uid, $db->escape(antique_pay_now())));
  return $res ? (int)$res->row()['n'] : 0;
}

// Limits are enforced only when the member can buy their way past them
function antique_member_enforced() {
  return antique_member_on() && antique_pay_enabled();
}

function antique_member_can_post($uid) {
  if (!antique_member_enforced()) { return true; }
  $limit = antique_member_limit($uid);
  return $limit === 0 || antique_member_live_count($uid) < $limit;
}

/* ---------- Pages ---------- */

function antique_plans_url($query = array()) { return antique_route_url('al-plans', $query); }

// Public page: anyone can compare plans; buying needs an account
osc_add_route('al-plans', 'seller-plans/?(?:\?.*)?$', 'seller-plans/', 'al-plans.php', false, 'custom', 'al-plans', __('Seller plans', 'starter'));

function antique_plans_title($text) {
  if (osc_get_osclass_location() == 'custom' && Params::getParam('route') == 'al-plans') {
    return __('Seller plans and pricing', 'starter') . ' - ' . osc_page_title();
  }
  return $text;
}
osc_add_filter('meta_title_filter', 'antique_plans_title');

/**
 * POST hook=al_plan_buy: plan (plus|pro) + days, or plan=pack.
 */
function antique_plan_buy_post() {
  $back = antique_plans_url();
  if (!osc_is_web_user_logged_in()) {
    Session::newInstance()->_set('al_after_login', $back);
    osc_redirect_to(osc_user_login_url());
  }
  $uid = (int)osc_logged_user_id();
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  if (!antique_member_on() || !antique_pay_enabled()) {
    antique_msg_reply(false, __('Plans are not available right now. Please try again later.', 'starter'), $back);
  }
  if (!antique_is_seller($uid)) { osc_redirect_to(antique_seller_setup_url()); }
  $plan = (string)Params::getParam('plan');
  if ($plan === 'pack') {
    $pack = antique_member_pack();
    $order = antique_pay_create_order($uid, 0, 'pack', ANTIQUE_MEMBER_PACK_DAYS, $pack['usd']);
  } else {
    $plans = antique_member_plans();
    $days = (int)Params::getParam('days');
    if (!isset($plans[$plan]['prices'][$days])) { antique_msg_reply(false, __('Please choose a plan.', 'starter'), $back); }
    $order = antique_pay_create_order($uid, 0, 'plan:' . $plan, $days, $plans[$plan]['prices'][$days]);
  }
  if (!$order) { antique_msg_reply(false, __('We could not start the payment. Please try again.', 'starter'), $back); }
  antique_pay_render_checkout($order);
}
osc_add_hook('ajax_al_plan_buy', 'antique_plan_buy_post');

function antique_member_order_plan($order) {
  return strpos($order['s_product'], 'plan:') === 0 ? substr($order['s_product'], 5) : ($order['s_product'] === 'pack' ? 'pack' : '');
}

function antique_member_order_label($order) {
  $plan = antique_member_order_plan($order);
  if ($plan === 'pack') {
    $slots = (int)antique_member_pack()['slots'];
    return sprintf(__('Listing pack: %1$d extra listings for %2$d days', 'starter'), $slots, $order['i_days']);
  }
  $plans = antique_member_plans();
  return isset($plans[$plan]) ? sprintf('%s, %s', $plans[$plan]['name'], antique_member_period($order['i_days'])) : $order['s_product'];
}

/**
 * Start a paid plan or pack. Same plan: queued after the running one.
 * Different plan: replaces the running plan from now on (packs are kept).
 */
function antique_member_fulfil($order) {
  $plan = antique_member_order_plan($order);
  if ($plan === '') { return; }
  $slots = $plan === 'pack' ? (int)antique_member_pack()['slots'] : 0;
  antique_member_start($order['fk_i_user_id'], $plan, $order['i_days'], $order['pk_i_id'], $slots);
}

function antique_member_start($uid, $plan, $days, $orderId = null, $slots = 0) {
  $db = antique_pay_db();
  $t = antique_members_table();
  $uid = (int)$uid;
  $now = antique_pay_now();
  $start = $now;
  if ($plan !== 'pack') {
    // another plan stops now; the same plan continues and the new time follows it
    $db->query(sprintf("UPDATE `%s` SET dt_end = %s WHERE fk_i_user_id = %d AND s_plan NOT IN ('pack', %s) AND b_cancelled = 0 AND dt_end > %s",
      $t, $db->escape($now), $uid, $db->escape($plan), $db->escape($now)));
    $db->query(sprintf("UPDATE `%s` SET b_cancelled = 1 WHERE fk_i_user_id = %d AND s_plan NOT IN ('pack', %s) AND dt_start > %s",
      $t, $uid, $db->escape($plan), $db->escape($now)));
    $end = antique_member_plan_end($uid, $plan);
    if ($end) { $start = $end; }
  }
  $stop = date('Y-m-d H:i:s', strtotime($start) + (int)$days * 86400);
  $db->query(sprintf("INSERT INTO `%s` (fk_i_user_id, fk_i_order_id, s_plan, i_slots, dt_start, dt_end) VALUES (%d, %s, %s, %d, %s, %s)",
    $t, $uid, $orderId ? (int)$orderId : 'NULL', $db->escape($plan), (int)$slots, $db->escape($start), $db->escape($stop)));
}

function antique_member_cancel_order($orderId) {
  $db = antique_pay_db();
  $db->query(sprintf("UPDATE `%s` SET b_cancelled = 1 WHERE fk_i_order_id = %d", antique_members_table(), (int)$orderId));
}

function antique_member_order_end($order) {
  $db = antique_pay_db();
  $res = $db->query(sprintf("SELECT dt_end FROM `%s` WHERE fk_i_order_id = %d LIMIT 1", antique_members_table(), (int)$order['pk_i_id']));
  return ($res && $res->numRows() > 0) ? $res->row()['dt_end'] : null;
}

/* ---------- Posting gate and notices ---------- */

// Runs after the seller and verification checks in antique_seller_router (priority 2)
function antique_member_post_gate() {
  if (Params::getParam('page') != 'item' || !in_array(Params::getParam('action'), array('item_add', 'item_add_post'), true)) { return; }
  if (!osc_is_web_user_logged_in() || !antique_can_list()) { return; }
  $uid = (int)osc_logged_user_id();
  if (antique_member_can_post($uid)) { return; }
  osc_add_flash_info_message(sprintf(__('You have %d live listings, the most your plan allows. Upgrade your plan or add a listing pack to post more. Selling or ending a listing also frees a place.', 'starter'), antique_member_live_count($uid)));
  osc_redirect_to(antique_plans_url());
}
osc_add_hook('init', 'antique_member_post_gate', 3);

/**
 * "3 of 5 listings used" bar on the seller's listings page.
 */
function antique_member_usage_bar() {
  if (!osc_is_web_user_logged_in() || !antique_member_enforced() || !antique_is_seller()) { return ''; }
  $uid = (int)osc_logged_user_id();
  $limit = antique_member_limit($uid);
  $used = antique_member_live_count($uid);
  $plan = antique_member_plan($uid);
  $plans = antique_member_plans();
  $name = $plan !== '' ? $plans[$plan]['name'] : __('Free', 'starter');
  $html = '<div class="al-usage' . ($limit && $used >= $limit ? ' is-full' : '') . '"><div class="al-usage-text"><strong>' . osc_esc_html($name) . '</strong> ';
  if ($limit === 0) {
    $html .= osc_esc_html(sprintf(__('%d live listings · unlimited', 'starter'), $used)) . '</div>';
  } else {
    $pct = min(100, round($used / max(1, $limit) * 100));
    $html .= osc_esc_html(sprintf(__('%1$d of %2$d live listings used', 'starter'), $used, $limit)) . '</div>'
      . '<div class="al-usage-bar" role="progressbar" aria-valuemin="0" aria-valuemax="' . (int)$limit . '" aria-valuenow="' . (int)$used . '"><span style="width:' . $pct . '%"></span></div>';
  }
  $html .= '<a class="al-btn ' . ($limit && $used >= $limit ? 'al-btn-brass' : 'al-btn-outline') . '" href="' . osc_esc_html(antique_plans_url()) . '">' . osc_esc_html($plan === 'pro' ? __('Manage plan', 'starter') : __('Upgrade', 'starter')) . '</a></div>';
  return $html;
}

/**
 * Plus / Pro badge next to the seller's name.
 */
function antique_member_badge($uid) {
  if (!antique_member_on()) { return ''; }
  $plan = antique_member_plan($uid);
  if ($plan === '') { return ''; }
  $plans = antique_member_plans();
  return '<span class="al-member-badge is-' . $plan . '" title="' . osc_esc_html($plans[$plan]['name']) . '">' . osc_esc_html($plans[$plan]['badge']) . '</span>';
}

/* ---------- Reminders ---------- */

function antique_member_job() {
  if (!antique_member_on() || !antique_job_due('memberships', 1800)) { return; }
  $db = antique_pay_db();
  $t = antique_members_table();
  $plans = antique_member_plans();
  $now = $db->escape(antique_pay_now());

  // ending soon: the latest row of a plan, with nothing queued after it
  $res = $db->query("SELECT m.* FROM `{$t}` m WHERE m.s_plan <> 'pack' AND m.b_cancelled = 0 AND m.b_remind_sent = 0 AND m.dt_end > {$now}
    AND m.dt_end <= DATE_ADD({$now}, INTERVAL " . (int)ANTIQUE_MEMBER_REMIND_DAYS . " DAY)
    AND NOT EXISTS (SELECT 1 FROM `{$t}` n WHERE n.fk_i_user_id = m.fk_i_user_id AND n.s_plan = m.s_plan AND n.b_cancelled = 0 AND n.dt_end > m.dt_end) LIMIT 50");
  foreach ($res ? $res->result() : array() as $r) {
    $claim = $db->query("UPDATE `{$t}` SET b_remind_sent = 1 WHERE pk_i_id = " . (int)$r['pk_i_id'] . " AND b_remind_sent = 0");
    if (!$claim || $db->affectedRows() < 1 || !isset($plans[$r['s_plan']])) { continue; }
    $u = User::newInstance()->findByPrimaryKey($r['fk_i_user_id']);
    if (!$u) { continue; }
    antique_mail($u['s_email'], $u['s_name'],
      sprintf(__('Your %s plan ends soon', 'starter'), $plans[$r['s_plan']]['name']),
      sprintf(__('Your %s plan ends soon', 'starter'), $plans[$r['s_plan']]['name']),
      array(
        sprintf(__('Your plan ends on %s.', 'starter'), '<strong>' . osc_esc_html(date('j M Y', strtotime($r['dt_end']))) . '</strong>'),
        sprintf(__('After that your account goes back to the free plan (%d live listings) and your promotion discount stops. Listings already live stay online.', 'starter'), antique_member_free_limit())
      ),
      antique_plans_url(), __('Renew my plan', 'starter'));
  }

  // ended
  $res = $db->query("SELECT m.* FROM `{$t}` m WHERE m.s_plan <> 'pack' AND m.b_cancelled = 0 AND m.b_end_sent = 0 AND m.dt_end <= {$now}
    AND m.dt_end > DATE_SUB({$now}, INTERVAL 7 DAY)
    AND NOT EXISTS (SELECT 1 FROM `{$t}` n WHERE n.fk_i_user_id = m.fk_i_user_id AND n.s_plan <> 'pack' AND n.b_cancelled = 0 AND n.dt_end > {$now}) LIMIT 50");
  foreach ($res ? $res->result() : array() as $r) {
    $claim = $db->query("UPDATE `{$t}` SET b_end_sent = 1 WHERE fk_i_user_id = " . (int)$r['fk_i_user_id'] . " AND b_end_sent = 0 AND dt_end <= {$now}");
    if (!$claim || $db->affectedRows() < 1 || !isset($plans[$r['s_plan']])) { continue; }
    $u = User::newInstance()->findByPrimaryKey($r['fk_i_user_id']);
    if (!$u) { continue; }
    antique_mail($u['s_email'], $u['s_name'],
      sprintf(__('Your %s plan has ended', 'starter'), $plans[$r['s_plan']]['name']),
      sprintf(__('Your %s plan has ended', 'starter'), $plans[$r['s_plan']]['name']),
      array(
        sprintf(__('Your account is now on the free plan, with up to %d live listings. Your listings stay online, but you cannot add new ones while you are over the limit.', 'starter'), antique_member_free_limit()),
        __('Renew at any time to get your listings, badge and promotion discount back.', 'starter')
      ),
      antique_plans_url(), __('See plans', 'starter'));
  }
}
osc_add_hook('init', 'antique_member_job');
osc_add_hook('cron', 'antique_member_job');

/* ---------- Admin (Marketplace > Payments) ---------- */

function antique_member_admin_actions() {
  $act = Params::getParam('al_mk');
  if (!in_array($act, array('member_save', 'member_grant', 'member_stop'), true) || !osc_is_admin_user_logged_in()) { return; }
  $url = antique_marketplace_admin_url('payments');
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    osc_add_flash_error_message(__('The page expired. Please try again.', 'starter'), 'admin');
    osc_redirect_to($url);
  }
  switch ($act) {
    case 'member_save':
      osc_set_preference('member_enabled', Params::getParam('member_enabled') ? '1' : '0', 'starter_theme');
      osc_set_preference('member_free_limit', max(1, (int)Params::getParam('member_free_limit')), 'starter_theme');
      osc_set_preference('member_pack_slots', max(1, (int)Params::getParam('member_pack_slots')), 'starter_theme');
      osc_set_preference('member_pack_usd', max(0.5, round((float)Params::getParam('member_pack_usd'), 2)), 'starter_theme');
      $posted = (array)Params::getParam('plan');
      $save = array();
      foreach (antique_member_plans() as $key => $p) {
        $in = isset($posted[$key]) ? (array)$posted[$key] : array();
        $save[$key] = array(
          'limit' => isset($in['limit']) ? max(0, (int)$in['limit']) : $p['limit'],
          'discount' => isset($in['discount']) ? max(0, min(90, (int)$in['discount'])) : $p['discount'],
          'prices' => array()
        );
        foreach ($p['prices'] as $days => $usd) {
          $v = isset($in['prices'][$days]) ? (float)$in['prices'][$days] : $usd;
          $save[$key]['prices'][$days] = $v > 0 ? round($v, 2) : $usd;
        }
      }
      osc_set_preference('member_plans', json_encode($save), 'starter_theme');
      osc_add_flash_ok_message(__('Plan settings saved.', 'starter'), 'admin');
      break;
    case 'member_grant':
      $who = trim((string)Params::getParam('member'));
      $u = ctype_digit($who) ? User::newInstance()->findByPrimaryKey((int)$who) : User::newInstance()->findByEmail($who);
      $plan = (string)Params::getParam('plan_key');
      $days = max(1, min(400, (int)Params::getParam('days')));
      $plans = antique_member_plans();
      if (!$u || (!isset($plans[$plan]) && $plan !== 'pack')) {
        osc_add_flash_error_message(__('Check the member and plan.', 'starter'), 'admin');
        break;
      }
      antique_member_start($u['pk_i_id'], $plan, $days, null, $plan === 'pack' ? antique_member_pack()['slots'] : 0);
      osc_add_flash_ok_message(sprintf(__('%1$s given to %2$s for %3$d days.', 'starter'), $plan === 'pack' ? __('Listing pack', 'starter') : $plans[$plan]['name'], $u['s_email'], $days), 'admin');
      break;
    case 'member_stop':
      $db = antique_pay_db();
      $db->query(sprintf("UPDATE `%s` SET b_cancelled = 1 WHERE pk_i_id = %d", antique_members_table(), (int)Params::getParam('id')));
      osc_add_flash_ok_message(__('Plan stopped.', 'starter'), 'admin');
      break;
  }
  osc_redirect_to($url);
}
osc_add_hook('init_admin', 'antique_member_admin_actions', 1);
