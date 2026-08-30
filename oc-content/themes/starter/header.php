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


<div id="header-bar">
  <!-- VERIFIED UPDATED HEADER v2.0 -->
  <div class="inside">
    <?php osc_run_hook('header_top'); ?>

    <!-- 1. Logo Block (Left) -->
    <div class="middle-block logo">
      <a class="resp-logo" href="<?php echo osc_base_url(); ?>">
        <?php echo logo_header(); ?>
        <div class="loader"></div>
      </a>
    </div>

    <!-- 2. Navigation Links (Center) -->
    <div class="left-block">
      <!-- Home -->
      <a href="<?php echo osc_base_url(); ?>" class="le-btn home<?php if(osc_is_home_page()) { ?> active<?php } ?>">
        <i class="fa fa-home"></i>
        <span><?php _e('Home', 'starter'); ?></span>
      </a>

      <!-- Browse Ads -->
      <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="le-btn browse-ads<?php if(osc_is_search_page()) { ?> active<?php } ?>">
        <i class="fa fa-th-large"></i>
        <span><?php _e('Browse Ads', 'starter'); ?></span>
      </a>

      <!-- Contact Us -->
      <a href="<?php echo osc_contact_url(); ?>" class="le-btn contact-us<?php if($loc == 'contact') { ?> active<?php } ?>">
        <i class="fa fa-envelope-o"></i>
        <span><?php _e('Contact Us', 'starter'); ?></span>
      </a>

      <!-- Messages -->
      <?php 
        $user_dash = function_exists('osc_user_dashboard_url') ? osc_user_dashboard_url() : osc_user_list_items_url();
        $im_url = osc_is_web_user_logged_in() ? (function_exists('osc_route_url') && function_exists('osc_route_name_exists') && osc_route_name_exists('im-threads') ? osc_route_url('im-threads') : $user_dash) : osc_user_login_url();
      ?>
      <a class="le-btn messages<?php if($sec == 'im-threads' || (isset($_GET['file']) && $_GET['file'] == 'instant_messenger/threads.php')) { ?> active<?php } ?>" href="<?php echo $im_url; ?>">
        <i class="fa fa-commenting-o"></i> 
        <span><?php _e('Messages', 'starter'); ?></span> 
        <?php if($message_count > 0) { ?>
          <em class="counter"><?php echo $message_count; ?></em>
        <?php } ?>
      </a>

      <!-- Cart -->
      <?php if(function_exists('osp_install') && class_exists('ModelOSP') && method_exists('ModelOSP', 'getCart')) { ?>
        <?php
          $cart_count = 0;
          if(osc_is_web_user_logged_in()) {
            try {
              $cart = ModelOSP::newInstance()->getCart(osc_logged_user_id());
              $cart_count = is_string($cart) ? count(array_filter(explode('|', $cart))) : (is_array($cart) ? count($cart) : 0);
            } catch(Throwable $e) {
              $cart_count = 0;
            }
          }
        ?>
        <a class="le-btn cart" href="<?php echo (function_exists('osc_route_url') && function_exists('osc_route_name_exists') && osc_route_name_exists('osp-cart')) ? osc_route_url('osp-cart') : '#'; ?>">
          <i class="fa fa-shopping-basket"></i> 
          <span><?php _e('Cart', 'starter'); ?></span> 
          <?php if($cart_count > 0) { ?>
            <em class="counter"><?php echo $cart_count; ?></em>
          <?php } ?>
        </a>
      <?php } ?>

      <!-- Currency selector -->
      <?php if(function_exists('starter_header_currency_selector')) { echo starter_header_currency_selector(); } ?>

      <!-- Language selector -->
      <div class="language not767">
        <?php if ( osc_count_web_enabled_locales() > 1) { ?>
          <?php $current_locale = function_exists('mb_get_current_user_locale') ? mb_get_current_user_locale() : array('s_short_name' => strtoupper(substr(osc_locale_code(), 0, 2)), 'pk_c_code' => osc_locale_code()); ?>
          <?php osc_goto_first_locale(); ?>
          <div id="lang-open-box">
            <span id="lang_open">
              <span class="le-btn">
                <i class="fa fa-globe"></i>
                <span class="non-resp"><?php echo $current_locale['s_short_name']; ?></span>
                <span class="resp"><?php echo strtoupper(substr($current_locale['pk_c_code'], 0, 2)); ?></span>
                <i class="fa fa-angle-down arrow"></i>
              </span>
            </span>
            <div id="lang-wrap" class="mb-tool-wrap">
              <div class="mb-tool-cover">
                <ul id="lang-box">
                  <?php $i = 0 ;  ?>
                  <?php while ( osc_has_web_enabled_locales() ) { ?>
                    <li <?php if( $i == 0 ) { echo "class='first'" ; } ?> title="<?php echo osc_esc_html(osc_locale_field("s_description")); ?>"><a id="<?php echo osc_locale_code() ; ?>" href="<?php echo osc_change_language_url ( osc_locale_code() ) ; ?>"><span><?php echo osc_locale_name(); ?></span><?php if (osc_locale_code() == $current_locale['pk_c_code']) { ?><i class="fa fa-check"></i><?php } ?></a></li>
                    <?php $i++ ; ?>
                  <?php } ?>
                </ul>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- 3. Actions Block (Right) -->
    <div class="right-block">
      <!-- Search Input field -->
      <div class="search-top">
        <form action="<?php echo osc_base_url(true); ?>" method="get" class="nocsrf spin">
          <input type="hidden" name="page" value="search" />
          <input type="text" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php _e('Search antiques...', 'starter'); ?>" autocomplete="off" />
          <button type="submit"><i class="fa fa-search"></i></button>
        </form>
      </div>

      <!-- User avatar circle or guest login -->
      <div class="user-header-block">
        <?php if(osc_is_web_user_logged_in()) { ?>
          <a class="picture user-logged-btn tr1 has-menu" href="<?php echo osc_user_dashboard_url(); ?>">
            <img src="<?php echo starter_profile_picture(osc_logged_user_id(), 'small', true); ?>" alt="<?php echo osc_esc_html(__('User picture', 'starter')); ?>"/>
            <span class="uname-text"><?php echo osc_esc_html(osc_logged_user_name()); ?></span>
            <i class="fa fa-angle-down"></i>
          </a>
          <div class="user_account user-header" style="display:none;">
            <div id="sidebar">
              <?php echo starter_user_menu(); ?>
            </div>
          </div>
        <?php } else { ?>
          <a class="le-login-link" href="<?php echo osc_user_login_url(); ?>">
            <i class="fa fa-user-o"></i>
            <span><?php _e('Login', 'starter'); ?></span>
          </a>
        <?php } ?>
      </div>

      <!-- CTA Publish button -->
      <a class="publish round8 tr1" href="<?php echo osc_item_post_url(); ?>">
        <i class="fa fa-plus"></i>
        <span><?php _e('Post Your Ad', 'starter'); ?></span>
      </a>
    </div>

    <?php osc_run_hook('header_bottom'); ?>
  </div>
</div>

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
  // SHOW SEARCH BAR AND CATEGORY LIST ON HOME & SEARCH PAGE
  if(osc_is_home_page()) {
    osc_current_web_theme_path('inc.search.php');
  }

  if(osc_is_home_page() or osc_is_search_page()) {
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