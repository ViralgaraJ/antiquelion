<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Seller reviews. Only buyers who really dealt with a seller can review them, once per
 * listing: the winner of an auction, a buyer whose offer was accepted, or a buyer who
 * talked with the seller about the listing (both wrote). Each review shows which of
 * these it is ("Won at auction", "Offer accepted", "Contacted the seller").
 * Sellers can answer publicly once per review; the site team can hide reviews.
 * Rows live in t_al_reviews (created on first use).
 */

define('ANTIQUE_REVIEW_SCHEMA', '1');
define('ANTIQUE_REVIEW_MIN', 10);       // characters
define('ANTIQUE_REVIEW_MAX', 1500);
define('ANTIQUE_REVIEW_EDIT_DAYS', 60); // a buyer can change their review this long

function antique_reviews_table() { return DB_TABLE_PREFIX . 't_al_reviews'; }

function antique_review_schema() {
  if (osc_get_preference('review_schema', 'starter_theme') == ANTIQUE_REVIEW_SCHEMA) { return; }
  $ok = antique_msg_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_reviews_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_seller_id` int(10) unsigned NOT NULL,
    `fk_i_buyer_id` int(10) unsigned NOT NULL,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `s_item_title` varchar(200) NOT NULL DEFAULT '',
    `i_rating` tinyint(1) unsigned NOT NULL,
    `s_body` text NOT NULL,
    `s_basis` varchar(10) NOT NULL,
    `dt_created` datetime NOT NULL,
    `dt_updated` datetime DEFAULT NULL,
    `s_reply` text,
    `dt_reply` datetime DEFAULT NULL,
    `b_hidden` tinyint(1) NOT NULL DEFAULT 0,
    `b_reported` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_i_id`),
    UNIQUE KEY `uq_review` (`fk_i_buyer_id`, `fk_i_seller_id`, `fk_i_item_id`),
    KEY `idx_seller` (`fk_i_seller_id`, `b_hidden`, `dt_created`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if ($ok) { osc_set_preference('review_schema', ANTIQUE_REVIEW_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_review_schema', 1);

// Account > Leave a review (?item=ID)
osc_add_route('al-review', 'account/review/?$', 'account/review/', 'al-review.php', true, 'custom', 'al-review', __('Leave a review', 'starter'));

function antique_review_url($itemId) {
  return osc_route_url('al-review', array('item' => (int)$itemId));
}

function antique_review_basis_labels() {
  return array(
    'auction' => __('Won at auction', 'starter'),
    'offer'   => __('Offer accepted', 'starter'),
    'message' => __('Contacted the seller', 'starter')
  );
}

/**
 * Why $buyerId may review the seller of $itemId: 'auction', 'offer', 'message' or ''.
 */
function antique_review_basis($buyerId, $itemId) {
  $buyerId = (int)$buyerId;
  $itemId = (int)$itemId;
  $item = $itemId > 0 ? Item::newInstance()->findByPrimaryKey($itemId) : null;
  if (!$item || (int)$item['fk_i_user_id'] <= 0 || (int)$item['fk_i_user_id'] === $buyerId || $buyerId <= 0) { return ''; }
  $sellerId = (int)$item['fk_i_user_id'];
  $db = antique_msg_db();
  $p = DB_TABLE_PREFIX;
  $res = $db->query("SELECT 1 FROM `{$p}t_item_bids` WHERE fk_i_item_id = {$itemId} AND fk_i_user_id = {$buyerId} AND s_status = 'won' LIMIT 1");
  if ($res && $res->numRows() > 0) { return 'auction'; }
  if (function_exists('antique_offers_table')) {
    $res = $db->query("SELECT 1 FROM `" . antique_offers_table() . "` WHERE fk_i_item_id = {$itemId} AND fk_i_buyer_id = {$buyerId} AND fk_i_seller_id = {$sellerId} AND s_status = 'accepted' LIMIT 1");
    if ($res && $res->numRows() > 0) { return 'offer'; }
  }
  $thread = antique_msg_find_thread($itemId, $buyerId, $sellerId);
  if ($thread) {
    $res = $db->query(sprintf("SELECT COUNT(DISTINCT fk_i_sender_id) AS n FROM `%s` WHERE fk_i_thread_id = %d AND s_kind = 'text' AND fk_i_sender_id IN (%d, %d)",
      antique_msg_table(), (int)$thread['pk_i_id'], $buyerId, $sellerId));
    if ($res && (int)$res->row()['n'] >= 2) { return 'message'; }
  }
  return '';
}

function antique_review_find($buyerId, $itemId) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_buyer_id = %d AND fk_i_item_id = %d LIMIT 1", antique_reviews_table(), (int)$buyerId, (int)$itemId));
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_review($id) {
  $res = antique_msg_db()->query("SELECT * FROM `" . antique_reviews_table() . "` WHERE pk_i_id = " . (int)$id);
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_review_can_edit($review) {
  return $review && strtotime($review['dt_created']) > time() - ANTIQUE_REVIEW_EDIT_DAYS * 86400;
}

/**
 * Average, count and 5..1 star counts for a seller (visible reviews only).
 */
function antique_seller_rating($sellerId) {
  static $cache = array();
  $sellerId = (int)$sellerId;
  if (isset($cache[$sellerId])) { return $cache[$sellerId]; }
  $out = array('avg' => 0, 'count' => 0, 'dist' => array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0));
  $res = $sellerId > 0 ? antique_msg_db()->query("SELECT i_rating, COUNT(*) AS n FROM `" . antique_reviews_table() . "` WHERE fk_i_seller_id = {$sellerId} AND b_hidden = 0 GROUP BY i_rating") : false;
  $sum = 0;
  foreach (($res ? $res->result() : array()) as $r) {
    $out['dist'][(int)$r['i_rating']] = (int)$r['n'];
    $out['count'] += (int)$r['n'];
    $sum += (int)$r['i_rating'] * (int)$r['n'];
  }
  if ($out['count'] > 0) { $out['avg'] = round($sum / $out['count'], 1); }
  return $cache[$sellerId] = $out;
}

function antique_seller_reviews($sellerId, $limit = 50, $offset = 0) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_seller_id = %d AND b_hidden = 0 ORDER BY dt_created DESC LIMIT %d OFFSET %d",
    antique_reviews_table(), (int)$sellerId, (int)$limit, (int)$offset));
  return $res ? $res->result() : array();
}

