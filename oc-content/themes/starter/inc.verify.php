<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Verified sellers. A seller sends their legal or business name, address and a
 * document (photo ID, or business registration for dealers) from Account > Get
 * verified; the site team checks it under Theme Setting > Marketplace in oc-admin and
 * approves or declines. Approved sellers show a "Verified dealer" / "Verified seller"
 * badge. Documents are kept in oc-content/uploads/al-private/ (web access denied,
 * git-ignored) and only admins can open them; they are deleted once a request is decided.
 */

define('ANTIQUE_VERIFY_SCHEMA', '1');
define('ANTIQUE_VERIFY_MAX_BYTES', 8 * 1024 * 1024);

// i_verify values
define('ANTIQUE_VERIFY_NONE', 0);
define('ANTIQUE_VERIFY_PENDING', 1);
define('ANTIQUE_VERIFY_OK', 2);
define('ANTIQUE_VERIFY_DECLINED', 3);

function antique_verify_schema() {
  if (osc_get_preference('verify_schema', 'starter_theme') == ANTIQUE_VERIFY_SCHEMA) { return; }
  if (osc_get_preference('seller_schema', 'starter_theme') != ANTIQUE_SELLER_SCHEMA) { return; }   // sellers table first
  $db = antique_msg_db();
  $table = antique_sellers_table();
  $cols = array(
    'i_verify'         => "tinyint(1) NOT NULL DEFAULT 0",
    's_legal_name'     => "varchar(150) DEFAULT NULL",
    's_reg_number'     => "varchar(60) DEFAULT NULL",
    's_address'        => "varchar(255) DEFAULT NULL",
    's_website'        => "varchar(200) DEFAULT NULL",
    's_doc'            => "varchar(80) DEFAULT NULL",
    's_verify_note'    => "varchar(500) DEFAULT NULL",
    'dt_verify_req'    => "datetime DEFAULT NULL",
    'dt_verified'      => "datetime DEFAULT NULL",
    // shop page (storefront)
    's_tagline'        => "varchar(160) DEFAULT NULL",
    's_banner'         => "varchar(80) DEFAULT NULL",
    's_policy_returns' => "text",
    's_policy_ship'    => "text",
    's_policy_pay'     => "text"
  );
  $ok = true;
  foreach ($cols as $name => $def) {
    $res = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$name}'");
    if ($res && $res->numRows() == 0) {
      $ok = $db->query("ALTER TABLE `{$table}` ADD `{$name}` {$def}") && $ok;
    }
  }
  if ($ok) { osc_set_preference('verify_schema', ANTIQUE_VERIFY_SCHEMA, 'starter_theme'); }
}
osc_add_hook('init', 'antique_verify_schema', 1);
osc_add_hook('init_admin', 'antique_verify_schema', 1);

// Account > Get verified (sellers)
osc_add_route('al-verify', 'account/verification/?$', 'account/verification/', 'al-verify.php', true, 'custom', 'al-verify', __('Get verified', 'starter'));

function antique_verify_url() { return osc_route_url('al-verify'); }

function antique_verify_dir() {
  return osc_content_path() . 'uploads/al-private/verify/';
}

// The folder that holds ID documents: created with web access denied
function antique_verify_prepare_dir() {
  $root = osc_content_path() . 'uploads/al-private/';
  $dir = antique_verify_dir();
  if (!is_dir($dir) && !@mkdir($dir, 0750, true)) { return false; }
  $deny = "# Private files: never served to browsers\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n";
  foreach (array($root, $dir) as $d) {
    if (!is_file($d . '.htaccess')) { @file_put_contents($d . '.htaccess', $deny); }
    if (!is_file($d . 'index.html')) { @file_put_contents($d . 'index.html', ''); }
  }
  return is_writable($dir);
}

function antique_verify_status($uid) {
  $row = antique_seller_row($uid);
  return $row && isset($row['i_verify']) ? (int)$row['i_verify'] : ANTIQUE_VERIFY_NONE;
}

function antique_seller_verified($uid) {
  return antique_verify_status($uid) === ANTIQUE_VERIFY_OK;
}

