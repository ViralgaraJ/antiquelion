<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Make an offer on fixed-price listings. An offer lives in the buyer's conversation
 * with the seller: the seller accepts, declines or counters; either side can counter
 * back; the person who made an offer can withdraw it. Offers expire after 48 hours.
 * Accepting reserves the listing and declines the other open offers on it.
 * Rows live in t_al_offers (created on first use).
 */

define('ANTIQUE_OFFER_SCHEMA', '1');
define('ANTIQUE_OFFER_HOURS', 48);       // an offer stays open this long
define('ANTIQUE_OFFER_FLOOR', 0.5);      // offers below half the asking price are refused
define('ANTIQUE_OFFER_MAX', 5);          // offers per buyer per listing (counters included)

function antique_offers_table() { return DB_TABLE_PREFIX . 't_al_offers'; }

function antique_offer_schema() {
  if (osc_get_preference('offer_schema', 'starter_theme') == ANTIQUE_OFFER_SCHEMA) { return; }
  $ok = antique_msg_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_offers_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_thread_id` int(10) unsigned NOT NULL,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `fk_i_buyer_id` int(10) unsigned NOT NULL,
    `fk_i_seller_id` int(10) unsigned NOT NULL,
    `fk_i_from_id` int(10) unsigned NOT NULL,
    `d_amount` decimal(14,2) NOT NULL,
    `s_currency` varchar(3) NOT NULL,
    `s_status` varchar(10) NOT NULL DEFAULT 'pending',
    `fk_i_parent_id` int(10) unsigned DEFAULT NULL,
    `dt_created` datetime NOT NULL,
    `dt_expires` datetime NOT NULL,
    `dt_responded` datetime DEFAULT NULL,
    PRIMARY KEY (`pk_i_id`),
    KEY `idx_item` (`fk_i_item_id`, `s_status`),
    KEY `idx_thread` (`fk_i_thread_id`),
    KEY `idx_open` (`s_status`, `dt_expires`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if ($ok) { osc_set_preference('offer_schema', ANTIQUE_OFFER_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_offer_schema', 1);

function antique_offer($id) {
  $res = antique_msg_db()->query("SELECT * FROM `" . antique_offers_table() . "` WHERE pk_i_id = " . (int)$id);
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_offer_money($amount, $currency) {
  return antique_money($amount, $currency);
}

/**
 * Can this listing take offers from $uid? Returns '' or the reason it cannot.
 * $uid 0 = a visitor (only the listing is checked).
 */
function antique_offer_block_reason($item, $uid = 0) {
  if (!$item || (int)$item['fk_i_user_id'] <= 0) { return __('This listing does not take offers.', 'starter'); }
  if ($item['b_enabled'] != 1 || $item['b_active'] != 1 || $item['b_spam'] != 0 || osc_isExpired($item['dt_expiration'])) {
    return __('This listing is no longer available.', 'starter');
  }
  if ($item['i_price'] === null || (float)$item['i_price'] <= 0) { return __('This listing does not have a price, so send the seller a message instead.', 'starter'); }
  if (class_exists('ModelBids')) {
    $a = ModelBids::newInstance()->getAuction($item['pk_i_id']);
    if ($a && $a['b_active'] == 1) { return __('This listing is sold by auction. Place a bid instead.', 'starter'); }
  }
  $extra = starter_item_extra($item['pk_i_id']);
  if ((int)$extra['i_sold'] == 1) { return __('This item has been sold.', 'starter'); }
  if ((int)$extra['i_sold'] == 2) { return __('This item is reserved for another buyer.', 'starter'); }
  $det = antique_item_details($item['pk_i_id']);
  if (!(int)$det['b_offers']) { return __('The seller is not taking offers on this listing.', 'starter'); }
  if ($uid > 0 && (int)$item['fk_i_user_id'] == (int)$uid) { return __('This is your own listing.', 'starter'); }
  return '';
}

function antique_offer_open_for($itemId, $buyerId) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_item_id = %d AND fk_i_buyer_id = %d AND s_status = 'pending' AND dt_expires > '%s' ORDER BY pk_i_id DESC LIMIT 1",
    antique_offers_table(), (int)$itemId, (int)$buyerId, antique_msg_now()));
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_offer_accepted_for($itemId, $buyerId) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_item_id = %d AND fk_i_buyer_id = %d AND s_status = 'accepted' LIMIT 1",
    antique_offers_table(), (int)$itemId, (int)$buyerId));
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_offer_count($itemId, $buyerId) {
  $res = antique_msg_db()->query(sprintf("SELECT COUNT(*) AS n FROM `%s` WHERE fk_i_item_id = %d AND fk_i_buyer_id = %d", antique_offers_table(), (int)$itemId, (int)$buyerId));
  return $res ? (int)$res->row()['n'] : 0;
}

function antique_offer_parse_amount($raw) {
  $raw = preg_replace('/[^0-9.,]/', '', (string)$raw);
  // "1,250.50" and "1250,50"
  if (preg_match('/,\d{1,2}$/', $raw) && strpos($raw, '.') === false) { $raw = str_replace(',', '.', $raw); }
  return round((float)str_replace(',', '', $raw), 2);
}

function antique_offer_insert($thread, $item, $fromId, $amount, $parentId = null) {
  $db = antique_msg_db();
  $now = antique_msg_now();
  $db->query(sprintf("INSERT INTO `%s` (fk_i_thread_id, fk_i_item_id, fk_i_buyer_id, fk_i_seller_id, fk_i_from_id, d_amount, s_currency, s_status, fk_i_parent_id, dt_created, dt_expires)
    VALUES (%d, %d, %d, %d, %d, %s, %s, 'pending', %s, '%s', '%s')",
    antique_offers_table(), (int)$thread['pk_i_id'], (int)$item['pk_i_id'], (int)$thread['fk_i_buyer_id'], (int)$thread['fk_i_seller_id'], (int)$fromId,
    number_format($amount, 2, '.', ''), $db->escape($item['fk_c_currency_code']), $parentId ? (int)$parentId : 'NULL', $now, date('Y-m-d H:i:s', time() + ANTIQUE_OFFER_HOURS * 3600)));
  return (int)$db->insertedId();
}

// Changes an open offer's status; false when someone else changed it first
function antique_offer_set_status($offerId, $status) {
  $db = antique_msg_db();
  $db->query(sprintf("UPDATE `%s` SET s_status = %s, dt_responded = '%s' WHERE pk_i_id = %d AND s_status = 'pending'",
    antique_offers_table(), $db->escape($status), antique_msg_now(), (int)$offerId));
  return $db->affectedRows() > 0;
}

function antique_offer_set_sold_flag($itemId, $value) {
  antique_msg_db()->query(sprintf("INSERT INTO `%st_item_starter` (fk_i_item_id, i_sold) VALUES (%d, '%s') ON DUPLICATE KEY UPDATE i_sold = VALUES(i_sold)",
    DB_TABLE_PREFIX, (int)$itemId, (int)$value));
}

/**
 * Expired offers: closed with a note in the conversation. Runs at most every 5 minutes.
 */
function antique_offers_expire() {
  if (time() - (int)osc_get_preference('offer_expire_run', 'starter_theme') < 300) { return; }
  osc_set_preference('offer_expire_run', time(), 'starter_theme', 'INTEGER');
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE s_status = 'pending' AND dt_expires <= '%s' LIMIT 100", antique_offers_table(), antique_msg_now()));
  if (!$res) { return; }
  foreach ($res->result() as $o) {
    if (antique_offer_set_status($o['pk_i_id'], 'expired')) {
      antique_msg_add($o['fk_i_thread_id'], 0, sprintf(__('The offer of %s expired without a reply.', 'starter'), antique_offer_money($o['d_amount'], $o['s_currency'])), 'event');
    }
  }
}
osc_add_hook('init', 'antique_offers_expire', 9);

function antique_offer_mail($toId, $subject, $heading, $lines, $thread, $cta) {
  $to = antique_msg_user($toId);
  if ($to['email'] == '') { return; }
  antique_mail($to['email'], $to['name'], $subject, $heading, $lines, antique_thread_url($thread['pk_i_id']), $cta);
}

/**
 * POST hook=al_offer: op=make (item, amount, note), accept / decline / withdraw (offer),
 * counter (offer, amount).
 */
function antique_offer_post() {
  $back = antique_msg_back_url(Params::getParam('back'));
  if (!osc_is_web_user_logged_in()) {
    Session::newInstance()->_set('al_after_login', $back);
    osc_add_flash_info_message(__('Sign in or create a free account to make an offer.', 'starter'));
    osc_redirect_to(osc_user_login_url());
  }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  $op = Params::getParam('op');
  $me = antique_msg_user($uid);

  if ($op == 'make') {
    $item = Item::newInstance()->findByPrimaryKey((int)Params::getParam('item'));
    $why = antique_offer_block_reason($item, $uid);
    if ($why !== '') { antique_msg_reply(false, $why, $back); }
    $amount = antique_offer_parse_amount(Params::getParam('amount'));
    $asking = (float)$item['i_price'] / 1000000;
    $cur = $item['fk_c_currency_code'];
    if ($amount <= 0) { antique_msg_reply(false, __('Enter the amount you would like to offer.', 'starter'), $back); }
    if ($amount >= $asking) { antique_msg_reply(false, sprintf(__('Your offer must be below the asking price of %s. To pay the full price, send the seller a message.', 'starter'), antique_offer_money($asking, $cur)), $back); }
    if ($amount < $asking * ANTIQUE_OFFER_FLOOR) { antique_msg_reply(false, sprintf(__('Offers must be at least %s (half the asking price).', 'starter'), antique_offer_money(ceil($asking * ANTIQUE_OFFER_FLOOR), $cur)), $back); }
    if (antique_offer_open_for($item['pk_i_id'], $uid)) { antique_msg_reply(false, __('You already have an open offer on this item. Wait for the seller to reply, or withdraw it first.', 'starter'), $back); }
    if (antique_offer_count($item['pk_i_id'], $uid) >= ANTIQUE_OFFER_MAX) { antique_msg_reply(false, __('You have reached the limit of offers on this item. Send the seller a message instead.', 'starter'), $back); }
    $existing = antique_msg_find_thread($item['pk_i_id'], $uid, $item['fk_i_user_id']);
    $err = antique_msg_rate_error($uid, !$existing);
    if ($err !== '') { antique_msg_reply(false, $err, $back); }

    $thread = antique_msg_open_thread($item['pk_i_id'], $uid, $item['fk_i_user_id']);
    $offerId = antique_offer_insert($thread, $item, $uid, $amount);
    $note = antique_msg_clean(Params::getParam('note'));
    antique_msg_add($thread['pk_i_id'], $uid, sprintf(__('Offer: %s', 'starter'), antique_offer_money($amount, $cur)), 'offer', $offerId, false);
    if ($note !== '') { antique_msg_add($thread['pk_i_id'], $uid, $note, 'text', null, false); }

    // the seller's automatic floor
    $det = antique_item_details($item['pk_i_id']);
    if ($det['d_offer_min'] !== null && (float)$det['d_offer_min'] > 0 && $amount < (float)$det['d_offer_min']) {
      antique_offer_set_status($offerId, 'declined');
      antique_msg_add($thread['pk_i_id'], 0, sprintf(__('The offer of %s was declined automatically because it is below the lowest amount the seller accepts.', 'starter'), antique_offer_money($amount, $cur)), 'event');
      antique_msg_reply(true, __('Your offer was declined automatically: it is below what the seller accepts. You can make a higher offer.', 'starter'), antique_thread_url($thread['pk_i_id']));
    }

    antique_offer_mail($item['fk_i_user_id'], sprintf(__('New offer of %1$s for %2$s', 'starter'), antique_offer_money($amount, $cur), $item['s_title']), __('You have a new offer', 'starter'), array(
      sprintf(__('%1$s offered %2$s for %3$s (your price: %4$s).', 'starter'), '<strong>' . osc_esc_html($me['name']) . '</strong>', '<strong>' . osc_esc_html(antique_offer_money($amount, $cur)) . '</strong>', '<strong>' . osc_esc_html($item['s_title']) . '</strong>', osc_esc_html(antique_offer_money($asking, $cur))),
      $note !== '' ? antique_mail_quote($note) : '',
      sprintf(__('Accept, decline or send a counter-offer within %d hours, after which the offer expires.', 'starter'), ANTIQUE_OFFER_HOURS)
    ), $thread, __('Reply to the offer', 'starter'));
    antique_msg_reply(true, sprintf(__('Offer sent. The seller has %d hours to reply, and we will email you when they do.', 'starter'), ANTIQUE_OFFER_HOURS), antique_thread_url($thread['pk_i_id']));
  }

  // actions on an existing offer
  $offer = antique_offer((int)Params::getParam('offer'));
  $thread = $offer ? antique_msg_thread($offer['fk_i_thread_id']) : null;
  $role = antique_msg_role($thread, $uid);
  if ($role === null) { antique_msg_reply(false, __('This offer is not available.', 'starter'), antique_inbox_url()); }
  $url = antique_thread_url($thread['pk_i_id']);
  if ($offer['s_status'] != 'pending' || strtotime($offer['dt_expires']) <= time()) {
    antique_msg_reply(false, __('This offer is no longer open.', 'starter'), $url);
  }
  $mine = (int)$offer['fk_i_from_id'] === $uid;
  $item = Item::newInstance()->findByPrimaryKey($offer['fk_i_item_id']);
  $title = $item ? $item['s_title'] : __('the item', 'starter');
  $money = antique_offer_money($offer['d_amount'], $offer['s_currency']);
  $otherId = antique_msg_other_id($thread, $uid);

  if ($op == 'withdraw') {
    if (!$mine) { antique_msg_reply(false, __('Only the person who made an offer can withdraw it.', 'starter'), $url); }
    if (antique_offer_set_status($offer['pk_i_id'], 'withdrawn')) {
      antique_msg_add($thread['pk_i_id'], $uid, sprintf(__('%1$s withdrew the offer of %2$s.', 'starter'), $me['name'], $money), 'event');
    }
    antique_msg_reply(true, __('Offer withdrawn.', 'starter'), $url);
  }

  if ($mine) { antique_msg_reply(false, __('Wait for the other person to reply to your offer.', 'starter'), $url); }

  if ($op == 'decline') {
    if (antique_offer_set_status($offer['pk_i_id'], 'declined')) {
      antique_msg_add($thread['pk_i_id'], $uid, sprintf(__('%1$s declined the offer of %2$s.', 'starter'), $me['name'], $money), 'event');
      antique_offer_mail($otherId, sprintf(__('Offer declined: %s', 'starter'), $title), __('Your offer was declined', 'starter'), array(
        sprintf(__('%1$s declined your offer of %2$s for %3$s.', 'starter'), '<strong>' . osc_esc_html($me['name']) . '</strong>', '<strong>' . osc_esc_html($money) . '</strong>', '<strong>' . osc_esc_html($title) . '</strong>'),
        __('You can make a new offer or ask a question in your conversation.', 'starter')
      ), $thread, __('Open the conversation', 'starter'));
    }
    antique_msg_reply(true, __('Offer declined.', 'starter'), $url);
  }

  if ($op == 'counter') {
    $amount = antique_offer_parse_amount(Params::getParam('amount'));
    $asking = $item ? (float)$item['i_price'] / 1000000 : 0;
    if ($amount <= 0) { antique_msg_reply(false, __('Enter the amount for your counter-offer.', 'starter'), $url); }
    if ($asking > 0 && $amount > $asking) { antique_msg_reply(false, sprintf(__('A counter-offer cannot be more than the asking price of %s.', 'starter'), antique_offer_money($asking, $offer['s_currency'])), $url); }
    if (abs($amount - (float)$offer['d_amount']) < 0.005) { antique_msg_reply(false, __('That is the same amount. Accept the offer instead.', 'starter'), $url); }
    if (antique_offer_count($offer['fk_i_item_id'], $thread['fk_i_buyer_id']) >= ANTIQUE_OFFER_MAX) { antique_msg_reply(false, __('This item has reached the limit of offers. Accept, decline or keep talking in messages.', 'starter'), $url); }
    $why = antique_offer_block_reason($item);
    if ($why !== '') { antique_msg_reply(false, $why, $url); }
    if (!antique_offer_set_status($offer['pk_i_id'], 'countered')) { antique_msg_reply(false, __('This offer is no longer open.', 'starter'), $url); }
    $newId = antique_offer_insert($thread, $item, $uid, $amount, $offer['pk_i_id']);
    $newMoney = antique_offer_money($amount, $offer['s_currency']);
    antique_msg_add($thread['pk_i_id'], $uid, sprintf(__('Counter-offer: %s', 'starter'), $newMoney), 'offer', $newId);
    antique_offer_mail($otherId, sprintf(__('Counter-offer of %1$s for %2$s', 'starter'), $newMoney, $title), __('You have a counter-offer', 'starter'), array(
      sprintf(__('%1$s replied to your offer of %2$s with a counter-offer of %3$s for %4$s.', 'starter'), '<strong>' . osc_esc_html($me['name']) . '</strong>', osc_esc_html($money), '<strong>' . osc_esc_html($newMoney) . '</strong>', '<strong>' . osc_esc_html($title) . '</strong>'),
      sprintf(__('Accept, decline or counter within %d hours.', 'starter'), ANTIQUE_OFFER_HOURS)
    ), $thread, __('Reply to the counter-offer', 'starter'));
    antique_msg_reply(true, __('Counter-offer sent.', 'starter'), $url);
  }

  if ($op == 'accept') {
    $why = $item ? antique_offer_block_reason($item) : __('This listing is no longer available.', 'starter');
    if ($why !== '') { antique_msg_reply(false, $why, $url); }
    if (!antique_offer_set_status($offer['pk_i_id'], 'accepted')) { antique_msg_reply(false, __('This offer is no longer open.', 'starter'), $url); }
    antique_offer_set_sold_flag($offer['fk_i_item_id'], 2);   // reserved for this buyer
    antique_msg_add($thread['pk_i_id'], $uid, sprintf(__('%1$s accepted the offer of %2$s. The item is now reserved.', 'starter'), $me['name'], $money), 'event');

    // close the other open offers on this item
    $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_item_id = %d AND s_status = 'pending' AND pk_i_id <> %d", antique_offers_table(), (int)$offer['fk_i_item_id'], (int)$offer['pk_i_id']));
    foreach (($res ? $res->result() : array()) as $o) {
      if (antique_offer_set_status($o['pk_i_id'], 'declined')) {
        antique_msg_add($o['fk_i_thread_id'], 0, sprintf(__('The offer of %s was closed because the seller accepted another offer.', 'starter'), antique_offer_money($o['d_amount'], $o['s_currency'])), 'event');
      }
    }

    $buyerId = (int)$thread['fk_i_buyer_id'];
    $sellerId = (int)$thread['fk_i_seller_id'];
    $seller = antique_msg_user($sellerId);
    $buyer = antique_msg_user($buyerId);
    antique_offer_mail($buyerId, sprintf(__('Offer accepted: %s', 'starter'), $title), __('Your offer was accepted', 'starter'), array(
      sprintf(__('Good news: your offer of %1$s for %2$s was accepted. The item is reserved for you.', 'starter'), '<strong>' . osc_esc_html($money) . '</strong>', '<strong>' . osc_esc_html($title) . '</strong>'),
      sprintf(__('Agree payment and delivery with %s in your conversation. Pay only by a method you trust, and keep the agreement in writing on the site.', 'starter'), osc_esc_html($seller['name'])),
      __('Once the item arrives, please leave a review for the seller. It helps other collectors buy safely.', 'starter')
    ), $thread, __('Arrange payment and delivery', 'starter'));
    antique_offer_mail($sellerId, sprintf(__('You accepted an offer: %s', 'starter'), $title), __('Offer accepted', 'starter'), array(
      sprintf(__('You accepted %1$s\'s offer of %2$s for %3$s. The listing now shows as reserved.', 'starter'), osc_esc_html($buyer['name']), '<strong>' . osc_esc_html($money) . '</strong>', '<strong>' . osc_esc_html($title) . '</strong>'),
      __('Agree payment and delivery in the conversation. When the sale is complete, mark the listing as sold.', 'starter')
    ), $thread, __('Open the conversation', 'starter'));
    antique_msg_reply(true, __('Offer accepted. The item is now reserved for this buyer.', 'starter'), $url);
  }

  antique_msg_reply(false, __('Something went wrong. Please try again.', 'starter'), $url);
}
osc_add_hook('ajax_al_offer', 'antique_offer_post');

/**
 * The offer card shown in a conversation.
 */
function antique_offer_card($offerId, $thread, $uid) {
  $o = antique_offer($offerId);
  if (!$o) { return ''; }
  $item = antique_msg_item($o['fk_i_item_id']);
  $mine = (int)$o['fk_i_from_id'] === (int)$uid;
  $from = antique_msg_user($o['fk_i_from_id']);
  $status = $o['s_status'];
  if ($status == 'pending' && strtotime($o['dt_expires']) <= time()) { $status = 'expired'; }
  $labels = array(
    'pending'   => array(__('Waiting for a reply', 'starter'), 'is-pending'),
    'accepted'  => array(__('Accepted', 'starter'), 'is-good'),
    'declined'  => array(__('Declined', 'starter'), 'is-bad'),
    'countered' => array(__('Countered', 'starter'), 'is-muted'),
    'withdrawn' => array(__('Withdrawn', 'starter'), 'is-muted'),
    'expired'   => array(__('Expired', 'starter'), 'is-muted')
  );
  $l = isset($labels[$status]) ? $labels[$status] : $labels['expired'];
  $isCounter = !empty($o['fk_i_parent_id']);
  $who = $mine ? ($isCounter ? __('Your counter-offer', 'starter') : __('Your offer', 'starter')) : sprintf($isCounter ? __('Counter-offer from %s', 'starter') : __('Offer from %s', 'starter'), $from['name']);
  $asking = $item && $item['i_price'] > 0 ? (float)$item['i_price'] / 1000000 : 0;
  $form = function ($op, $label, $class, $extra = '') use ($o) {
    return '<form method="post" action="' . osc_esc_html(osc_ajax_hook_url('al_offer')) . '">'
      . '<input type="hidden" name="octoken" value="' . osc_csrfguard_generate_token() . '" />'
      . '<input type="hidden" name="op" value="' . $op . '" /><input type="hidden" name="offer" value="' . (int)$o['pk_i_id'] . '" />'
      . $extra . '<button type="submit" class="al-btn ' . $class . '">' . osc_esc_html($label) . '</button></form>';
  };

  $html = '<div class="al-offer-card ' . $l[1] . ($mine ? ' is-mine' : '') . '">';
  $html .= '<div class="al-offer-top"><span class="al-offer-who">' . osc_esc_html($who) . '</span><span class="al-offer-status ' . $l[1] . '">' . osc_esc_html($l[0]) . '</span></div>';
  $html .= '<div class="al-offer-amount">' . osc_esc_html(antique_offer_money($o['d_amount'], $o['s_currency'])) . '</div>';
  if ($asking > 0) {
    $pct = round(100 - ((float)$o['d_amount'] / $asking) * 100);
    $html .= '<div class="al-offer-sub">' . sprintf(__('Asking price %1$s%2$s', 'starter'), osc_esc_html(antique_offer_money($asking, $o['s_currency'])), $pct > 0 ? ' · ' . sprintf(__('%d%% below', 'starter'), $pct) : '') . '</div>';
  }
  if ($status == 'pending') {
    $html .= '<div class="al-offer-sub">' . sprintf(__('Expires in %s', 'starter'), osc_esc_html(antique_offer_time_left(strtotime($o['dt_expires']) - time()))) . '</div>';
    $html .= '<div class="al-offer-actions">';
    if ($mine) {
      $html .= $form('withdraw', __('Withdraw', 'starter'), 'al-btn-outline');
    } else {
      $html .= $form('accept', __('Accept', 'starter'), 'al-btn-brass', '');
      $html .= '<button type="button" class="al-btn al-btn-outline" data-al-counter="' . (int)$o['pk_i_id'] . '">' . osc_esc_html(__('Counter', 'starter')) . '</button>';
      $html .= $form('decline', __('Decline', 'starter'), 'al-btn-danger');
    }
    $html .= '</div>';
    if (!$mine) {
      $html .= '<form class="al-offer-counter" method="post" action="' . osc_esc_html(osc_ajax_hook_url('al_offer')) . '" hidden data-al-counter-form="' . (int)$o['pk_i_id'] . '">'
        . '<input type="hidden" name="octoken" value="' . osc_csrfguard_generate_token() . '" />'
        . '<input type="hidden" name="op" value="counter" /><input type="hidden" name="offer" value="' . (int)$o['pk_i_id'] . '" />'
        . '<label><span>' . osc_esc_html(sprintf(__('Your counter-offer (%s)', 'starter'), $o['s_currency'])) . '</span>'
        . '<input type="text" name="amount" inputmode="decimal" autocomplete="off" required placeholder="' . osc_esc_html(antique_details_num($asking > 0 ? round(((float)$o['d_amount'] + $asking) / 2) : $o['d_amount'])) . '" /></label>'
        . '<button type="submit" class="al-btn al-btn-ink">' . osc_esc_html(__('Send counter-offer', 'starter')) . '</button></form>';
    }
  } elseif ($status == 'accepted') {
    $html .= '<div class="al-offer-sub">' . osc_esc_html(__('Agree payment and delivery below. Keep everything in writing here.', 'starter')) . '</div>';
  }
  return $html . '</div>';
}

function antique_offer_time_left($seconds) {
  $seconds = max(60, (int)$seconds);
  $h = floor($seconds / 3600);
  $m = floor(($seconds % 3600) / 60);
  if ($h >= 24) { return sprintf(_n('%d day', '%d days', floor($h / 24), 'starter'), floor($h / 24)) . ($h % 24 ? ' ' . sprintf(_n('%d hour', '%d hours', $h % 24, 'starter'), $h % 24) : ''); }
  if ($h >= 1) { return sprintf(_n('%d hour', '%d hours', $h, 'starter'), $h) . ($m ? ' ' . sprintf(__('%d min', 'starter'), $m) : ''); }
  return sprintf(__('%d min', 'starter'), $m);
}

/**
 * Item page: "Make an offer" button and dialog (or the buyer's open offer).
 */
function antique_offer_box($item, $inThread = false) {
  $uid = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
  if (antique_offer_block_reason($item, 0) !== '' || ($uid && (int)$item['fk_i_user_id'] == $uid)) { return ''; }
  $cur = $item['fk_c_currency_code'];
  $asking = (float)$item['i_price'] / 1000000;
  if ($uid) {
    $open = antique_offer_open_for($item['pk_i_id'], $uid);
    if ($open) {
      if ($inThread) { return ''; }   // the offer card is already in the conversation
      return '<a class="al-offer-pending" href="' . antique_thread_url($open['fk_i_thread_id']) . '">'
        . sprintf((int)$open['fk_i_from_id'] === $uid ? __('Your offer of %s is waiting for the seller', 'starter') : __('The seller sent you a counter-offer of %s', 'starter'), '<strong>' . osc_esc_html(antique_offer_money($open['d_amount'], $cur)) . '</strong>')
        . ' &rarr;</a>';
    }
  }
  $btn = '<button type="button" class="al-btn al-btn-outline al-btn-block al-offer-btn" data-al-offer-open>'
    . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>'
    . osc_esc_html(__('Make an offer', 'starter')) . '</button>';
  if (!$uid) {
    return '<form method="post" action="' . osc_esc_html(osc_ajax_hook_url('al_offer')) . '" class="al-offer-guest"><input type="hidden" name="op" value="make" /><input type="hidden" name="back" value="' . osc_esc_html(osc_item_url_from_item($item)) . '" />'
      . str_replace(array('type="button"', ' data-al-offer-open'), array('type="submit"', ''), $btn) . '</form>';
  }
  $sugg = array();
  foreach (array(0.9, 0.85, 0.8) as $f) {
    $v = $asking >= 100 ? round($asking * $f / 5) * 5 : round($asking * $f);
    if ($v > 0 && $v < $asking && $v >= $asking * ANTIQUE_OFFER_FLOOR) { $sugg[] = $v; }
  }
  ob_start(); ?>
  <?php echo $btn; ?>
  <dialog class="al-dialog al-offer-dialog" aria-labelledby="al-offer-title">
    <form method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_offer')); ?>" class="al-form">
      <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
      <input type="hidden" name="op" value="make" />
      <input type="hidden" name="item" value="<?php echo (int)$item['pk_i_id']; ?>" />
      <input type="hidden" name="back" value="<?php echo osc_esc_html(osc_item_url_from_item($item)); ?>" />
      <div class="al-dialog-head">
        <h2 id="al-offer-title"><?php _e('Make an offer', 'starter'); ?></h2>
        <button type="button" class="al-dialog-close" data-al-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'starter')); ?>">&times;</button>
      </div>
      <p class="al-offer-asking"><?php echo sprintf(__('Asking price: %s', 'starter'), '<strong>' . osc_esc_html(antique_offer_money($asking, $cur)) . '</strong>'); ?></p>
      <label class="al-form-full"><span><?php echo sprintf(__('Your offer (%s)', 'starter'), osc_esc_html($cur)); ?></span>
        <span class="al-offer-input"><em><?php echo osc_esc_html($cur); ?></em><input type="text" name="amount" inputmode="decimal" autocomplete="off" required /></span>
      </label>
      <?php if ($sugg) { ?>
        <div class="al-offer-sugg">
          <?php foreach ($sugg as $v) { ?><button type="button" class="al-chip" data-al-amount="<?php echo $v; ?>"><?php echo osc_esc_html(antique_offer_money($v, $cur)); ?></button><?php } ?>
        </div>
      <?php } ?>
      <label class="al-form-full"><span><?php _e('Message to the seller', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
        <textarea name="note" rows="3" maxlength="<?php echo ANTIQUE_MSG_MAX; ?>" placeholder="<?php echo osc_esc_html(__('e.g. I can collect this weekend.', 'starter')); ?>"></textarea>
      </label>
      <ul class="al-offer-rules">
        <li><?php echo sprintf(__('The seller has %d hours to accept, decline or counter.', 'starter'), ANTIQUE_OFFER_HOURS); ?></li>
        <li><?php _e('If the seller accepts, you agree to buy at that price. Payment and delivery are agreed in your conversation.', 'starter'); ?></li>
      </ul>
      <button type="submit" class="al-btn al-btn-brass al-btn-block"><?php _e('Send offer', 'starter'); ?></button>
    </form>
  </dialog>
  <script>
    (function () {
      var d = document.querySelector('.al-offer-dialog'), open = document.querySelector('[data-al-offer-open]');
      if (!d || !open || typeof d.showModal !== 'function') { return; }
      var input = d.querySelector('input[name="amount"]');
      open.addEventListener('click', function () { d.showModal(); setTimeout(function () { input.focus(); }, 50); });
      d.querySelector('[data-al-dialog-close]').addEventListener('click', function () { d.close(); });
      d.addEventListener('click', function (e) { if (e.target === d) { d.close(); } });
      d.querySelectorAll('[data-al-amount]').forEach(function (b) {
        b.addEventListener('click', function () { input.value = b.getAttribute('data-al-amount'); input.focus(); });
      });
    })();
  </script>
  <?php return ob_get_clean();
}
