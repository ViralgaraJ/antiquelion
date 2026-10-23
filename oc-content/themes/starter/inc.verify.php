<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Verified sellers. Sellers must be verified before they can list items. A seller sends their legal or business name, address and a
 * document (photo ID, or business registration for dealers) from Account > Get
 * verified; the site team checks it under Theme Setting > Marketplace in oc-admin and
 * approves or declines. Approved sellers show a "Verified dealer" / "Verified seller"
 * badge. Documents are kept in oc-content/uploads/al-private/ (web access denied,
 * git-ignored) and only admins can open them; they are deleted once a request is decided.
 */

define('ANTIQUE_VERIFY_SCHEMA', '2');
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
    's_doc'            => "varchar(80) DEFAULT NULL",   // file names, "front|back"
    's_doc_type'       => "varchar(20) DEFAULT NULL",
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
osc_add_route('al-verify', 'account/verification/?(?:\?.*)?$', 'account/verification/', 'al-verify.php', true, 'custom', 'al-verify', __('Get verified', 'starter'));

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

/**
 * Document types a seller can send: private sellers send photo ID, dealers a business document.
 * 'sides' = 2 when the document has a back side worth sending.
 */
function antique_verify_doc_types($dealer) {
  if ($dealer) {
    return array(
      'business_reg' => array('label' => __('Business registration certificate', 'starter'), 'sides' => 1),
      'business_doc' => array('label' => __('Recent business document (tax, utility or bank letter)', 'starter'), 'sides' => 1),
      'passport'     => array('label' => __('Owner\'s passport', 'starter'), 'sides' => 1),
      'licence'      => array('label' => __('Owner\'s driving licence', 'starter'), 'sides' => 2)
    );
  }
  return array(
    'passport' => array('label' => __('Passport', 'starter'), 'sides' => 1),
    'licence'  => array('label' => __('Driving licence', 'starter'), 'sides' => 2),
    'id_card'  => array('label' => __('National ID card', 'starter'), 'sides' => 2),
    'permit'   => array('label' => __('Residence permit', 'starter'), 'sides' => 2)
  );
}

function antique_verify_doc_label($type) {
  $all = antique_verify_doc_types(false) + antique_verify_doc_types(true);
  return isset($all[$type]) ? $all[$type]['label'] : __('Document', 'starter');
}

// Stored document file names for a seller row (front first)
function antique_verify_docs($row) {
  if (!$row || empty($row['s_doc'])) { return array(); }
  return array_values(array_filter(array_map('basename', explode('|', (string)$row['s_doc']))));
}

function antique_verify_delete_docs($row) {
  foreach (antique_verify_docs($row) as $f) { @unlink(antique_verify_dir() . $f); }
}

// The largest upload the server accepts, in bytes (PHP upload and post limits)
function antique_verify_upload_limit() {
  $bytes = function ($v) {
    $v = trim((string)$v);
    if ($v === '' || $v === '-1' || $v === '0') { return PHP_INT_MAX; }
    $n = (float)$v;
    switch (strtolower(substr($v, -1))) { case 'g': $n *= 1024; case 'm': $n *= 1024; case 'k': $n *= 1024; }
    return (int)$n;
  };
  return min(ANTIQUE_VERIFY_MAX_BYTES, $bytes(ini_get('upload_max_filesize')), $bytes(ini_get('post_max_size')));
}

function antique_verify_limit_text() {
  $mb = antique_verify_upload_limit() / 1048576;
  return ($mb >= 1 ? rtrim(rtrim(number_format($mb, 1), '0'), '.') . ' MB' : round($mb * 1024) . ' KB');
}

/**
 * File type from the file's first bytes: jpg, png, webp, pdf, heic or ''. Does not rely on
 * PHP's fileinfo extension, which some hosts leave switched off.
 */
function antique_verify_sniff($path) {
  $h = @file_get_contents($path, false, null, 0, 16);
  if ($h === false || strlen($h) < 12) { return ''; }
  if (strncmp($h, "\xFF\xD8\xFF", 3) === 0) { return 'jpg'; }
  if (strncmp($h, "\x89PNG\r\n\x1A\n", 8) === 0) { return 'png'; }
  if (substr($h, 0, 4) === 'RIFF' && substr($h, 8, 4) === 'WEBP') { return 'webp'; }
  if (strpos(substr($h, 0, 8), '%PDF-') !== false || strpos((string)@file_get_contents($path, false, null, 0, 1024), '%PDF-') !== false) { return 'pdf'; }
  if (substr($h, 4, 4) === 'ftyp' && in_array(substr($h, 8, 4), array('heic', 'heix', 'hevc', 'heim', 'heis', 'mif1', 'msf1'), true)) { return 'heic'; }
  return '';
}

