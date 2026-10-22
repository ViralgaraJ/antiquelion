<?php
  $al_uid = (int)osc_logged_user_id();
  $al_thread = antique_msg_thread((int)Params::getParam('thread'));
  $al_role = antique_msg_role($al_thread, $al_uid);
  if ($al_role === null) { ?>
    <div class="al-empty-state">
      <h2><?php _e('Conversation not found', 'starter'); ?></h2>
      <p><?php _e('It may have been removed, or it belongs to another account.', 'starter'); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo antique_inbox_url(); ?>"><?php _e('Back to messages', 'starter'); ?></a>
    </div>
<?php
    return;
  }
  antique_msg_mark_read($al_thread, $al_role);
  $al_thread = antique_msg_thread($al_thread['pk_i_id']);
  $al_other_id = antique_msg_other_id($al_thread, $al_uid);
  $al_other = antique_msg_user($al_other_id);
  $al_item = antique_msg_item($al_thread['fk_i_item_id']);
  $al_messages = antique_msg_messages($al_thread['pk_i_id']);
  $al_last = $al_messages ? (int)end($al_messages)['pk_i_id'] : 0;
  $al_archived = (int)$al_thread['b_' . $al_role . '_archived'] === 1;
  $al_gone = $al_other['id'] === 0;

  // "Seen" under my latest message
  $al_seen = 0;
  for ($i = count($al_messages) - 1; $i >= 0; $i--) {
    $m = $al_messages[$i];
    if ($m['s_kind'] == 'text' && (int)$m['fk_i_sender_id'] === $al_uid) {
      if (antique_msg_seen($al_thread, $al_uid, $m['dt_sent'])) { $al_seen = (int)$m['pk_i_id']; }
      break;
    }
  }
  $al_item_row = ($al_item && $al_role == 'buyer') ? Item::newInstance()->findByPrimaryKey($al_item['id']) : null;
  $al_post = osc_ajax_hook_url('al_msg');
