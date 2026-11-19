<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Paid ad promotions through PayHere (payhere.lk), the way big classifieds sites
 * earn: buyers still pay sellers directly; sellers pay AntiqueLion to promote a listing.
 *
 *   Bump up   the listing goes back to the top of "Newest" once a day
 *   Top ad    shown in the "Top ads" strip above search results of its category
 *   Urgent    red "Urgent" flag on the card and the listing page
 *   Featured  "Featured listings" section on the home page (Osclass b_premium)
 *
 * Flow: seller picks a package on Account > Promote (route al-promote) -> an order row
 * is created (t_al_orders, status pending) -> the browser posts a signed form to the
 * PayHere checkout -> PayHere calls notify_url (ajax hook al_payhere_notify) server to
 * server with a signed status -> a paid order starts the promotion (t_al_promotions).
 * The return page only shows the status; it never marks anything paid.
 *
 * Prices are set in USD (Marketplace > Payments) and charged in the visitor's display
 * currency when PayHere supports it, otherwise in USD.
 */

define('ANTIQUE_PAY_SCHEMA', '1');
define('ANTIQUE_PAY_CURRENCIES', 'LKR,USD,GBP,EUR,AUD');   // what PayHere can charge
define('ANTIQUE_PAY_TOP_SLOTS', 3);                         // top ads shown above search results

function antique_orders_table() { return DB_TABLE_PREFIX . 't_al_orders'; }
function antique_promos_table() { return DB_TABLE_PREFIX . 't_al_promotions'; }
function antique_pay_db() { return Item::newInstance()->dao; }
function antique_pay_now() { return date('Y-m-d H:i:s'); }