/**
 * Sellers can list items only once the site team has verified them.
 */
function antique_can_list($uid = null) {
  if ($uid === null) { $uid = osc_is_web_user_logged_in() ? osc_logged_user_id() : 0; }
  return antique_is_seller($uid) && antique_seller_verified($uid);
}

/**
 * Notice on the seller's listings page while they cannot list yet.
 */
function antique_verify_banner() {
  if (!osc_is_web_user_logged_in() || !antique_is_seller() || antique_can_list()) { return ''; }
  $st = antique_verify_status(osc_logged_user_id());
  if ($st == ANTIQUE_VERIFY_PENDING) {
    $text = __('We are checking your details. You can list new items as soon as your account is verified, usually within 2 working days.', 'starter');
    $btn = __('See status', 'starter');
  } else {
    $text = $st == ANTIQUE_VERIFY_DECLINED ? __('Your last verification request was not approved. Send a new one to start listing items.', 'starter') : __('Get verified to list items. Every seller is checked by our team to keep buyers safe.', 'starter');
    $btn = __('Get verified', 'starter');
  }
  return '<div class="al-notice al-verify-banner"><p>' . osc_esc_html($text) . '</p><a class="al-btn al-btn-outline" href="' . osc_esc_html(antique_verify_url()) . '">' . osc_esc_html($btn) . '</a></div>';
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
  // over the server's post limit PHP drops the whole form, token included
  if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    antique_msg_reply(false, sprintf(__('The files are too big to send. Use photos under %s in total, or take the photo again at a lower resolution.', 'starter'), antique_verify_limit_text()), $back);
  }
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
  $types = antique_verify_doc_types($dealer);
  $docType = (string)Params::getParam('doc_type');
  Session::newInstance()->_set('al_verify_form', compact('legal', 'reg', 'address', 'website', 'docType'));

  $errors = array();
  if (!isset($types[$docType])) { $errors[] = $dealer ? __('Choose the document you are sending.', 'starter') : __('Choose your ID type: passport, driving licence, national ID card or residence permit.', 'starter'); }
  if (mb_strlen($legal) < 3) { $errors[] = $dealer ? __('Enter your registered business name.', 'starter') : __('Enter your full legal name, as on your ID.', 'starter'); }
  if (mb_strlen($address) < 8) { $errors[] = __('Enter your full address.', 'starter'); }
  if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) { $errors[] = __('Enter a valid website address, or leave it empty.', 'starter'); }
  if (Params::getParam('consent') != '1') { $errors[] = __('Please confirm that the details are true and that we may check them.', 'starter'); }

  $limit = antique_verify_limit_text();
  $check = function ($field, $required, $what) use (&$errors, $limit) {
    $f = isset($_FILES[$field]) ? $_FILES[$field] : null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
      if ($required) { $errors[] = $what; }
      return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
      $errors[] = in_array($f['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true) ? sprintf(__('The document is too big. The limit is %s.', 'starter'), $limit) : __('The document did not upload. Please try again.', 'starter');
      return null;
    }
    if ($f['size'] > ANTIQUE_VERIFY_MAX_BYTES) { $errors[] = sprintf(__('The document is too big. The limit is %s.', 'starter'), $limit); return null; }
    $ext = antique_verify_sniff($f['tmp_name']);
    if ($ext === 'heic') { $errors[] = __('This is an iPhone HEIC photo, which we cannot open. Take a screenshot of the photo and upload that, or set Settings > Camera > Formats to Most Compatible.', 'starter'); return null; }
    if ($ext === '') { $errors[] = __('Upload a JPG, PNG, WebP or PDF file.', 'starter'); return null; }
    return array('tmp' => $f['tmp_name'], 'ext' => $ext);
  };
  $front = $check('doc', true, $dealer ? __('Upload your business document.', 'starter') : __('Upload a photo of your ID (the side with your photo).', 'starter'));
  $backSide = (isset($types[$docType]) && $types[$docType]['sides'] == 2) ? $check('doc_back', false, '') : null;
  if ($errors) {
    osc_add_flash_error_message(implode('<br>', array_map('osc_esc_html', $errors)));
    osc_redirect_to($back);
  }
  if (!antique_verify_prepare_dir()) {
    antique_msg_reply(false, __('Documents cannot be received right now. Please try again later.', 'starter'), $back);
  }
  antique_verify_delete_docs(antique_seller_row($uid));
  $files = array();
  foreach (array($front, $backSide) as $doc) {
    if (!$doc) { continue; }
    $name = 'v' . $uid . '-' . bin2hex(random_bytes(10)) . '.' . $doc['ext'];
    if (!move_uploaded_file($doc['tmp'], antique_verify_dir() . $name)) {
      foreach ($files as $done) { @unlink(antique_verify_dir() . $done); }
      antique_msg_reply(false, __('The document did not upload. Please try again.', 'starter'), $back);
    }
    @chmod(antique_verify_dir() . $name, 0640);
    $files[] = $name;
  }
  $file = implode('|', $files);

  $db = antique_msg_db();
  $db->query(sprintf("UPDATE `%s` SET i_verify = %d, s_legal_name = %s, s_reg_number = %s, s_address = %s, s_website = %s, s_doc = %s, s_doc_type = %s, s_verify_note = NULL, dt_verify_req = '%s' WHERE fk_i_user_id = %d",
    antique_sellers_table(), ANTIQUE_VERIFY_PENDING, $db->escape($legal), $reg === '' ? 'NULL' : $db->escape($reg), $db->escape($address), $website === '' ? 'NULL' : $db->escape($website), $db->escape($file), $db->escape($docType), antique_msg_now(), $uid));
  Session::newInstance()->_drop('al_verify_form');

  $admin = osc_contact_email();
  if ($admin !== '') {
    antique_mail($admin, osc_page_title(), sprintf(__('Verification request from %s', 'starter'), antique_seller_display_name($uid, $u['s_name'])), __('A seller asked to be verified', 'starter'), array(
      sprintf(__('%1$s (%2$s) sent their details and a document for verification.', 'starter'), osc_esc_html(antique_seller_display_name($uid, $u['s_name'])), osc_esc_html($u['s_email'])),
      __('Check them under Theme Setting > Marketplace in oc-admin.', 'starter')
    ), osc_admin_base_url(true) . '?page=appearance&action=render&file=oc-content/themes/' . osc_current_web_theme() . '/admin/marketplace.php', __('Open Marketplace', 'starter'));
  }
  antique_msg_reply(true, __('Thank you. We will check your details, usually within 2 working days, and email you the result. You can list items as soon as you are verified.', 'starter'), $back);
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
  // the documents are no longer needed once a decision is made
  antique_verify_delete_docs($row);
  $u = User::newInstance()->findByPrimaryKey($uid);
  if ($u) {
    if ($approve) {
      antique_mail($u['s_email'], $u['s_name'], __('Your account is verified', 'starter'), __('You are now a verified seller', 'starter'), array(
        __('Good news: we checked your details and your account is now verified.', 'starter'),
        __('You can now list items for sale, and buyers see a verified badge next to your name on your listings and shop page.', 'starter')
      ), osc_item_post_url(), __('List an item', 'starter'));
    } else {
      antique_mail($u['s_email'], $u['s_name'], __('We could not verify your account', 'starter'), __('Verification was not approved', 'starter'), array(
        __('We checked your details but could not verify your account this time.', 'starter'),
        $note !== '' ? sprintf(__('Reason: %s', 'starter'), osc_esc_html($note)) : '',
        __('You can correct your details and send a new request at any time. Your uploaded documents have been deleted.', 'starter')
      ), antique_verify_url(), __('Send a new request', 'starter'));
    }
  }
  return true;
}