/**
 * "Verified dealer" / "Verified seller" badge, or '' for sellers who are not verified.
 */
function antique_seller_badge($uid, $size = 'normal') {
  if (!antique_seller_verified($uid)) { return ''; }
  $u = User::newInstance()->findByPrimaryKey((int)$uid);
  $label = !empty($u['b_company']) ? __('Verified dealer', 'starter') : __('Verified seller', 'starter');
  return '<span class="al-verified' . ($size == 'small' ? ' is-small' : '') . '" title="' . osc_esc_html(__('The site team checked this seller\'s identity and contact details.', 'starter')) . '">'
    . '<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 1.8l2.4 1.8 3-.1.9 2.8 2.5 1.7-1 2.8 1 2.8-2.5 1.7-.9 2.8-3-.1L12 22.2l-2.4-1.8-3 .1-.9-2.8-2.5-1.7 1-2.8-1-2.8 2.5-1.7.9-2.8 3 .1z"/><path d="m8 12.2 2.7 2.7L16.2 9.4" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    . osc_esc_html($label) . '</span>';
}

/**
 * POST hook=al_verify: the seller's verification request (multipart, file "doc").
 */
function antique_verify_post() {
  $back = antique_verify_url();
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(osc_user_login_url()); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  if (!antique_is_seller($uid)) { osc_redirect_to(antique_seller_setup_url()); }
  $status = antique_verify_status($uid);
  if (in_array($status, array(ANTIQUE_VERIFY_PENDING, ANTIQUE_VERIFY_OK), true)) {
    antique_msg_reply(false, $status == ANTIQUE_VERIFY_OK ? __('Your account is already verified.', 'starter') : __('We are already checking your details.', 'starter'), $back);
  }
  $u = User::newInstance()->findByPrimaryKey($uid);
  $dealer = !empty($u['b_company']);
  $clean = function ($k, $max) { return trim(mb_substr(strip_tags((string)Params::getParam($k)), 0, $max)); };
  $legal = $clean('legal_name', 150);
  $reg = $clean('reg_number', 60);
  $address = $clean('address', 255);
  $website = $clean('website', 200);
  if ($website !== '' && !preg_match('#^https?://#i', $website)) { $website = 'https://' . $website; }
  Session::newInstance()->_set('al_verify_form', compact('legal', 'reg', 'address', 'website'));

  $errors = array();
  if (mb_strlen($legal) < 3) { $errors[] = $dealer ? __('Enter your registered business name.', 'starter') : __('Enter your full legal name, as on your ID.', 'starter'); }
  if (mb_strlen($address) < 8) { $errors[] = __('Enter your full address.', 'starter'); }
  if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) { $errors[] = __('Enter a valid website address, or leave it empty.', 'starter'); }
  if (Params::getParam('consent') != '1') { $errors[] = __('Please confirm that the details are true and that we may check them.', 'starter'); }

  $f = isset($_FILES['doc']) ? $_FILES['doc'] : null;
  $ext = '';
  if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = $dealer ? __('Upload your business registration or a recent business document.', 'starter') : __('Upload a photo of your passport, driving licence or national ID card.', 'starter');
  } elseif ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    $errors[] = in_array($f['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true) ? __('The document is too big. The limit is 8 MB.', 'starter') : __('The document did not upload. Please try again.', 'starter');
  } elseif ($f['size'] > ANTIQUE_VERIFY_MAX_BYTES) {
    $errors[] = __('The document is too big. The limit is 8 MB.', 'starter');
  } else {
    $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : '';
    $types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf');
    if (!isset($types[$mime])) {
      $errors[] = __('Upload a JPG, PNG, WebP or PDF file.', 'starter');
    } else {
      $ext = $types[$mime];
    }
  }
  if ($errors) {
    osc_add_flash_error_message(implode('<br>', array_map('osc_esc_html', $errors)));
    osc_redirect_to($back);
  }
  if (!antique_verify_prepare_dir()) {
    antique_msg_reply(false, __('Documents cannot be received right now. Please try again later.', 'starter'), $back);
  }
  $row = antique_seller_row($uid);
  if (!empty($row['s_doc'])) { @unlink(antique_verify_dir() . basename($row['s_doc'])); }
  $file = 'v' . $uid . '-' . bin2hex(random_bytes(12)) . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], antique_verify_dir() . $file)) {
    antique_msg_reply(false, __('The document did not upload. Please try again.', 'starter'), $back);
  }
  @chmod(antique_verify_dir() . $file, 0640);

  $db = antique_msg_db();
  $db->query(sprintf("UPDATE `%s` SET i_verify = %d, s_legal_name = %s, s_reg_number = %s, s_address = %s, s_website = %s, s_doc = %s, s_verify_note = NULL, dt_verify_req = '%s' WHERE fk_i_user_id = %d",
    antique_sellers_table(), ANTIQUE_VERIFY_PENDING, $db->escape($legal), $reg === '' ? 'NULL' : $db->escape($reg), $db->escape($address), $website === '' ? 'NULL' : $db->escape($website), $db->escape($file), antique_msg_now(), $uid));
  Session::newInstance()->_drop('al_verify_form');

  $admin = osc_contact_email();
  if ($admin !== '') {
    antique_mail($admin, osc_page_title(), sprintf(__('Verification request from %s', 'starter'), antique_seller_display_name($uid, $u['s_name'])), __('A seller asked to be verified', 'starter'), array(
      sprintf(__('%1$s (%2$s) sent their details and a document for verification.', 'starter'), osc_esc_html(antique_seller_display_name($uid, $u['s_name'])), osc_esc_html($u['s_email'])),
      __('Check them under Theme Setting > Marketplace in oc-admin.', 'starter')
    ), osc_admin_base_url(true) . '?page=appearance&action=render&file=oc-content/themes/' . osc_current_web_theme() . '/admin/marketplace.php', __('Open Marketplace', 'starter'));
  }
  antique_msg_reply(true, __('Thank you. We will check your details, usually within 2 working days, and email you the result.', 'starter'), $back);
}
osc_add_hook('ajax_al_verify', 'antique_verify_post');

