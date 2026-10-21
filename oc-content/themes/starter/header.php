<?php 
  osc_goto_first_locale();

  $message_count = 0;
  if(osc_is_web_user_logged_in() && class_exists('ModelIM') && method_exists('ModelIM', 'countMessagesByUserId')) {
    try {
      $im_res = ModelIM::newInstance()->countMessagesByUserId( osc_logged_user_id() );
      $message_count = isset($im_res['i_count']) ? (int)$im_res['i_count'] : 0;
    } catch (Throwable $e) {
      $message_count = 0;
    }
  }

  $loc = function_exists('osc_get_osclass_location') ? osc_get_osclass_location() : (isset($_GET['page']) ? $_GET['page'] : '');
  $sec = function_exists('osc_get_osclass_section') ? osc_get_osclass_section() : (isset($_GET['section']) ? $_GET['section'] : '');
?>


<?php
  $al_country = antique_visitor_country();
  $al_categories = osc_get_categories();
  $al_search_cat = osc_search_category_id();
  $al_search_cat = isset($al_search_cat[0]) ? (int)$al_search_cat[0] : 0;
  $al_auctions_active = (osc_is_search_page() && Params::getParam('sAuction') == '1');
?>
<header id="al-header">
  <?php osc_run_hook('header_top'); ?>

  <div class="al-utility">
    <div class="al-wrap">
      <div class="al-utility-left">
        <span class="al-ship-to">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"></path></svg>
          <?php if ($al_country['code'] !== '') { ?>
            <?php _e('Ship to', 'starter'); ?>: <strong><?php echo osc_esc_html($al_country['name']); ?></strong>
          <?php } else { ?>
            <?php _e('Shipping worldwide', 'starter'); ?>
          <?php } ?>
        </span>
        <?php echo starter_header_currency_selector(); ?>
      </div>
      <nav class="al-utility-right" aria-label="<?php echo osc_esc_html(__('Help', 'starter')); ?>">
        <a href="<?php echo osc_contact_url(); ?>"><?php _e('Help & contact', 'starter'); ?></a>
      </nav>
    </div>
  </div>

  <div class="al-main">
    <div class="al-wrap">
      <a class="al-logo resp-logo" href="<?php echo osc_base_url(); ?>" aria-label="<?php echo osc_esc_html(osc_page_title()); ?> - <?php echo osc_esc_html(__('Home', 'starter')); ?>">
        <?php echo logo_header(); ?>
      </a>

      <form class="al-search nocsrf" action="<?php echo osc_base_url(true); ?>" method="get" role="search">
        <input type="hidden" name="page" value="search" />
        <label class="al-search-cat">
          <span class="al-sr-only"><?php _e('Category', 'starter'); ?></span>
          <select name="sCategory">
            <option value=""><?php _e('All categories', 'starter'); ?></option>
            <?php foreach ($al_categories as $c) { ?>
              <option value="<?php echo (int)$c['pk_i_id']; ?>"<?php echo $al_search_cat == $c['pk_i_id'] ? ' selected' : ''; ?>><?php echo osc_esc_html($c['s_name']); ?></option>
            <?php } ?>
          </select>
        </label>
        <label class="al-search-input">
          <span class="al-sr-only"><?php _e('Search antiques', 'starter'); ?></span>
          <input type="search" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php echo osc_esc_html(__('Search furniture, clocks, manuscripts…', 'starter')); ?>" autocomplete="off" />
        </label>
        <button type="submit" class="al-search-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
          <span><?php _e('Search', 'starter'); ?></span>
        </button>
      </form>

      <div class="al-actions">
        <?php $al_watch_total = count(antique_watch_ids()); ?>
        <a class="al-watch-link" href="<?php echo osc_is_web_user_logged_in() ? antique_watchlist_url() : osc_user_login_url(); ?>" aria-label="<?php echo osc_esc_html(__('Watchlist', 'starter')); ?>" title="<?php echo osc_esc_html(__('Watchlist', 'starter')); ?>">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.4C1.5 7.9 3.6 4.5 7.2 4.5c2 0 3.6 1.1 4.8 2.8 1.2-1.7 2.8-2.8 4.8-2.8 3.6 0 5.7 3.4 4.5 6.6-1.8 4.8-9.3 9.4-9.3 9.4z"/></svg>
          <span class="al-watch-badge" data-al-watch-total<?php echo $al_watch_total ? '' : ' hidden'; ?>><?php echo $al_watch_total; ?></span>
        </a>
        <?php if (osc_is_web_user_logged_in()) { ?>
          <div class="al-account">
            <button type="button" class="al-account-btn" aria-haspopup="true" aria-expanded="false">
              <img src="<?php echo starter_profile_picture(osc_logged_user_id(), 'small', true); ?>" alt="" width="28" height="28" />
              <span class="al-account-name"><?php echo osc_esc_html(osc_logged_user_name()); ?></span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
            </button>
            <div class="al-account-menu" hidden>
              <?php echo antique_account_nav(true); ?>
            </div>
          </div>
        <?php } else { ?>
          <a class="al-signin" href="<?php echo osc_user_login_url(); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
            <span><?php _e('Sign in', 'starter'); ?></span>
          </a>
        <?php } ?>
        <a class="al-sell" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
      </div>
    </div>
  </div>

  <nav class="al-cats" aria-label="<?php echo osc_esc_html(__('Categories', 'starter')); ?>">
    <div class="al-wrap">
      <a href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>" class="al-cat-auctions<?php echo $al_auctions_active ? ' active' : ''; ?>"<?php echo $al_auctions_active ? ' aria-current="page"' : ''; ?>>
        <span class="al-live-dot" aria-hidden="true"></span><?php _e('Live auctions', 'starter'); ?>
      </a>
      <?php foreach ($al_categories as $c) { $active = (!$al_auctions_active && $al_search_cat == $c['pk_i_id']); ?>
        <a href="<?php echo osc_search_url(array('page' => 'search', 'sCategory' => $c['pk_i_id'])); ?>"<?php echo $active ? ' class="active" aria-current="page"' : ''; ?>><?php echo osc_esc_html($c['s_name']); ?></a>
      <?php } ?>
    </div>
  </nav>

  <?php osc_run_hook('header_bottom'); ?>
