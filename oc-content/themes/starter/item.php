<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
<?php osc_current_web_theme_path('head.php'); ?>
<?php
  $content_only = (Params::getParam('contentOnly') == 1 ? 1 : 0);

  if (osc_item_price() == '') {
    $og_price = __('Check with seller', 'starter');
  } else if (osc_item_price() == 0) {
    $og_price = __('Free', 'starter');
  } else {
    $og_price = osc_item_price() / 1000000;
  }

  $item_extra = starter_item_extra(osc_item_id());
  $item_user = osc_item_user_id() <> 0 ? User::newInstance()->findByPrimaryKey(osc_item_user_id()) : array();
  $is_owner = osc_is_web_user_logged_in() && osc_item_user_id() == osc_logged_user_id();

  $location_array = array_filter(array(osc_item_city(), osc_item_region(), osc_item_country()));
  $item_loc = implode(', ', $location_array);

  // Running or finished auction on this listing (the bidding box replaces the plain price)
  $al_auction = null;
  if (class_exists('ModelBids')) {
    $al_auction = ModelBids::newInstance()->getAuction(osc_item_id());
    if (!$al_auction || $al_auction['b_active'] == 0) { $al_auction = null; }
  }

  // Seller phone: listing phone, then the seller's profile phones
  $mobile = trim((string)$item_extra['s_phone']);
  if ($mobile == '' && function_exists('bo_mgr_show_mobile')) { $mobile = trim((string)bo_mgr_show_mobile()); }
  if ($mobile == '' && !empty($item_user)) { $mobile = trim((string)$item_user['s_phone_mobile']); }
  if ($mobile == '' && !empty($item_user)) { $mobile = trim((string)$item_user['s_phone_land']); }
  $phone_ok = strlen($mobile) >= 4;
  $phone_login = $phone_ok && osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in();
  $al_phone = antique_phone_parts($mobile, array(osc_item_country_code(), !empty($item_user['fk_c_country_code']) ? $item_user['fk_c_country_code'] : ''));
  $phone_digits = $al_phone['e164'] !== '' ? $al_phone['e164'] : preg_replace('/[^0-9+]/', '', $mobile);
  $mobile = $al_phone['display'];

  $seller_name = osc_item_user_id() ? antique_seller_display_name(osc_item_user_id(), osc_item_contact_name()) : osc_item_contact_name();
  if ($seller_name == '' || $seller_name == __('Anonymous', 'starter')) {
    $seller_name = !empty($item_user['s_name']) ? $item_user['s_name'] : __('Private seller', 'starter');
  }

  $show_details = !in_array(osc_item_category_id(), starter_extra_fields_hide());
  $condition = $show_details ? starter_condition_name($item_extra['i_condition']) : '';
  $transaction = $show_details ? starter_transaction_name($item_extra['i_transaction']) : '';

  osc_get_item_resources();