// oc-admin: open a seller's document (admins only)
function antique_verify_doc_admin() {
  if (!osc_is_admin_user_logged_in()) { http_response_code(403); exit; }
  $docs = antique_verify_docs(antique_seller_row((int)Params::getParam('seller')));
  $n = (int)Params::getParam('n');
  $row = antique_seller_row((int)Params::getParam('seller'));
  $file = isset($docs[$n]) ? antique_verify_dir() . $docs[$n] : '';
  if ($file === '' || !is_file($file)) { http_response_code(404); echo 'Document not found.'; exit; }
  $types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf');
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  while (ob_get_level()) { ob_end_clean(); }
  header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
  header('Content-Length: ' . filesize($file));
  header('Content-Disposition: inline; filename="verification-' . (int)$row['fk_i_user_id'] . ($n ? '-back' : '') . '.' . $ext . '"');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, no-store');
  header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; plugin-types application/pdf");
  readfile($file);
  exit;
}
osc_add_hook('ajax_admin_al_verify_doc', 'antique_verify_doc_admin');

function antique_verify_delete_user($uid) {
  $row = antique_seller_row($uid);
  antique_verify_delete_docs($row);
  if ($row && !empty($row['s_banner']) && function_exists('antique_shop_banner_dir')) { @unlink(antique_shop_banner_dir() . basename($row['s_banner'])); }
  antique_msg_db()->query("DELETE FROM `" . antique_sellers_table() . "` WHERE fk_i_user_id = " . (int)$uid);
}
osc_add_hook('delete_user', 'antique_verify_delete_user');

