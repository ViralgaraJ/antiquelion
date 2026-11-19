<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * "Continue with Google" sign-in (OAuth 2.0 authorization code flow, server side).
 *
 * oauth/google/?start=1  -> Google consent screen (state kept in the session)
 * oauth/google/?code=... -> back from Google: code exchanged for a token server to
 *                           server, profile read from Google's userinfo endpoint.
 *
 * A Google account is matched by its Google ID (t_al_social), then by a verified email
 * (an existing member is linked), otherwise a new member is created with a random
 * password; they can set one later with "Forgot password". Settings: oc-admin >
 * Theme Setting > Integrations.
 */

define('ANTIQUE_SOCIAL_SCHEMA', '1');

function antique_social_table() { return DB_TABLE_PREFIX . 't_al_social'; }

function antique_social_schema() {
  if (osc_get_preference('social_schema', 'starter_theme') == ANTIQUE_SOCIAL_SCHEMA) { return; }
  $db = User::newInstance()->dao;
  $ok = $db->query("CREATE TABLE IF NOT EXISTS `" . antique_social_table() . "` (
    `s_provider` varchar(20) NOT NULL,
    `s_subject` varchar(64) NOT NULL,
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `s_email` varchar(150) DEFAULT NULL,
    `dt_linked` datetime NOT NULL,
    `dt_last_login` datetime DEFAULT NULL,
    PRIMARY KEY (`s_provider`, `s_subject`),
    KEY `idx_user` (`fk_i_user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('social_schema', ANTIQUE_SOCIAL_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_social_schema', 1);

function antique_google_setting($k) { return trim((string)osc_get_preference('google_' . $k, 'starter_theme')); }

function antique_google_enabled() {
  return antique_google_setting('enabled') === '1' && antique_google_setting('client_id') !== '' && antique_google_setting('client_secret') !== '';
}

osc_add_route('al-google', 'oauth/google/?(?:\?.*)?$', 'oauth/google/', 'al-google.php', false, 'custom', 'al-google', __('Sign in with Google', 'starter'));

// The exact address to register in Google Cloud as "Authorized redirect URI"
function antique_google_redirect_uri() {
  return osc_route_url('al-google');
}

function antique_google_start_url() {
  return antique_route_url('al-google', array('start' => 1));
}

/**
 * "Continue with Google" button for the sign-in and register forms.
 */
function antique_google_button($label = '') {
  if (!antique_google_enabled() || osc_is_web_user_logged_in()) { return ''; }
  $label = $label !== '' ? $label : __('Continue with Google', 'starter');
  return '<div class="al-social"><a class="al-btn al-google-btn" href="' . osc_esc_html(antique_google_start_url()) . '" rel="nofollow">'
    . '<svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>'
    . '<span>' . osc_esc_html($label) . '</span></a><div class="al-social-or"><span>' . osc_esc_html(__('or', 'starter')) . '</span></div></div>';
}

function antique_google_fail($message) {
  osc_add_flash_error_message($message);
  osc_redirect_to(osc_user_login_url());
}

function antique_google_http($url, $post = null, $token = '') {
  if (!function_exists('curl_init')) { return null; }
  $ch = curl_init($url);
  $opts = array(CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10);
  if ($post !== null) { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = http_build_query($post); }
  if ($token !== '') { $opts[CURLOPT_HTTPHEADER] = array('Authorization: Bearer ' . $token); }
  curl_setopt_array($ch, $opts);
  $body = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $data = json_decode((string)$body, true);
  return ($code >= 200 && $code < 300 && is_array($data)) ? $data : null;
}

/**
 * Runs before any output on the al-google route.
 */
function antique_google_route() {
  if (Params::getParam('page') !== 'custom' || Params::getParam('route') !== 'al-google') { return; }
  $session = Session::newInstance();
  if (!antique_google_enabled()) { antique_google_fail(__('Google sign-in is not available right now.', 'starter')); }
  if (osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_dashboard_url()); }

  if (Params::getParam('start') == '1') {
    $state = bin2hex(random_bytes(16));
    $session->_set('al_google_state', $state);
    osc_redirect_to('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(array(
      'client_id' => antique_google_setting('client_id'),
      'redirect_uri' => antique_google_redirect_uri(),
      'response_type' => 'code',
      'scope' => 'openid email profile',
      'state' => $state,
      'prompt' => 'select_account'
    )));
  }

  if (Params::getParam('error') !== '') {
    antique_google_fail(__('Google sign-in was cancelled.', 'starter'));
  }
  $state = (string)$session->_get('al_google_state');
  $session->_drop('al_google_state');
  if ($state === '' || !hash_equals($state, (string)Params::getParam('state'))) {
    antique_google_fail(__('Your sign-in link expired. Please try again.', 'starter'));
  }
  $token = antique_google_http('https://oauth2.googleapis.com/token', array(
    'code' => (string)Params::getParam('code'),
    'client_id' => antique_google_setting('client_id'),
    'client_secret' => antique_google_setting('client_secret'),
    'redirect_uri' => antique_google_redirect_uri(),
    'grant_type' => 'authorization_code'
  ));
  if (!$token || empty($token['access_token'])) { antique_google_fail(__('We could not sign you in with Google. Please try again.', 'starter')); }
  $profile = antique_google_http('https://openidconnect.googleapis.com/v1/userinfo', null, $token['access_token']);
  if (!$profile || empty($profile['sub']) || empty($profile['email'])) { antique_google_fail(__('We could not read your Google profile. Please try again.', 'starter')); }
  if (empty($profile['email_verified'])) { antique_google_fail(__('Your Google email address is not verified. Verify it with Google, or register with your email.', 'starter')); }

  $uid = antique_google_user($profile);
  if ($uid < 1) { antique_google_fail(__('We could not create your account. Please register with your email.', 'starter')); }

  $email = strtolower($profile['email']);
  if (osc_is_banned($email) !== 0) { antique_google_fail(__('This account cannot sign in. Please contact us.', 'starter')); }
  require_once LIB_PATH . 'osclass/UserActions.php';
  $actions = new UserActions(false);
  $result = $actions->bootstrap_login($uid);
  if ($result == 2) { antique_google_fail(__('Your account has been suspended. Please contact us.', 'starter')); }
  if ($result != 3) { antique_google_fail(__('We could not sign you in. Please try again.', 'starter')); }
  User::newInstance()->update(array('dt_access_date' => date('Y-m-d H:i:s'), 's_access_ip' => osc_get_ip()), array('pk_i_id' => $uid));
  osc_run_hook('after_login', User::newInstance()->findByPrimaryKey($uid), '');

  $next = (string)$session->_get('al_after_login');
  $session->_drop('al_after_login');
  osc_redirect_to(($next !== '' && strpos($next, osc_base_url()) === 0) ? $next : osc_user_dashboard_url());
}
osc_add_hook('init', 'antique_google_route', 3);

/**
 * Member ID for a Google profile: linked account > same verified email > new member.
 */
function antique_google_user($profile) {
  $db = User::newInstance()->dao;
  $t = antique_social_table();
  $sub = substr(preg_replace('/[^0-9A-Za-z_-]/', '', (string)$profile['sub']), 0, 64);
  $email = strtolower(trim($profile['email']));
  $now = date('Y-m-d H:i:s');

  $res = $db->query(sprintf("SELECT fk_i_user_id FROM `%s` WHERE s_provider = 'google' AND s_subject = %s", $t, $db->escape($sub)));
  if ($res && $res->numRows() > 0) {
    $uid = (int)$res->row()['fk_i_user_id'];
    if (User::newInstance()->findByPrimaryKey($uid)) {
      $db->query(sprintf("UPDATE `%s` SET dt_last_login = %s WHERE s_provider = 'google' AND s_subject = %s", $t, $db->escape($now), $db->escape($sub)));
      return $uid;
    }
    $db->query(sprintf("DELETE FROM `%s` WHERE s_provider = 'google' AND s_subject = %s", $t, $db->escape($sub)));
  }

  $user = User::newInstance()->findByEmail($email);
  if ($user) {
    $uid = (int)$user['pk_i_id'];
    // Google proved the email, so an account waiting for email activation can be activated
    if (!(int)$user['b_active']) { User::newInstance()->update(array('b_active' => 1), array('pk_i_id' => $uid)); }
  } else {
    $name = trim((string)(isset($profile['name']) ? $profile['name'] : ''));
    if ($name === '') { $name = strstr($email, '@', true); }
    $date = date('Y-m-d H:i:s');
    $input = array(
      's_name' => mb_substr($name, 0, 100),
      's_email' => $email,
      's_password' => osc_hash_password(bin2hex(random_bytes(24))),
      's_secret' => osc_genRandomPassword(),
      'dt_reg_date' => $date, 'dt_mod_date' => $date, 'dt_access_date' => $date,
      's_access_ip' => osc_get_ip(),
      'b_active' => 1, 'b_enabled' => 1
    );
    if (!User::newInstance()->insert($input)) { return 0; }
    $uid = (int)User::newInstance()->dao->insertedId();
    if ($uid < 1) { return 0; }
    $username = osc_sanitize_username($name);
    if ($username === '' || User::newInstance()->findByUsername($username) || osc_is_username_blacklisted($username)) { $username = $username !== '' ? $username . $uid : (string)$uid; }
    User::newInstance()->update(array('s_username' => $username), array('pk_i_id' => $uid));
    osc_run_hook('user_register_completed', $uid);
  }
  $db->query(sprintf("INSERT INTO `%s` (s_provider, s_subject, fk_i_user_id, s_email, dt_linked, dt_last_login) VALUES ('google', %s, %d, %s, %s, %s)
    ON DUPLICATE KEY UPDATE fk_i_user_id = VALUES(fk_i_user_id), dt_last_login = VALUES(dt_last_login)",
    $t, $db->escape($sub), $uid, $db->escape($email), $db->escape($now), $db->escape($now)));
  return $uid;
}

function antique_social_delete_user($uid) {
  User::newInstance()->dao->query("DELETE FROM `" . antique_social_table() . "` WHERE fk_i_user_id = " . (int)$uid);
}
osc_add_hook('delete_user', 'antique_social_delete_user');
