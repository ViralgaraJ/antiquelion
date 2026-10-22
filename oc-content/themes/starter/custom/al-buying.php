<?php
  $al_bids = antique_my_bids();
  $al_items = array();
  if ($al_bids) {
    $res = Item::newInstance()->dao->query("SELECT * FROM `" . DB_TABLE_PREFIX . "t_item` WHERE pk_i_id IN (" . implode(',', array_map('intval', array_keys($al_bids))) . ")");
    $rows = array();
    if ($res) { foreach ($res->result() as $r) { $rows[(int)$r['pk_i_id']] = $r; } }
    foreach (array_keys($al_bids) as $id) { if (isset($rows[$id])) { $al_items[] = $rows[$id]; } }
    $al_items = Item::newInstance()->extendData($al_items);
  }
  View::newInstance()->_exportVariableToView('items', $al_items);
  $al_watch_n = count(antique_watch_ids());
  $al_labels = array(
    'leading' => array(__('Winning', 'starter'), 'is-good'),
    'outbid'  => array(__('Outbid', 'starter'), 'is-bad'),
    'won'     => array(__('You won', 'starter'), 'is-good'),
    'lost'    => array(__('Not won', 'starter'), 'is-muted')
  );
?>
<div class="al-account-head">
  <h2><?php _e('Buying', 'starter'); ?></h2>
  <a class="al-btn al-btn-outline" href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('Live auctions', 'starter'); ?></a>
</div>

<div class="al-buy-stats">
  <?php
    $al_count = array('leading' => 0, 'outbid' => 0, 'won' => 0);
    foreach ($al_bids as $b) { if (isset($al_count[$b['status']])) { $al_count[$b['status']]++; } }
  ?>
  <div class="al-stat al-panel"><strong><?php echo $al_count['leading']; ?></strong><span><?php _e('Winning now', 'starter'); ?></span></div>
  <div class="al-stat al-panel<?php echo $al_count['outbid'] ? ' is-alert' : ''; ?>"><strong><?php echo $al_count['outbid']; ?></strong><span><?php _e('Outbid', 'starter'); ?></span></div>
  <div class="al-stat al-panel"><strong><?php echo $al_count['won']; ?></strong><span><?php _e('Won', 'starter'); ?></span></div>
  <a class="al-stat al-panel" href="<?php echo antique_watchlist_url(); ?>"><strong><?php echo $al_watch_n; ?></strong><span><?php _e('Watching', 'starter'); ?></span></a>
</div>

<?php $al_to_review = antique_reviews_waiting(osc_logged_user_id()); ?>
<?php if ($al_to_review) { ?>
  <h3 class="al-account-sub"><?php _e('Waiting for your review', 'starter'); ?></h3>
  <div class="al-review-waiting">
    <?php foreach ($al_to_review as $it) { ?>
      <div class="al-panel al-review-waiting-item">
        <?php if ($it['thumb']) { ?><img src="<?php echo osc_esc_html($it['thumb']); ?>" alt="" width="56" height="56" loading="lazy" /><?php } else { ?><span class="al-card-noimg"></span><?php } ?>
        <span><strong><?php echo osc_esc_html($it['title']); ?></strong><em><?php echo osc_esc_html(antique_msg_user($it['user'])['name']); ?></em></span>
        <a class="al-btn al-btn-brass" href="<?php echo antique_review_url($it['id']); ?>"><?php _e('Review', 'starter'); ?></a>
      </div>
    <?php } ?>
  </div>
<?php } ?>

<h3 class="al-account-sub"><?php _e('My bids', 'starter'); ?></h3>
<?php if (count($al_items) > 0) { ?>
  <div class="al-listing-rows">
    <?php while (osc_has_items()) { $b = $al_bids[osc_item_id()]; $cur = osc_item_currency(); ?>
      <article class="al-listing-row al-bid-row-item">
        <a class="al-listing-thumb" href="<?php echo osc_item_url(); ?>" tabindex="-1" aria-hidden="true">
          <?php if (osc_count_item_resources()) { ?><img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="" loading="lazy" /><?php } else { ?><span class="al-card-noimg"></span><?php } ?>
        </a>
        <div class="al-listing-info">
          <div class="al-listing-top"><span class="al-bid-badge <?php echo $al_labels[$b['status']][1]; ?>"><?php echo $al_labels[$b['status']][0]; ?></span>
            <span class="al-listing-cat"><?php echo $b['live'] ? sprintf(__('Ends in %s', 'starter'), antique_bids_time_left(strtotime($b['end']) - time())) : sprintf(__('Ended %s', 'starter'), date('j M Y', strtotime($b['end']))); ?></span></div>
          <h3><a href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_item_title()); ?></a></h3>
          <p class="al-bid-line">
            <?php if ($b['current'] > 0) { echo sprintf($b['live'] ? __('Current bid %s', 'starter') : __('Final bid %s', 'starter'), '<strong>' . osc_esc_html(antique_bids_money($b['current'], $cur)) . '</strong>') . ' · '; } ?>
            <?php echo sprintf(__('Your maximum %s', 'starter'), osc_esc_html(antique_bids_money($b['my_max'], $cur))); ?>
          </p>
        </div>
        <div class="al-listing-actions">
          <?php if ($b['status'] == 'outbid') { ?><a class="al-btn al-btn-brass" href="<?php echo osc_item_url(); ?>#al-auction"><?php _e('Bid again', 'starter'); ?></a>
          <?php } elseif ($b['status'] == 'won') { ?><a class="al-btn al-btn-ink" href="<?php echo osc_item_url(); ?>#al-contact"><?php _e('Contact seller', 'starter'); ?></a>
          <?php } else { ?><a class="al-btn al-btn-outline" href="<?php echo osc_item_url(); ?>"><?php _e('View', 'starter'); ?></a><?php } ?>
        </div>
      </article>
    <?php } ?>
  </div>
<?php } else { ?>
  <div class="al-empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="m14 13-7.5 7.5a2.1 2.1 0 0 1-3-3L11 10M16 16l6-6M8 8l6-6M9 7l8 8M21 11l-8-8"/></svg>
    <h2><?php _e('You have not bid on anything yet', 'starter'); ?></h2>
    <p><?php _e('Find an auction you like and enter your maximum bid. We will tell you if someone outbids you.', 'starter'); ?></p>
    <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('See live auctions', 'starter'); ?></a>
  </div>
<?php } ?>