/* ---------------------------- oc-admin: Marketplace ---------------------------- */

function antique_marketplace_admin_url($tab = '') {
  return osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/marketplace.php') . ($tab !== '' ? '&tab=' . $tab : '');
}


/*
 * Moderation reasons. When a listing is hidden or deleted, or a member suspended, the
 * person is told why (a "statement of reasons", as the EU Digital Services Act expects).
 */
function antique_moderation_reasons() {
  return array(
    'prohibited' => __('The item is prohibited, stolen or not genuine', 'starter'),
    'scam'       => __('The listing looks like spam, a scam or is misleading', 'starter'),
    'duplicate'  => __('The same item is listed more than once', 'starter'),
    'terms'      => __('The listing breaks our terms of use', 'starter'),
    'reports'    => __('Several members reported problems with your account', 'starter'),
    'other'      => __('Another reason (see the note below)', 'starter')
  );
}

// Reason picker and note for a moderation form
function antique_moderation_fields() {
  $html = '<select name="reason" required><option value="">' . osc_esc_html(__('Reason (emailed to the member)', 'starter')) . '</option>';
  foreach (antique_moderation_reasons() as $k => $label) { $html .= '<option value="' . $k . '">' . osc_esc_html($label) . '</option>'; }
  return $html . '</select><input type="text" name="note" maxlength="500" placeholder="' . osc_esc_html(__('Note for the member (optional)', 'starter')) . '" />';
}

/**
 * Emails the member what was done and why. $what: 'hidden', 'deleted' or 'suspended'.
 */