?>
<div class="al-thread" data-al-thread="<?php echo (int)$al_thread['pk_i_id']; ?>" data-after="<?php echo $al_last; ?>" data-seen="<?php echo $al_seen; ?>"
  data-poll="<?php echo osc_esc_html(osc_ajax_hook_url('al_msg_poll', array('thread' => (int)$al_thread['pk_i_id']))); ?>">

  <div class="al-thread-head">
    <a class="al-thread-back" href="<?php echo antique_inbox_url(); ?>" aria-label="<?php echo osc_esc_html(__('All messages', 'starter')); ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </a>
    <img class="al-thread-avatar" src="<?php echo osc_esc_html($al_other['avatar']); ?>" alt="" width="44" height="44" />
    <div class="al-thread-who">
      <?php if ($al_other['url']) { ?><a href="<?php echo osc_esc_html($al_other['url']); ?>"><?php echo osc_esc_html($al_other['name']); ?></a><?php } else { ?><span><?php echo osc_esc_html($al_other['name']); ?></span><?php } ?>
      <small><?php echo $al_role == 'buyer' ? __('Seller', 'starter') : __('Buyer', 'starter'); ?><?php if (function_exists('antique_seller_badge') && $al_role == 'buyer') { echo ' ' . antique_seller_badge($al_other_id, 'small'); } ?></small>
    </div>
    <details class="al-thread-menu">
      <summary aria-label="<?php echo osc_esc_html(__('More actions', 'starter')); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></summary>
      <div class="al-thread-menu-list">
        <form method="post" action="<?php echo osc_esc_html($al_post); ?>">
          <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
          <input type="hidden" name="thread" value="<?php echo (int)$al_thread['pk_i_id']; ?>" />
          <input type="hidden" name="op" value="<?php echo $al_archived ? 'unarchive' : 'archive'; ?>" />
          <button type="submit"><?php echo $al_archived ? __('Move to inbox', 'starter') : __('Archive conversation', 'starter'); ?></button>
        </form>
        <button type="button" data-al-report-open class="is-danger"><?php _e('Report this member', 'starter'); ?></button>
      </div>
    </details>
  </div>

  <?php if ($al_item) { ?>
    <a class="al-thread-item" href="<?php echo osc_esc_html($al_item['url']); ?>">
      <?php if ($al_item['thumb']) { ?><img src="<?php echo osc_esc_html($al_item['thumb']); ?>" alt="" width="56" height="56" /><?php } else { ?><span class="al-card-noimg"></span><?php } ?>
      <span class="al-thread-item-text">
        <strong><?php echo osc_esc_html($al_item['title']); ?></strong>
        <span><?php echo $al_item['price']; ?>
          <?php if (!$al_item['live']) { ?><em class="al-tag"><?php _e('No longer listed', 'starter'); ?></em><?php } elseif ($al_item['sold'] == 1) { ?><em class="al-tag is-sold"><?php _e('Sold', 'starter'); ?></em><?php } elseif ($al_item['sold'] == 2) { ?><em class="al-tag is-reserved"><?php _e('Reserved', 'starter'); ?></em><?php } ?>
        </span>
      </span>
      <span class="al-thread-item-go" aria-hidden="true">&rarr;</span>
    </a>
  <?php } elseif ((int)$al_thread['fk_i_item_id'] > 0) { ?>
    <div class="al-thread-item is-gone"><span class="al-thread-item-text"><strong><?php _e('This listing has been removed', 'starter'); ?></strong></span></div>
  <?php } ?>

  <?php
    $al_tools = '';
    if ($al_item_row) { $al_tools .= antique_offer_box($al_item_row, true); }
    if (function_exists('antique_review_cta')) { $al_tools .= antique_review_cta($al_thread, $al_uid); }
  ?>
  <?php if (trim($al_tools) !== '') { ?><div class="al-thread-tools"><?php echo $al_tools; ?></div><?php } ?>

  <div class="al-thread-list" role="log" aria-live="polite" aria-label="<?php echo osc_esc_html(__('Messages', 'starter')); ?>">
    <p class="al-thread-safety">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <span><?php echo sprintf(__('Keep the conversation here so there is a record of what you agree. Never pay by bank transfer to someone you have not checked, and never share passwords or codes. <a href="%s">Buying safely</a>', 'starter'), antique_info_page_url('al-safety')); ?></span>
    </p>
    <?php echo antique_msg_render_list($al_messages, $al_thread, $al_uid); ?>
  </div>
  <div class="al-msg-seen-note"<?php echo $al_seen ? '' : ' hidden'; ?>><?php _e('Seen', 'starter'); ?></div>

  <?php if ($al_gone) { ?>
    <p class="al-thread-closed"><?php _e('This member has closed their account, so you cannot reply.', 'starter'); ?></p>
  <?php } else { ?>
    <form class="al-thread-compose" method="post" action="<?php echo osc_esc_html($al_post); ?>">
      <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
      <input type="hidden" name="op" value="send" />
      <input type="hidden" name="thread" value="<?php echo (int)$al_thread['pk_i_id']; ?>" />
      <label class="al-sr-only" for="al-compose-body"><?php _e('Your message', 'starter'); ?></label>
      <textarea id="al-compose-body" name="body" rows="1" maxlength="<?php echo ANTIQUE_MSG_MAX; ?>" required placeholder="<?php echo osc_esc_html(sprintf(__('Message %s', 'starter'), $al_other['name'])); ?>"></textarea>
      <button type="submit" class="al-btn al-btn-brass" aria-label="<?php echo osc_esc_html(__('Send', 'starter')); ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg>
        <span><?php _e('Send', 'starter'); ?></span>
      </button>
      <p class="al-thread-error" role="alert" hidden></p>
    </form>
  <?php } ?>

  <dialog class="al-dialog al-report-dialog" aria-labelledby="al-report-title">
    <form method="post" action="<?php echo osc_esc_html($al_post); ?>" class="al-form">
      <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
      <input type="hidden" name="op" value="report" />
      <input type="hidden" name="thread" value="<?php echo (int)$al_thread['pk_i_id']; ?>" />
      <div class="al-dialog-head">
        <h2 id="al-report-title"><?php echo sprintf(__('Report %s', 'starter'), osc_esc_html($al_other['name'])); ?></h2>
        <button type="button" class="al-dialog-close" data-al-dialog-close aria-label="<?php echo osc_esc_html(__('Close', 'starter')); ?>">&times;</button>
      </div>
      <p class="al-muted"><?php _e('Our team will read this conversation. The other member is not told who reported them.', 'starter'); ?></p>
      <label class="al-form-full"><span><?php _e('What happened?', 'starter'); ?></span>
        <textarea name="reason" rows="3" maxlength="500" placeholder="<?php echo osc_esc_html(__('e.g. asked me to pay outside the site, sent a suspicious link', 'starter')); ?>"></textarea>
      </label>
      <button type="submit" class="al-btn al-btn-danger al-btn-block"><?php _e('Send report', 'starter'); ?></button>
    </form>
  </dialog>
</div>