function antique_pay_schema() {
  if (osc_get_preference('pay_schema', 'starter_theme') == ANTIQUE_PAY_SCHEMA) { return; }
  $db = antique_pay_db();
  if (!$db) { return; }
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_orders_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `s_ref` varchar(40) NOT NULL DEFAULT '',
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `s_product` varchar(20) NOT NULL,
    `i_days` smallint(5) unsigned NOT NULL,
    `d_amount` decimal(12,2) NOT NULL,
    `s_currency` char(3) NOT NULL,
    `d_usd` decimal(12,2) NOT NULL,
    `s_status` varchar(20) NOT NULL DEFAULT 'pending',
    `s_mode` varchar(10) NOT NULL DEFAULT 'sandbox',
    `s_gateway_id` varchar(64) DEFAULT NULL,
    `s_method` varchar(30) DEFAULT NULL,
    `s_message` varchar(255) DEFAULT NULL,
    `dt_created` datetime NOT NULL,
    `dt_paid` datetime DEFAULT NULL,
    `dt_updated` datetime DEFAULT NULL,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_ref` (`s_ref`),
    KEY `idx_user` (`fk_i_user_id`, `dt_created`),
    KEY `idx_status` (`s_status`, `dt_created`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  $ok = $ok && $db->query("CREATE TABLE IF NOT EXISTS `" . antique_promos_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `fk_i_order_id` int(10) unsigned DEFAULT NULL,
    `s_type` varchar(20) NOT NULL,
    `dt_start` datetime NOT NULL,
    `dt_end` datetime NOT NULL,
    `dt_last_bump` datetime DEFAULT NULL,
    `b_cancelled` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_item` (`fk_i_item_id`, `s_type`),
    KEY `idx_active` (`s_type`, `dt_end`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('pay_schema', ANTIQUE_PAY_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_pay_schema', 1);
osc_add_hook('init_admin', 'antique_pay_schema', 1);

/* ---------- Settings ---------- */

function antique_pay_setting($key, $default = '') {
  $v = osc_get_preference('pay_' . $key, 'starter_theme');
  return ($v === '' || $v === null) ? $default : $v;
}

function antique_pay_live() { return antique_pay_setting('mode', 'sandbox') === 'live'; }

function antique_pay_configured() {
  return antique_pay_setting('merchant_id') !== '' && antique_pay_setting('merchant_secret') !== '';
}

/**
 * Promotions offered to the current visitor. In sandbox mode only the test members
 * listed in Marketplace > Payments see them, so the live site can be tested with
 * PayHere's sandbox without other sellers seeing test checkouts.
 */
function antique_pay_enabled() {
  if (antique_pay_setting('enabled', '0') !== '1' || !antique_pay_configured()) { return false; }
  return antique_pay_live() || antique_pay_is_tester();
}

// Test members: user IDs or emails, comma separated
function antique_pay_testers() {
  return array_values(array_filter(array_map(function ($v) { return strtolower(trim($v)); }, explode(',', (string)antique_pay_setting('testers')))));
}

function antique_pay_is_tester() {
  if (!osc_is_web_user_logged_in()) { return false; }
  $list = antique_pay_testers();
  return in_array((string)osc_logged_user_id(), $list, true) || in_array(strtolower((string)osc_logged_user_email()), $list, true);
}

function antique_pay_checkout_url() {
  return antique_pay_live() ? 'https://www.payhere.lk/pay/checkout' : 'https://sandbox.payhere.lk/pay/checkout';
}

/**
 * Promotion products. Prices (USD, per number of days) can be changed in
 * Marketplace > Payments; these are the defaults.
 */
function antique_promo_products() {
  $products = array(
    'bump' => array(
      'name' => __('Bump up', 'starter'),
      'desc' => __('Your listing jumps back to the top of the newest listings every day, as if you had just posted it.', 'starter'),
      'prices' => array(3 => 2, 7 => 4, 15 => 7)
    ),
    'top' => array(
      'name' => __('Top ad', 'starter'),
      'desc' => __('Shown in the Top ads row above the search results of its category, on every results page.', 'starter'),
      'prices' => array(3 => 5, 7 => 10, 15 => 18)
    ),
    'urgent' => array(
      'name' => __('Urgent', 'starter'),
      'desc' => __('A red Urgent flag on your listing tells buyers you want a quick sale.', 'starter'),
      'prices' => array(3 => 2, 7 => 3, 15 => 5)
    ),
    'featured' => array(
      'name' => __('Featured', 'starter'),
      'desc' => __('Shown in Featured listings on the home page, the first thing every visitor sees.', 'starter'),
      'prices' => array(7 => 15, 15 => 25, 30 => 40)
    )
  );
  $saved = json_decode((string)antique_pay_setting('prices'), true);
  if (is_array($saved)) {
    foreach ($products as $key => $p) {
      foreach ($p['prices'] as $days => $usd) {
        if (isset($saved[$key][$days]) && (float)$saved[$key][$days] > 0) {
          $products[$key]['prices'][$days] = (float)$saved[$key][$days];
        }
      }
    }
  }
  return $products;
}

/* ---------- Money ---------- */

// Currency the visitor pays in: their display currency when PayHere supports it
function antique_pay_currency() {
  $code = function_exists('antique_visitor_currency') ? antique_visitor_currency() : 'USD';
  return in_array($code, explode(',', ANTIQUE_PAY_CURRENCIES), true) ? $code : 'USD';
}

/**
 * USD price -> array(amount, currency). Converted prices are rounded to whole units
 * (LKR to the nearest 10) so they read like set prices. Falls back to USD without a rate.
 */
function antique_pay_quote($usd, $currency) {
  if ($currency !== 'USD') {
    $amount = function_exists('antique_convert') ? antique_convert($usd, 'USD', $currency) : null;
    if ($amount !== null && $amount > 0) {
      $amount = $currency === 'LKR' ? max(10, round($amount, -1)) : max(1, round($amount));
      return array((float)$amount, $currency);
    }
  }
  return array(round((float)$usd, 2), 'USD');
}

function antique_pay_money($amount, $currency) {
  return function_exists('antique_money') ? antique_money($amount, $currency) : $currency . ' ' . number_format((float)$amount, 2);
}

function antique_pay_amount_str($amount) {
  return number_format((float)$amount, 2, '.', '');
}

// PayHere checkout hash: MD5(merchant_id + order_id + amount + currency + MD5(secret)), uppercase
function antique_pay_hash($orderRef, $amount, $currency) {
  $secret = strtoupper(md5(antique_pay_setting('merchant_secret')));
  return strtoupper(md5(antique_pay_setting('merchant_id') . $orderRef . antique_pay_amount_str($amount) . $currency . $secret));
}

/* ---------- Orders ---------- */

function antique_order_by_ref($ref) {
  $db = antique_pay_db();
  $res = $db->query("SELECT * FROM `" . antique_orders_table() . "` WHERE s_ref = " . $db->escape((string)$ref) . " LIMIT 1");
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_order_statuses() {
  return array(
    'pending'     => __('Waiting for payment', 'starter'),
    'paid'        => __('Paid', 'starter'),
    'cancelled'   => __('Cancelled', 'starter'),
    'failed'      => __('Payment failed', 'starter'),
    'chargedback' => __('Charged back', 'starter'),
    'refunded'    => __('Refunded', 'starter')
  );
}

function antique_order_status_label($status) {
  $all = antique_order_statuses();
  return isset($all[$status]) ? $all[$status] : $status;
}

/**
 * Whether $uid may promote $item: their own live, unsold listing.
 * Returns '' when allowed, otherwise the reason.
 */
function antique_promo_block_reason($item, $uid) {
  if (!$item || (int)$item['fk_i_user_id'] !== (int)$uid) { return __('You can only promote your own listings.', 'starter'); }
  if (!(int)$item['b_enabled'] || !(int)$item['b_active'] || (int)$item['b_spam']) { return __('This listing is not live, so it cannot be promoted.', 'starter'); }
  if ($item['dt_expiration'] && $item['dt_expiration'] < antique_pay_now() && substr($item['dt_expiration'], 0, 4) < '9000') { return __('This listing has expired. Renew it before promoting it.', 'starter'); }
  $extra = function_exists('starter_item_extra') ? starter_item_extra($item['pk_i_id']) : array('i_sold' => 0);
  if (!empty($extra['i_sold'])) { return __('Sold and reserved listings cannot be promoted.', 'starter'); }
  return '';
}

function antique_promote_url($itemId) {
  return antique_route_url('al-promote', array('item' => (int)$itemId));
}

function antique_payments_url($query = array()) {
  return antique_route_url('al-payments', $query);
}

osc_add_route('al-promote', 'account/promote/?(?:\?.*)?$', 'account/promote/', 'al-promote.php', true, 'custom', 'al-promote', __('Promote your listing', 'starter'));
osc_add_route('al-payments', 'account/payments/?(?:\?.*)?$', 'account/payments/', 'al-payments.php', true, 'custom', 'al-payments', __('Payments', 'starter'));

/**
 * POST hook=al_promote: create the order and send the browser to PayHere.
 */
function antique_promote_post() {
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_login_url()); }
  $uid = (int)osc_logged_user_id();
  $itemId = (int)Params::getParam('item');
  $back = antique_promote_url($itemId);
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  if (!antique_pay_enabled()) {
    antique_msg_reply(false, __('Online payments are not available right now. Please try again later.', 'starter'), $back);
  }
  $item = Item::newInstance()->findByPrimaryKey($itemId);
  $why = antique_promo_block_reason($item, $uid);
  if ($why !== '') { antique_msg_reply(false, $why, osc_user_list_items_url()); }

  $products = antique_promo_products();
  $product = (string)Params::getParam('product');
  $days = (int)Params::getParam('days');
  if (!isset($products[$product]['prices'][$days])) {
    antique_msg_reply(false, __('Please choose a promotion.', 'starter'), $back);
  }
  $usd = antique_promo_price((float)$products[$product]['prices'][$days], $uid);
  $order = antique_pay_create_order($uid, $itemId, $product, $days, $usd);
  if (!$order) { antique_msg_reply(false, __('We could not start the payment. Please try again.', 'starter'), $back); }
  antique_pay_render_checkout($order);
}
osc_add_hook('ajax_al_promote', 'antique_promote_post');

// Promotion price after the seller's membership discount (inc.membership.php)
function antique_promo_price($usd, $uid) {
  $off = function_exists('antique_member_discount') ? antique_member_discount($uid) : 0;
  return $off > 0 ? round($usd * (100 - $off) / 100, 2) : (float)$usd;
}

/**
 * New pending order, priced in the visitor's currency. Returns the order row or null.
 * $itemId is 0 for orders that are not about a listing (memberships).
 */
function antique_pay_create_order($uid, $itemId, $product, $days, $usd) {
  list($amount, $currency) = antique_pay_quote($usd, antique_pay_currency());
  $db = antique_pay_db();
  $ok = $db->query(sprintf("INSERT INTO `%s` (s_ref, fk_i_user_id, fk_i_item_id, s_product, i_days, d_amount, s_currency, d_usd, s_status, s_mode, dt_created)
    VALUES ('', %d, %d, %s, %d, %s, %s, %s, 'pending', %s, %s)",
    antique_orders_table(), (int)$uid, (int)$itemId, $db->escape($product), (int)$days, antique_pay_amount_str($amount), $db->escape($currency),
    antique_pay_amount_str($usd), $db->escape(antique_pay_live() ? 'live' : 'sandbox'), $db->escape(antique_pay_now())));
  $id = $ok ? (int)$db->insertedId() : 0;
  if ($id < 1) { return null; }
  $ref = 'AL' . str_pad($id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(3)));
  $db->query(sprintf("UPDATE `%s` SET s_ref = %s WHERE pk_i_id = %d", antique_orders_table(), $db->escape($ref), $id));
  return antique_order_by_ref($ref);
}

function antique_pay_is_promo($order) {
  return array_key_exists($order['s_product'], antique_promo_products());
}

/**
 * What an order is for, e.g. "Urgent, 7 days: Teak chair" or "Dealer Pro, 3 months".
 */
function antique_pay_order_label($order, $withTitle = true) {
  if (antique_pay_is_promo($order)) {
    $products = antique_promo_products();
    $label = sprintf('%s, %s', $products[$order['s_product']]['name'], sprintf(_n('%d day', '%d days', $order['i_days'], 'starter'), $order['i_days']));
    if ($withTitle) {
      $item = Item::newInstance()->findByPrimaryKey($order['fk_i_item_id']);
      if ($item) { $label .= ': ' . $item['s_title']; }
    }
    return $label;
  }
  return function_exists('antique_member_order_label') ? antique_member_order_label($order) : $order['s_product'];
}

// Start what a paid order bought
function antique_pay_fulfil($order) {
  if (antique_pay_is_promo($order)) {
    antique_promo_start($order['fk_i_item_id'], $order['s_product'], $order['i_days'], $order['pk_i_id']);
  } elseif (function_exists('antique_member_fulfil')) {
    antique_member_fulfil($order);
  }
}

// Stop what a refunded or charged-back order bought
function antique_pay_unfulfil($orderId) {
  antique_promo_cancel_order($orderId);
  if (function_exists('antique_member_cancel_order')) { antique_member_cancel_order($orderId); }
}

// Where the buyer goes after paying
function antique_pay_order_url($order) {
  if (antique_pay_is_promo($order)) {
    $item = Item::newInstance()->findByPrimaryKey($order['fk_i_item_id']);
    return $item ? osc_item_url_from_item($item) : osc_user_list_items_url();
  }
  return function_exists('antique_plans_url') ? antique_plans_url() : antique_payments_url();
}

/**
 * Self-submitting form to the PayHere checkout (PayHere only accepts a browser POST).
 */
function antique_pay_render_checkout($order) {
  $user = User::newInstance()->findByPrimaryKey($order['fk_i_user_id']);
  $name = trim(preg_replace('/\s+/', ' ', (string)$user['s_name']));
  $parts = explode(' ', $name, 2);
  $phone = trim((string)($user['s_phone_mobile'] ?: $user['s_phone_land']));
  $fields = array(
    'merchant_id' => antique_pay_setting('merchant_id'),
    'return_url'  => antique_payments_url(array('order' => $order['s_ref'])),
    'cancel_url'  => antique_payments_url(array('order' => $order['s_ref'], 'cancelled' => 1)),
    'notify_url'  => osc_ajax_hook_url('al_payhere_notify'),
    'order_id'    => $order['s_ref'],
    'items'       => mb_substr(antique_pay_order_label($order), 0, 250),
    'currency'    => $order['s_currency'],
    'amount'      => antique_pay_amount_str($order['d_amount']),
    'first_name'  => $parts[0] !== '' ? $parts[0] : 'AntiqueLion',
    'last_name'   => isset($parts[1]) ? $parts[1] : 'Member',
    'email'       => $user['s_email'],
    'phone'       => $phone !== '' ? $phone : '0000000000',
    'address'     => trim((string)$user['s_address']) !== '' ? $user['s_address'] : 'N/A',
    'city'        => trim((string)$user['s_city']) !== '' ? $user['s_city'] : 'N/A',
    'country'     => trim((string)$user['s_country']) !== '' ? $user['s_country'] : 'N/A',
    'custom_1'    => (string)$order['pk_i_id'],
    'hash'        => antique_pay_hash($order['s_ref'], $order['d_amount'], $order['s_currency'])
  );
  if (ob_get_length()) { ob_clean(); }
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: no-store');
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">'
    . '<title>' . osc_esc_html(__('Redirecting to secure payment', 'starter')) . '</title>'
    . '<style>body{margin:0;display:grid;place-items:center;min-height:100vh;background:#F7F4EE;color:#14213D;font:16px/1.5 Manrope,Arial,sans-serif;text-align:center}main{padding:24px}button{margin-top:14px;padding:12px 22px;border:0;border-radius:4px;background:#14213D;color:#fff;font:inherit;font-weight:700;cursor:pointer}</style></head><body><main>'
    . '<p>' . osc_esc_html(sprintf(__('Taking you to PayHere to pay %s securely...', 'starter'), antique_pay_money($order['d_amount'], $order['s_currency']))) . '</p>'
    . '<form id="al-pay" method="post" action="' . osc_esc_html(antique_pay_checkout_url()) . '">';
  foreach ($fields as $k => $v) {
    echo '<input type="hidden" name="' . $k . '" value="' . osc_esc_html($v) . '">';
  }
  echo '<button type="submit">' . osc_esc_html(__('Continue to payment', 'starter')) . '</button></form></main>'
    . '<script>document.getElementById("al-pay").submit();</script></body></html>';
  exit;
}

/**
 * PayHere server-to-server notification. Signed with the merchant secret; amount and
 * currency must match the order. Safe to receive more than once.
 */
function antique_payhere_notify() {
  if (ob_get_length()) { ob_clean(); }
  $p = function ($k) { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; };
  $merchant = $p('merchant_id');
  $ref = $p('order_id');
  $amount = $p('payhere_amount');
  $currency = $p('payhere_currency');
  $status = $p('status_code');
  $sig = strtoupper($p('md5sig'));

  $expected = strtoupper(md5($merchant . $ref . $amount . $currency . $status . strtoupper(md5(antique_pay_setting('merchant_secret')))));
  if (!antique_pay_configured() || $merchant !== antique_pay_setting('merchant_id') || !hash_equals($expected, $sig)) {
    http_response_code(400);
    exit('bad signature');
  }
  $order = antique_order_by_ref($ref);
  if (!$order) {
    http_response_code(404);
    exit('unknown order');
  }
  if ($currency !== $order['s_currency'] || abs((float)$amount - (float)$order['d_amount']) > 0.001) {
    antique_pay_update($order, $order['s_status'], $p, sprintf('Amount mismatch: got %s %s', $currency, $amount));
    http_response_code(400);
    exit('amount mismatch');
  }

  switch ((int)$status) {
    case 2:
      antique_pay_mark_paid($order, $p);
      break;
    case 0:
      antique_pay_update($order, 'pending', $p);
      break;
    case -1:
      if ($order['s_status'] === 'pending') { antique_pay_update($order, 'cancelled', $p); }
      break;
    case -2:
      if ($order['s_status'] === 'pending') { antique_pay_update($order, 'failed', $p); }
      break;
    case -3:
      antique_pay_update($order, 'chargedback', $p);
      antique_pay_unfulfil($order['pk_i_id']);
      break;
  }
  header('Content-Type: text/plain');
  echo 'OK';
  exit;
}
osc_add_hook('ajax_al_payhere_notify', 'antique_payhere_notify');

function antique_pay_update($order, $status, $p, $note = '') {
  $db = antique_pay_db();
  $db->query(sprintf("UPDATE `%s` SET s_status = %s, s_gateway_id = %s, s_method = %s, s_message = %s, dt_updated = %s WHERE pk_i_id = %d",
    antique_orders_table(), $db->escape($status), $db->escape(mb_substr($p('payment_id'), 0, 64)), $db->escape(mb_substr($p('method'), 0, 30)),
    $db->escape(mb_substr($note !== '' ? $note : $p('status_message'), 0, 255)), $db->escape(antique_pay_now()), (int)$order['pk_i_id']));
}

/**
 * Paid: claim the order (only one request wins), start the promotion, email a receipt.
 */
function antique_pay_mark_paid($order, $p) {
  $db = antique_pay_db();
  $now = antique_pay_now();
  $claim = $db->query(sprintf("UPDATE `%s` SET s_status = 'paid', dt_paid = %s, dt_updated = %s, s_gateway_id = %s, s_method = %s, s_message = %s
    WHERE pk_i_id = %d AND s_status IN ('pending', 'cancelled', 'failed')",
    antique_orders_table(), $db->escape($now), $db->escape($now), $db->escape(mb_substr($p('payment_id'), 0, 64)),
    $db->escape(mb_substr($p('method'), 0, 30)), $db->escape(mb_substr($p('status_message'), 0, 255)), (int)$order['pk_i_id']));
  if (!$claim || $db->affectedRows() < 1) { return; }
  antique_pay_fulfil($order);
  antique_pay_receipt(antique_order_by_ref($order['s_ref']));
}

function antique_pay_receipt($order) {
  $user = User::newInstance()->findByPrimaryKey($order['fk_i_user_id']);
  if (!$user || !function_exists('antique_mail')) { return; }
  $promo = antique_pay_is_promo($order);
  $end = $promo ? antique_promo_end($order['fk_i_item_id'], $order['s_product']) : (function_exists('antique_member_order_end') ? antique_member_order_end($order) : null);
  antique_mail(
    $user['s_email'], $user['s_name'],
    sprintf(__('Payment received: %s', 'starter'), $order['s_ref']),
    $promo ? __('Thank you. Your promotion is live.', 'starter') : __('Thank you. Your purchase is active.', 'starter'),
    array_values(array_filter(array(
      sprintf(__('%s is now active.', 'starter'), '<strong>' . osc_esc_html(antique_pay_order_label($order)) . '</strong>'),
      $end ? sprintf(__('It runs until %s.', 'starter'), osc_esc_html(date('j M Y, H:i', strtotime($end)))) : '',
      sprintf(__('Amount paid: %1$s. Order %2$s, PayHere payment %3$s.', 'starter'), '<strong>' . osc_esc_html(antique_pay_money($order['d_amount'], $order['s_currency'])) . '</strong>', osc_esc_html($order['s_ref']), osc_esc_html((string)$order['s_gateway_id']))
    ))),
    antique_payments_url(), __('See your payments', 'starter')
  );
}

/* ---------- Promotions ---------- */

/**
 * Start (or extend) a promotion. A new one queues after any running one of the same
 * type, so paying twice gives twice the days.
 */
function antique_promo_start($itemId, $type, $days, $orderId = null) {
  $db = antique_pay_db();
  $t = antique_promos_table();
  $itemId = (int)$itemId;
  $now = antique_pay_now();
  $res = $db->query(sprintf("SELECT MAX(dt_end) AS e FROM `%s` WHERE fk_i_item_id = %d AND s_type = %s AND b_cancelled = 0 AND dt_end > %s", $t, $itemId, $db->escape($type), $db->escape($now)));
  $start = ($res && $res->row()['e']) ? $res->row()['e'] : $now;
  $end = date('Y-m-d H:i:s', strtotime($start) + (int)$days * 86400);
  $db->query(sprintf("INSERT INTO `%s` (fk_i_item_id, fk_i_order_id, s_type, dt_start, dt_end) VALUES (%d, %s, %s, %s, %s)",
    $t, $itemId, $orderId ? (int)$orderId : 'NULL', $db->escape($type), $db->escape($start), $db->escape($end)));
  antique_promo_apply();
}

function antique_promo_cancel_order($orderId) {
  $db = antique_pay_db();
  $db->query(sprintf("UPDATE `%s` SET b_cancelled = 1 WHERE fk_i_order_id = %d", antique_promos_table(), (int)$orderId));
  antique_promo_apply();
}

// Latest end date of a running or queued promotion
function antique_promo_end($itemId, $type) {
  $db = antique_pay_db();
  $res = $db->query(sprintf("SELECT MAX(dt_end) AS e FROM `%s` WHERE fk_i_item_id = %d AND s_type = %s AND b_cancelled = 0 AND dt_end > %s",
    antique_promos_table(), (int)$itemId, $db->escape($type), $db->escape(antique_pay_now())));
  return $res ? $res->row()['e'] : null;
}

/**
 * Item ID => list of running promotion types (one query per request).
 */
function antique_promo_active_map() {
  static $map = null;
  if ($map !== null) { return $map; }
  $map = array();
  $db = antique_pay_db();
  $now = $db->escape(antique_pay_now());
  $res = $db ? $db->query("SELECT DISTINCT fk_i_item_id, s_type FROM `" . antique_promos_table() . "` WHERE b_cancelled = 0 AND dt_start <= {$now} AND dt_end > {$now}") : false;
  if ($res) {
    foreach ($res->result() as $r) { $map[(int)$r['fk_i_item_id']][] = $r['s_type']; }
  }
  return $map;
}

function antique_promo_has($itemId, $type) {
  $map = antique_promo_active_map();
  return isset($map[(int)$itemId]) && in_array($type, $map[(int)$itemId], true);
}

/**
 * Bring listings in line with running promotions: daily bumps, home-page featured
 * flag on and off. Runs after each payment and from the cron.
 */
function antique_promo_apply() {
  $db = antique_pay_db();
  $p = DB_TABLE_PREFIX;
  $t = antique_promos_table();
  $now = $db->escape(antique_pay_now());
  $running = "b_cancelled = 0 AND dt_start <= {$now} AND dt_end > {$now}";

  // Bump: newest date once a day while running
  $res = $db->query("SELECT pk_i_id, fk_i_item_id FROM `{$t}` WHERE s_type = 'bump' AND {$running}
    AND (dt_last_bump IS NULL OR dt_last_bump <= DATE_SUB({$now}, INTERVAL 23 HOUR)) LIMIT 200");
  if ($res) {
    foreach ($res->result() as $r) {
      $db->query("UPDATE `{$p}t_item` SET dt_pub_date = {$now} WHERE pk_i_id = " . (int)$r['fk_i_item_id']);
      $db->query("UPDATE `{$t}` SET dt_last_bump = {$now} WHERE pk_i_id = " . (int)$r['pk_i_id']);
    }
  }

  // Featured: Osclass premium flag. Only listings that ever had a paid featured
  // promotion are switched off, so premiums set by hand in oc-admin stay.
  $db->query("UPDATE `{$p}t_item` SET b_premium = 1 WHERE b_premium = 0 AND pk_i_id IN (SELECT fk_i_item_id FROM `{$t}` WHERE s_type = 'featured' AND {$running})");
  $db->query("UPDATE `{$p}t_item` SET b_premium = 0 WHERE b_premium = 1
    AND pk_i_id IN (SELECT fk_i_item_id FROM `{$t}` WHERE s_type = 'featured')
    AND pk_i_id NOT IN (SELECT fk_i_item_id FROM `{$t}` WHERE s_type = 'featured' AND {$running})");
}

function antique_promo_job() {
  if (!antique_job_due('promotions', 900)) { return; }
  antique_promo_apply();
  // abandoned checkouts
  $db = antique_pay_db();
  $db->query("UPDATE `" . antique_orders_table() . "` SET s_status = 'cancelled', s_message = 'Not paid within 24 hours', dt_updated = " . $db->escape(antique_pay_now()) . "
    WHERE s_status = 'pending' AND dt_created < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}
osc_add_hook('init', 'antique_promo_job');
osc_add_hook('cron', 'antique_promo_job');

function antique_promo_delete_item($itemId) {
  $db = antique_pay_db();
  if ($db) { $db->query("UPDATE `" . antique_promos_table() . "` SET b_cancelled = 1 WHERE fk_i_item_id = " . (int)$itemId); }
}
osc_add_hook('delete_item', 'antique_promo_delete_item');

/* ---------- Display ---------- */

/**
 * Render listing cards for item rows without disturbing the page's own item loop.
 */
function antique_render_cards($rows) {
  if (empty($rows)) { return; }
  $view = View::newInstance();
  $saved = $view->_exists('items') ? $view->_get('items') : array();
  $savedItem = $view->_exists('item') ? $view->_get('item') : null;
  $savedLoop = $view->_exists('itemLoop') ? $view->_get('itemLoop') : '';
  $view->_exportVariableToView('items', $rows);
  $view->_exportVariableToView('itemLoop', '');
  while (osc_has_items()) { include __DIR__ . '/inc.al-card.php'; }
  $view->_exportVariableToView('items', $saved);
  $view->_reset('items');
  $view->_exportVariableToView('item', $savedItem);
  $view->_exportVariableToView('itemLoop', $savedLoop);
}

/**
 * Live listings running promotion $type, random order. $categories limits them to
 * these category IDs (a category and its subcategories).
 */
function antique_promo_items($type, $limit, $categories = array()) {
  $db = antique_pay_db();
  $p = DB_TABLE_PREFIX;
  $now = $db->escape(antique_pay_now());
  $cat = $categories ? ' AND i.fk_i_category_id IN (' . implode(',', array_map('intval', $categories)) . ')' : '';
  $res = $db->query("SELECT i.* FROM `{$p}t_item` i
    JOIN (SELECT DISTINCT fk_i_item_id FROM `" . antique_promos_table() . "` WHERE s_type = " . $db->escape($type) . " AND b_cancelled = 0 AND dt_start <= {$now} AND dt_end > {$now}) pr ON pr.fk_i_item_id = i.pk_i_id
    LEFT JOIN `{$p}t_item_starter` s ON s.fk_i_item_id = i.pk_i_id
    WHERE i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0 AND (i.dt_expiration IS NULL OR i.dt_expiration >= {$now})
      AND COALESCE(s.i_sold, '') NOT IN ('1', '2'){$cat}
    ORDER BY RAND() LIMIT " . (int)$limit);
  return $res ? Item::newInstance()->extendData($res->result()) : array();
}

// Home page "Featured listings": live premium listings (paid Featured sets b_premium)
function antique_featured_items($limit) {
  $db = antique_pay_db();
  $p = DB_TABLE_PREFIX;
  $now = $db->escape(antique_pay_now());
  $res = $db->query("SELECT i.* FROM `{$p}t_item` i LEFT JOIN `{$p}t_item_starter` s ON s.fk_i_item_id = i.pk_i_id
    WHERE i.b_premium = 1 AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
      AND (i.dt_expiration IS NULL OR i.dt_expiration >= {$now}) AND COALESCE(s.i_sold, '') NOT IN ('1', '2')
    ORDER BY RAND() LIMIT " . max(1, (int)$limit));
  return $res ? Item::newInstance()->extendData($res->result()) : array();
}

// "Top ads" row above search results
function antique_promo_search_top() {
  $cats = array();
  $sel = osc_search_category_id();
  if (!empty($sel[0])) {
    $cats[] = (int)$sel[0];
    foreach (Category::newInstance()->findSubcategories((int)$sel[0]) as $s) { $cats[] = (int)$s['pk_i_id']; }
  }
  $rows = antique_promo_items('top', ANTIQUE_PAY_TOP_SLOTS, $cats);
  if (empty($rows)) { return; }
  echo '<section class="al-top-ads" aria-labelledby="al-top-ads-h"><div class="al-top-ads-head"><h2 id="al-top-ads-h">' . osc_esc_html(__('Top ads', 'starter')) . '</h2>';
  if (osc_is_web_user_logged_in()) {
    echo '<a href="' . osc_esc_html(osc_user_list_items_url()) . '">' . osc_esc_html(__('Promote your listing', 'starter')) . '</a>';
  }
  echo '</div><div class="al-grid">';
  antique_render_cards($rows);
  echo '</div></section>';
}
osc_add_hook('search_items_top', 'antique_promo_search_top');

/**
 * Small flags for a card or listing page ("Urgent", "Top ad").
 */
function antique_promo_flags($itemId, $context = 'card') {
  $html = '';
  if (antique_promo_has($itemId, 'urgent')) { $html .= '<span class="al-flag is-urgent">' . osc_esc_html(__('Urgent', 'starter')) . '</span>'; }
  if ($context == 'card' && antique_promo_has($itemId, 'top')) { $html .= '<span class="al-flag is-top">' . osc_esc_html(__('Top ad', 'starter')) . '</span>'; }
  if ($context == 'card' && antique_promo_has($itemId, 'featured')) { $html .= '<span class="al-flag is-featured">' . osc_esc_html(__('Featured', 'starter')) . '</span>'; }
  return $html !== '' ? '<span class="al-flags">' . $html . '</span>' : '';
}

/**
 * "Promote" panel on the owner's listing page.
 */
function antique_promo_owner_box($itemId) {
  if (!antique_pay_enabled()) { return ''; }
  $map = antique_promo_active_map();
  $running = isset($map[(int)$itemId]) ? $map[(int)$itemId] : array();
  $products = antique_promo_products();
  $html = '<div class="al-promo-box">';
  if ($running) {
    $names = array();
    foreach ($running as $type) {
      $end = antique_promo_end($itemId, $type);
      $names[] = $products[$type]['name'] . ($end ? ' ' . sprintf(__('until %s', 'starter'), date('j M', strtotime($end))) : '');
    }
    $html .= '<p class="al-promo-running">' . osc_esc_html(sprintf(__('Running: %s', 'starter'), implode(', ', $names))) . '</p>';
  } else {
    $html .= '<p>' . osc_esc_html(__('Get more buyers to see this listing.', 'starter')) . '</p>';
  }
  $html .= '<a class="al-btn al-btn-brass" href="' . osc_esc_html(antique_promote_url($itemId)) . '">' . osc_esc_html(__('Promote this listing', 'starter')) . '</a></div>';
  return $html;
}

/* ---------- Admin (Marketplace > Payments) ---------- */

function antique_pay_admin_actions() {
  $act = Params::getParam('al_mk');
  if (!in_array($act, array('pay_save', 'pay_grant', 'pay_mark'), true) || !osc_is_admin_user_logged_in()) { return; }
  $url = antique_marketplace_admin_url('payments');
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    osc_add_flash_error_message(__('The page expired. Please try again.', 'starter'), 'admin');
    osc_redirect_to($url);
  }
  switch ($act) {
    case 'pay_save':
      $mid = preg_replace('/[^0-9]/', '', (string)Params::getParam('pay_merchant_id'));
      $secret = trim((string)Params::getParam('pay_merchant_secret', false, false));
      osc_set_preference('pay_enabled', Params::getParam('pay_enabled') ? '1' : '0', 'starter_theme');
      osc_set_preference('pay_mode', Params::getParam('pay_mode') === 'live' ? 'live' : 'sandbox', 'starter_theme');
      osc_set_preference('pay_merchant_id', $mid, 'starter_theme');
      $testers = array_filter(array_map('trim', explode(',', strip_tags((string)Params::getParam('pay_testers')))));
      osc_set_preference('pay_testers', implode(', ', array_slice($testers, 0, 20)), 'starter_theme');
      // empty field keeps the saved secret (it is never printed back into the page)
      if ($secret !== '') { osc_set_preference('pay_merchant_secret', $secret, 'starter_theme'); }
      $prices = array();
      $posted = (array)Params::getParam('price');
      foreach (antique_promo_products() as $key => $p) {
        foreach ($p['prices'] as $days => $usd) {
          $v = isset($posted[$key][$days]) ? (float)$posted[$key][$days] : $usd;
          $prices[$key][$days] = $v > 0 ? round($v, 2) : $usd;
        }
      }
      osc_set_preference('pay_prices', json_encode($prices), 'starter_theme');
      osc_add_flash_ok_message(__('Payment settings saved.', 'starter'), 'admin');
      break;
    case 'pay_grant':
      // free promotion from the admin (goodwill, offline payment)
      $itemId = (int)Params::getParam('item');
      $type = (string)Params::getParam('product');
      $days = max(1, min(90, (int)Params::getParam('days')));
      $products = antique_promo_products();
      if (!isset($products[$type]) || !Item::newInstance()->findByPrimaryKey($itemId)) {
        osc_add_flash_error_message(__('Check the listing ID and promotion.', 'starter'), 'admin');
        break;
      }
      antique_promo_start($itemId, $type, $days);
      osc_add_flash_ok_message(sprintf(__('%1$s started for listing #%2$d.', 'starter'), $products[$type]['name'], $itemId), 'admin');
      break;
    case 'pay_mark':
      // refunded in the PayHere portal: record it and stop the promotion
      $id = (int)Params::getParam('id');
      $db = antique_pay_db();
      $db->query(sprintf("UPDATE `%s` SET s_status = 'refunded', dt_updated = %s WHERE pk_i_id = %d AND s_status = 'paid'", antique_orders_table(), $db->escape(antique_pay_now()), $id));
      if ($db->affectedRows() > 0) { antique_pay_unfulfil($id); }
      osc_add_flash_ok_message(__('Order marked as refunded and its promotion stopped.', 'starter'), 'admin');
      break;
  }
  osc_redirect_to($url);
}
osc_add_hook('init_admin', 'antique_pay_admin_actions', 1);
