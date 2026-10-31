<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * "Report this listing". Only signed-in members with a confirmed email can report,
 * through a POST form with a reason and details, once per listing and at most
 * ANTIQUE_REPORT_DAILY times a day. Osclass's old one-click report links
 * (/item/mark/...) are switched off: bots and link scanners followed them.
 *
 * Reports are rows in t_al_reports (open / dismissed / actioned) and are reviewed under
 * Marketplace > Reported listings. The admin gets one digest email a day, and an
 * instant email only when a report is urgent (fake, stolen or prohibited item) or
 * ANTIQUE_REPORT_ALERT_AT different members report the same listing.
 */

define('ANTIQUE_REPORT_DAILY', 10);         // reports per member per 24 hours
define('ANTIQUE_REPORT_ALERT_AT', 3);       // different reporters that trigger an instant email
define('ANTIQUE_REPORT_DIGEST_HOUR', 8);    // daily digest after this hour (site timezone)
define('ANTIQUE_REPORT_DETAILS_MIN', 10);   // characters of explanation for spam / prohibited

function antique_reports_table() { return DB_TABLE_PREFIX . 't_al_reports'; }

function antique_report_schema() {
  if ((int)osc_get_preference('report_schema', 'starter_theme') >= 2) { return; }
  $ok = antique_msg_db()->query("CREATE TABLE IF NOT EXISTS `" . antique_reports_table() . "` (
    `pk_i_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `fk_i_user_id` int(10) unsigned NOT NULL,
    `fk_i_seller_id` int(10) unsigned DEFAULT NULL,
    `s_reason` varchar(20) NOT NULL,
    `s_details` text,
    `s_status` varchar(12) NOT NULL DEFAULT 'open',
    `b_alerted` tinyint(1) NOT NULL DEFAULT 0,
    `dt_created` datetime NOT NULL,
    `dt_closed` datetime DEFAULT NULL,
    PRIMARY KEY (`pk_i_id`),
    UNIQUE KEY `item_user` (`fk_i_item_id`, `fk_i_user_id`),
    KEY `status` (`s_status`, `dt_created`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($ok) { osc_set_preference('report_schema', 2, 'starter_theme', 'INTEGER'); }
}
osc_add_hook('init', 'antique_report_schema', 1);

/**
 * Reasons: key => array(label, urgent, details required). Keys match Osclass's old
 * report counters, which the admin page still shows for older reports.
 */
function antique_report_reasons() {
  return array(
    'offensive' => array(__('Fake, stolen or prohibited item', 'starter'), true, true),
    'spam'      => array(__('Spam or a scam', 'starter'), false, true),
    'repeated'  => array(__('Duplicate listing', 'starter'), false, false),
    'badcat'    => array(__('Wrong category', 'starter'), false, false),
    'expired'   => array(__('Already sold or no longer available', 'starter'), false, false)
  );
}

// The old one-click links: thank whoever opened one, count nothing
function antique_report_block_links() {
  if (Params::getParam('page') != 'item' || Params::getParam('action') != 'mark') { return; }
  $item = Item::newInstance()->findByPrimaryKey((int)Params::getParam('id'));
  osc_add_flash_info_message(__('To report a listing, sign in and use "Report this listing" on the page.', 'starter'));
  osc_redirect_to($item ? osc_item_url_from_item($item) . '#al-report' : osc_base_url());
}
osc_add_hook('init', 'antique_report_block_links', 2);

function antique_report_mine($itemId, $uid) {
  $res = antique_msg_db()->query(sprintf("SELECT * FROM `%s` WHERE fk_i_item_id = %d AND fk_i_user_id = %d", antique_reports_table(), (int)$itemId, (int)$uid));
  return ($res && $res->numRows() > 0) ? $res->row() : null;
}

/**
 * "Report this listing" panel on the item page.
 */
function antique_report_box($item) {
  $itemId = (int)$item['pk_i_id'];
  $url = osc_item_url_from_item($item);
  ob_start();
  ?>
  <details class="al-report" id="al-report">
    <summary><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 22V4M4 4h13l-2 4 2 4H4"/></svg><?php _e('Report this listing', 'starter'); ?></summary>
    <?php if (!osc_is_web_user_logged_in()) { ?>
      <p><?php _e('Members can report listings that break our rules. Sign in so we can follow up with you.', 'starter'); ?></p>
      <a class="al-btn al-btn-outline al-report-signin" href="<?php echo osc_esc_html(antique_login_back_url($url . '#al-report')); ?>"><?php _e('Sign in to report', 'starter'); ?></a>
    <?php } elseif ($mine = antique_report_mine($itemId, osc_logged_user_id())) { ?>
      <p><?php echo sprintf(__('You reported this listing on %s. Thank you, our team will review it.', 'starter'), osc_esc_html(date('j M Y', strtotime($mine['dt_created'])))); ?></p>
    <?php } else { ?>
      <form method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_report')); ?>" class="al-report-form">
        <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
        <input type="hidden" name="item" value="<?php echo $itemId; ?>" />
        <p><?php _e('What is wrong with it? The seller does not see who reported.', 'starter'); ?></p>
        <fieldset>
          <legend class="al-sr-only"><?php _e('Reason', 'starter'); ?></legend>
          <?php foreach (antique_report_reasons() as $k => $r) { ?>
            <label class="al-check"><input type="radio" name="reason" value="<?php echo $k; ?>" required /><span><?php echo osc_esc_html($r[0]); ?></span></label>
          <?php } ?>
        </fieldset>
        <label class="al-report-details"><span><?php _e('Details', 'starter'); ?></span>
          <textarea name="details" rows="3" maxlength="1000" placeholder="<?php echo osc_esc_html(__('e.g. These photos are copied from another website.', 'starter')); ?>"></textarea>
        </label>
        <button type="submit" class="al-btn al-btn-outline"><?php _e('Send report', 'starter'); ?></button>
      </form>
    <?php } ?>
  </details>
  <script>if (location.hash === '#al-report') { document.getElementById('al-report').open = true; }</script>
  <?php
  return ob_get_clean();
}

/**
 * POST hook=al_report: item, reason, details, octoken.
 */
function antique_report_post() {
  $item = Item::newInstance()->findByPrimaryKey((int)Params::getParam('item'));
  $back = $item ? osc_item_url_from_item($item) . '#al-report' : osc_base_url();
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$item) { osc_redirect_to($back); }
  if (!osc_is_web_user_logged_in()) { osc_redirect_to(antique_login_back_url($back)); }
  if (!osc_csrfguard_validate_token(Params::getParam('octoken'))) {
    antique_msg_reply(false, __('Your session expired. Please try again.', 'starter'), $back);
  }
  $uid = (int)osc_logged_user_id();
  $user = User::newInstance()->findByPrimaryKey($uid);
  if (!$user || !(int)$user['b_active']) {
    antique_msg_reply(false, __('Please confirm your email address before reporting listings.', 'starter'), $back);
  }
  if ((int)$item['fk_i_user_id'] === $uid) { osc_redirect_to($back); }

  $reasons = antique_report_reasons();
  $reason = (string)Params::getParam('reason');
  if (!isset($reasons[$reason])) { antique_msg_reply(false, __('Choose what is wrong with the listing.', 'starter'), $back); }
  $details = trim(mb_substr(str_replace("\r", '', strip_tags((string)Params::getParam('details'))), 0, 1000));
  if ($reasons[$reason][2] && mb_strlen($details) < ANTIQUE_REPORT_DETAILS_MIN) {
    antique_msg_reply(false, __('Please add a few words about what is wrong, so we can check it quickly.', 'starter'), $back);
  }

  $db = antique_msg_db();
  $t = antique_reports_table();
  if (antique_report_mine($item['pk_i_id'], $uid)) {
    antique_msg_reply(true, __('You have already reported this listing. Thank you, we will look at it.', 'starter'), $back);
  }
  $res = $db->query(sprintf("SELECT COUNT(*) AS n FROM `%s` WHERE fk_i_user_id = %d AND dt_created > '%s'", $t, $uid, date('Y-m-d H:i:s', time() - 86400)));
  if ($res && (int)$res->row()['n'] >= ANTIQUE_REPORT_DAILY) {
    antique_msg_reply(false, __('You have sent a lot of reports today. Please try again tomorrow, or contact us if something is urgent.', 'starter'), $back);
  }

  $db->query(sprintf("INSERT IGNORE INTO `%s` (fk_i_item_id, fk_i_user_id, fk_i_seller_id, s_reason, s_details, dt_created) VALUES (%d, %d, %s, %s, %s, '%s')",
    $t, (int)$item['pk_i_id'], $uid, (int)$item['fk_i_user_id'] > 0 ? (int)$item['fk_i_user_id'] : 'NULL', $db->escape($reason), $db->escape($details), date('Y-m-d H:i:s')));
  $reportId = (int)$db->insertedId();
  if ($reportId > 0) { antique_report_maybe_alert($item, $reportId, $reason); }
  antique_msg_reply(true, __('Thank you. Our team will review this listing.', 'starter'), $back);
}
osc_add_hook('ajax_al_report', 'antique_report_post');

/**
 * Instant email for urgent reports, or when ANTIQUE_REPORT_ALERT_AT members reported
 * the same listing. At most one instant email per listing a day; the rest wait for the digest.
 */
function antique_report_maybe_alert($item, $reportId, $reason) {
  $db = antique_msg_db();
  $t = antique_reports_table();
  $itemId = (int)$item['pk_i_id'];
  $reasons = antique_report_reasons();
  $res = $db->query(sprintf("SELECT COUNT(*) AS n, SUM(b_alerted = 1 AND dt_created > '%s') AS alerted FROM `%s` WHERE fk_i_item_id = %d AND s_status = 'open'",
    date('Y-m-d H:i:s', time() - 86400), $t, $itemId));
  $row = $res ? $res->row() : array('n' => 1, 'alerted' => 0);
  $urgent = !empty($reasons[$reason][1]);
  if ((int)$row['alerted'] > 0 || (!$urgent && (int)$row['n'] < ANTIQUE_REPORT_ALERT_AT)) { return; }
  $db->query(sprintf("UPDATE `%s` SET b_alerted = 1 WHERE pk_i_id = %d", $t, (int)$reportId));

  $admin = osc_contact_email();
  if ($admin == '') { return; }
  $lines = array(
    $urgent
      ? sprintf(__('%1$s was reported as: %2$s.', 'starter'), '<strong>' . osc_esc_html($item['s_title']) . '</strong>', '<strong>' . osc_esc_html($reasons[$reason][0]) . '</strong>')
      : sprintf(__('%1$d members have reported %2$s.', 'starter'), (int)$row['n'], '<strong>' . osc_esc_html($item['s_title']) . '</strong>'),
    __('Review it under Marketplace > Reported listings, and hide or delete it if it breaks the terms.', 'starter')
  );
  antique_mail($admin, osc_page_title(), sprintf(__('Urgent report: %s', 'starter'), $item['s_title']), __('A listing needs a look', 'starter'), $lines,
    antique_marketplace_admin_url('listings'), __('Open reported listings', 'starter'));
}

/**
 * Daily digest of new reports for the site's contact address. Runs from the server
 * cron (inc.cron.php) after ANTIQUE_REPORT_DIGEST_HOUR; skipped when nothing is new.
 */
function antique_report_digest() {
  if ((int)date('G') < ANTIQUE_REPORT_DIGEST_HOUR || osc_get_preference('report_digest_day', 'starter_theme') === date('Y-m-d')) { return; }
  if (!antique_job_due('report_digest', 600)) { return; }
  osc_set_preference('report_digest_day', date('Y-m-d'), 'starter_theme');

  $since = (string)osc_get_preference('report_digest_at', 'starter_theme');
  if ($since === '') { $since = date('Y-m-d H:i:s', time() - 86400); }
  osc_set_preference('report_digest_at', date('Y-m-d H:i:s'), 'starter_theme');

  $db = antique_msg_db();
  $res = $db->query(sprintf("SELECT fk_i_item_id, COUNT(*) AS n, GROUP_CONCAT(DISTINCT s_reason) AS reasons FROM `%s`
    WHERE s_status = 'open' AND dt_created > '%s' GROUP BY fk_i_item_id ORDER BY n DESC LIMIT 25", antique_reports_table(), $db->escapeStr($since)));
  $rows = $res ? $res->result() : array();
  $admin = osc_contact_email();
  if (!$rows || $admin == '') { return; }

  $reasons = antique_report_reasons();
  $total = array_sum(array_map(function ($r) { return (int)$r['n']; }, $rows));
  $list = '';
  foreach ($rows as $r) {
    $item = Item::newInstance()->findByPrimaryKey($r['fk_i_item_id']);
    if (!$item) { continue; }
    $labels = array();
    foreach (explode(',', (string)$r['reasons']) as $k) { if (isset($reasons[$k])) { $labels[] = $reasons[$k][0]; } }
    $list .= '<br/>&bull; <a href="' . osc_esc_html(osc_item_url_from_item($item)) . '" style="color:#8A6420;">' . osc_esc_html($item['s_title']) . '</a> &ndash; '
      . sprintf(_n('%d report', '%d reports', (int)$r['n'], 'starter'), (int)$r['n']) . ' (' . osc_esc_html(implode(', ', $labels)) . ')';
  }
  antique_mail($admin, osc_page_title(), sprintf(__('Daily reports: %d new', 'starter'), $total), __('New reports since yesterday', 'starter'), array(
    sprintf(__('%1$d new reports on %2$d listings:', 'starter'), $total, count($rows)) . $list,
    __('Urgent reports were already emailed to you as they came in.', 'starter')
  ), antique_marketplace_admin_url('listings'), __('Review reported listings', 'starter'));
}
osc_add_hook('cron', 'antique_report_digest');
osc_add_hook('init', 'antique_report_digest', 9);

/**
 * Closes a listing's open reports. $status: 'dismissed' or 'actioned'.
 */
function antique_report_close($itemId, $status) {
  antique_msg_db()->query(sprintf("UPDATE `%s` SET s_status = '%s', dt_closed = '%s' WHERE fk_i_item_id = %d AND s_status = 'open'",
    antique_reports_table(), $status === 'actioned' ? 'actioned' : 'dismissed', date('Y-m-d H:i:s'), (int)$itemId));
}

/**
 * Open reports grouped by listing for the admin page: item id => array(reports, legacy counts).
 * Includes listings that only have Osclass's older anonymous report counters.
 */
function antique_report_queue() {
  $db = antique_msg_db();
  $p = DB_TABLE_PREFIX;
  $queue = array();
  $res = $db->query("SELECT * FROM `" . antique_reports_table() . "` WHERE s_status = 'open' ORDER BY dt_created DESC LIMIT 1000");
  foreach (($res ? $res->result() : array()) as $r) {
    $id = (int)$r['fk_i_item_id'];
    if (!isset($queue[$id])) { $queue[$id] = array('reports' => array(), 'legacy' => array(), 'last' => $r['dt_created'], 'urgent' => false); }
    $queue[$id]['reports'][] = $r;
    if (!empty(antique_report_reasons()[$r['s_reason']][1])) { $queue[$id]['urgent'] = true; }
  }
  $res = $db->query("SELECT fk_i_item_id, SUM(i_num_spam) AS spam, SUM(i_num_offensive) AS offensive, SUM(i_num_bad_classified) AS badcat,
      SUM(i_num_repeated) AS repeated, SUM(i_num_expired) AS expired, MAX(dt_date) AS last
    FROM `{$p}t_item_stats` GROUP BY fk_i_item_id
    HAVING SUM(i_num_spam + i_num_offensive + i_num_bad_classified + i_num_repeated + i_num_expired) > 0");
  foreach (($res ? $res->result() : array()) as $r) {
    $id = (int)$r['fk_i_item_id'];
    if (!isset($queue[$id])) { $queue[$id] = array('reports' => array(), 'legacy' => array(), 'last' => $r['last'], 'urgent' => false); }
    $queue[$id]['legacy'] = array_filter(array_intersect_key($r, array_flip(array('spam', 'offensive', 'badcat', 'repeated', 'expired'))), function ($n) { return (int)$n > 0; });
  }
  // urgent first, then most reporters, then newest
  uasort($queue, function ($a, $b) {
    if ($a['urgent'] != $b['urgent']) { return $a['urgent'] ? -1 : 1; }
    if (count($a['reports']) != count($b['reports'])) { return count($b['reports']) - count($a['reports']); }
    return strcmp($b['last'], $a['last']);
  });
  return $queue;
}