/**
 * Admin decision on a request. $approve true/false, $note shown to the seller when declined.
 */
function antique_verify_decide($uid, $approve, $note = '') {
  $uid = (int)$uid;
  $row = antique_seller_row($uid);
  if (!$row) { return false; }
  $db = antique_msg_db();
  $db->query(sprintf("UPDATE `%s` SET i_verify = %d, s_verify_note = %s, dt_verified = %s, s_doc = NULL WHERE fk_i_user_id = %d",
    antique_sellers_table(), $approve ? ANTIQUE_VERIFY_OK : ANTIQUE_VERIFY_DECLINED, $note === '' ? 'NULL' : $db->escape(mb_substr($note, 0, 500)),
    $approve ? "'" . antique_msg_now() . "'" : 'NULL', $uid));
  // the document is no longer needed once a decision is made
  if (!empty($row['s_doc'])) { @unlink(antique_verify_dir() . basename($row['s_doc'])); }
  $u = User::newInstance()->findByPrimaryKey($uid);
  if ($u) {
    if ($approve) {
      antique_mail($u['s_email'], $u['s_name'], __('Your account is verified', 'starter'), __('You are now a verified seller', 'starter'), array(
        __('Good news: we checked your details and your account is now verified.', 'starter'),
        __('Buyers now see a verified badge next to your name on your listings and shop page.', 'starter')
      ), osc_user_public_profile_url($uid), __('See your shop page', 'starter'));
    } else {
      antique_mail($u['s_email'], $u['s_name'], __('We could not verify your account', 'starter'), __('Verification was not approved', 'starter'), array(
        __('We checked your details but could not verify your account this time.', 'starter'),
        $note !== '' ? sprintf(__('Reason: %s', 'starter'), osc_esc_html($note)) : '',
        __('You can correct your details and send a new request at any time. Your uploaded document has been deleted.', 'starter')
      ), antique_verify_url(), __('Send a new request', 'starter'));
    }
  }
  return true;
}

