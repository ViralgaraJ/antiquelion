<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * "Report this listing": the reasons map to Osclass's built-in report counters
 * (oc-admin > Listings > Reported). The site's contact address also gets an email,
 * once per visitor session and listing, so reports are not missed.
 */

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

  $sent = (array)Session::newInstance()->_get('al_reported');
  if (in_array($id, $sent)) { return; }
  $sent[] = $id;
  Session::newInstance()->_set('al_reported', $sent);

  $item = Item::newInstance()->findByPrimaryKey($id);
  if (!$item) { return; }
  $who = osc_is_web_user_logged_in() ? osc_logged_user_name() . ' (' . osc_logged_user_email() . ')' : __('a visitor who was not signed in', 'starter');
  $paras = array(
    sprintf(__('%1$s was reported as: %2$s.', 'starter'), '<strong>' . osc_esc_html($item['s_title']) . '</strong>', '<strong>' . osc_esc_html($reasons[$as][0]) . '</strong>'),
    sprintf(__('Reported by %s.', 'starter'), osc_esc_html($who)),
    __('Review it under oc-admin > Listings > Reported, and hide or delete it if it breaks the terms.', 'starter')
  );
  $subject = sprintf(__('Listing reported: %s', 'starter'), $item['s_title']);
  if (function_exists('antique_bids_mail')) {
    antique_bids_mail(osc_contact_email(), osc_page_title(), $subject, __('A listing was reported', 'starter'), $paras, osc_item_url_from_item($item), __('Open the listing', 'starter'));
  } else {
    @osc_sendMail(array('from' => _osc_from_email_aux(), 'to' => osc_contact_email(), 'to_name' => osc_page_title(), 'subject' => $subject, 'body' => '<p>' . implode('</p><p>', $paras) . '</p>', 'alt_body' => strip_tags(implode("\n", $paras))));
  }
}
osc_add_hook('init', 'antique_report_notify');
