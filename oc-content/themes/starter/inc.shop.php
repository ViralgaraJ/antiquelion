<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Seller shop pages (the public profile): banner, tagline, about, shop policies
 * (shipping, returns, payment), sales and reply-time figures, reviews.
 * Sellers edit them under Account > Shop settings. Banners are re-encoded on the
 * server and kept in oc-content/uploads/shops/ (git-ignored).
 */

define('ANTIQUE_SHOP_BANNER_W', 1600);
define('ANTIQUE_SHOP_BANNER_H', 400);
define('ANTIQUE_SHOP_POLICY_MAX', 1000);

// Account > Shop settings (sellers)
osc_add_route('al-shop', 'account/shop/?$', 'account/shop/', 'al-shop.php', true, 'custom', 'al-shop', __('Shop settings', 'starter'));

function antique_shop_settings_url() { return osc_route_url('al-shop'); }

function antique_shop_banner_dir() { return osc_content_path() . 'uploads/shops/'; }

function antique_shop_banner_url($uid) {
  $row = antique_seller_row($uid);
  $file = $row && !empty($row['s_banner']) ? basename($row['s_banner']) : '';
  return ($file !== '' && is_file(antique_shop_banner_dir() . $file)) ? osc_content_url() . 'uploads/shops/' . $file : '';
}

/**
 * Shop fields for a seller (empty strings when not set).
 */
function antique_shop($uid) {
  $row = antique_seller_row($uid);
  $get = function ($k) use ($row) { return $row && isset($row[$k]) ? trim((string)$row[$k]) : ''; };
  return array(
    'tagline'  => $get('s_tagline'),
    'returns'  => $get('s_policy_returns'),
    'shipping' => $get('s_policy_ship'),
    'payment'  => $get('s_policy_pay'),
    'banner'   => antique_shop_banner_url($uid),
    'since'    => $get('dt_started')
  );
}

/**
 * Items sold: listings marked sold plus auctions won by a buyer.
 */
