<?php
  if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }
  // Listing card (Heritage Gallery). Expects the current item to be loaded
  // (osc_has_items / osc_has_latest_items / osc_has_premiums loop).
  $al_extra = starter_item_extra(osc_item_id());
  $al_auction = function_exists('antique_bids_card_info') ? antique_bids_card_info(osc_item_id()) : null;
  $al_place = implode(', ', array_filter(array(
    osc_item_city() <> '' ? osc_item_city() : osc_item_region(),
    osc_item_country()
  )));

  $al_badge = '';
  $al_badge_class = '';
  if ($al_extra['i_sold'] == 1) {
    $al_badge = __('Sold', 'starter');
    $al_badge_class = ' is-sold';
  } elseif ($al_extra['i_sold'] == 2) {
    $al_badge = __('Reserved', 'starter');
    $al_badge_class = ' is-reserved';
  } elseif ($al_auction && $al_auction['live']) {
    $al_badge = sprintf(__('%s left', 'starter'), $al_auction['left']);
    $al_badge_class = ' is-live';
  } elseif ($al_auction) {
    $al_badge = __('Auction ended', 'starter');
  }
?>
<article class="al-card<?php echo osc_item_is_premium() ? ' is-premium' : ''; ?>">
  <a class="al-card-media" href="<?php echo osc_item_url(); ?>" tabindex="-1" aria-hidden="true">
    <?php if (osc_count_item_resources()) { ?>
      <img src="<?php echo osc_resource_preview_url(); ?>" alt="" loading="lazy" />
    <?php } else { ?>
      <span class="al-card-noimg"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg><?php _e('No photo', 'starter'); ?></span>
    <?php } ?>
    <?php if ($al_badge !== '') { ?>
      <span class="al-card-badge<?php echo $al_badge_class; ?>"><?php echo osc_esc_html($al_badge); ?></span>
    <?php } ?>
  </a>
  <div class="al-card-body">
    <div class="al-card-cat"><?php echo osc_esc_html(osc_item_category()); ?></div>
    <h3 class="al-card-title"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_highlight(osc_item_title(), 80)); ?></a></h3>
    <div class="al-card-place"><?php if ($al_place !== '') { ?><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><span><?php echo osc_esc_html($al_place); ?></span><?php } else { ?>&nbsp;<?php } ?></div>
    <?php if (osc_price_enabled_at_items()) { ?>
      <div class="al-card-price">
        <?php if ($al_auction) { ?>
          <span class="al-card-price-label"><?php echo osc_esc_html(__($al_auction['label'], 'starter')); ?></span>
          <span class="al-card-price-value"><?php echo antique_price_html($al_auction['amount'] * 1000000, osc_item_currency()); ?></span>
        <?php } else { ?>
          <span class="al-card-price-label"><?php _e('Price', 'starter'); ?></span>
          <span class="al-card-price-value"><?php echo antique_format_price(osc_item_price()); ?></span>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
</article>