function antique_moderation_notify($what, $email, $name, $title = '') {
  if (trim((string)$email) === '') { return; }
  $reasons = antique_moderation_reasons();
  $key = (string)Params::getParam('reason');
  $note = trim(mb_substr(strip_tags((string)Params::getParam('note')), 0, 500));
  $reason = isset($reasons[$key]) ? $reasons[$key] : $reasons['terms'];
  $quoted = '<strong>' . osc_esc_html($title) . '</strong>';
  $texts = array(
    'hidden'    => array(sprintf(__('Your listing was hidden: %s', 'starter'), $title), __('We hid one of your listings', 'starter'), sprintf(__('We have hidden your listing %s. Buyers can no longer see it.', 'starter'), $quoted)),
    'deleted'   => array(sprintf(__('Your listing was removed: %s', 'starter'), $title), __('We removed one of your listings', 'starter'), sprintf(__('We have removed your listing %s from AntiqueLion.', 'starter'), $quoted)),
    'suspended' => array(__('Your account has been suspended', 'starter'), __('Your account has been suspended', 'starter'), __('We have suspended your account. You cannot sign in, and your listings are hidden.', 'starter'))
  );
  $t = $texts[$what];
  $lines = array($t[2], sprintf(__('Reason: %s.', 'starter'), osc_esc_html($reason)));
  if ($note !== '') { $lines[] = antique_mail_quote($note, 500); }
  $lines[] = __('If you think this is a mistake, reply through our contact page and tell us why. We review every appeal.', 'starter');
  antique_mail($email, $name, $t[0], $t[1], $lines, osc_contact_url(), __('Contact us', 'starter'));
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
    case 'verify_direct':
      antique_verify_decide($id, true);
      osc_add_flash_ok_message(__('Seller verified. They can list items now and have been emailed.', 'starter'), 'admin');
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
    case 'sitemap':
      osc_add_flash_ok_message(antique_seo_write_sitemap() ? __('Sitemap and robots.txt rebuilt.', 'starter') : __('Could not write sitemap.xml or robots.txt. Check that the site folder is writable.', 'starter'), 'admin');
      break;
    case 'seo_save':
      $ga = strtoupper(trim((string)Params::getParam('seo_ga4')));
      if ($ga !== '' && !preg_match('/^G-[A-Z0-9]{4,16}$/', $ga)) {
        osc_add_flash_error_message(__('The Analytics ID should look like G-ABC123XYZ.', 'starter'), 'admin');
        break;
      }
      $code = function ($k) { $v = trim(html_entity_decode((string)Params::getParam($k, false, false), ENT_QUOTES, 'UTF-8')); if (preg_match('/content=["\']([^"\']+)/', $v, $m)) { $v = $m[1]; } return preg_replace('/[^A-Za-z0-9_\-]/', '', $v); };
      osc_set_preference('seo_ga4', $ga, 'starter_theme');
      osc_set_preference('seo_google_verify', $code('seo_google_verify'), 'starter_theme');
      osc_set_preference('seo_bing_verify', $code('seo_bing_verify'), 'starter_theme');
      $desc = trim(mb_substr(strip_tags((string)Params::getParam('seo_home_desc')), 0, 300));
      osc_set_preference('seo_home_desc', $desc === antique_seo_default_home_desc() ? '' : $desc, 'starter_theme');
      osc_add_flash_ok_message(__('SEO settings saved.', 'starter'), 'admin');
      break;
    case 'clear_thread':
      $db->query(sprintf("UPDATE `%s` SET b_reported = 0 WHERE pk_i_id = %d", antique_msg_threads_table(), $id));
      osc_add_flash_ok_message(__('Report closed.', 'starter'), 'admin');
      break;
    // Reported listings (Osclass's report counters in t_item_stats)
    case 'clear_item_reports':
      Item::newInstance()->clearStat($id, 'all');
      osc_add_flash_ok_message(__('Reports dismissed. The listing stays as it is.', 'starter'), 'admin');
      break;
    case 'hide_item':
    case 'show_item':
      $items = new ItemActions(true);
      $act == 'hide_item' ? $items->disable($id) : $items->enable($id);
      if ($act == 'hide_item') {
        Item::newInstance()->clearStat($id, 'all');
        $item = Item::newInstance()->findByPrimaryKey($id);
        if ($item) { antique_moderation_notify('hidden', $item['s_contact_email'], $item['s_contact_name'], $item['s_title']); }
      }
      osc_add_flash_ok_message($act == 'hide_item' ? __('Listing hidden. Only you and the seller can see it now.', 'starter') : __('Listing is visible again.', 'starter'), 'admin');
      break;
    case 'delete_item':
      $item = Item::newInstance()->findByPrimaryKey($id);
      if ($item) {
        $items = new ItemActions(true);
        $items->delete($item['s_secret'], $id);
        antique_moderation_notify('deleted', $item['s_contact_email'], $item['s_contact_name'], $item['s_title']);
      }
      osc_add_flash_ok_message(__('Listing deleted.', 'starter'), 'admin');
      break;
    // Suspending a member disables their account and hides their listings (Osclass "Block")
    case 'suspend_user':
    case 'unsuspend_user':
      require_once LIB_PATH . 'osclass/UserActions.php';
      $users = new UserActions(true);
      $act == 'suspend_user' ? $users->disable($id) : $users->enable($id);
      if ($act == 'suspend_user') {
        $u = User::newInstance()->findByPrimaryKey($id);
        if ($u) { antique_moderation_notify('suspended', $u['s_email'], $u['s_name']); }
      }
      osc_add_flash_ok_message($act == 'suspend_user' ? __('Member suspended. They cannot sign in and their listings are hidden.', 'starter') : __('Member restored. Their listings are visible again.', 'starter'), 'admin');
      break;
  }
  osc_redirect_to(antique_marketplace_admin_url($tab));
}
osc_add_hook('init_admin', 'antique_marketplace_admin_actions');
