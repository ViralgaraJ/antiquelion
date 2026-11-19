<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Two-step sign-in for oc-admin (TOTP, RFC 6238: Google Authenticator, Microsoft
 * Authenticator, 1Password...) and the oc-admin "Integrations" page that also holds the
 * Google sign-in and push notification settings.
 *
 * After the normal admin password, an admin who turned it on must enter the 6-digit
 * code (or a one-time recovery code) before any oc-admin page opens. The check runs on
 * init_admin, so it covers every admin page and admin AJAX call.
 */

define('ANTIQUE_2FA_SCHEMA', '1');
define('ANTIQUE_2FA_SESSION', 'al_2fa_ok');

function antique_2fa_table() { return DB_TABLE_PREFIX . 't_al_admin_2fa'; }
function antique_2fa_db() { return User::newInstance()->dao; }

function antique_2fa_schema() {
  if (osc_get_preference('twofa_schema', 'starter_theme') == ANTIQUE_2FA_SCHEMA) { return; }
  $ok = antique_2fa_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_2fa_table() . "` (
    `fk_i_admin_id` int(10) unsigned NOT NULL,
    `s_secret` varchar(64) NOT NULL,
    `b_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `s_recovery` text,
    `i_last_step` bigint(20) NOT NULL DEFAULT 0,
    `i_fails` int(10) unsigned NOT NULL DEFAULT 0,
    `dt_fail` datetime DEFAULT NULL,
    `dt_enabled` datetime DEFAULT NULL,
    PRIMARY KEY (`fk_i_admin_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('twofa_schema', ANTIQUE_2FA_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init_admin', 'antique_2fa_schema', 1);

/* ---------- TOTP ---------- */

function antique_base32_encode($bin) {
  $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $bits = '';
  foreach (str_split($bin) as $c) { $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT); }
  $out = '';
  foreach (str_split($bits, 5) as $chunk) { $out .= $alpha[bindec(str_pad($chunk, 5, '0'))]; }
  return $out;
}

function antique_base32_decode($s) {
  $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
  $bits = '';
  foreach (str_split($s) as $c) { $bits .= str_pad(decbin(strpos($alpha, $c)), 5, '0', STR_PAD_LEFT); }
  $out = '';
  foreach (str_split($bits, 8) as $byte) { if (strlen($byte) == 8) { $out .= chr(bindec($byte)); } }
  return $out;
}

function antique_totp($secret, $step) {
  $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $step), antique_base32_decode($secret), true);
  $o = ord($hash[19]) & 0x0f;
  $num = ((ord($hash[$o]) & 0x7f) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);
  return str_pad((string)($num % 1000000), 6, '0', STR_PAD_LEFT);
}

/**
 * Time step the code matches (current, one before or after), or 0. A step at or before
 * $lastStep is refused so a code cannot be used twice.
 */
function antique_totp_check($secret, $code, $lastStep = 0) {
  $code = preg_replace('/\D/', '', (string)$code);
  if (strlen($code) !== 6) { return 0; }
  $now = (int)floor(time() / 30);
  foreach (array(0, -1, 1) as $d) {
    $step = $now + $d;
    if ($step > $lastStep && hash_equals(antique_totp($secret, $step), $code)) { return $step; }
  }
  return 0;
}

/* ---------- Admin records ---------- */

