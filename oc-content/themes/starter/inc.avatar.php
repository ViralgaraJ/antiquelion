<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Profile photos. Uploaded from Account settings, re-encoded on the server (square
 * crop, 512 px JPEG, turned the right way up, camera/GPS metadata dropped) and stored
 * in oc-content/uploads/avatars/ (git-ignored, so deploys never touch them).
 * Photos from the old profile_picture plugin are still shown until replaced.
 */

define('ANTIQUE_AVATAR_SCHEMA', '1');
define('ANTIQUE_AVATAR_SIZE', 512);
define('ANTIQUE_AVATAR_MAX_BYTES', 8 * 1024 * 1024);

function antique_avatar_dir() {
  return osc_content_path() . 'uploads/avatars/';
}

function antique_avatar_schema() {
  if (osc_get_preference('avatar_schema', 'starter_theme') == ANTIQUE_AVATAR_SCHEMA) { return; }
  $ok = Item::newInstance()->dao->query("CREATE TABLE IF NOT EXISTS `" . DB_TABLE_PREFIX . "t_al_avatars` (
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_file` varchar(64) NOT NULL,
    `dt_updated` datetime NOT NULL,
    PRIMARY KEY (`fk_i_user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) {
    osc_set_preference('avatar_schema', ANTIQUE_AVATAR_SCHEMA, 'starter_theme');
  }
}
osc_add_hook('init', 'antique_avatar_schema', 1);

function antique_avatar_file($uid) {
  static $cache = array();
  $uid = (int)$uid;
  if ($uid <= 0) { return ''; }
  if (!array_key_exists($uid, $cache)) {
    $res = Item::newInstance()->dao->query("SELECT s_file FROM `" . DB_TABLE_PREFIX . "t_al_avatars` WHERE fk_i_user_id = " . $uid);
    $file = ($res && $res->numRows() > 0) ? $res->row()['s_file'] : '';
    $cache[$uid] = ($file !== '' && is_file(antique_avatar_dir() . $file)) ? $file : '';
  }
  return $cache[$uid];
}

// URL of the person's uploaded photo, or '' when they have none
function antique_avatar_url($uid) {
  $file = antique_avatar_file($uid);
  return $file !== '' ? osc_content_url() . 'uploads/avatars/' . $file : '';
}

function antique_avatar_json($ok, $data = array()) {
  ob_clean();
  header('Content-Type: application/json');
  echo json_encode(array_merge(array('success' => $ok), $data));
  exit;
}

/**
 * Square, upright, metadata-free JPEG from an uploaded image. Returns a GD image or an error string.
 */
function antique_avatar_process($tmp) {
  $info = @getimagesize($tmp);
  if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP), true)) {
    return __('Please choose a JPG, PNG, WebP or GIF image.', 'starter');
  }
  if ($info[0] < 80 || $info[1] < 80) {
    return __('This image is too small. Use one at least 200 × 200 pixels.', 'starter');
  }
  if ($info[0] * $info[1] > 40000000) {
    return __('This image is too large. Use one under 40 megapixels.', 'starter');
  }
  $src = @imagecreatefromstring(file_get_contents($tmp));
  if (!$src) {
    return __('This image could not be read. Please try another one.', 'starter');
  }

  // phone photos are often stored sideways with an orientation flag
  if ($info[2] == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $exif = @exif_read_data($tmp);
    $o = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
    $angle = array(3 => 180, 6 => -90, 8 => 90);
    if (isset($angle[$o])) {
      $rot = imagerotate($src, $angle[$o], 0);
      if ($rot) { imagedestroy($src); $src = $rot; }
    }
  }

  $w = imagesx($src);
  $h = imagesy($src);
  $side = min($w, $h);
  $size = min(ANTIQUE_AVATAR_SIZE, $side);
  $dst = imagecreatetruecolor($size, $size);
  imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // transparent PNGs get a white background
  imagecopyresampled($dst, $src, 0, 0, (int)(($w - $side) / 2), (int)(($h - $side) / 2), $size, $size, $side, $side);
  imagedestroy($src);
  return $dst;
}

// AJAX: index.php?page=ajax&action=runhook&hook=al_avatar  (POST photo | op=delete, octoken)
function antique_avatar_ajax() {
  if (!osc_is_web_user_logged_in()) {
    antique_avatar_json(false, array('error' => __('Please sign in again.', 'starter')));
  }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_avatar_json(false, array('error' => __('Your session expired. Please reload the page.', 'starter')));
  }
  $uid = (int)osc_logged_user_id();
  $dao = Item::newInstance()->dao;
  $dir = antique_avatar_dir();
  $old = antique_avatar_file($uid);

  if (Params::getParam('op') == 'delete') {
    if ($old !== '') { @unlink($dir . $old); }
    $dao->query("DELETE FROM `" . DB_TABLE_PREFIX . "t_al_avatars` WHERE fk_i_user_id = " . $uid);
    $dao->query("DELETE FROM `" . DB_TABLE_PREFIX . "t_profile_picture` WHERE user_id = " . $uid);   // old plugin photo too
    antique_avatar_json(true, array('url' => osc_current_web_theme_url('images/profile-256.png'), 'removed' => true));
  }

  if (empty($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $err = isset($_FILES['photo']['error']) ? (int)$_FILES['photo']['error'] : UPLOAD_ERR_NO_FILE;
    antique_avatar_json(false, array('error' => in_array($err, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true) ? __('This file is too big. The limit is 8 MB.', 'starter') : __('No photo was received. Please try again.', 'starter')));
  }
  if ($_FILES['photo']['size'] > ANTIQUE_AVATAR_MAX_BYTES) {
    antique_avatar_json(false, array('error' => __('This file is too big. The limit is 8 MB.', 'starter')));
  }

  $img = antique_avatar_process($_FILES['photo']['tmp_name']);
  if (is_string($img)) {
    antique_avatar_json(false, array('error' => $img));
  }
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    antique_avatar_json(false, array('error' => __('Photos cannot be saved right now. Please try again later.', 'starter')));
  }
  if (!is_file($dir . 'index.html')) { @file_put_contents($dir . 'index.html', ''); }

  $file = 'u' . $uid . '-' . substr(md5(uniqid('', true)), 0, 10) . '.jpg';
  $saved = imagejpeg($img, $dir . $file, 86);
  imagedestroy($img);
  if (!$saved) {
    antique_avatar_json(false, array('error' => __('Photos cannot be saved right now. Please try again later.', 'starter')));
  }
  if ($old !== '' && $old !== $file) { @unlink($dir . $old); }
  $dao->query("REPLACE INTO `" . DB_TABLE_PREFIX . "t_al_avatars` (fk_i_user_id, s_file, dt_updated) VALUES (" . $uid . ", '" . $file . "', '" . date('Y-m-d H:i:s') . "')");

  antique_avatar_json(true, array('url' => osc_content_url() . 'uploads/avatars/' . $file));
}
osc_add_hook('ajax_al_avatar', 'antique_avatar_ajax');

function antique_avatar_delete_user($uid) {
  $file = antique_avatar_file($uid);
  if ($file !== '') { @unlink(antique_avatar_dir() . $file); }
  Item::newInstance()->dao->query("DELETE FROM `" . DB_TABLE_PREFIX . "t_al_avatars` WHERE fk_i_user_id = " . (int)$uid);
}
osc_add_hook('delete_user', 'antique_avatar_delete_user');
