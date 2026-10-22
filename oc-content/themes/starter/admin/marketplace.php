<?php
  require_once 'functions.php';
  starter_backoffice_menu(__('Marketplace', 'starter'));

  $al_tab = Params::getParam('tab');
  if (!in_array($al_tab, array('overview', 'listings', 'verify', 'reviews', 'messages', 'seo'), true)) { $al_tab = 'overview'; }
  $al_db = antique_msg_db();
  $al_url = function ($tab) { return antique_marketplace_admin_url($tab); };
  $al_token = osc_csrfguard_generate_token();
  // $confirm: question shown before a destructive action
  $al_form = function ($act, $id, $label, $class = '', $extra = '', $confirm = '') use ($al_token, $al_tab) {
    return '<form method="post" action="' . osc_esc_html(antique_marketplace_admin_url($al_tab)) . '" class="almk-inline"'
      . ($confirm !== '' ? ' onsubmit="return confirm(' . osc_esc_html(json_encode($confirm)) . ')"' : '') . '>'
      . '<input type="hidden" name="octoken" value="' . $al_token . '" /><input type="hidden" name="al_mk" value="' . $act . '" /><input type="hidden" name="tab" value="' . $al_tab . '" /><input type="hidden" name="id" value="' . (int)$id . '" />'
      . $extra . '<button type="submit" class="almk-btn ' . $class . '">' . osc_esc_html($label) . '</button></form>';
  };
  $al_count = function ($sql) use ($al_db) { $r = $al_db->query($sql); return $r ? (int)$r->row()['n'] : 0; };
  $al_pending = $al_count("SELECT COUNT(*) AS n FROM `" . antique_sellers_table() . "` WHERE i_verify = " . ANTIQUE_VERIFY_PENDING);
  $al_rep_reviews = $al_count("SELECT COUNT(*) AS n FROM `" . antique_reviews_table() . "` WHERE b_reported = 1");
  $al_rep_threads = $al_count("SELECT COUNT(*) AS n FROM `" . antique_msg_threads_table() . "` WHERE b_reported = 1");
  $al_p = DB_TABLE_PREFIX;
  // listings with open reports (Osclass keeps the counters per day in t_item_stats)
  $al_rep_sql = "SELECT s.fk_i_item_id, SUM(s.i_num_spam) AS spam, SUM(s.i_num_offensive) AS offensive, SUM(s.i_num_bad_classified) AS badcat,
      SUM(s.i_num_repeated) AS repeated, SUM(s.i_num_expired) AS expired, MAX(s.dt_date) AS last
    FROM `{$al_p}t_item_stats` s GROUP BY s.fk_i_item_id
    HAVING SUM(s.i_num_spam + s.i_num_offensive + s.i_num_bad_classified + s.i_num_repeated + s.i_num_expired) > 0";
  $al_rep_items = $al_count("SELECT COUNT(*) AS n FROM ({$al_rep_sql}) x");
  $al_suspended = function ($uid) { $u = User::newInstance()->findByPrimaryKey($uid); return $u && !(int)$u['b_enabled']; };
  $al_suspend_form = function ($uid, $who) use ($al_form, $al_suspended) {
    return $al_suspended($uid)
      ? $al_form('unsuspend_user', $uid, sprintf(__('Restore %s', 'starter'), $who))
      : $al_form('suspend_user', $uid, sprintf(__('Suspend %s', 'starter'), $who), 'is-bad', '', __('Suspend this member? They will not be able to sign in, and all their listings will be hidden.', 'starter'));
  };