?>
<?php if ($content_only == 0) { ?>
  <meta property="og:title" content="<?php echo osc_esc_html(osc_item_title()); ?>" />
  <?php if (osc_count_item_resources() > 0) { ?><meta property="og:image" content="<?php echo osc_resource_url(); ?>" /><?php } ?>
  <meta property="og:site_name" content="<?php echo osc_esc_html(osc_page_title()); ?>" />
  <meta property="og:url" content="<?php echo osc_item_url(); ?>" />
  <meta property="og:description" content="<?php echo osc_esc_html(osc_highlight(osc_item_description(), 500)); ?>" />
  <meta property="og:type" content="product" />
  <meta property="og:locale" content="<?php echo osc_current_user_locale(); ?>" />
  <meta property="product:retailer_item_id" content="<?php echo osc_item_id(); ?>" />
  <meta property="product:price:amount" content="<?php echo osc_esc_html($og_price); ?>" />
  <?php if (osc_item_price() <> '' && osc_item_price() <> 0) { ?><meta property="product:price:currency" content="<?php echo osc_item_currency(); ?>" /><?php } ?>
  <script type="application/ld+json"><?php
    $ld = array(
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => osc_item_title(),
      'description' => osc_highlight(osc_item_description(), 500),
      'sku' => (string)osc_item_id(),
      'url' => osc_item_url()
    );
    if (osc_count_item_resources() > 0) { $ld['image'] = osc_resource_url(); }
    $al_ld_det = antique_item_details(osc_item_id());
    $al_ld_opt = antique_detail_options();
    if (isset($al_ld_opt['material'][$al_ld_det['s_material']])) { $ld['material'] = $al_ld_opt['material'][$al_ld_det['s_material']]; }
    if ($al_ld_det['s_origin']) { $ld['countryOfOrigin'] = array('@type' => 'Country', 'name' => strtoupper($al_ld_det['s_origin'])); }
    if ($al_ld_det['s_maker'] !== '') { $ld['brand'] = array('@type' => 'Brand', 'name' => $al_ld_det['s_maker']); }
    // antiques are second-hand unless the seller says the item is new
    $ld['itemCondition'] = ($condition !== '' && stripos($condition, 'new') === 0) ? 'https://schema.org/NewCondition' : 'https://schema.org/UsedCondition';
    $al_ld_price = osc_item_price() > 0 ? osc_item_price() / 1000000 : 0;
    if ($al_auction && function_exists('antique_bids_state')) {
      $al_ld_state = antique_bids_state(osc_item_id());
      if (!empty($al_ld_state['amount'])) { $al_ld_price = (float)$al_ld_state['amount']; }
    }
    if ($al_ld_price > 0) {
      $ld['offers'] = array(
        '@type' => 'Offer',
        'price' => round($al_ld_price, 2),
        'priceCurrency' => osc_item_currency(),
        'availability' => $item_extra['i_sold'] == 1 ? 'https://schema.org/SoldOut' : ($item_extra['i_sold'] == 2 ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock'),
        'itemCondition' => $ld['itemCondition'],
        'url' => osc_item_url()
      );
      if (osc_item_user_id() > 0) {
        $ld['offers']['seller'] = array('@type' => (!empty($item_user['b_company']) ? 'Organization' : 'Person'), 'name' => $seller_name, 'url' => osc_user_public_profile_url(osc_item_user_id()));
      }
      if (!osc_item_is_expired() && osc_item_field('dt_expiration') && strtotime(osc_item_field('dt_expiration')) < strtotime('+5 years')) {
        $ld['offers']['priceValidUntil'] = date('Y-m-d', strtotime(osc_item_field('dt_expiration')));
      }
    }
    echo json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
  ?></script>
<?php } ?>
</head>

<body id="body-item" class="page-body al-item-page<?php if ($content_only == 1) { ?> content_only<?php } ?>">
<?php if ($content_only == 0) { osc_current_web_theme_path('header.php'); } ?>

<div id="al-item" class="al-wrap">
  <?php osc_run_hook('item_top'); ?>
  <?php echo starter_banner('item_top'); ?>

  <?php
    $breadcrumb = osc_breadcrumb('<span class="al-crumb-sep" aria-hidden="true">/</span>', false);
    $breadcrumb = str_replace('<span itemprop="title">' . osc_page_title() . '</span>', '<span itemprop="title">' . __('Home', 'starter') . '</span>', $breadcrumb);
  ?>
  <?php if ($breadcrumb != '' && $content_only == 0) { ?>
    <nav class="al-crumbs" aria-label="<?php echo osc_esc_html(__('Breadcrumb', 'starter')); ?>"><?php echo $breadcrumb; ?></nav>
  <?php } ?>

  <?php if (osc_item_is_expired()) { ?>
    <div class="al-notice"><?php _e('This listing has expired and is no longer available.', 'starter'); ?></div>
  <?php } ?>

  <div class="al-item-grid">

    <!-- GALLERY -->
    <section class="al-gallery" aria-label="<?php echo osc_esc_html(__('Photos', 'starter')); ?>">
      <?php if (osc_images_enabled_at_items() && osc_count_item_resources() > 0) { ?>
        <?php osc_reset_resources(); $al_n = osc_count_item_resources(); ?>
        <div class="al-gallery-main">
          <?php for ($i = 0; osc_has_item_resources(); $i++) { ?>
            <?php
              // zoom opens the photo as uploaded when the original is kept (Settings > Media)
              $al_zoom = osc_resource_url();
              if (osc_keep_original_image() && is_file(osc_base_path() . osc_resource_field('s_path') . osc_resource_id() . '_original.' . osc_resource_field('s_extension'))) { $al_zoom = osc_resource_original_url(); }
            ?>
            <a href="<?php echo $al_zoom; ?>" style="--al-photo: url('<?php echo osc_esc_html(osc_resource_url()); ?>')" data-fancybox="gallery" class="al-gallery-slide<?php echo $i == 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>"<?php echo $i == 0 ? '' : ' hidden'; ?>>
              <img src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo sprintf(__('photo %1$d of %2$d', 'starter'), $i + 1, $al_n); ?>"<?php echo $i == 0 ? '' : ' loading="lazy"'; ?> />
            </a>
          <?php } ?>
          <?php if ($al_n > 1) { ?>
            <button type="button" class="al-gallery-nav is-prev" aria-label="<?php echo osc_esc_html(__('Previous photo', 'starter')); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
            <button type="button" class="al-gallery-nav is-next" aria-label="<?php echo osc_esc_html(__('Next photo', 'starter')); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
          <?php } ?>
          <span class="al-gallery-count"><span class="al-gallery-current">1</span> / <?php echo $al_n; ?></span>
        </div>
        <?php if ($al_n > 1) { ?>
          <div class="al-gallery-thumbs">
            <?php osc_reset_resources(); ?>
            <?php for ($i = 0; osc_has_item_resources(); $i++) { ?>
              <button type="button" class="al-gallery-thumb<?php echo $i == 0 ? ' is-active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="<?php echo osc_esc_html(sprintf(__('Show photo %d', 'starter'), $i + 1)); ?>">
                <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="" loading="lazy" />
              </button>
            <?php } ?>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="al-gallery-main is-empty">
          <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg>
          <span><?php _e('No photos yet', 'starter'); ?></span>
        </div>
      <?php } ?>
      <?php osc_run_hook('item_images'); ?>
    </section>

    <!-- SUMMARY + BUY BOX -->
    <aside class="al-item-aside">
      <div class="al-panel al-summary">
        <a class="al-summary-cat" href="<?php echo osc_search_url(array('page' => 'search', 'sCategory' => osc_item_category_id())); ?>"><?php echo osc_esc_html(osc_item_category()); ?></a>
        <h1 class="al-item-title"><?php echo osc_esc_html(osc_item_title()); ?></h1>

        <div class="al-tags">
          <?php if ($item_extra['i_sold'] == 1) { ?><span class="al-tag is-sold"><?php _e('Sold', 'starter'); ?></span><?php } ?>
          <?php if ($item_extra['i_sold'] == 2) { ?><span class="al-tag is-reserved"><?php _e('Reserved', 'starter'); ?></span><?php } ?>
          <?php if (osc_item_is_premium()) { ?><span class="al-tag is-premium"><?php _e('Featured', 'starter'); ?></span><?php } ?>
          <?php if ($condition) { ?><span class="al-tag"><?php echo osc_esc_html($condition); ?></span><?php } ?>
          <?php if ($transaction) { ?><span class="al-tag"><?php echo osc_esc_html($transaction); ?></span><?php } ?>
        </div>

        <?php $al_watchers = antique_watch_count(osc_item_id()); ?>
        <div class="al-watch-row">
          <?php if (!$is_owner) { echo antique_watch_button(osc_item_id(), 'item'); } ?>
          <span class="al-watch-count" data-al-watch-count="<?php echo osc_item_id(); ?>"<?php echo $al_watchers > 0 ? '' : ' hidden'; ?>><?php echo sprintf(_n('%d person watching', '%d people watching', $al_watchers, 'starter'), $al_watchers); ?></span>
        </div>

        <?php osc_run_hook('item_title'); ?>

        <?php if (!$al_auction && osc_price_enabled_at_items()) { ?>
          <div class="al-buy-price">
            <span class="al-buy-label"><?php _e('Price', 'starter'); ?></span>
            <div class="al-buy-amount"><?php echo antique_format_price(osc_item_price()); ?></div>
          </div>
          <?php if (!$is_owner && $content_only == 0) { echo antique_offer_box(osc_item()); } ?>
        <?php } ?>

        <ul class="al-meta">
          <?php if ($item_loc <> '') { ?>
            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><?php echo osc_esc_html($item_loc); ?></li>
          <?php } ?>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span title="<?php echo osc_esc_html(osc_format_date(osc_item_pub_date())); ?>"><?php echo osc_item_mod_date() == '' ? __('Listed', 'starter') : __('Updated', 'starter'); ?> <?php echo starter_smart_date(osc_item_mod_date() == '' ? osc_item_pub_date() : osc_item_mod_date()); ?></span>
          </li>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg><?php echo sprintf(__('Listing %d', 'starter'), osc_item_id()); ?></li>
        </ul>
      </div>

      <?php if ($content_only == 0) { ?>
        <?php osc_run_hook('item_sidebar_top'); ?>
        <?php echo starter_banner('item_sidebar'); ?>

        <?php if ($is_owner) { ?>
          <?php
            $sep = osc_rewrite_enabled() ? '?' : '&';
            $secret = '&secret=' . osc_item_field('s_secret') . '&itemType=active';
            $sold_url = osc_user_list_items_url() . $sep . 'itemId=' . osc_item_id() . '&markSold=' . ($item_extra['i_sold'] == 1 ? 0 : 1) . $secret;
            $reserved_url = osc_user_list_items_url() . $sep . 'itemId=' . osc_item_id() . '&markSold=' . ($item_extra['i_sold'] == 2 ? 0 : 2) . $secret;
          ?>
          <div class="al-panel al-owner-tools">
            <h2 class="al-panel-title"><?php _e('Manage your listing', 'starter'); ?></h2>
            <div class="al-owner-grid">
              <a class="al-btn al-btn-ink" href="<?php echo osc_item_edit_url(); ?>"><?php _e('Edit listing', 'starter'); ?></a>
              <?php if ($show_details) { ?>
                <a class="al-btn al-btn-outline" href="<?php echo osc_esc_html($sold_url); ?>"><?php echo $item_extra['i_sold'] == 1 ? __('Mark as available', 'starter') : __('Mark as sold', 'starter'); ?></a>
                <a class="al-btn al-btn-outline" href="<?php echo osc_esc_html($reserved_url); ?>"><?php echo $item_extra['i_sold'] == 2 ? __('Remove reservation', 'starter') : __('Mark as reserved', 'starter'); ?></a>
              <?php } ?>
              <a class="al-btn al-btn-danger" href="<?php echo osc_item_delete_url(); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Delete this listing? This cannot be undone.', 'starter')); ?>');"><?php _e('Delete', 'starter'); ?></a>
            </div>
          </div>
        <?php } ?>

        <!-- SELLER -->
        <div class="al-panel al-seller">
          <div class="al-seller-head">
            <img class="al-seller-avatar" src="<?php echo starter_profile_picture(osc_item_user_id(), 'medium'); ?>" alt="" width="56" height="56" />
            <div>
              <div class="al-seller-label"><?php _e('Sold by', 'starter'); ?></div>
              <?php if (osc_item_user_id() <> 0) { ?>
                <a class="al-seller-name" href="<?php echo osc_user_public_profile_url(osc_item_user_id()); ?>"><?php echo osc_esc_html($seller_name); ?></a>
                <?php echo antique_seller_badge(osc_item_user_id()); ?>
              <?php } else { ?>
                <span class="al-seller-name"><?php echo osc_esc_html($seller_name); ?></span>
              <?php } ?>
              <div class="al-seller-sub">
                <?php
                  $sub = array();
                  if (!empty($item_user)) {
                    $sub[] = $item_user['b_company'] == 1 ? __('Dealer', 'starter') : __('Private seller', 'starter');
                    if (!empty($item_user['dt_reg_date'])) { $sub[] = sprintf(__('Member since %s', 'starter'), date('M Y', strtotime($item_user['dt_reg_date']))); }
                  } else {
                    $sub[] = __('Guest seller', 'starter');
                  }
                  echo osc_esc_html(implode(' · ', $sub));
                ?>
              </div>
              <?php if (osc_item_user_id() > 0) { echo antique_rating_line(osc_item_user_id()); } ?>
            </div>
          </div>

          <?php if (!$is_owner && !osc_item_is_expired()) { ?>
            <div class="al-seller-actions">
              <a class="al-btn al-btn-brass al-btn-block" href="#al-contact"><?php _e('Message seller', 'starter'); ?></a>
              <?php if ($phone_ok) { ?>
                <?php if ($phone_login) { ?>
                  <a class="al-btn al-btn-outline al-btn-block" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in to see phone number', 'starter'); ?></a>
                <?php } else { ?>
                  <button type="button" class="al-btn al-btn-outline al-btn-block al-reveal-phone" data-phone="<?php echo osc_esc_html($mobile); ?>" data-tel="<?php echo osc_esc_html($phone_digits); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
                    <span><?php _e('Show phone number', 'starter'); ?></span>
                  </button>
                <?php } ?>
              <?php } ?>
            </div>
          <?php } ?>

          <ul class="al-seller-links">
            <?php if (osc_item_user_id() <> 0) { ?>
              <li><a href="<?php echo osc_search_url(array('page' => 'search', 'seller_post' => osc_item_user_id())); ?>"><?php echo !empty($item_user['i_items']) ? sprintf(_n('See %d listing from this seller', 'See all %d listings from this seller', (int)$item_user['i_items'], 'starter'), (int)$item_user['i_items']) : __('More from this seller', 'starter'); ?></a></li>
            <?php } ?>
            <li><a href="<?php echo osc_item_send_friend_url(); ?>"><?php _e('Email this listing to a friend', 'starter'); ?></a></li>
          </ul>
          <?php if (!$is_owner && !osc_item_mark_disable()) { ?>
            <details class="al-report">
              <summary><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 22V4M4 4h13l-2 4 2 4H4"/></svg><?php _e('Report this listing', 'starter'); ?></summary>
              <p><?php _e('Tell us what is wrong. Reports are anonymous to the seller.', 'starter'); ?></p>
              <ul>
                <?php foreach (antique_report_reasons() as $al_r) { ?>
                  <li><a rel="nofollow" href="<?php echo call_user_func($al_r[1]); ?>"><?php echo osc_esc_html($al_r[0]); ?></a></li>
                <?php } ?>
              </ul>
            </details>
          <?php } ?>
          <?php osc_run_hook('item_contact'); ?>
        </div>

        <?php if (function_exists('sp_buttons')) { ?>
          <div class="al-panel"><?php echo sp_buttons(osc_item_id()); ?></div>
        <?php } ?>

        <?php osc_run_hook('item_sidebar_bottom'); ?>
      <?php } ?>
    </aside>

    <!-- DETAILS -->
    <div class="al-item-details">
      <section class="al-panel al-section-block">
        <h2 class="al-panel-title"><?php _e('Description', 'starter'); ?></h2>
        <div class="al-description"><?php echo osc_item_description(); ?></div>
        <?php osc_run_hook('item_description'); ?>
      </section>

      <?php
        $al_facts = antique_details_facts(osc_item_id());
        $al_details = antique_item_details(osc_item_id());
        if ($condition) { $al_facts[__('Condition', 'starter')] = $condition; }
        if ($transaction) { $al_facts[__('Transaction', 'starter')] = $transaction; }
        $al_facts[__('Category', 'starter')] = osc_item_category();
        if ($item_loc <> '') { $al_facts[__('Item location', 'starter')] = $item_loc; }
        if (osc_count_item_meta() >= 1) {
          while (osc_has_item_meta()) {
            if (osc_item_meta_value() != '') { $al_facts[osc_item_meta_name()] = strip_tags(osc_item_meta_value()); }
          }
        }
      ?>
      <section class="al-panel al-section-block">
        <h2 class="al-panel-title"><?php _e('Item details', 'starter'); ?></h2>
        <dl class="al-facts">
          <?php foreach ($al_facts as $k => $v) { ?>
            <div><dt><?php echo osc_esc_html($k); ?></dt><dd><?php echo osc_esc_html($v); ?></dd></div>
          <?php } ?>
        </dl>
        <?php if (trim((string)$al_details['s_provenance']) !== '') { ?>
          <div class="al-provenance">
            <h3><?php _e('Provenance and history', 'starter'); ?></h3>
            <p><?php echo nl2br(osc_esc_html($al_details['s_provenance'])); ?></p>
          </div>
        <?php } ?>
        <?php if ($item_loc <> '') { ?>
          <a class="al-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo urlencode($item_loc); ?>" target="_blank" rel="noopener"><?php _e('View location on Google Maps', 'starter'); ?> &rarr;</a>
        <?php } ?>
        <?php osc_run_hook('item_meta'); ?>
        <div id="plugin-details"><?php osc_run_hook('item_detail', osc_item()); ?></div>
      </section>

      <?php echo starter_banner('item_description'); ?>

      <?php if ($content_only == 0) { ?>
        <?php if (!$is_owner) { ?>
        <!-- CONTACT SELLER (not shown to the seller on their own listing) -->
        <section class="al-panel al-section-block" id="al-contact">
          <h2 class="al-panel-title"><?php _e('Ask the seller a question', 'starter'); ?></h2>
          <?php if (osc_item_is_expired()) { ?>
            <p class="al-muted"><?php _e('This listing has expired, so the seller can no longer be contacted.', 'starter'); ?></p>
          <?php } else if (osc_item_user_id() > 0 && osc_is_web_user_logged_in()) { ?>
            <?php echo antique_msg_compose(osc_item_id(), osc_item_user_id(), $seller_name); ?>
          <?php } else if (osc_item_user_id() > 0 || (osc_reg_user_can_contact() && !osc_is_web_user_logged_in())) { ?>
            <p class="al-muted"><?php _e('Sign in to message the seller. Your conversation stays on the site and your email address is never shown.', 'starter'); ?></p>
            <div class="al-inline-actions">
              <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_login_back_url(osc_item_url() . '#al-contact')); ?>"><?php _e('Sign in', 'starter'); ?></a>
              <a class="al-btn al-btn-outline" href="<?php echo osc_register_account_url(); ?>"><?php _e('Create an account', 'starter'); ?></a>
            </div>
          <?php } else { ?>
            <ul id="error_list"></ul>
            <?php ContactForm::js_validation(); ?>
            <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form" class="al-form"<?php if (osc_item_attachment()) { echo ' enctype="multipart/form-data"'; } ?>>
              <input type="hidden" name="action" value="contact_post" />
              <input type="hidden" name="page" value="item" />
              <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />
              <?php osc_prepare_user_info(); ?>
              <div class="al-form-row al-form-2">
                <label><span><?php _e('Your name', 'starter'); ?></span><?php ContactForm::your_name(); ?></label>
                <label><span><?php _e('Email', 'starter'); ?> <em>*</em></span><?php ContactForm::your_email(); ?></label>
              </div>
              <div class="al-form-row al-form-2">
                <label><span><?php _e('Phone (optional)', 'starter'); ?></span><?php ContactForm::your_phone_number(); ?></label>
              </div>
              <label class="al-form-full"><span><?php _e('Message', 'starter'); ?> <em>*</em></span><?php ContactForm::your_message(); ?></label>
              <?php if (osc_item_attachment()) { ?>
                <label class="al-form-full"><span><?php _e('Attachment', 'starter'); ?></span><?php ContactForm::your_attachment(); ?>
                  <small><?php echo sprintf(__('Allowed: %1$s. Max %2$s MB.', 'starter'), osc_allowed_extension(), round(osc_max_size_kb() / 1000, 1)); ?></small>
                </label>
              <?php } ?>
              <?php osc_run_hook('item_contact_form', osc_item_id()); ?>
              <?php osc_show_recaptcha(); ?>
              <button type="submit" class="al-btn al-btn-brass" id="al-item-message"><?php _e('Send message', 'starter'); ?></button>
            </form>
          <?php } ?>
        </section>
        <?php } ?>

        <?php if (osc_comments_enabled()) { ?>
          <section class="al-panel al-section-block" id="al-comments">
            <h2 class="al-panel-title"><?php _e('Questions & comments', 'starter'); ?></h2>
            <?php $al_has_comments = false; ?>
            <div class="al-comments">
              <?php while (osc_has_item_comments()) { $al_has_comments = true; ?>
                <article class="al-comment">
                  <img src="<?php echo starter_profile_picture(osc_comment_user_id(), 'small'); ?>" alt="" width="40" height="40" />
                  <div>
                    <div class="al-comment-head">
                      <strong><?php echo osc_comment_author_name() == '' ? __('Anonymous', 'starter') : osc_esc_html(osc_comment_author_name()); ?></strong>
                      <?php if (osc_comment_title() != '') { ?><span><?php echo osc_esc_html(osc_comment_title()); ?></span><?php } ?>
                    </div>
                    <div class="al-comment-body"><?php echo nl2br(osc_esc_html(osc_comment_body())); ?></div>
                    <?php if (osc_comment_user_id() && osc_comment_user_id() == osc_logged_user_id()) { ?>
                      <a rel="nofollow" class="al-text-link is-danger" href="<?php echo osc_delete_comment_url(); ?>"><?php _e('Delete', 'starter'); ?></a>
                    <?php } ?>
                  </div>
                </article>
              <?php } ?>
              <?php if (!$al_has_comments) { ?>
                <p class="al-muted"><?php _e('No questions yet.', 'starter'); ?></p>
              <?php } ?>
              <?php $al_cpages = $al_has_comments ? osc_comments_pagination() : ''; ?>
              <?php if (trim(strip_tags((string)$al_cpages)) !== '') { ?><nav class="al-pagination" aria-label="<?php echo osc_esc_html(__('Comment pages', 'starter')); ?>"><?php echo $al_cpages; ?></nav><?php } ?>
            </div>
            <?php if ((osc_reg_user_post_comments() && osc_is_web_user_logged_in()) || !osc_reg_user_post_comments()) { ?>
              <form action="<?php echo osc_base_url(true); ?>" method="post" name="comment_form" id="comment_form" class="al-form al-comment-form">
                <input type="hidden" name="action" value="add_comment" />
                <input type="hidden" name="page" value="item" />
                <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />
                <h3><?php echo $is_owner ? __('Reply to buyers', 'starter') : __('Ask a question', 'starter'); ?></h3>
                <?php if (osc_is_web_user_logged_in()) { ?>
                  <input type="hidden" name="authorName" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
                  <input type="hidden" name="authorEmail" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
                <?php } else { ?>
                  <div class="al-form-row al-form-2">
                    <label><span><?php _e('Your name', 'starter'); ?></span><input type="text" name="authorName" autocomplete="name" value="" /></label>
                    <label><span><?php _e('Email (not shown)', 'starter'); ?> <em>*</em></span><input type="email" name="authorEmail" required autocomplete="email" value="" /></label>
                  </div>
                <?php } ?>
                <label class="al-form-full"><span><?php _e('Your question or comment', 'starter'); ?> <em>*</em></span><textarea name="body" required rows="4" maxlength="2000"></textarea></label>
                <?php osc_show_recaptcha(); ?>
                <button type="submit" class="al-btn al-btn-ink"><?php _e('Post', 'starter'); ?></button>
              </form>
            <?php } else { ?>
              <p class="al-comment-signin"><a class="al-btn al-btn-outline" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in to ask a question', 'starter'); ?></a></p>
            <?php } ?>
          </section>
        <?php } ?>
        <?php osc_run_hook('item_comment'); ?>
      <?php } ?>
    </div>
  </div>

  <?php echo starter_banner('item_bottom'); ?>

  <?php
    // Similar listings: same category first, newest first, never this listing
    if ($content_only == 0) {
      $al_current = View::newInstance()->_get('item');
      $al_rel = Item::newInstance()->dao->query(sprintf(
        "SELECT pk_i_id FROM %st_item WHERE b_enabled = 1 AND b_active = 1 AND b_spam = 0 AND pk_i_id <> %d
         AND (dt_expiration IS NULL OR dt_expiration > NOW())
         ORDER BY (fk_i_category_id = %d) DESC, dt_pub_date DESC LIMIT 4",
        DB_TABLE_PREFIX, osc_item_id(), osc_item_category_id()
      ));
      $al_rel_items = array();
      if ($al_rel) {
        foreach ($al_rel->result() as $r) {
          $it = Item::newInstance()->findByPrimaryKey($r['pk_i_id']);
          if ($it) { $al_rel_items[] = $it; }
        }
      }
      if (count($al_rel_items) > 0) {
  ?>
    <section class="al-related">
      <div class="al-section-head">
        <h2><?php _e('You may also like', 'starter'); ?></h2>
        <a href="<?php echo osc_search_url(array('page' => 'search', 'sCategory' => osc_item_category_id())); ?>"><?php echo sprintf(__('More in %s', 'starter'), osc_esc_html(osc_item_category())); ?> &rarr;</a>
      </div>
      <div class="al-grid">
        <?php
          View::newInstance()->_exportVariableToView('items', $al_rel_items);
          while (osc_has_items()) { include __DIR__ . '/inc.al-card.php'; }
          View::newInstance()->_erase('items');
          View::newInstance()->_exportVariableToView('item', $al_current);
        ?>
      </div>
    </section>
  <?php } } ?>

  <?php osc_run_hook('item_bottom'); ?>
</div>

<?php if ($content_only == 0) { ?>
  <script>
    (function () {
      // Gallery: arrows, thumbnails and swipe
      var main = document.querySelector('#al-item .al-gallery-main');
      if (main) {
        var slides = main.querySelectorAll('.al-gallery-slide');
        var thumbs = document.querySelectorAll('#al-item .al-gallery-thumb');
        var counter = main.querySelector('.al-gallery-current');
        var current = 0;
        var show = function (i) {
          if (!slides.length) return;
          current = (i + slides.length) % slides.length;
          for (var k = 0; k < slides.length; k++) {
            slides[k].hidden = k !== current;
            slides[k].classList.toggle('is-active', k === current);
          }
          for (var t = 0; t < thumbs.length; t++) {
            thumbs[t].classList.toggle('is-active', t === current);
          }
          if (counter) counter.textContent = current + 1;
        };
        var prev = main.querySelector('.is-prev'), next = main.querySelector('.is-next');
        if (prev) prev.addEventListener('click', function () { show(current - 1); });
        if (next) next.addEventListener('click', function () { show(current + 1); });
        for (var t = 0; t < thumbs.length; t++) {
          thumbs[t].addEventListener('click', function () { show(+this.getAttribute('data-index')); });
        }
        var x0 = null;
        main.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        main.addEventListener('touchend', function (e) {
          if (x0 === null) return;
          var dx = e.changedTouches[0].clientX - x0;
          if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
          x0 = null;
        });
      }

      // Phone number: reveal on request, then call / WhatsApp
      var btn = document.querySelector('#al-item .al-reveal-phone');
      if (btn) {
        btn.addEventListener('click', function () {
          var phone = btn.getAttribute('data-phone'), tel = btn.getAttribute('data-tel');
          var wrap = document.createElement('div');
          wrap.className = 'al-phone-revealed';
          var call = document.createElement('a');
          call.className = 'al-btn al-btn-outline al-btn-block';
          call.href = 'tel:' + tel;
          call.textContent = '<?php echo osc_esc_js(__('Call', 'starter')); ?> ' + phone;
          wrap.appendChild(call);
          if (tel.charAt(0) === '+') {
            var wa = document.createElement('a');
            wa.className = 'al-btn al-btn-whatsapp al-btn-block';
            wa.href = 'https://wa.me/' + tel.replace('+', '');
            wa.target = '_blank';
            wa.rel = 'noopener';
            wa.textContent = 'WhatsApp';
            wrap.appendChild(wa);
          }
          btn.replaceWith(wrap);
        });
      }
    })();
  </script>
  <?php osc_current_web_theme_path('footer.php'); ?>
<?php } ?>
</body>
</html>