</header>

<script>
  (function() {
    var acc = document.querySelector('#al-header .al-account');
    if (!acc) return;
    var btn = acc.querySelector('.al-account-btn'), menu = acc.querySelector('.al-account-menu');
    function setOpen(open) { menu.hidden = !open; btn.setAttribute('aria-expanded', open); }
    btn.addEventListener('click', function(e) { e.stopPropagation(); setOpen(menu.hidden); });
    document.addEventListener('click', function(e) { if (!acc.contains(e.target)) setOpen(false); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') setOpen(false); });
  })();
</script>

<!-- Enterprise Mobile App Bottom Dock (Mobile only) -->
<div class="mobile-bottom-dock is767">
  <a href="<?php echo osc_base_url(); ?>" class="dock-item<?php if(osc_is_home_page()) { ?> active<?php } ?>">
    <i class="fa fa-home"></i>
    <span><?php _e('Home', 'starter'); ?></span>
  </a>
  <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="dock-item<?php if(osc_is_search_page()) { ?> active<?php } ?>">
    <i class="fa fa-th-large"></i>
    <span><?php _e('Browse', 'starter'); ?></span>
  </a>
  <a href="<?php echo osc_item_post_url(); ?>" class="dock-item post-cta">
    <div class="cta-circle">
      <i class="fa fa-plus"></i>
    </div>
    <span><?php _e('Sell', 'starter'); ?></span>
  </a>
  <?php if(function_exists('im_messages') && function_exists('osc_route_url') && osc_is_web_user_logged_in()) { ?>
    <a href="<?php echo (function_exists('osc_route_name_exists') && osc_route_name_exists('im-threads')) ? osc_route_url('im-threads') : osc_user_dashboard_url(); ?>" class="dock-item">
      <i class="fa fa-commenting-o"></i>
      <span><?php _e('Messages', 'starter'); ?></span>
      <?php if($message_count > 0) { ?><em class="dock-badge"><?php echo $message_count; ?></em><?php } ?>
    </a>
  <?php } else { ?>
    <a href="<?php echo osc_contact_url(); ?>" class="dock-item<?php if($loc == 'contact') { ?> active<?php } ?>">
      <i class="fa fa-envelope-o"></i>
      <span><?php _e('Contact', 'starter'); ?></span>
    </a>
  <?php } ?>
  <a href="<?php echo osc_is_web_user_logged_in() ? osc_user_dashboard_url() : osc_user_login_url(); ?>" class="dock-item<?php if($loc == 'user' || $loc == 'login') { ?> active<?php } ?>">
    <i class="fa fa-user-circle-o"></i>
    <span><?php echo osc_is_web_user_logged_in() ? __('Account', 'starter') : __('Sign in', 'starter'); ?></span>
  </a>
</div>

<?php osc_run_hook('header_after'); ?>


<?php
  // Page title band. Home and listing pages render their own headings.
  $al_title = '';
  $al_sub = '';
  if (osc_is_search_page()) {
    $cat_id = osc_search_category_id();
    $cat_full = isset($cat_id[0]) ? Category::newInstance()->findByPrimaryKey($cat_id[0]) : null;
    if (Params::getParam('sAuction') == '1') {
      $al_title = __('Live auctions', 'starter');
    } else if (osc_search_pattern() != '') {
      $al_title = sprintf(__('Results for "%s"', 'starter'), osc_search_pattern());
    } else if (!empty($cat_full['s_name'])) {
      $al_title = $cat_full['s_name'];
    } else if (Params::getParam('seller_post') != '') {
      $al_seller = User::newInstance()->findByPrimaryKey((int)Params::getParam('seller_post'));
      $al_title = !empty($al_seller['s_name']) ? sprintf(__('Listings from %s', 'starter'), $al_seller['s_name']) : __('Seller listings', 'starter');
    } else {
      $al_title = __('All listings', 'starter');
    }
    $al_where = array_filter(array(osc_search_city(), osc_search_region(), osc_search_country()));
    $al_sub = sprintf(_n('%d result', '%d results', osc_search_total_items(), 'starter'), osc_search_total_items()) . (count($al_where) ? ' · ' . implode(', ', $al_where) : '');
  } else if ($loc == 'register' || $loc == 'login') {
    $al_title = '';   // the sign-in / register / reset cards carry their own heading
  } else if ($loc == 'item' && $sec == 'item_add') {
    $al_title = __('Sell an item', 'starter');
    $al_sub = __('Listing is free. Add clear photos and an honest description to sell faster.', 'starter');
  } else if ($loc == 'item' && $sec == 'item_edit') {
    $al_title = __('Edit your listing', 'starter');
  } else if ($loc == 'item' && $sec == 'send_friend') {
    $al_title = __('Share this listing', 'starter');
  } else if ($loc == 'user' && $sec == 'pub_profile') {
    $al_title = '';   // the profile card shows the seller's name
  } else if ($loc == 'user' && ($sec == 'items' || $sec == 'dashboard')) {
    $al_title = '';   // account pages carry their own heading
  } else if ($loc == 'user' && $sec == 'alerts') {
    $al_title = '';
  } else if ($loc == 'user' && in_array($sec, array('profile', 'change_email', 'change_password', 'change_username'))) {
    $al_title = '';
  } else if ($loc == 'contact') {
    $al_title = __('Help & contact', 'starter');
    $al_sub = __('Questions about buying, selling or your account? Send us a message and we will reply by email.', 'starter');
  } else if ($loc == 'custom' && function_exists('antique_info_pages') && isset(antique_info_pages()[Params::getParam('route')])) {
    $al_info = antique_info_pages()[Params::getParam('route')];
    $al_title = $al_info['title'];
    $al_sub = $al_info['lead'];
  } else if ($loc == 'page') {
    $al_title = osc_static_page_title();
  } else if ($loc == 'error' || $loc == '404') {
    $al_title = '';
  }
?>
<?php if ($al_title !== '' && !osc_is_home_page() && !osc_is_ad_page()) { ?>
<div id="header-line" class="al-page-head" data-loc="<?php echo osc_esc_html($loc); ?>" data-sec="<?php echo osc_esc_html($sec); ?>">
  <div class="al-wrap">
    <h1><?php echo osc_esc_html($al_title); ?></h1>
    <?php if ($al_sub !== '') { ?><p><?php echo osc_esc_html($al_sub); ?></p><?php } ?>
  </div>
</div>
<?php } ?>


<?php
  // GET CURRENT POSITION
  $position = array(osc_get_osclass_location(), osc_get_osclass_section());
  $position = array_filter($position);
  $position = implode('-', $position);
?>

<div class="container-outer <?php echo $position; ?>">


<?php if(!osc_is_home_page()) { ?>
  <div class="container">
<?php } ?>

<?php if ( OSC_DEBUG || OSC_DEBUG_DB ) { ?>
  <div id="debug-mode" class="noselect"><?php _e('You have enabled DEBUG MODE, autocomplete for locations and items will not work! Disable it in your config.php.', 'veronka'); ?></div>
<?php } ?>


<?php if(function_exists('scrolltop')) { scrolltop(); } ?>


<div class="clear"></div>


<div class="flash-wrap">
  <?php osc_show_flash_message(); ?>
</div>


<?php View::newInstance()->_erase('countries'); ?>
<?php View::newInstance()->_erase('regions'); ?>
<?php View::newInstance()->_erase('cities'); ?>	
<?php osc_current_web_theme_path('inc.location-modal.php'); ?>