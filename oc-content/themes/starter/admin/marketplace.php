<?php
  require_once 'functions.php';
  starter_backoffice_menu(__('Marketplace', 'starter'));

  $al_tab = Params::getParam('tab');
  if (!in_array($al_tab, array('verify', 'reviews', 'messages'), true)) { $al_tab = 'verify'; }
  $al_db = antique_msg_db();
  $al_url = function ($tab) { return antique_marketplace_admin_url($tab); };
  $al_token = osc_csrfguard_generate_token();
  $al_form = function ($act, $id, $label, $class = '', $extra = '') use ($al_token, $al_tab) {
    return '<form method="post" action="' . osc_esc_html(antique_marketplace_admin_url($al_tab)) . '" class="almk-inline">'
      . '<input type="hidden" name="octoken" value="' . $al_token . '" /><input type="hidden" name="al_mk" value="' . $act . '" /><input type="hidden" name="tab" value="' . $al_tab . '" /><input type="hidden" name="id" value="' . (int)$id . '" />'
      . $extra . '<button type="submit" class="almk-btn ' . $class . '">' . osc_esc_html($label) . '</button></form>';
  };
  $al_count = function ($sql) use ($al_db) { $r = $al_db->query($sql); return $r ? (int)$r->row()['n'] : 0; };
  $al_pending = $al_count("SELECT COUNT(*) AS n FROM `" . antique_sellers_table() . "` WHERE i_verify = " . ANTIQUE_VERIFY_PENDING);
  $al_rep_reviews = $al_count("SELECT COUNT(*) AS n FROM `" . antique_reviews_table() . "` WHERE b_reported = 1");
  $al_rep_threads = $al_count("SELECT COUNT(*) AS n FROM `" . antique_msg_threads_table() . "` WHERE b_reported = 1");
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
  .almk-doc { display: inline-block; padding: 7px 14px; border-radius: 4px; background: #A67C2E; color: #fff !important; font-weight: 700; text-decoration: none; }
  .almk-tag { display: inline-block; padding: 1px 8px; border-radius: 999px; background: #F3EFE6; color: #4B5563; font-size: 12px; font-weight: 700; }
  .almk-tag.is-ok { background: #DCF5E7; color: #067647; }
  .almk-tag.is-bad { background: #FDE8E6; color: #B42318; }
  .almk-quote { margin: 8px 0; padding: 10px 12px; border-left: 3px solid #D9B26A; background: #FBF9F4; white-space: pre-wrap; }
  .almk-msgs { max-height: 320px; overflow: auto; margin: 10px 0; padding: 10px; border: 1px solid #EFEAE0; border-radius: 6px; background: #FCFBF8; font-size: 13px; }
  .almk-msgs p { margin: 0 0 6px; }
  .almk-empty { padding: 30px; border: 1px dashed #D6CDBB; border-radius: 8px; background: #fff; color: #6B7280; text-align: center; }
</style>

<div class="mb-body almk">
  <nav class="almk-tabs">
    <a href="<?php echo $al_url('verify'); ?>"<?php echo $al_tab == 'verify' ? ' class="is-active"' : ''; ?>><?php _e('Seller verification', 'starter'); ?><?php if ($al_pending) { ?><em><?php echo $al_pending; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('reviews'); ?>"<?php echo $al_tab == 'reviews' ? ' class="is-active"' : ''; ?>><?php _e('Reviews', 'starter'); ?><?php if ($al_rep_reviews) { ?><em><?php echo $al_rep_reviews; ?></em><?php } ?></a>
    <a href="<?php echo $al_url('messages'); ?>"<?php echo $al_tab == 'messages' ? ' class="is-active"' : ''; ?>><?php _e('Reported conversations', 'starter'); ?><?php if ($al_rep_threads) { ?><em><?php echo $al_rep_threads; ?></em><?php } ?></a>
  </nav>

  <?php if ($al_tab == 'verify') { ?>
    <?php
      $res = $al_db->query("SELECT s.*, u.s_name, u.s_email, u.b_company, u.s_phone_mobile, u.dt_reg_date FROM `" . antique_sellers_table() . "` s JOIN `" . DB_TABLE_PREFIX . "t_user` u ON u.pk_i_id = s.fk_i_user_id
        WHERE s.i_verify IN (" . ANTIQUE_VERIFY_PENDING . "," . ANTIQUE_VERIFY_OK . ") ORDER BY (s.i_verify = " . ANTIQUE_VERIFY_PENDING . ") DESC, s.dt_verify_req ASC LIMIT 200");
      $rows = $res ? $res->result() : array();
    ?>
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
          <?php if ($r['s_doc']) { ?><a class="almk-doc" href="<?php echo osc_esc_html(osc_admin_ajax_hook_url('al_verify_doc', array('seller' => (int)$r['fk_i_user_id']))); ?>" target="_blank" rel="noopener"><?php _e('Open document', 'starter'); ?></a><?php } ?>
          <div class="almk-actions">
            <?php echo $al_form('approve', $r['fk_i_user_id'], __('Approve and verify', 'starter'), 'is-ok'); ?>
            <?php echo $al_form('decline', $r['fk_i_user_id'], __('Decline', 'starter'), 'is-bad', '<input type="text" name="note" maxlength="500" placeholder="' . osc_esc_html(__('Reason shown to the seller (e.g. the document is not readable)', 'starter')) . '" />'); ?>
          </div>
        <?php } else { ?>
          <div class="almk-actions"><?php echo $al_form('unverify', $r['fk_i_user_id'], __('Remove badge', 'starter'), 'is-bad'); ?></div>
        <?php } ?>
      </div>
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
        </div>
      </div>
    <?php } ?>
  <?php } ?>
</div>