function antique_shop_sold_count($uid) {
  $uid = (int)$uid;
  $p = DB_TABLE_PREFIX;
  $res = antique_msg_db()->query("SELECT COUNT(*) AS n FROM `{$p}t_item` i LEFT JOIN `{$p}t_item_starter` s ON s.fk_i_item_id = i.pk_i_id
    WHERE i.fk_i_user_id = {$uid} AND (s.i_sold = '1' OR EXISTS (SELECT 1 FROM `{$p}t_item_bids` b WHERE b.fk_i_item_id = i.pk_i_id AND b.s_status = 'won'))");
  return $res ? (int)$res->row()['n'] : 0;
}

/**
 * How quickly the seller answers new conversations (last 90 days). Needs at least
 * 3 conversations; returns array('rate' => %, 'label' => 'within an hour'...) or null.
 */
function antique_shop_response($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if (array_key_exists($uid, $cache)) { return $cache[$uid]; }
  $t = antique_msg_threads_table();
  $m = antique_msg_table();
  $since = date('Y-m-d H:i:s', time() - 90 * 86400);
  $res = antique_msg_db()->query("SELECT x.b_first,
      (SELECT MIN(m2.dt_sent) FROM `{$m}` m2 WHERE m2.fk_i_thread_id = x.pk_i_id AND m2.fk_i_sender_id = x.fk_i_seller_id AND m2.dt_sent >= x.b_first) AS s_first
    FROM (SELECT th.pk_i_id, th.fk_i_seller_id,
            (SELECT MIN(m1.dt_sent) FROM `{$m}` m1 WHERE m1.fk_i_thread_id = th.pk_i_id AND m1.fk_i_sender_id = th.fk_i_buyer_id) AS b_first
          FROM `{$t}` th WHERE th.fk_i_seller_id = {$uid} AND th.dt_created >= '{$since}' ORDER BY th.dt_created DESC LIMIT 100) x
    WHERE x.b_first IS NOT NULL");
  $rows = $res ? $res->result() : array();
  if (count($rows) < 3) { return $cache[$uid] = null; }
  $times = array();
  foreach ($rows as $r) {
    if ($r['s_first']) { $times[] = max(0, strtotime($r['s_first']) - strtotime($r['b_first'])); }
  }
  $rate = round(count($times) / count($rows) * 100);
  $label = '';
  if ($times) {
    sort($times);
    $median = $times[(int)floor((count($times) - 1) / 2)];
    if ($median <= 3600) { $label = __('within an hour', 'starter'); }
    elseif ($median <= 6 * 3600) { $label = __('within a few hours', 'starter'); }
    elseif ($median <= 86400) { $label = __('within a day', 'starter'); }
    else { $label = __('within a few days', 'starter'); }
  }
  return $cache[$uid] = array('rate' => $rate, 'label' => $label);
}

/**
 * Re-encodes an uploaded banner to a 1600 x 400 JPEG (centre crop). GD image or error text.
 */
function antique_shop_banner_process($tmp) {
  $info = @getimagesize($tmp);
  if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) {
    return __('Please choose a JPG, PNG or WebP image.', 'starter');
  }
  if ($info[0] < 800 || $info[1] < 200) { return __('This image is too small. Use one at least 1200 × 300 pixels.', 'starter'); }
  if ($info[0] * $info[1] > 40000000) { return __('This image is too large. Use one under 40 megapixels.', 'starter'); }
  $src = @imagecreatefromstring(file_get_contents($tmp));
  if (!$src) { return __('This image could not be read. Please try another one.', 'starter'); }
  if ($info[2] == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $exif = @exif_read_data($tmp);
    $angle = array(3 => 180, 6 => -90, 8 => 90);
    $o = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
    if (isset($angle[$o]) && ($rot = imagerotate($src, $angle[$o], 0))) { imagedestroy($src); $src = $rot; }
  }
  $w = imagesx($src); $h = imagesy($src);
  $ratio = ANTIQUE_SHOP_BANNER_W / ANTIQUE_SHOP_BANNER_H;
  if ($w / $h > $ratio) { $cw = (int)round($h * $ratio); $ch = $h; } else { $cw = $w; $ch = (int)round($w / $ratio); }
  $dw = min(ANTIQUE_SHOP_BANNER_W, $cw);
  $dh = (int)round($dw / $ratio);
  $dst = imagecreatetruecolor($dw, $dh);
  imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
  imagecopyresampled($dst, $src, 0, 0, (int)(($w - $cw) / 2), (int)(($h - $ch) / 2), $dw, $dh, $cw, $ch);
  imagedestroy($src);
  return $dst;
}

/**
 * POST hook=al_shop: shop settings (multipart; banner file optional, remove_banner=1).
 */
function antique_shop_post() {
  $back = antique_shop_settings_url();
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_login_url()); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  if (!antique_is_seller($uid)) { osc_redirect_to(antique_seller_setup_url()); }
  $db = antique_msg_db();
  $clean = function ($k, $max) { return trim(mb_substr(preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', strip_tags((string)Params::getParam($k)))), 0, $max)); };
  $tagline = $clean('tagline', 160);
  $about = $clean('about', 1500);
  $website = $clean('website', 200);
  if ($website !== '' && !preg_match('#^https?://#i', $website)) { $website = 'https://' . $website; }
  if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
    antique_msg_reply(false, __('Enter a valid website address, or leave it empty.', 'starter'), $back);
  }
  $set = array(
    's_tagline' => $tagline,
    's_policy_ship' => $clean('policy_ship', ANTIQUE_SHOP_POLICY_MAX),
    's_policy_returns' => $clean('policy_returns', ANTIQUE_SHOP_POLICY_MAX),
    's_policy_pay' => $clean('policy_pay', ANTIQUE_SHOP_POLICY_MAX)
  );

  $row = antique_seller_row($uid);
  $dir = antique_shop_banner_dir();
  $old = $row && !empty($row['s_banner']) ? basename($row['s_banner']) : '';
  $f = isset($_FILES['banner']) ? $_FILES['banner'] : null;
  if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']) || $f['size'] > 8 * 1024 * 1024) {
      antique_msg_reply(false, __('The banner did not upload. Use an image under 8 MB.', 'starter'), $back);
    }
    $img = antique_shop_banner_process($f['tmp_name']);
    if (is_string($img)) { antique_msg_reply(false, $img, $back); }
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { antique_msg_reply(false, __('Images cannot be saved right now. Please try again later.', 'starter'), $back); }
    if (!is_file($dir . 'index.html')) { @file_put_contents($dir . 'index.html', ''); }
    $file = 's' . $uid . '-' . substr(md5(uniqid('', true)), 0, 10) . '.jpg';
    $ok = imagejpeg($img, $dir . $file, 84);
    imagedestroy($img);
    if (!$ok) { antique_msg_reply(false, __('Images cannot be saved right now. Please try again later.', 'starter'), $back); }
    if ($old !== '') { @unlink($dir . $old); }
    $set['s_banner'] = $file;
  } elseif (Params::getParam('remove_banner') == '1' && $old !== '') {
    @unlink($dir . $old);
    $set['s_banner'] = null;
  }

  $parts = array();
  foreach ($set as $k => $v) { $parts[] = "`{$k}` = " . ($v === null || $v === '' ? 'NULL' : $db->escape($v)); }
  $db->query("UPDATE `" . antique_sellers_table() . "` SET " . implode(', ', $parts) . " WHERE fk_i_user_id = " . $uid);
  User::newInstance()->update(array('s_website' => $website), array('pk_i_id' => $uid));
  User::newInstance()->updateDescription($uid, osc_current_user_locale(), $about);
  antique_msg_reply(true, __('Your shop page has been updated.', 'starter'), $back);
}
osc_add_hook('ajax_al_shop', 'antique_shop_post');