/**
 * Five stars filled to $value (0-5). $size in px.
 */
function antique_stars($value, $size = 16) {
  $pct = max(0, min(100, $value / 5 * 100));
  $star = '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>';
  return '<span class="al-stars" style="--al-star:' . (int)$size . 'px" role="img" aria-label="' . osc_esc_html(sprintf(__('%s out of 5 stars', 'starter'), rtrim(rtrim(number_format($value, 1), '0'), '.'))) . '">'
    . '<span class="al-stars-bg">' . str_repeat($star, 5) . '</span>'
    . '<span class="al-stars-fg" style="width:' . $pct . '%">' . str_repeat($star, 5) . '</span></span>';
}

/**
 * Compact rating line: stars, average and count (links to the shop's reviews).
 */
function antique_rating_line($sellerId, $link = true) {
  $r = antique_seller_rating($sellerId);
  $url = osc_user_public_profile_url($sellerId) . '#al-reviews';
  if ($r['count'] == 0) {
    return '<span class="al-rating-line is-empty">' . __('No reviews yet', 'starter') . '</span>';
  }
  $text = '<strong>' . number_format($r['avg'], 1) . '</strong> <span>(' . sprintf(_n('%d review', '%d reviews', $r['count'], 'starter'), $r['count']) . ')</span>';
  $inner = antique_stars($r['avg'], 15) . $text;
  return $link ? '<a class="al-rating-line" href="' . osc_esc_html($url) . '">' . $inner . '</a>' : '<span class="al-rating-line">' . $inner . '</span>';
}

/**
 * Conversation page: "Leave a review" for the buyer when they qualify.
 */
function antique_review_cta($thread, $uid) {
  if (antique_msg_role($thread, $uid) != 'buyer' || (int)$thread['fk_i_item_id'] <= 0) { return ''; }
  $mine = antique_review_find($uid, $thread['fk_i_item_id']);
  if ($mine) {
    return antique_review_can_edit($mine) ? '<a class="al-btn al-btn-outline" href="' . antique_review_url($thread['fk_i_item_id']) . '">' . __('Edit your review', 'starter') . '</a>' : '';
  }
  if (antique_review_basis($uid, $thread['fk_i_item_id']) === '') { return ''; }
  return '<a class="al-btn al-btn-outline al-review-cta" href="' . antique_review_url($thread['fk_i_item_id']) . '">'
    . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>'
    . __('Review the seller', 'starter') . '</a>';
}