function antique_2fa_row($adminId) {
  $res = antique_2fa_db()->query("SELECT * FROM `" . antique_2fa_table() . "` WHERE fk_i_admin_id = " . (int)$adminId);
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

function antique_2fa_enabled($adminId) {
  $row = antique_2fa_row($adminId);
  return $row && (int)$row['b_enabled'] === 1;
}

function antique_2fa_otpauth($secret) {
  $admin = Admin::newInstance()->findByPrimaryKey(osc_logged_admin_id());
  $label = rawurlencode('AntiqueLion admin:' . ($admin ? $admin['s_username'] : 'admin'));
  return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . rawurlencode('AntiqueLion') . '&digits=6&period=30';
}

// Ten one-time recovery codes; only their hashes are kept
function antique_2fa_new_recovery() {
  $codes = array();
  for ($i = 0; $i < 10; $i++) { $codes[] = strtolower(substr(bin2hex(random_bytes(5)), 0, 5) . '-' . substr(bin2hex(random_bytes(5)), 0, 5)); }
  return $codes;
}

function antique_2fa_use_recovery($adminId, $code) {
  $row = antique_2fa_row($adminId);
  $list = $row ? json_decode((string)$row['s_recovery'], true) : array();
  $code = strtolower(trim($code));
  if (!is_array($list) || $code === '') { return false; }
  foreach ($list as $i => $hash) {
    if (password_verify($code, $hash)) {
      unset($list[$i]);
      $db = antique_2fa_db();
      $db->query(sprintf("UPDATE `%s` SET s_recovery = %s WHERE fk_i_admin_id = %d", antique_2fa_table(), $db->escape(json_encode(array_values($list))), (int)$adminId));
      return true;
    }
  }
  return false;
}

/* ---------- The sign-in gate ---------- */

function antique_2fa_gate() {
  if (!osc_is_admin_user_logged_in()) { return; }
  $adminId = (int)osc_logged_admin_id();
  $session = Session::newInstance();
  if ((int)$session->_get(ANTIQUE_2FA_SESSION) === $adminId) { return; }
  $row = antique_2fa_row($adminId);
  if (!$row || (int)$row['b_enabled'] !== 1) { return; }
  if (Params::getParam('action') === 'logout') { return; }

  $error = '';
  $code = trim((string)Params::getParam('al_2fa_code'));
  if ($code !== '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = antique_2fa_db();
    $locked = (int)$row['i_fails'] >= 5 && $row['dt_fail'] && strtotime($row['dt_fail']) > time() - 900;
    if ($locked) {
      $error = __('Too many wrong codes. Wait 15 minutes and try again.', 'starter');
    } elseif (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
      $error = __('The page expired. Please enter the code again.', 'starter');
    } else {
      $step = antique_totp_check($row['s_secret'], $code, (int)$row['i_last_step']);
      $ok = $step > 0 || antique_2fa_use_recovery($adminId, $code);
      if ($ok) {
        $db->query(sprintf("UPDATE `%s` SET i_fails = 0, dt_fail = NULL%s WHERE fk_i_admin_id = %d", antique_2fa_table(), $step > 0 ? ', i_last_step = ' . (int)$step : '', $adminId));
        $session->_set(ANTIQUE_2FA_SESSION, $adminId);
        osc_redirect_to(osc_admin_base_url());
      }
      $db->query(sprintf("UPDATE `%s` SET i_fails = i_fails + 1, dt_fail = %s WHERE fk_i_admin_id = %d", antique_2fa_table(), $db->escape(date('Y-m-d H:i:s')), $adminId));
      $error = __('That code is not right. Check the time on your phone is set automatically, and try again.', 'starter');
    }
  }

  if (ob_get_length()) { ob_clean(); }
  if (Params::getParam('page') === 'ajax') { http_response_code(401); header('Content-Type: application/json'); echo json_encode(array('error' => 'two-step sign-in required')); exit; }
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: no-store');
  $logout = osc_admin_base_url(true) . '?action=logout';
  ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?php echo osc_esc_html(__('Two-step sign-in', 'starter')); ?></title>
<style>
  body{margin:0;display:grid;place-items:center;min-height:100vh;background:#F7F4EE;color:#14213D;font:15px/1.5 Manrope,Arial,sans-serif}
  main{width:min(380px,calc(100% - 32px));padding:28px;border:1px solid #E5DFD3;border-radius:8px;background:#fff}
  h1{margin:0 0 6px;font-size:22px} p{margin:0 0 16px;color:#4B5563}
  input{box-sizing:border-box;width:100%;height:48px;padding:0 14px;border:1px solid #D9D2C3;border-radius:4px;font-size:22px;letter-spacing:4px;text-align:center}
  button{width:100%;height:46px;margin-top:12px;border:0;border-radius:4px;background:#14213D;color:#fff;font:inherit;font-weight:700;cursor:pointer}
  .err{padding:10px 12px;border-radius:4px;background:#FDE8E6;color:#B42318} a{color:#8A6420}
</style></head><body><main>
  <h1><?php _e('Two-step sign-in', 'starter'); ?></h1>
  <p><?php _e('Enter the 6-digit code from your authenticator app. Lost your phone? Enter one of your recovery codes instead.', 'starter'); ?></p>
  <?php if ($error !== '') { ?><p class="err" role="alert"><?php echo osc_esc_html($error); ?></p><?php } ?>
  <form method="post" action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" autocomplete="off">
    <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>">
    <label for="al2fa" style="position:absolute;left:-9999px"><?php _e('Code', 'starter'); ?></label>
    <input id="al2fa" name="al_2fa_code" inputmode="text" autocomplete="one-time-code" autofocus required maxlength="11">
    <button type="submit"><?php _e('Verify', 'starter'); ?></button>
  </form>
  <p style="margin:16px 0 0;font-size:13px"><a href="<?php echo osc_esc_html($logout); ?>"><?php _e('Sign out', 'starter'); ?></a></p>
</main></body></html><?php
  exit;
}
osc_add_hook('init_admin', 'antique_2fa_gate', 2);

/* ---------- oc-admin > Theme Setting > Integrations ---------- */

function antique_integrations_admin_url() {
  return osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/integrations.php');
}

// menu link: functions.php, next to Marketplace (the admin menu is built before the theme loads)

function antique_integrations_admin_actions() {
  $act = (string)Params::getParam('al_int');
  if ($act === '' || !osc_is_admin_user_logged_in()) { return; }
  $url = antique_integrations_admin_url();
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    osc_add_flash_error_message(__('The page expired. Please try again.', 'starter'), 'admin');
    osc_redirect_to($url);
  }
  $adminId = (int)osc_logged_admin_id();
  $db = antique_2fa_db();
  $session = Session::newInstance();
  switch ($act) {
    case 'google':
      osc_set_preference('google_enabled', Params::getParam('google_enabled') ? '1' : '0', 'starter_theme');
      osc_set_preference('google_client_id', trim((string)Params::getParam('google_client_id')), 'starter_theme');
      $secret = trim((string)Params::getParam('google_client_secret', false, false));
      if ($secret !== '') { osc_set_preference('google_client_secret', $secret, 'starter_theme'); }
      osc_add_flash_ok_message(__('Google sign-in settings saved.', 'starter'), 'admin');
      break;
    case 'push':
      osc_set_preference('push_enabled', Params::getParam('push_enabled') ? '1' : '0', 'starter_theme');
      osc_add_flash_ok_message(__('Notification settings saved.', 'starter'), 'admin');
      break;
    case 'push_test':
      $uid = (int)Params::getParam('user');
      $sent = function_exists('antique_push_notify') ? antique_push_notify($uid, __('Test notification', 'starter'), __('Notifications from AntiqueLion are working on this device.', 'starter'), osc_base_url()) : 0;
      $sent > 0 ? osc_add_flash_ok_message(sprintf(__('Sent to %d device(s).', 'starter'), $sent), 'admin')
                : osc_add_flash_error_message(__('Nothing was sent. That member has not turned on notifications on any device, or the push service refused it.', 'starter'), 'admin');
      break;
    case '2fa_start':
      $secret = antique_base32_encode(random_bytes(20));
      $db->query(sprintf("INSERT INTO `%s` (fk_i_admin_id, s_secret, b_enabled) VALUES (%d, %s, 0) ON DUPLICATE KEY UPDATE s_secret = IF(b_enabled = 1, s_secret, VALUES(s_secret))",
        antique_2fa_table(), $adminId, $db->escape($secret)));
      break;
    case '2fa_confirm':
      $row = antique_2fa_row($adminId);
      $step = $row ? antique_totp_check($row['s_secret'], Params::getParam('code')) : 0;
      if (!$row || $step < 1) {
        osc_add_flash_error_message(__('That code is not right. Scan the QR code again and enter the newest code.', 'starter'), 'admin');
        break;
      }
      $codes = antique_2fa_new_recovery();
      $hashes = array_map(function ($c) { return password_hash($c, PASSWORD_DEFAULT); }, $codes);
      $db->query(sprintf("UPDATE `%s` SET b_enabled = 1, i_last_step = %d, s_recovery = %s, dt_enabled = %s, i_fails = 0 WHERE fk_i_admin_id = %d",
        antique_2fa_table(), (int)$step, $db->escape(json_encode($hashes)), $db->escape(date('Y-m-d H:i:s')), $adminId));
      $session->_set(ANTIQUE_2FA_SESSION, $adminId);
      $session->_set('al_2fa_codes', json_encode($codes));
      osc_add_flash_ok_message(__('Two-step sign-in is on. Save your recovery codes now; they are shown only once.', 'starter'), 'admin');
      break;
    case '2fa_off':
      $row = antique_2fa_row($adminId);
      $ok = $row && (antique_totp_check($row['s_secret'], Params::getParam('code')) > 0 || antique_2fa_use_recovery($adminId, Params::getParam('code')));
      if (!$ok) { osc_add_flash_error_message(__('Enter a current code from your app to turn two-step sign-in off.', 'starter'), 'admin'); break; }
      $db->query("DELETE FROM `" . antique_2fa_table() . "` WHERE fk_i_admin_id = " . $adminId);
      osc_add_flash_ok_message(__('Two-step sign-in is off for your admin account.', 'starter'), 'admin');
      break;
  }
  osc_redirect_to($url);
}
osc_add_hook('init_admin', 'antique_integrations_admin_actions', 3);
