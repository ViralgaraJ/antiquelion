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
        <a href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('Live auctions', 'starter'); ?></a>
        <a class="al-utility-sell" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell on AntiqueLion', 'starter'); ?></a>
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
        <?php if (osc_is_web_user_logged_in()) { ?>
          <div class="al-account">
            <button type="button" class="al-account-btn" aria-haspopup="true" aria-expanded="false">
              <img src="<?php echo starter_profile_picture(osc_logged_user_id(), 'small', true); ?>" alt="" width="28" height="28" />
              <span class="al-account-name"><?php echo osc_esc_html(osc_logged_user_name()); ?></span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
            </button>
            <div class="al-account-menu" hidden>
              <?php echo starter_user_menu(); ?>
            </div>
          </div>
        <?php } else { ?>
          <a class="al-signin" href="<?php echo osc_user_login_url(); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
            <span><?php _e('Sign in', 'starter'); ?></span>
          </a>
          <a class="al-register" href="<?php echo osc_register_account_url(); ?>"><?php _e('Register', 'starter'); ?></a>
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
    <span><?php _e('Post Ad', 'starter'); ?></span>
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
    <span><?php echo osc_is_web_user_logged_in() ? __('Account', 'starter') : __('Login', 'starter'); ?></span>
  </a>
</div>

<?php osc_run_hook('header_after'); ?>


<div id="header-line" data-loc="<?php echo $loc; ?>" data-sec="<?php echo $sec; ?>">
  <div class="inside">
    <h1>
      <?php 
        if(osc_is_home_page()) {
          echo osc_page_title(); 
        } else if(osc_is_ad_page()) {
          echo '<span>' . osc_item_title() . '</span>';


          $item_extra = starter_item_extra( osc_item_id() );

          if($item_extra['i_sold'] == 1) {
            echo '<div class="elem sold">' . __('Sold', 'starter') . '</div>';
          } else if($item_extra['i_sold'] == 2) { 
            echo '<div class="elem reserved">' . __('Reserved', 'starter') . '</div>';
          }

          if(osc_item_is_premium()) {
            echo '<div class="elem premium">' . __('Premium', 'starter') . '</div>';
          }

          if(!in_array(osc_item_category_id(), starter_extra_fields_hide())) {
            if(starter_condition_name($item_extra['i_condition'])) {
              echo '<div class="elem condition">' . __('Condition', 'starter') . ': <span>' . starter_condition_name($item_extra['i_condition']) . '</span></div>';
            }

            if(starter_condition_name($item_extra['i_transaction'])) {
              echo '<div class="elem transaction">' . __('Transaction', 'starter') . ': <span>' . starter_transaction_name($item_extra['i_transaction']) . '</span></div>';
            }
          }

        } else if(osc_is_search_page()) {
          $cat_id = osc_search_category_id();
          $cat_id = isset($cat_id[0]) ? $cat_id[0] : '';

          $cat_full = Category::newInstance()->findByPrimaryKey($cat_id);

          $cat = @$cat_full['s_name'];

          $entries = array_filter(array_map('ucfirst', array($cat, osc_search_region(), osc_search_city())));

          if(count($entries) > 0) {
            echo implode(' - ', $entries);
          } else {
            _e('Search', 'starter');
          }

          echo ' - ' . osc_search_total_items() . ' ' . (osc_search_total_items() == 1 ? __('result', 'starter') : __('results', 'starter'));

        } else if ($loc == 'register') {
          _e('Authenticate', 'starter');

        } else if ($loc == 'login' && $sec == 'recover') {
          _e('Recover password', 'starter');

        } else if ($loc == 'item' && $sec == 'item_add') {
          _e('Publish new listing', 'starter');

        } else if ($loc == 'item' && $sec == 'item_edit') {
          _e('Edit your listing', 'starter');

        } else if ($loc == 'user' && $sec == 'pub_profile') {
          echo sprintf(__('%s\'s profile', 'starter'), osc_user_name());

        } else if ($loc == 'user' && $sec == 'items') {
          _e('User items', 'starter');

        } else if ($loc == 'user' && $sec == 'alerts') {
          _e('User alerts', 'starter');

        } else if ($loc == 'user' && $sec == 'profile') {
          _e('User profile', 'starter');

        }
      ?>
    </h1>
  </div>
</div>


<?php
  // CATEGORY SUB-NAV ON SEARCH PAGE (home has its own category section in main.php)
  if(osc_is_search_page()) {
    osc_current_web_theme_path('inc.category.php');
  } else if(osc_is_ad_page()) {
    osc_current_web_theme_path('inc.item.php');
  }

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