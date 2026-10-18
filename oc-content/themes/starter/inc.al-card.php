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
      <img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" alt="" loading="lazy" class="is-placeholder" />
    <?php } ?>
    <?php if ($al_badge !== '') { ?>
      <span class="al-card-badge<?php echo $al_badge_class; ?>"><?php echo osc_esc_html($al_badge); ?></span>
    <?php } ?>
  </a>
  <div class="al-card-body">
    <div class="al-card-cat"><?php echo osc_esc_html(osc_item_category()); ?></div>
    <h3 class="al-card-title"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_highlight(osc_item_title(), 80)); ?></a></h3>
    <?php if ($al_place !== '') { ?>
      <div class="al-card-place"><?php echo osc_esc_html($al_place); ?></div>
    <?php } ?>
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