?>
<style>
  .almk { max-width: 1180px; font-size: 14px; }
  .almk-tabs { display: flex; gap: 8px; margin: 0 0 18px; }
  .almk-tabs a { padding: 9px 16px; border: 1px solid #D9D2C3; border-radius: 999px; background: #fff; color: #14213D; font-weight: 700; text-decoration: none; }
  .almk-tabs a.is-active { background: #14213D; border-color: #14213D; color: #fff; }
  .almk-tabs em { display: inline-block; min-width: 18px; margin-left: 6px; padding: 0 6px; border-radius: 999px; background: #B4232A; color: #fff; font-style: normal; font-size: 11px; line-height: 18px; text-align: center; }
  .almk-card { margin: 0 0 14px; padding: 16px 18px; border: 1px solid #E5DFD3; border-radius: 8px; background: #fff; }
  .almk-card h3 { margin: 0 0 6px; font-size: 16px; }
  .almk-card dl { display: grid; grid-template-columns: 180px 1fr; gap: 4px 14px; margin: 10px 0; }
  .almk-card dt { color: #6B7280; font-weight: 600; }
  .almk-card dd { margin: 0; color: #14213D; word-break: break-word; }
  .almk-meta { color: #6B7280; font-size: 13px; }
  .almk-actions { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; margin-top: 12px; }
  .almk-inline { display: inline-flex; align-items: center; gap: 8px; margin: 0; }
  .almk-inline input[type=text] { width: 320px; max-width: 100%; height: 34px; padding: 0 10px; border: 1px solid #D9D2C3; border-radius: 4px; }
  .almk-btn { height: 34px; padding: 0 14px; border: 1px solid #14213D; border-radius: 4px; background: #fff; color: #14213D; font-weight: 700; cursor: pointer; }
  .almk-btn.is-ok { background: #067647; border-color: #067647; color: #fff; }
  .almk-btn.is-bad { border-color: #B42318; color: #B42318; }
  .almk-docs { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
  .almk-sub { margin: 26px 0 6px; font-size: 16px; }
  .almk-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #EFEAE0; background: #fff; }
  .almk-row:first-of-type { border-top-left-radius: 8px; border-top-right-radius: 8px; }
  .almk-doc { display: inline-block; padding: 7px 14px; border-radius: 4px; background: #A67C2E; color: #fff !important; font-weight: 700; text-decoration: none; }
  .almk-tag { display: inline-block; padding: 1px 8px; border-radius: 999px; background: #F3EFE6; color: #4B5563; font-size: 12px; font-weight: 700; }
  .almk-tag.is-ok { background: #DCF5E7; color: #067647; }
  .almk-tag.is-bad { background: #FDE8E6; color: #B42318; }
  .almk-quote { margin: 8px 0; padding: 10px 12px; border-left: 3px solid #D9B26A; background: #FBF9F4; white-space: pre-wrap; }
  .almk-msgs { max-height: 320px; overflow: auto; margin: 10px 0; padding: 10px; border: 1px solid #EFEAE0; border-radius: 6px; background: #FCFBF8; font-size: 13px; }
  .almk-msgs p { margin: 0 0 6px; }
  .almk-seo label { display: flex; flex-direction: column; gap: 4px; margin: 0 0 14px; }
  .almk-seo input[type=text], .almk-seo textarea { width: 100%; max-width: 640px; padding: 8px 10px; border: 1px solid #D9D2C3; border-radius: 4px; font-size: 14px; }
  .almk-tabs { flex-wrap: wrap; }
  .almk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; margin: 0 0 22px; }
  .almk-stat { display: block; padding: 14px 16px; border: 1px solid #E5DFD3; border-radius: 8px; background: #fff; color: #14213D; text-decoration: none; }
  .almk-stat strong { display: block; font-size: 26px; line-height: 1.2; }
  .almk-stat span { color: #6B7280; font-size: 13px; }
  a.almk-stat:hover { border-color: #A67C2E; }
  .almk-stat.is-alert { border-color: #F3B4AE; background: #FFF6F5; }
  .almk-stat.is-alert strong { color: #B42318; }
  .almk-stat.is-ok strong { color: #067647; }
  .almk-h { margin: 0 0 10px; font-size: 15px; color: #14213D; }
  .almk-empty { padding: 30px; border: 1px dashed #D6CDBB; border-radius: 8px; background: #fff; color: #6B7280; text-align: center; }
</style>

<div class="mb-body almk">
  <nav class="almk-tabs">
    <a href="<?php echo $al_url('overview'); ?>"<?php echo $al_tab == 'overview' ? ' class="is-active"' : ''; ?>><?php _e('Overview', 'starter'); ?></a>
    <a href="<?php echo $al_url('listings'); ?>"<?php echo $al_tab == 'listings' ? ' class="is-active"' : ''; ?>><?php _e('Reported listings', 'starter'); ?><?php if ($al_rep_items) { ?><em><?php echo $al_rep_items; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('verify'); ?>"<?php echo $al_tab == 'verify' ? ' class="is-active"' : ''; ?>><?php _e('Seller verification', 'starter'); ?><?php if ($al_pending) { ?><em><?php echo $al_pending; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('reviews'); ?>"<?php echo $al_tab == 'reviews' ? ' class="is-active"' : ''; ?>><?php _e('Reviews', 'starter'); ?><?php if ($al_rep_reviews) { ?><em><?php echo $al_rep_reviews; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('messages'); ?>"<?php echo $al_tab == 'messages' ? ' class="is-active"' : ''; ?>><?php _e('Reported conversations', 'starter'); ?><?php if ($al_rep_threads) { ?><em><?php echo $al_rep_threads; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('seo'); ?>"<?php echo $al_tab == 'seo' ? ' class="is-active"' : ''; ?>><?php _e('SEO and analytics', 'starter'); ?></a>
  </nav>

  <?php if ($al_tab == 'overview') { ?>
    <?php
      $al_week = "DATE_SUB(NOW(), INTERVAL 7 DAY)";
      $al_waiting = $al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_item` WHERE b_active = 0 AND b_enabled = 1");
      $al_todo = array(
        array($al_rep_items, __('Reported listings', 'starter'), $al_url('listings')),
        array($al_pending, __('Sellers waiting for verification', 'starter'), $al_url('verify')),
        array($al_rep_threads, __('Reported conversations', 'starter'), $al_url('messages')),
        array($al_rep_reviews, __('Reported reviews', 'starter'), $al_url('reviews')),
        array($al_waiting, __('Listings not activated yet', 'starter'), osc_admin_base_url(true) . '?page=items')
      );
      $al_live = array(
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_item` WHERE b_enabled = 1 AND b_active = 1 AND b_spam = 0 AND (dt_expiration IS NULL OR dt_expiration >= NOW())"), __('Listings online', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_item_auctions` WHERE b_active = 1 AND dt_end_date > NOW()"), __('Auctions running', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `" . antique_sellers_table() . "` WHERE i_verify = " . ANTIQUE_VERIFY_OK), __('Verified sellers', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_user` WHERE b_enabled = 1 AND b_active = 1"), __('Members', 'starter'))
      );
      $al_recent = array(
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_user` WHERE dt_reg_date >= {$al_week}"), __('New members', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_item` WHERE dt_pub_date >= {$al_week}"), __('New listings', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `{$al_p}t_item_bids` WHERE dt_date >= {$al_week}"), __('Bids', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `" . antique_msg_table() . "` WHERE s_kind = 'text' AND dt_sent >= {$al_week}"), __('Messages', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `" . antique_offers_table() . "` WHERE dt_created >= {$al_week}"), __('Offers made', 'starter')),
        array($al_count("SELECT COUNT(*) AS n FROM `" . antique_offers_table() . "` WHERE s_status = 'accepted' AND dt_responded >= {$al_week}"), __('Offers accepted', 'starter'))
      );
      $al_cron = (int)osc_get_preference('cron_seen', 'starter_theme');
      $al_cron_ok = $al_cron > 0 && time() - $al_cron < ANTIQUE_CRON_FALLBACK;
    ?>
    <h3 class="almk-h"><?php _e('Needs your attention', 'starter'); ?></h3>
    <div class="almk-grid">
      <?php foreach ($al_todo as $al_s) { ?>
        <a class="almk-stat<?php echo $al_s[0] ? ' is-alert' : ''; ?>" href="<?php echo osc_esc_html($al_s[2]); ?>"><strong><?php echo (int)$al_s[0]; ?></strong><span><?php echo osc_esc_html($al_s[1]); ?></span></a>
      <?php } ?>
    </div>
    <h3 class="almk-h"><?php _e('Right now', 'starter'); ?></h3>
    <div class="almk-grid">
      <?php foreach ($al_live as $al_s) { ?><div class="almk-stat"><strong><?php echo (int)$al_s[0]; ?></strong><span><?php echo osc_esc_html($al_s[1]); ?></span></div><?php } ?>
    </div>
    <h3 class="almk-h"><?php _e('Last 7 days', 'starter'); ?></h3>
    <div class="almk-grid">
      <?php foreach ($al_recent as $al_s) { ?><div class="almk-stat"><strong><?php echo (int)$al_s[0]; ?></strong><span><?php echo osc_esc_html($al_s[1]); ?></span></div><?php } ?>
    </div>
    <h3 class="almk-h"><?php _e('Site health', 'starter'); ?></h3>
    <div class="almk-card">
      <h3><?php _e('Server cron', 'starter'); ?> <span class="almk-tag<?php echo $al_cron_ok ? ' is-ok' : ' is-bad'; ?>"><?php echo $al_cron_ok ? __('Running', 'starter') : __('Not running', 'starter'); ?></span></h3>
      <p class="almk-meta"><?php echo $al_cron
        ? sprintf(__('Last ran %s ago. It closes auctions, expires offers and sends watchlist emails.', 'starter'), osc_esc_html(antique_bids_time_left(max(60, time() - $al_cron))))
        : __('It has not run yet. Add the cron job in cPanel so auctions close on time even when nobody is browsing.', 'starter'); ?>
        <?php if (!$al_cron_ok) { ?><br><?php _e('Until it runs, visitors\' page loads do this work instead.', 'starter'); ?><?php } ?></p>
    </div>

  <?php } elseif ($al_tab == 'listings') { ?>
    <?php
      // wrapped: MariaDB cannot sort by an expression over aggregate aliases
      $res = $al_db->query("SELECT * FROM ({$al_rep_sql}) x ORDER BY (x.spam + x.offensive) DESC, x.last DESC LIMIT 200");
      $rows = $res ? $res->result() : array();
      $al_reasons = antique_report_reasons();
      $al_cols = array('spam' => 'spam', 'offensive' => 'offensive', 'badcat' => 'badcat', 'repeated' => 'repeated', 'expired' => 'expired');
    ?>
    <p class="almk-meta"><?php _e('Listings visitors reported with "Report this listing". Hide or delete what breaks the terms, and dismiss the rest.', 'starter'); ?></p>
    <?php if (!$rows) { ?><div class="almk-empty"><?php _e('No reported listings.', 'starter'); ?></div><?php } ?>
    <?php foreach ($rows as $r) {
      $al_item = Item::newInstance()->findByPrimaryKey($r['fk_i_item_id']);
      if (!$al_item) { continue; }
      $al_hidden = !(int)$al_item['b_enabled'];
      $al_seller = (int)$al_item['fk_i_user_id'];
    ?>
      <div class="almk-card">
        <h3><a href="<?php echo osc_esc_html(osc_item_url_from_item($al_item)); ?>" target="_blank"><?php echo osc_esc_html($al_item['s_title']); ?></a>
          <?php if ($al_hidden) { ?><span class="almk-tag"><?php _e('Hidden', 'starter'); ?></span><?php } ?>
          <?php if ((int)$al_item['b_spam']) { ?><span class="almk-tag is-bad"><?php _e('Spam', 'starter'); ?></span><?php } ?>
        </h3>
        <div class="almk-meta">
          <?php echo sprintf(__('Seller: %1$s (%2$s)', 'starter'), osc_esc_html($al_item['s_contact_name']), osc_esc_html($al_item['s_contact_email'])); ?>
          <?php if ($al_seller && $al_suspended($al_seller)) { ?> <span class="almk-tag is-bad"><?php _e('Suspended', 'starter'); ?></span><?php } ?>
          · <?php echo sprintf(__('Listed %s', 'starter'), osc_esc_html(date('j M Y', strtotime($al_item['dt_pub_date'])))); ?>
          · <?php echo sprintf(__('Last report %s', 'starter'), osc_esc_html(date('j M Y', strtotime($r['last'])))); ?>
          · <a href="<?php echo osc_admin_base_url(true); ?>?page=items&action=item_edit&id=<?php echo (int)$al_item['pk_i_id']; ?>"><?php _e('Edit in oc-admin', 'starter'); ?></a>
        </div>
        <div class="almk-docs" style="margin-top:10px">
          <?php foreach ($al_cols as $al_k => $al_col) { if ((int)$r[$al_col] < 1) { continue; } ?>
            <span class="almk-tag<?php echo in_array($al_k, array('spam', 'offensive'), true) ? ' is-bad' : ''; ?>"><?php echo osc_esc_html($al_reasons[$al_k][0]); ?> &times; <?php echo (int)$r[$al_col]; ?></span>
          <?php } ?>
        </div>
        <div class="almk-actions">
          <?php echo $al_form('clear_item_reports', $al_item['pk_i_id'], __('Dismiss reports', 'starter')); ?>
          <?php echo $al_hidden ? $al_form('show_item', $al_item['pk_i_id'], __('Show listing again', 'starter')) : $al_form('hide_item', $al_item['pk_i_id'], __('Hide listing', 'starter'), 'is-bad'); ?>
          <?php echo $al_form('delete_item', $al_item['pk_i_id'], __('Delete listing', 'starter'), 'is-bad', '', __('Delete this listing for good? Its photos, bids and offers are removed too.', 'starter')); ?>
          <?php if ($al_seller) { echo $al_suspend_form($al_seller, __('seller', 'starter')); } ?>
        </div>
      </div>
    <?php } ?>

  <?php } elseif ($al_tab == 'verify') { ?>
    <?php
      $res = $al_db->query("SELECT s.*, u.s_name, u.s_email, u.b_company, u.s_phone_mobile, u.dt_reg_date FROM `" . antique_sellers_table() . "` s JOIN `" . DB_TABLE_PREFIX . "t_user` u ON u.pk_i_id = s.fk_i_user_id
        WHERE s.i_verify IN (" . ANTIQUE_VERIFY_PENDING . "," . ANTIQUE_VERIFY_OK . ") ORDER BY (s.i_verify = " . ANTIQUE_VERIFY_PENDING . ") DESC, s.dt_verify_req ASC LIMIT 200");
      $rows = $res ? $res->result() : array();
    ?>
    <p class="almk-meta"><?php _e('Sellers can list items only after you approve them here. Check that the name and address match the document.', 'starter'); ?></p>
    <?php if (!$rows) { ?><div class="almk-empty"><?php _e('No verification requests yet.', 'starter'); ?></div><?php } ?>
    <?php foreach ($rows as $r) { $pending = (int)$r['i_verify'] === ANTIQUE_VERIFY_PENDING; ?>
      <div class="almk-card">
        <h3><?php echo osc_esc_html(antique_seller_display_name($r['fk_i_user_id'], $r['s_name'])); ?>
          <span class="almk-tag<?php echo $pending ? '' : ' is-ok'; ?>"><?php echo $pending ? __('Waiting for a decision', 'starter') : __('Verified', 'starter'); ?></span>
          <span class="almk-tag"><?php echo $r['b_company'] ? __('Dealer', 'starter') : __('Private seller', 'starter'); ?></span>
        </h3>
        <div class="almk-meta"><?php echo osc_esc_html($r['s_email']); ?> · <?php echo sprintf(__('Member since %s', 'starter'), osc_esc_html(date('j M Y', strtotime($r['dt_reg_date'])))); ?> · <a href="<?php echo osc_user_public_profile_url($r['fk_i_user_id']); ?>" target="_blank"><?php _e('Shop page', 'starter'); ?></a> · <a href="<?php echo osc_admin_base_url(true); ?>?page=users&action=edit&id=<?php echo (int)$r['fk_i_user_id']; ?>"><?php _e('User in oc-admin', 'starter'); ?></a></div>
        <dl>
          <dt><?php _e('Legal / business name', 'starter'); ?></dt><dd><?php echo osc_esc_html((string)$r['s_legal_name']); ?></dd>
          <?php if ($r['s_reg_number']) { ?><dt><?php _e('Registration number', 'starter'); ?></dt><dd><?php echo osc_esc_html($r['s_reg_number']); ?></dd><?php } ?>
          <dt><?php _e('Address', 'starter'); ?></dt><dd><?php echo osc_esc_html((string)$r['s_address']); ?></dd>
          <?php if ($r['s_website']) { ?><dt><?php _e('Website', 'starter'); ?></dt><dd><a href="<?php echo osc_esc_html($r['s_website']); ?>" target="_blank" rel="noopener nofollow"><?php echo osc_esc_html($r['s_website']); ?></a></dd><?php } ?>
          <dt><?php _e('Mobile on account', 'starter'); ?></dt><dd><?php echo osc_esc_html((string)$r['s_phone_mobile']); ?></dd>
          <dt><?php echo $pending ? __('Requested', 'starter') : __('Verified', 'starter'); ?></dt><dd><?php echo osc_esc_html($pending ? (string)$r['dt_verify_req'] : (string)$r['dt_verified']); ?></dd>
        </dl>
        <?php if ($pending) { ?>
          <?php $al_docs = antique_verify_docs($r); ?>
          <?php if ($al_docs) { ?>
            <div class="almk-docs">
              <span class="almk-tag"><?php echo osc_esc_html(antique_verify_doc_label((string)$r['s_doc_type'])); ?></span>
              <?php foreach ($al_docs as $al_i => $al_d) { ?>
                <a class="almk-doc" href="<?php echo osc_esc_html(osc_admin_ajax_hook_url('al_verify_doc', array('seller' => (int)$r['fk_i_user_id'], 'n' => $al_i))); ?>" target="_blank" rel="noopener"><?php echo count($al_docs) > 1 ? ($al_i ? __('Open back', 'starter') : __('Open front', 'starter')) : __('Open document', 'starter'); ?></a>
              <?php } ?>
            </div>
          <?php } ?>
          <div class="almk-actions">
            <?php echo $al_form('approve', $r['fk_i_user_id'], __('Approve and verify', 'starter'), 'is-ok'); ?>
            <?php echo $al_form('decline', $r['fk_i_user_id'], __('Decline', 'starter'), 'is-bad', '<input type="text" name="note" maxlength="500" placeholder="' . osc_esc_html(__('Reason shown to the seller (e.g. the document is not readable)', 'starter')) . '" />'); ?>
          </div>
        <?php } else { ?>
          <div class="almk-actions"><?php echo $al_form('unverify', $r['fk_i_user_id'], __('Remove badge', 'starter'), 'is-bad'); ?></div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php
      $res = $al_db->query("SELECT s.fk_i_user_id, s.i_verify, s.dt_started, u.s_name, u.s_email, u.b_company,
          (SELECT COUNT(*) FROM `" . DB_TABLE_PREFIX . "t_item` i WHERE i.fk_i_user_id = s.fk_i_user_id) AS n_items
        FROM `" . antique_sellers_table() . "` s JOIN `" . DB_TABLE_PREFIX . "t_user` u ON u.pk_i_id = s.fk_i_user_id
        WHERE s.i_verify IN (" . ANTIQUE_VERIFY_NONE . "," . ANTIQUE_VERIFY_DECLINED . ") ORDER BY n_items DESC, s.dt_started DESC LIMIT 300");
      $al_unver = $res ? $res->result() : array();
    ?>
    <?php if ($al_unver) { ?>
      <h3 class="almk-sub"><?php echo sprintf(__('Sellers not verified yet (%d)', 'starter'), count($al_unver)); ?></h3>
      <p class="almk-meta"><?php _e('They cannot list new items. Their existing listings stay online. Ask them to send their details from Account > Get verified, or verify people you already know without a document.', 'starter'); ?></p>
      <?php foreach ($al_unver as $r) { ?>
        <div class="almk-row">
          <div>
            <strong><?php echo osc_esc_html(antique_seller_display_name($r['fk_i_user_id'], $r['s_name'])); ?></strong>
            <span class="almk-tag"><?php echo $r['b_company'] ? __('Dealer', 'starter') : __('Private seller', 'starter'); ?></span>
            <?php if ((int)$r['i_verify'] === ANTIQUE_VERIFY_DECLINED) { ?><span class="almk-tag is-bad"><?php _e('Declined', 'starter'); ?></span><?php } ?>
            <div class="almk-meta"><?php echo osc_esc_html($r['s_email']); ?> · <?php echo sprintf(__('%d listings', 'starter'), (int)$r['n_items']); ?> · <a href="<?php echo osc_user_public_profile_url($r['fk_i_user_id']); ?>" target="_blank"><?php _e('Shop page', 'starter'); ?></a></div>
          </div>
          <?php echo $al_form('verify_direct', $r['fk_i_user_id'], __('Verify without a document', 'starter')); ?>
        </div>
      <?php } ?>
    <?php } ?>

  <?php } elseif ($al_tab == 'reviews') { ?>
    <?php
      $res = $al_db->query("SELECT * FROM `" . antique_reviews_table() . "` ORDER BY b_reported DESC, dt_created DESC LIMIT 200");
      $rows = $res ? $res->result() : array();
      $labels = antique_review_basis_labels();
    ?>
    <?php if (!$rows) { ?><div class="almk-empty"><?php _e('No reviews yet.', 'starter'); ?></div><?php } ?>
    <?php foreach ($rows as $r) { $seller = antique_msg_user($r['fk_i_seller_id']); $buyer = antique_msg_user($r['fk_i_buyer_id']); ?>
      <div class="almk-card">
        <h3><?php echo str_repeat('&#9733;', (int)$r['i_rating']) . str_repeat('&#9734;', 5 - (int)$r['i_rating']); ?>
          <?php if ($r['b_reported']) { ?><span class="almk-tag is-bad"><?php _e('Reported', 'starter'); ?></span><?php } ?>
          <?php if ($r['b_hidden']) { ?><span class="almk-tag"><?php _e('Hidden', 'starter'); ?></span><?php } ?>
          <span class="almk-tag"><?php echo osc_esc_html(isset($labels[$r['s_basis']]) ? $labels[$r['s_basis']] : $r['s_basis']); ?></span>
        </h3>
        <div class="almk-meta"><?php echo sprintf(__('%1$s reviewed %2$s for "%3$s" on %4$s', 'starter'), osc_esc_html($buyer['name']), '<a href="' . osc_esc_html($seller['url']) . '#al-review-' . (int)$r['pk_i_id'] . '" target="_blank">' . osc_esc_html($seller['name']) . '</a>', osc_esc_html($r['s_item_title']), osc_esc_html(date('j M Y', strtotime($r['dt_created'])))); ?></div>
        <div class="almk-quote"><?php echo osc_esc_html($r['s_body']); ?></div>
        <?php if ($r['s_reply']) { ?><div class="almk-meta"><?php _e('Seller reply:', 'starter'); ?> <?php echo osc_esc_html($r['s_reply']); ?></div><?php } ?>
        <div class="almk-actions">
          <?php echo $r['b_hidden'] ? $al_form('show_review', $r['pk_i_id'], __('Show again', 'starter')) : $al_form('hide_review', $r['pk_i_id'], __('Hide review', 'starter'), 'is-bad'); ?>
          <?php if ($r['b_reported'] && !$r['b_hidden']) { echo $al_form('keep_review', $r['pk_i_id'], __('Keep it (close report)', 'starter')); } ?>
        </div>
      </div>
    <?php } ?>

  <?php } elseif ($al_tab == 'seo') { ?>
    <?php
      $al_built = (int)osc_get_preference('seo_sitemap_built', 'starter_theme');
      $al_sm = osc_base_path() . 'sitemap.xml';
      $al_sm_n = is_file($al_sm) ? substr_count((string)file_get_contents($al_sm), '<url>') : 0;
    ?>
    <div class="almk-card">
      <h3><?php _e('Friendly addresses', 'starter'); ?>
        <span class="almk-tag<?php echo osc_rewrite_enabled() ? ' is-ok' : ' is-bad'; ?>"><?php echo osc_rewrite_enabled() ? __('On', 'starter') : __('Off', 'starter'); ?></span></h3>
      <p class="almk-meta"><?php echo osc_rewrite_enabled()
        ? __('Listings use addresses like /antique-furniture/teak-almirah-i25. Old index.php?page=... links redirect to them automatically.', 'starter')
        : sprintf(__('Turn them on in <a href="%s">Settings &gt; Permalinks</a> (tick "Enable friendly urls" and save). Old links keep working.', 'starter'), osc_admin_base_url(true) . '?page=settings&action=permalinks'); ?></p>
    </div>
    <div class="almk-card">
      <h3><?php _e('Sitemap and robots.txt', 'starter'); ?></h3>
      <p class="almk-meta">
        <?php if ($al_sm_n) { ?><?php echo sprintf(__('%1$d addresses, built %2$s.', 'starter'), $al_sm_n, $al_built ? osc_esc_html(date('j M Y H:i', $al_built)) : '–'); ?> <a href="<?php echo osc_base_url(); ?>sitemap.xml" target="_blank">sitemap.xml</a> · <a href="<?php echo osc_base_url(); ?>robots.txt" target="_blank">robots.txt</a><?php } else { _e('Not built yet.', 'starter'); } ?>
        <br><?php echo sprintf(__('Rebuilt automatically every %d hours. Submit %s in Google Search Console and Bing Webmaster Tools.', 'starter'), ANTIQUE_SEO_SITEMAP_HOURS, '<strong>' . osc_base_url() . 'sitemap.xml</strong>'); ?>
      </p>
      <div class="almk-actions"><?php echo $al_form('sitemap', 0, __('Rebuild now', 'starter'), 'is-ok'); ?></div>
    </div>
    <form method="post" action="<?php echo osc_esc_html(antique_marketplace_admin_url('seo')); ?>" class="almk-card almk-seo">
      <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" /><input type="hidden" name="al_mk" value="seo_save" /><input type="hidden" name="tab" value="seo" />
      <h3><?php _e('Search and analytics settings', 'starter'); ?></h3>
      <label><strong><?php _e('Home page description', 'starter'); ?></strong><span class="almk-meta"><?php _e('Shown under your site name in Google. About 150 characters.', 'starter'); ?></span>
        <textarea name="seo_home_desc" rows="3" maxlength="300"><?php echo osc_esc_html(antique_seo_pref('seo_home_desc') !== '' ? antique_seo_pref('seo_home_desc') : antique_seo_default_home_desc()); ?></textarea></label>
      <label><strong><?php _e('Google Analytics 4 measurement ID', 'starter'); ?></strong><span class="almk-meta"><?php _e('Looks like G-ABC123XYZ (Analytics > Admin > Data streams). Visitors see a cookie banner, and nothing is tracked until they accept.', 'starter'); ?></span>
        <input type="text" name="seo_ga4" maxlength="20" value="<?php echo osc_esc_html(antique_seo_pref('seo_ga4')); ?>" placeholder="G-XXXXXXXXXX" /></label>
      <label><strong><?php _e('Google Search Console verification code', 'starter'); ?></strong><span class="almk-meta"><?php _e('Choose the "HTML tag" method and paste the tag (or just its content value).', 'starter'); ?></span>
        <input type="text" name="seo_google_verify" maxlength="100" value="<?php echo osc_esc_html(antique_seo_pref('seo_google_verify')); ?>" /></label>
      <label><strong><?php _e('Bing Webmaster verification code', 'starter'); ?></strong><span class="almk-meta"><?php _e('The content value of the msvalidate.01 meta tag.', 'starter'); ?></span>
        <input type="text" name="seo_bing_verify" maxlength="100" value="<?php echo osc_esc_html(antique_seo_pref('seo_bing_verify')); ?>" /></label>
      <div class="almk-actions"><button type="submit" class="almk-btn is-ok"><?php _e('Save', 'starter'); ?></button></div>
    </form>
  <?php } else { ?>
    <?php
      $res = $al_db->query("SELECT * FROM `" . antique_msg_threads_table() . "` WHERE b_reported = 1 ORDER BY dt_last DESC LIMIT 100");
      $rows = $res ? $res->result() : array();
    ?>
    <?php if (!$rows) { ?><div class="almk-empty"><?php _e('No reported conversations.', 'starter'); ?></div><?php } ?>
    <?php foreach ($rows as $t) { $b = antique_msg_user($t['fk_i_buyer_id']); $s = antique_msg_user($t['fk_i_seller_id']); $item = antique_msg_item($t['fk_i_item_id']); ?>
      <div class="almk-card">
        <h3><?php echo sprintf(__('Conversation #%d', 'starter'), (int)$t['pk_i_id']); ?> <span class="almk-tag is-bad"><?php _e('Reported', 'starter'); ?></span></h3>
        <div class="almk-meta">
          <?php echo sprintf(__('Buyer: %1$s (%2$s) · Seller: %3$s (%4$s)', 'starter'), osc_esc_html($b['name']), osc_esc_html($b['email']), osc_esc_html($s['name']), osc_esc_html($s['email'])); ?>
          <?php if ($item) { ?> · <a href="<?php echo osc_esc_html($item['url']); ?>" target="_blank"><?php echo osc_esc_html($item['title']); ?></a><?php } ?>
        </div>
        <div class="almk-msgs">
          <?php foreach (antique_msg_messages($t['pk_i_id']) as $m) { $who = (int)$m['fk_i_sender_id'] > 0 ? antique_msg_user($m['fk_i_sender_id'])['name'] : osc_page_title(); ?>
            <p><strong><?php echo osc_esc_html($who); ?></strong> <span class="almk-meta"><?php echo osc_esc_html($m['dt_sent']); ?></span><br><?php echo nl2br(osc_esc_html($m['s_body'])); ?></p>
          <?php } ?>
        </div>
        <div class="almk-actions">
          <?php echo $al_form('clear_thread', $t['pk_i_id'], __('Close report', 'starter')); ?>
          <a class="almk-btn" style="display:inline-flex;align-items:center;text-decoration:none" href="<?php echo osc_admin_base_url(true); ?>?page=users&action=edit&id=<?php echo (int)$t['fk_i_seller_id']; ?>"><?php _e('Seller in oc-admin', 'starter'); ?></a>
          <a class="almk-btn" style="display:inline-flex;align-items:center;text-decoration:none" href="<?php echo osc_admin_base_url(true); ?>?page=users&action=edit&id=<?php echo (int)$t['fk_i_buyer_id']; ?>"><?php _e('Buyer in oc-admin', 'starter'); ?></a>
          <?php echo $al_suspend_form((int)$t['fk_i_seller_id'], __('seller', 'starter')); ?>
          <?php echo $al_suspend_form((int)$t['fk_i_buyer_id'], __('buyer', 'starter')); ?>
        </div>
      </div>
    <?php } ?>
  <?php } ?>
</div>