/**
 * POST hook=al_review: op=save (item, rating, body), reply (review, reply), report (review).
 */
function antique_review_post() {
  $back = antique_msg_back_url(Params::getParam('back'));
  if (!osc_is_web_user_logged_in()) {
    Session::newInstance()->_set('al_after_login', $back);
    osc_redirect_to(osc_user_login_url());
  }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  $db = antique_msg_db();
  $op = Params::getParam('op');
  $now = antique_msg_now();

  if ($op == 'save') {
    $itemId = (int)Params::getParam('item');
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    $form = antique_review_url($itemId);
    $existing = antique_review_find($uid, $itemId);
    $basis = $existing ? $existing['s_basis'] : antique_review_basis($uid, $itemId);
    if (!$item || $basis === '') { antique_msg_reply(false, __('You can review a seller after you win their auction, they accept your offer, or you have talked with them about the item.', 'starter'), $back); }
    if ($existing && !antique_review_can_edit($existing)) { antique_msg_reply(false, __('This review can no longer be changed.', 'starter'), $form); }
    $rating = (int)Params::getParam('rating');
    $body = antique_msg_clean(Params::getParam('body'));
    $body = mb_substr($body, 0, ANTIQUE_REVIEW_MAX);
    Session::newInstance()->_set('al_review_form', array('rating' => $rating, 'body' => $body));
    if ($rating < 1 || $rating > 5) { antique_msg_reply(false, __('Choose a star rating from 1 to 5.', 'starter'), $form); }
    if (mb_strlen($body) < ANTIQUE_REVIEW_MIN) { antique_msg_reply(false, sprintf(__('Please write at least %d characters about your experience.', 'starter'), ANTIQUE_REVIEW_MIN), $form); }
    Session::newInstance()->_drop('al_review_form');
    $sellerId = (int)$item['fk_i_user_id'];
    if ($existing) {
      $db->query(sprintf("UPDATE `%s` SET i_rating = %d, s_body = %s, dt_updated = '%s' WHERE pk_i_id = %d",
        antique_reviews_table(), $rating, $db->escape($body), $now, (int)$existing['pk_i_id']));
      antique_msg_reply(true, __('Your review has been updated.', 'starter'), osc_user_public_profile_url($sellerId) . '#al-reviews');
    }
    $db->query(sprintf("INSERT INTO `%s` (fk_i_seller_id, fk_i_buyer_id, fk_i_item_id, s_item_title, i_rating, s_body, s_basis, dt_created) VALUES (%d, %d, %d, %s, %d, %s, %s, '%s')",
      antique_reviews_table(), $sellerId, $uid, $itemId, $db->escape(mb_substr($item['s_title'], 0, 200)), $rating, $db->escape($body), $db->escape($basis), $now));
    $seller = antique_msg_user($sellerId);
    $buyer = antique_msg_user($uid);
    if ($seller['email'] !== '') {
      antique_mail($seller['email'], $seller['name'], sprintf(__('%1$s left you a %2$d-star review', 'starter'), $buyer['name'], $rating), __('You have a new review', 'starter'), array(
        sprintf(__('%1$s reviewed you for %2$s:', 'starter'), '<strong>' . osc_esc_html($buyer['name']) . '</strong>', '<strong>' . osc_esc_html($item['s_title']) . '</strong>'),
        str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating),
        antique_mail_quote($body),
        __('You can post one public reply under the review on your shop page.', 'starter')
      ), osc_user_public_profile_url($sellerId) . '#al-reviews', __('See the review', 'starter'));
    }
    antique_msg_reply(true, __('Thank you. Your review is now on the seller\'s shop page.', 'starter'), osc_user_public_profile_url($sellerId) . '#al-reviews');
  }

  $review = antique_review((int)Params::getParam('review'));
  if (!$review) { antique_msg_reply(false, __('This review no longer exists.', 'starter'), $back); }
  $shop = osc_user_public_profile_url($review['fk_i_seller_id']) . '#al-review-' . (int)$review['pk_i_id'];

  if ($op == 'reply') {
    if ((int)$review['fk_i_seller_id'] !== $uid) { antique_msg_reply(false, __('Only the seller can reply to this review.', 'starter'), $shop); }
    $reply = mb_substr(antique_msg_clean(Params::getParam('reply')), 0, ANTIQUE_REVIEW_MAX);
    $db->query(sprintf("UPDATE `%s` SET s_reply = %s, dt_reply = %s WHERE pk_i_id = %d",
      antique_reviews_table(), $reply === '' ? 'NULL' : $db->escape($reply), $reply === '' ? 'NULL' : "'" . $now . "'", (int)$review['pk_i_id']));
    antique_msg_reply(true, $reply === '' ? __('Your reply was removed.', 'starter') : __('Your reply is now public.', 'starter'), $shop);
  }

  if ($op == 'report') {
    $reason = trim(mb_substr(strip_tags((string)Params::getParam('reason')), 0, 500));
    $db->query("UPDATE `" . antique_reviews_table() . "` SET b_reported = 1 WHERE pk_i_id = " . (int)$review['pk_i_id']);
    $admin = osc_contact_email();
    if ($admin !== '') {
      $by = antique_msg_user($uid);
      antique_mail($admin, osc_page_title(), __('A review was reported', 'starter'), __('A review was reported', 'starter'), array(
        sprintf(__('%1$s (%2$s) reported review #%3$d on the shop of %4$s.', 'starter'), osc_esc_html($by['name']), osc_esc_html($by['email']), (int)$review['pk_i_id'], osc_esc_html(antique_msg_user($review['fk_i_seller_id'])['name'])),
        $reason !== '' ? sprintf(__('Reason: %s', 'starter'), osc_esc_html($reason)) : __('No reason given.', 'starter'),
        antique_mail_quote($review['s_body']),
        __('Hide or keep it under Theme Setting > Marketplace in oc-admin.', 'starter')
      ), osc_user_public_profile_url($review['fk_i_seller_id']) . '#al-review-' . (int)$review['pk_i_id'], __('See the review', 'starter'));
    }
    antique_msg_reply(true, __('Thank you. Our team will check this review.', 'starter'), $shop);
  }

  antique_msg_reply(false, __('Something went wrong. Please try again.', 'starter'), $back);
}
osc_add_hook('ajax_al_review', 'antique_review_post');

