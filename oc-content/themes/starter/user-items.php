<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
</head>
<body id="body-user-items" class="body-ua al-account-page">
  <?php osc_current_web_theme_path('header.php'); ?>
  <?php
    $item_type = Params::getParam('itemType');
    $titles = array(
      'active' => __('Active listings', 'starter'),
      'pending_validate' => __('Listings awaiting approval', 'starter'),
      'expired' => __('Expired listings', 'starter')
    );
    $section_title = isset($titles[$item_type]) ? $titles[$item_type] : __('All listings', 'starter');

    // make sure the user's listings are in the view
    if (!View::newInstance()->_exists('items') || osc_count_items() == 0) {
      $al_user_items = Item::newInstance()->findItemTypesByUserID(osc_logged_user_id(), 0, null, $item_type);
      if (!empty($al_user_items)) { View::newInstance()->_exportVariableToView('items', $al_user_items); }
    }
    $sep = osc_rewrite_enabled() ? '?' : '&';
  ?>

  <div class="al-wrap al-account-layout">
    <aside class="al-account-side"><?php echo antique_account_nav(); ?></aside>

    <main class="al-account-main">
      <div class="al-account-head">
        <h2><?php echo osc_esc_html($section_title); ?></h2>
        <a class="al-btn al-btn-brass" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
      </div>

      <?php osc_run_hook('user_items_top'); ?>

      <?php if (osc_count_items() > 0) { ?>
        <div class="al-listing-rows">
          <?php while (osc_has_items()) { ?>
            <?php
              $item_extra = starter_item_extra(osc_item_id());
              if (osc_item_is_expired()) {
                $st = array(__('Expired', 'starter'), 'is-expired');
              } else if (osc_item_is_inactive()) {
                $st = array(__('Awaiting approval', 'starter'), 'is-pending');
              } else {
                $st = array(__('Active', 'starter'), 'is-active');
              }
              if ($item_extra['i_sold'] == 1) { $st = array(__('Sold', 'starter'), 'is-sold'); }
              if ($item_extra['i_sold'] == 2) { $st = array(__('Reserved', 'starter'), 'is-reserved'); }

              $secret = '&secret=' . osc_item_field('s_secret') . '&itemType=' . urlencode($item_type);
              $sold_url = osc_user_list_items_url() . $sep . 'itemId=' . osc_item_id() . '&markSold=' . ($item_extra['i_sold'] == 1 ? 0 : 1) . $secret;
              $reserved_url = osc_user_list_items_url() . $sep . 'itemId=' . osc_item_id() . '&markSold=' . ($item_extra['i_sold'] == 2 ? 0 : 2) . $secret;
              $exp = osc_item_field('dt_expiration');
            ?>
            <article class="al-listing-row">
              <a class="al-listing-thumb" href="<?php echo osc_item_url(); ?>" tabindex="-1" aria-hidden="true">
                <?php if (osc_count_item_resources() > 0) { ?>
                  <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="" loading="lazy" />
                <?php } else { ?>
                  <span class="al-card-noimg"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg></span>
                <?php } ?>
              </a>

              <div class="al-listing-info">
                <div class="al-listing-top">
                  <span class="al-status <?php echo $st[1]; ?>"><?php echo $st[0]; ?></span>
                  <?php if (osc_item_is_premium()) { ?><span class="al-status is-premium"><?php _e('Featured', 'starter'); ?></span><?php } ?>
                  <span class="al-listing-cat"><?php echo osc_esc_html(osc_item_category()); ?></span>
                </div>
                <h3><a href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_highlight(osc_item_title(), 90)); ?></a></h3>
                <div class="al-listing-meta">
                  <?php if (osc_price_enabled_at_items()) { ?><strong><?php echo antique_format_price(osc_item_price()); ?></strong><?php } ?>
                  <span><?php echo sprintf(__('Listed %s', 'starter'), date('j M Y', strtotime(osc_item_pub_date()))); ?></span>
                  <span><?php echo (date('Y', strtotime($exp)) > 3000 || $exp == '') ? __('No expiry', 'starter') : sprintf(__('Expires %s', 'starter'), date('j M Y', strtotime($exp))); ?></span>
                  <span><?php echo sprintf(_n('%d photo', '%d photos', osc_count_item_resources(), 'starter'), osc_count_item_resources()); ?></span>
                </div>
                <?php osc_run_hook('user_items_body', osc_item_id()); ?>
              </div>

              <div class="al-listing-actions">
                <a class="al-btn al-btn-outline" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><?php _e('Edit', 'starter'); ?></a>
                <details class="al-more">
                  <summary aria-label="<?php echo osc_esc_html(__('More actions', 'starter')); ?>"><?php _e('More', 'starter'); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                  </summary>
                  <div class="al-more-menu">
                    <a href="<?php echo osc_item_url(); ?>"><?php _e('View listing', 'starter'); ?></a>
                    <?php if (osc_item_is_inactive()) { ?>
                      <a href="<?php echo osc_item_activate_url(); ?>"><?php _e('Validate listing', 'starter'); ?></a>
                    <?php } else if (!in_array(osc_item_category_id(), starter_extra_fields_hide())) { ?>
                      <a href="<?php echo osc_esc_html($sold_url); ?>"><?php echo $item_extra['i_sold'] == 1 ? __('Mark as available', 'starter') : __('Mark as sold', 'starter'); ?></a>
                      <a href="<?php echo osc_esc_html($reserved_url); ?>"><?php echo $item_extra['i_sold'] == 2 ? __('Remove reservation', 'starter') : __('Mark as reserved', 'starter'); ?></a>
                    <?php } ?>
                    <?php if (function_exists('republish_link_raw') && republish_link_raw(osc_item_id())) { ?>
                      <a href="<?php echo republish_link_raw(osc_item_id()); ?>" rel="nofollow"><?php _e('Republish', 'starter'); ?></a>
                    <?php } ?>
                    <?php osc_run_hook('user_items_action', osc_item_id()); ?>
                    <a class="is-danger" href="<?php echo osc_item_delete_url(); ?>" rel="nofollow" onclick="return confirm('<?php echo osc_esc_js(__('Delete this listing? This cannot be undone.', 'starter')); ?>');"><?php _e('Delete', 'starter'); ?></a>
                  </div>
                </details>
              </div>
            </article>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="al-empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M20 7h-9M14 17H5M17 20a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM7 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
          <h2><?php _e('Nothing here yet', 'starter'); ?></h2>
          <p><?php _e('List an antique in a few minutes. Listings are free.', 'starter'); ?></p>
          <a class="al-btn al-btn-brass" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
        </div>
      <?php } ?>

      <?php osc_run_hook('user_items_bottom'); ?>

      <?php if (osc_list_total_pages() > 1) { ?>
        <nav class="al-pagination" aria-label="<?php echo osc_esc_html(__('Pages', 'starter')); ?>"><ul>
          <?php for ($i = 0; $i < osc_list_total_pages(); $i++) { ?>
            <li><a class="<?php echo $i == osc_list_page() ? 'searchPaginationSelected' : ''; ?>" href="<?php echo osc_user_list_items_url($i + 1) . ($item_type != '' ? $sep . 'itemType=' . urlencode($item_type) : ''); ?>"<?php echo $i == osc_list_page() ? ' aria-current="page"' : ''; ?>><?php echo $i + 1; ?></a></li>
          <?php } ?>
        </ul></nav>
      <?php } ?>
    </main>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