// oc-admin: open a seller's document (admins only)
function antique_verify_doc_admin() {
  if (!osc_is_admin_user_logged_in()) { http_response_code(403); exit; }
  $row = antique_seller_row((int)Params::getParam('seller'));
  $file = $row && !empty($row['s_doc']) ? antique_verify_dir() . basename($row['s_doc']) : '';
  if ($file === '' || !is_file($file)) { http_response_code(404); echo 'Document not found.'; exit; }
  $types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf');
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  while (ob_get_level()) { ob_end_clean(); }
  header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
  header('Content-Length: ' . filesize($file));
  header('Content-Disposition: inline; filename="verification-' . (int)$row['fk_i_user_id'] . '.' . $ext . '"');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, no-store');
  header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; plugin-types application/pdf");
  readfile($file);
  exit;
}
osc_add_hook('ajax_admin_al_verify_doc', 'antique_verify_doc_admin');

function antique_verify_delete_user($uid) {
  $row = antique_seller_row($uid);
  if ($row && !empty($row['s_doc'])) { @unlink(antique_verify_dir() . basename($row['s_doc'])); }
  if ($row && !empty($row['s_banner']) && function_exists('antique_shop_banner_dir')) { @unlink(antique_shop_banner_dir() . basename($row['s_banner'])); }
  antique_msg_db()->query("DELETE FROM `" . antique_sellers_table() . "` WHERE fk_i_user_id = " . (int)$uid);
}
osc_add_hook('delete_user', 'antique_verify_delete_user');

/* ---------------------------- oc-admin: Marketplace ---------------------------- */

function antique_marketplace_admin_url($tab = '') {
  return osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/marketplace.php') . ($tab !== '' ? '&tab=' . $tab : '');
}


// Decisions posted from the Marketplace page
function antique_marketplace_admin_actions() {
  $act = Params::getParam('al_mk');
  if ($act === '' || !osc_is_admin_user_logged_in()) { return; }
  $tab = Params::getParam('tab');
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    osc_add_flash_error_message(__('The page expired. Please try again.', 'starter'), 'admin');
    osc_redirect_to(antique_marketplace_admin_url($tab));
  }
  $db = antique_msg_db();
  $id = (int)Params::getParam('id');
  switch ($act) {
    case 'approve':
    case 'decline':
      antique_verify_decide($id, $act == 'approve', trim(mb_substr(strip_tags((string)Params::getParam('note')), 0, 500)));
      osc_add_flash_ok_message($act == 'approve' ? __('Seller verified. They have been emailed.', 'starter') : __('Request declined. The seller has been emailed.', 'starter'), 'admin');
      break;
    case 'unverify':
      $db->query(sprintf("UPDATE `%s` SET i_verify = %d, dt_verified = NULL WHERE fk_i_user_id = %d", antique_sellers_table(), ANTIQUE_VERIFY_NONE, $id));
      osc_add_flash_ok_message(__('Verified badge removed.', 'starter'), 'admin');
      break;
    case 'hide_review':
    case 'show_review':
      $db->query(sprintf("UPDATE `%s` SET b_hidden = %d, b_reported = 0 WHERE pk_i_id = %d", antique_reviews_table(), $act == 'hide_review' ? 1 : 0, $id));
      osc_add_flash_ok_message($act == 'hide_review' ? __('Review hidden.', 'starter') : __('Review is visible again.', 'starter'), 'admin');
      break;
    case 'keep_review':
      $db->query(sprintf("UPDATE `%s` SET b_reported = 0 WHERE pk_i_id = %d", antique_reviews_table(), $id));
      osc_add_flash_ok_message(__('Report cleared. The review stays visible.', 'starter'), 'admin');
      break;
    case 'clear_thread':
      $db->query(sprintf("UPDATE `%s` SET b_reported = 0 WHERE pk_i_id = %d", antique_msg_threads_table(), $id));
      osc_add_flash_ok_message(__('Report closed.', 'starter'), 'admin');
      break;
  }
  osc_redirect_to(antique_marketplace_admin_url($tab));
}
osc_add_hook('init_admin', 'antique_marketplace_admin_actions');