/**
 * One review on the shop page.
 */
function antique_review_html($r, $viewerId) {
  $buyer = antique_msg_user($r['fk_i_buyer_id']);
  $labels = antique_review_basis_labels();
  $isSeller = (int)$viewerId > 0 && (int)$viewerId === (int)$r['fk_i_seller_id'];
  $isAuthor = (int)$viewerId > 0 && (int)$viewerId === (int)$r['fk_i_buyer_id'];
  // first name and initial only
  $parts = preg_split('/\s+/', trim($buyer['name']));
  $short = $parts[0] . (isset($parts[1]) && $parts[1] !== '' ? ' ' . mb_substr($parts[1], 0, 1) . '.' : '');
  $token = osc_is_web_user_logged_in() ? osc_csrfguard_generate_token() : '';
  $post = osc_esc_html(osc_ajax_hook_url('al_review'));
  $back = osc_esc_html(osc_user_public_profile_url($r['fk_i_seller_id']));

  $h = '<article class="al-review" id="al-review-' . (int)$r['pk_i_id'] . '">';
  $h .= '<header class="al-review-head"><img src="' . osc_esc_html($buyer['avatar']) . '" alt="" width="40" height="40" loading="lazy" />'
     . '<div><strong>' . osc_esc_html($short) . '</strong><span>' . antique_stars((int)$r['i_rating'], 14) . ' <time datetime="' . osc_esc_html(date('c', strtotime($r['dt_created']))) . '">' . osc_esc_html(date('j M Y', strtotime($r['dt_created']))) . '</time></span></div>'
     . '<span class="al-review-basis' . ($r['s_basis'] != 'message' ? ' is-verified' : '') . '">' . ($r['s_basis'] != 'message' ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>' : '') . osc_esc_html(isset($labels[$r['s_basis']]) ? $labels[$r['s_basis']] : '') . '</span></header>';
  $h .= '<p class="al-review-body">' . nl2br(osc_esc_html($r['s_body'])) . '</p>';
  if ($r['s_item_title'] !== '') {
    $h .= '<p class="al-review-item">' . sprintf(__('Item: %s', 'starter'), osc_esc_html($r['s_item_title'])) . '</p>';
  }
  if (trim((string)$r['s_reply']) !== '') {
    $h .= '<div class="al-review-reply"><strong>' . __('Seller reply', 'starter') . '</strong><p>' . nl2br(osc_esc_html($r['s_reply'])) . '</p></div>';
  }
  $tools = '';
  if ($isSeller) {
    $tools .= '<details class="al-review-replyform"><summary>' . (trim((string)$r['s_reply']) !== '' ? __('Edit your reply', 'starter') : __('Reply publicly', 'starter')) . '</summary>'
      . '<form class="al-form" method="post" action="' . $post . '"><input type="hidden" name="octoken" value="' . $token . '" /><input type="hidden" name="op" value="reply" /><input type="hidden" name="review" value="' . (int)$r['pk_i_id'] . '" /><input type="hidden" name="back" value="' . $back . '" />'
      . '<textarea name="reply" rows="3" maxlength="' . ANTIQUE_REVIEW_MAX . '" placeholder="' . osc_esc_html(__('Thank the buyer or explain what happened. Everyone can read your reply.', 'starter')) . '">' . osc_esc_html((string)$r['s_reply']) . '</textarea>'
      . '<button type="submit" class="al-btn al-btn-ink">' . __('Post reply', 'starter') . '</button></form></details>';
  }
  if ($isAuthor && antique_review_can_edit($r)) {
    $tools .= '<a class="al-text-link" href="' . antique_review_url($r['fk_i_item_id']) . '">' . __('Edit your review', 'starter') . '</a>';
  }
  if (osc_is_web_user_logged_in() && !$isAuthor) {
    $tools .= '<details class="al-review-report"><summary>' . __('Report', 'starter') . '</summary>'
      . '<form class="al-form" method="post" action="' . $post . '"><input type="hidden" name="octoken" value="' . $token . '" /><input type="hidden" name="op" value="report" /><input type="hidden" name="review" value="' . (int)$r['pk_i_id'] . '" /><input type="hidden" name="back" value="' . $back . '" />'
      . '<input type="text" name="reason" maxlength="500" placeholder="' . osc_esc_html(__('What is wrong with this review?', 'starter')) . '" />'
      . '<button type="submit" class="al-btn al-btn-outline">' . __('Send report', 'starter') . '</button></form></details>';
  }
  if ($tools !== '') { $h .= '<div class="al-review-tools">' . $tools . '</div>'; }
  return $h . '</article>';
}

/**
 * Rating summary: big average, stars and a bar per star level.
 */
function antique_rating_summary($sellerId) {
  $r = antique_seller_rating($sellerId);
  $h = '<div class="al-rating-summary"><div class="al-rating-big"><strong>' . ($r['count'] ? number_format($r['avg'], 1) : '–') . '</strong>'
     . antique_stars($r['avg'], 18) . '<span>' . sprintf(_n('%d review', '%d reviews', $r['count'], 'starter'), $r['count']) . '</span></div><ul class="al-rating-bars">';
  foreach ($r['dist'] as $stars => $n) {
    $pct = $r['count'] ? round($n / $r['count'] * 100) : 0;
    $h .= '<li><span>' . sprintf(_n('%d star', '%d stars', $stars, 'starter'), $stars) . '</span><span class="al-rating-bar"><span style="width:' . $pct . '%"></span></span><em>' . $n . '</em></li>';
  }
  return $h . '</ul></div>';
}

/**
 * Listings the signed-in buyer can still review (for the Buying page).
 */
function antique_reviews_waiting($uid) {
  $uid = (int)$uid;
  $ids = array();
  $db = antique_msg_db();
  $p = DB_TABLE_PREFIX;
  $res = $db->query("SELECT DISTINCT fk_i_item_id FROM `{$p}t_item_bids` WHERE fk_i_user_id = {$uid} AND s_status = 'won'");
  foreach (($res ? $res->result() : array()) as $r) { $ids[] = (int)$r['fk_i_item_id']; }
  if (function_exists('antique_offers_table')) {
    $res = $db->query("SELECT DISTINCT fk_i_item_id FROM `" . antique_offers_table() . "` WHERE fk_i_buyer_id = {$uid} AND s_status = 'accepted'");
    foreach (($res ? $res->result() : array()) as $r) { $ids[] = (int)$r['fk_i_item_id']; }
  }
  $out = array();
  foreach (array_unique($ids) as $id) {
    if (!antique_review_find($uid, $id) && ($item = antique_msg_item($id))) { $out[] = $item; }
  }
  return $out;
}

function antique_reviews_delete_user($uid) {
  antique_msg_db()->query(sprintf("DELETE FROM `%s` WHERE fk_i_seller_id = %d OR fk_i_buyer_id = %d", antique_reviews_table(), (int)$uid, (int)$uid));
}
osc_add_hook('delete_user', 'antique_reviews_delete_user');
