<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Buyer and seller accounts, as on the big marketplaces:
 * - everyone signs up as a buyer (name, email, password);
 * - "Start selling" turns the account into a seller account once (seller type,
 *   phone, location, about, seller terms), after which the person can switch
 *   between Buying (bids, watchlist, saved searches) and Selling (listings);
 * - posting a listing needs a seller account; visitors and buyers are guided there.
 * Sellers are rows in t_al_sellers (created on first use; anyone who already
 * has listings becomes a seller automatically).
 */

define('ANTIQUE_SELLER_SCHEMA', '1');

function antique_sellers_table() {
  return DB_TABLE_PREFIX . 't_al_sellers';
}

function antique_seller_schema() {
  if (osc_get_preference('seller_schema', 'starter_theme') == ANTIQUE_SELLER_SCHEMA) { return; }
  $dao = Item::newInstance()->dao;
  $ok = $dao->query("CREATE TABLE IF NOT EXISTS `" . antique_sellers_table() . "` (
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_business` varchar(100) DEFAULT NULL,
    `s_country` char(2) DEFAULT NULL,
    `dt_started` datetime NOT NULL,
    PRIMARY KEY (`fk_i_user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if ($ok) {
    // people who already sell keep selling
    $dao->query("INSERT IGNORE INTO `" . antique_sellers_table() . "` (fk_i_user_id, dt_started)
      SELECT fk_i_user_id, MIN(dt_pub_date) FROM `" . DB_TABLE_PREFIX . "t_item` WHERE fk_i_user_id IS NOT NULL AND fk_i_user_id > 0 GROUP BY fk_i_user_id");
    osc_set_preference('seller_schema', ANTIQUE_SELLER_SCHEMA, 'starter_theme');
  }
}
osc_add_hook('init', 'antique_seller_schema', 1);

function antique_seller_row($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if ($uid <= 0) { return null; }
  if (!array_key_exists($uid, $cache)) {
    $res = Item::newInstance()->dao->query("SELECT * FROM `" . antique_sellers_table() . "` WHERE fk_i_user_id = " . $uid);
    $cache[$uid] = ($res && $res->numRows() > 0) ? $res->row() : null;
  }
  return $cache[$uid];
}

function antique_is_seller($uid = null) {
  if ($uid === null) {
    if (!osc_is_web_user_logged_in()) { return false; }
    $uid = osc_logged_user_id();
  }
  return antique_seller_row($uid) !== null;
}

/**
 * The name buyers see: a dealer's business name, otherwise the person's name.
 */
function antique_seller_display_name($uid, $fallback) {
  $row = antique_seller_row($uid);
  return ($row && trim((string)$row['s_business']) !== '') ? $row['s_business'] : $fallback;
}

function antique_seller_setup_url() {
  return osc_route_url('al-sell');
}

function antique_buying_url() {
  return osc_route_url('al-buying');
}

// Account > Start selling, Account > Buying (signed-in only)
osc_add_route('al-sell', 'account/start-selling/?(?:\?.*)?$', 'account/start-selling/', 'al-sell.php', true, 'custom', 'al-sell', __('Start selling', 'starter'));
osc_add_route('al-buying', 'account/buying/?(?:\?.*)?$', 'account/buying/', 'al-buying.php', true, 'custom', 'al-buying', __('Buying', 'starter'));

/**
 * 'buying' or 'selling'. Seller pages are always selling and buyer pages always
 * buying; elsewhere the last mode the person chose (cookie). Buyers are always buying.
 */
function antique_account_mode() {
  if (!antique_is_seller()) { return 'buying'; }
  $loc = osc_get_osclass_location();
  $sec = osc_get_osclass_section();
  $route = Params::getParam('route');
  if (($loc == 'user' && in_array($sec, array('items', 'dashboard'))) || ($loc == 'item' && in_array($sec, array('item_add', 'item_edit'))) || in_array($route, array('al-verify', 'al-shop'))) { return 'selling'; }
  if (in_array($route, array('al-buying', 'al-watchlist')) || ($loc == 'user' && $sec == 'alerts')) { return 'buying'; }
  return (isset($_COOKIE['al_mode']) && $_COOKIE['al_mode'] == 'buying') ? 'buying' : 'selling';
}

function antique_mode_url($mode) {
  return osc_base_url(true) . '?al_mode=' . ($mode == 'selling' ? 'selling' : 'buying');
}

/**
 * Runs on every request (before any output):
 * - ?al_mode=buying|selling switches mode and opens that side's home page;
 * - posting a listing needs a signed-in seller;
 * - My account opens the side the person last used.
 */
function antique_seller_router() {
  $mode = Params::getParam('al_mode');
  if (in_array($mode, array('buying', 'selling'), true) && osc_is_web_user_logged_in()) {
    setcookie('al_mode', $mode, time() + 365 * 86400, '/');
    $_COOKIE['al_mode'] = $mode;
    if ($mode == 'selling' && !antique_is_seller()) {
      osc_redirect_to(antique_seller_setup_url());
    }
    osc_redirect_to($mode == 'selling' ? osc_user_list_items_url() : antique_buying_url());
  }

  $loginBack = html_entity_decode((string)Params::getParam('al_login_back'), ENT_QUOTES, 'UTF-8');
  if ($loginBack !== '' && strpos($loginBack, osc_base_url()) === 0) {
    if (osc_is_web_user_logged_in()) { osc_redirect_to($loginBack); }
    Session::newInstance()->_set('al_after_login', $loginBack);
    osc_redirect_to(osc_user_login_url());
  }

  $page = Params::getParam('page');
  $action = Params::getParam('action');
  if ($page == 'custom' && Params::getParam('route') == 'al-sell' && antique_is_seller()) {
    osc_redirect_to(osc_item_post_url());
  }
  if ($page == 'item' && in_array($action, array('item_add', 'item_add_post'), true)) {
    if (!osc_is_web_user_logged_in()) {
      Session::newInstance()->_set('al_after_login', osc_item_post_url());
      osc_add_flash_info_message(__('Sign in or create a free account to start selling.', 'starter'));
      osc_redirect_to(osc_user_login_url());
    }
    if (!antique_is_seller()) {
      osc_redirect_to(antique_seller_setup_url());
    }
  }

  if ($page == 'user' && $action == 'dashboard' && osc_is_web_user_logged_in()) {
    $last = isset($_COOKIE['al_mode']) ? $_COOKIE['al_mode'] : 'selling';
    osc_redirect_to(antique_is_seller() && $last != 'buying' ? osc_user_list_items_url() : antique_buying_url());
  }
}
osc_add_hook('init', 'antique_seller_router', 2);

// Sign-in link that returns to $url afterwards
function antique_login_back_url($url) {
  return osc_base_url(true) . '?al_login_back=' . rawurlencode($url);
}

// After signing in, go back to what the person was doing (e.g. "Sell an item")
function antique_after_login_redirect($url) {
  $next = Session::newInstance()->_get('al_after_login');
  if ($next != '' && strpos($next, osc_base_url()) === 0) {
    Session::newInstance()->_drop('al_after_login');
    return $next;
  }
  return $url;
}
osc_add_filter('correct_login_url_redirect', 'antique_after_login_redirect');

/**
 * Start selling form handler (POST to index.php?page=ajax&action=runhook&hook=al_seller_setup).
 */
function antique_seller_setup_post() {
  $back = antique_seller_setup_url();
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_login_url()); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    osc_add_flash_error_message(__('Your session expired. Please try again.', 'starter'));
    osc_redirect_to($back);
  }
  $uid = (int)osc_logged_user_id();
  $type = Params::getParam('al_type') == 'dealer' ? 1 : 0;
  $business = trim(mb_substr(strip_tags((string)Params::getParam('al_business')), 0, 100));
  $phone = preg_replace('/[^0-9+]/', '', (string)Params::getParam('s_phone_mobile'));
  $country = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)Params::getParam('al_country')), 0, 2));
  $city = trim(mb_substr(strip_tags((string)Params::getParam('al_city')), 0, 60));
  $about = trim(mb_substr(strip_tags((string)Params::getParam('al_about')), 0, 1500));
  $countries = antique_details_countries();

  $errors = array();
  if (!preg_match('/^\+[1-9][0-9]{6,14}$/', $phone)) { $errors[] = __('Enter a mobile number with its country code, for example +44 7400 123456.', 'starter'); }
  if (!isset($countries[$country])) { $errors[] = __('Choose the country you sell from.', 'starter'); }
  if ($city === '') { $errors[] = __('Enter your town or city.', 'starter'); }
  if ($type == 1 && $business === '') { $errors[] = __('Enter your business name.', 'starter'); }
  if (Params::getParam('al_terms') != '1') { $errors[] = __('Please accept the seller rules to continue.', 'starter'); }
  if ($errors) {
    Session::newInstance()->_set('al_seller_form', array('type' => $type ? 'dealer' : 'private', 'business' => $business, 'phone' => $phone, 'country' => $country, 'city' => $city, 'about' => $about));
    osc_add_flash_error_message(implode('<br>', array_map('osc_esc_html', $errors)));
    osc_redirect_to($back);
  }

  $fields = array(
    'b_company'      => $type,
    's_phone_mobile' => $phone,
    's_country'      => $countries[$country],
    's_city'         => $city
  );
  // the user table only accepts countries set up in oc-admin (foreign key)
  $known = Country::newInstance()->findByCode($country);
  $fields['fk_c_country_code'] = !empty($known['pk_c_code']) ? $known['pk_c_code'] : null;
  User::newInstance()->update($fields, array('pk_i_id' => $uid));
  if ($about !== '') {
    User::newInstance()->updateDescription($uid, osc_current_user_locale(), $about);
  }
  $dao = Item::newInstance()->dao;
  $dao->query("INSERT INTO `" . antique_sellers_table() . "` (fk_i_user_id, s_business, s_country, dt_started) VALUES (" . $uid . ", " . $dao->escape($type ? $business : '') . ", " . $dao->escape($country) . ", '" . date('Y-m-d H:i:s') . "')
    ON DUPLICATE KEY UPDATE s_business = VALUES(s_business), s_country = VALUES(s_country)");
  Session::newInstance()->_drop('al_seller_form');
  setcookie('al_mode', 'selling', time() + 365 * 86400, '/');

  osc_add_flash_ok_message(__('Your seller account is ready. List your first item below.', 'starter'));
  osc_redirect_to(osc_item_post_url());
}
osc_add_hook('ajax_al_seller_setup', 'antique_seller_setup_post');

