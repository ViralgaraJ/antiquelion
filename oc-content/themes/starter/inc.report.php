<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * "Report this listing": the reasons map to Osclass's built-in report counters
 * (Marketplace > Reported listings). The site's contact address also gets an email.
 * One report per listing per visitor: repeats from the same session or network
 * (hashed IP, 30 days) are stopped before Osclass counts them.
 */

define('ANTIQUE_REPORT_DAYS', 30);

function antique_report_table() { return DB_TABLE_PREFIX . 't_al_report_log'; }

function antique_report_schema() {
  if ((int)osc_get_preference('report_schema', 'starter_theme') >= 1) { return; }
  $ok = antique_msg_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_report_table() . "` (
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `s_ip_hash` char(64) NOT NULL,
    `dt_date` datetime NOT NULL,
    PRIMARY KEY (`fk_i_item_id`, `s_ip_hash`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('report_schema', 1, 'starter_theme', 'INTEGER'); }
}
osc_add_hook('init', 'antique_report_schema', 1);

function antique_report_reasons() {
  return array(
    'spam'      => array(__('Spam or a scam', 'starter'), 'osc_item_link_spam'),
    'offensive' => array(__('Fake, stolen or prohibited item', 'starter'), 'osc_item_link_offensive'),
    'badcat'    => array(__('Wrong category', 'starter'), 'osc_item_link_bad_category'),
    'repeated'  => array(__('Duplicate listing', 'starter'), 'osc_item_link_repeated'),
    'expired'   => array(__('Already sold or no longer available', 'starter'), 'osc_item_link_expired')
  );
}

function antique_report_notify() {
  if (Params::getParam('page') != 'item' || Params::getParam('action') != 'mark' || osc_item_mark_disable()) { return; }
  $reasons = antique_report_reasons();
  $as = Params::getParam('as');
  $id = (int)Params::getParam('id');
  if (!isset($reasons[$as]) || $id <= 0 || !osc_visitor_is_real_user()) { return; }

  $item = Item::newInstance()->findByPrimaryKey($id);
  if (!$item) { return; }

  // already reported by this visitor: thank them again, but do not count it twice
  $db = antique_msg_db();
  $ip = hash_hmac('sha256', (string)antique_visitor_ip(), function_exists('antique_notify_secret') ? antique_notify_secret() : DB_NAME);
  $db->query(sprintf("DELETE FROM `%s` WHERE dt_date < '%s'", antique_report_table(), date('Y-m-d H:i:s', time() - ANTIQUE_REPORT_DAYS * 86400)));
  $sent = (array)Session::newInstance()->_get('al_reported');
  $db->query(sprintf("INSERT IGNORE INTO `%s` (fk_i_item_id, s_ip_hash, dt_date) VALUES (%d, '%s', '%s')", antique_report_table(), $id, $ip, date('Y-m-d H:i:s')));
  if (in_array($id, $sent) || $db->affectedRows() < 1) {
    osc_add_flash_info_message(__('You have already reported this listing. Thank you, we will look at it.', 'starter'));
    osc_redirect_to(osc_item_url_from_item($item));
  }
  $sent[] = $id;
  Session::newInstance()->_set('al_reported', $sent);

  $who = osc_is_web_user_logged_in() ? osc_logged_user_name() . ' (' . osc_logged_user_email() . ')' : __('a visitor who was not signed in', 'starter');
  $paras = array(
    sprintf(__('%1$s was reported as: %2$s.', 'starter'), '<strong>' . osc_esc_html($item['s_title']) . '</strong>', '<strong>' . osc_esc_html($reasons[$as][0]) . '</strong>'),
    sprintf(__('Reported by %s.', 'starter'), osc_esc_html($who)),
    __('Review it under oc-admin > Theme Setting > Marketplace > Reported listings, and hide or delete it if it breaks the terms.', 'starter')
  );
  $subject = sprintf(__('Listing reported: %s', 'starter'), $item['s_title']);
  if (function_exists('antique_bids_mail')) {
    antique_bids_mail(osc_contact_email(), osc_page_title(), $subject, __('A listing was reported', 'starter'), $paras, osc_item_url_from_item($item), __('Open the listing', 'starter'));
  } else {
    @osc_sendMail(array('from' => _osc_from_email_aux(), 'to' => osc_contact_email(), 'to_name' => osc_page_title(), 'subject' => $subject, 'body' => '<p>' . implode('</p><p>', $paras) . '</p>', 'alt_body' => strip_tags(implode("\n", $paras))));
  }
}
osc_add_hook('init', 'antique_report_notify');
