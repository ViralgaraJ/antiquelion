<?php
  $al_uid = (int)osc_logged_user_id();
  $al_filter = Params::getParam('f');
  if (!in_array($al_filter, array('', 'buying', 'selling', 'unread', 'archived'), true)) { $al_filter = ''; }
  $al_threads = antique_msg_threads($al_uid, $al_filter);
  $al_tabs = array(
    ''         => __('All', 'starter'),
    'unread'   => __('Unread', 'starter'),
    'buying'   => __('Buying', 'starter'),
    'selling'  => __('Selling', 'starter'),
    'archived' => __('Archived', 'starter')
  );
  $al_unread = antique_msg_unread_total();
?>
<div class="al-account-head">
  <h2><?php _e('Messages', 'starter'); ?></h2>
  <?php if ($al_unread > 0) { ?><span class="al-account-count"><?php echo sprintf(_n('%d unread', '%d unread', $al_unread, 'starter'), $al_unread); ?></span><?php } ?>
</div>

<nav class="al-inbox-tabs" aria-label="<?php echo osc_esc_html(__('Filter messages', 'starter')); ?>">
  <?php foreach ($al_tabs as $k => $label) { ?>
    <a href="<?php echo antique_inbox_url($k); ?>"<?php echo $al_filter === $k ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo osc_esc_html($label); ?></a>
  <?php } ?>
</nav>

<?php if ($al_threads) { ?>
  <ul class="al-inbox">
    <?php foreach ($al_threads as $t) {
      $role = antique_msg_role($t, $al_uid);
      $other = antique_msg_user(antique_msg_other_id($t, $al_uid));
      $item = antique_msg_item($t['fk_i_item_id']);
      $unread = (int)$t['i_' . $role . '_unread'];
      $mine = (int)$t['fk_i_last_sender'] === $al_uid;
      $has_offer = function_exists('antique_offers_table') && antique_msg_db()->query(sprintf("SELECT 1 FROM `%s` WHERE fk_i_thread_id = %d AND s_status = 'pending' AND fk_i_from_id <> %d AND dt_expires > '%s' LIMIT 1", antique_offers_table(), (int)$t['pk_i_id'], $al_uid, antique_msg_now()))->numRows() > 0;
    ?>
      <li>
        <a class="al-inbox-row<?php echo $unread ? ' is-unread' : ''; ?>" href="<?php echo antique_thread_url($t['pk_i_id']); ?>">
          <img class="al-inbox-avatar" src="<?php echo osc_esc_html($other['avatar']); ?>" alt="" width="48" height="48" loading="lazy" />
          <span class="al-inbox-main">
            <span class="al-inbox-top">
              <strong><?php echo osc_esc_html($other['name']); ?></strong>
              <span class="al-inbox-role"><?php echo $role == 'buyer' ? __('Seller', 'starter') : __('Buyer', 'starter'); ?></span>
              <time datetime="<?php echo osc_esc_html(date('c', strtotime($t['dt_last']))); ?>"><?php echo osc_esc_html(antique_msg_short_time($t['dt_last'])); ?></time>
            </span>
            <span class="al-inbox-item"><?php echo $item ? osc_esc_html($item['title']) : ((int)$t['fk_i_item_id'] > 0 ? __('Listing removed', 'starter') : __('General enquiry', 'starter')); ?></span>
            <span class="al-inbox-preview">
              <?php if ($has_offer) { ?><span class="al-inbox-flag"><?php _e('Offer to answer', 'starter'); ?></span><?php } ?>
              <?php echo $mine ? '<em>' . __('You:', 'starter') . '</em> ' : ''; ?><?php echo osc_esc_html($t['s_last_preview']); ?>
            </span>
          </span>
          <?php if ($item && $item['thumb']) { ?><img class="al-inbox-thumb" src="<?php echo osc_esc_html($item['thumb']); ?>" alt="" width="56" height="56" loading="lazy" /><?php } ?>
          <?php if ($unread) { ?><span class="al-inbox-badge" aria-label="<?php echo osc_esc_html(sprintf(_n('%d unread message', '%d unread messages', $unread, 'starter'), $unread)); ?>"><?php echo $unread; ?></span><?php } ?>
        </a>
      </li>
    <?php } ?>
  </ul>
<?php } else { ?>
  <div class="al-empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <?php if ($al_filter === '') { ?>
      <h2><?php _e('No messages yet', 'starter'); ?></h2>
      <p><?php _e('Questions, offers and replies about listings appear here. Message a seller from any listing to get started.', 'starter'); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Browse listings', 'starter'); ?></a>
    <?php } else { ?>
      <h2><?php _e('Nothing here', 'starter'); ?></h2>
      <p><?php _e('No conversations match this filter.', 'starter'); ?></p>
      <a class="al-btn al-btn-outline" href="<?php echo antique_inbox_url(); ?>"><?php _e('Show all messages', 'starter'); ?></a>
    <?php } ?>
  </div>
<?php } ?>