/**
 * Auctions the signed-in person bid on, with their position: leading, outbid, won,
 * not won. Newest ending first.
 */
function antique_my_bids() {
  if (!osc_is_web_user_logged_in() || !class_exists('ModelBids')) { return array(); }
  $uid = (int)osc_logged_user_id();
  $p = DB_TABLE_PREFIX;
  $model = ModelBids::newInstance();
  $res = $model->dao->query("SELECT a.fk_i_item_id, a.dt_end_date, a.d_reserve_price,
      MAX(b.d_amount) AS my_bid, SUM(b.s_status = 'won') AS won
    FROM `{$p}t_item_auctions` a
    JOIN `{$p}t_item_bids` b ON b.fk_i_item_id = a.fk_i_item_id AND b.fk_i_user_id = {$uid}
    JOIN `{$p}t_item` i ON i.pk_i_id = a.fk_i_item_id
    GROUP BY a.fk_i_item_id, a.dt_end_date, a.d_reserve_price
    ORDER BY (a.dt_end_date > NOW()) DESC, a.dt_end_date ASC");
  $out = array();
  if (!$res) { return $out; }
  foreach ($res->result() as $r) {
    $id = (int)$r['fk_i_item_id'];
    $live = strtotime($r['dt_end_date']) > time();
    $high = $model->getHighestBid($id);
    if (!$high) {
      // finished auctions: the winning bid is no longer "active"
      $w = $model->dao->query("SELECT fk_i_user_id, d_amount FROM `{$p}t_item_bids` WHERE fk_i_item_id = {$id} AND s_status = 'won' LIMIT 1");
      $high = ($w && $w->numRows() > 0) ? $w->row() : null;
    }
    $leading = $high && (int)$high['fk_i_user_id'] == $uid;
    if ($live) {
      $status = $leading ? 'leading' : 'outbid';
    } else {
      $status = ((int)$r['won'] > 0) ? 'won' : 'lost';
    }
    $out[$id] = array(
      'status'  => $status,
      'live'    => $live,
      'end'     => $r['dt_end_date'],
      'current' => $high ? (float)$high['d_amount'] : 0,
      'my_max'  => max((float)$r['my_bid'], $model->getProxyMax($id, $uid))
    );
  }
  return $out;
}
