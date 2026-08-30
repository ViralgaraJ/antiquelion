<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()) ; ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>

<body id="body-home">
  <?php osc_current_web_theme_path('header.php'); ?>
  <?php osc_run_hook('home_top'); ?>
  <?php echo starter_banner('home_top'); ?>

  <div class="homepage-modern-wrapper">
    <!-- Hero Section with Search Panel -->
    <section class="modern-hero">
      <?php if(function_exists('osc_slider')) { osc_slider(); } ?>
      <div class="hero-content">
        <h1 class="hero-title"><?php _e("Find the Finest Antiques & Collectibles Worldwide", 'starter'); ?></h1>
        <p class="hero-subtitle"><?php _e("Explore traditional artifacts, vintage collectibles, retro home decor, old books, and rare antique furniture from around the world.", 'starter'); ?></p>
        
        <!-- Search Card Panel -->
        <div class="hero-search-panel">
          <form action="<?php echo osc_base_url(true); ?>" method="get" class="nocsrf spin">
            <input type="hidden" name="page" value="search" />
            <input type="hidden" name="sCountry" id="sCountry" value="<?php echo osc_esc_html(isset($_GET['sCountry']) ? $_GET['sCountry'] : ''); ?>"/>
            <input type="hidden" name="sRegion" id="sRegion" value="<?php echo osc_esc_html(isset($_GET['sRegion']) ? $_GET['sRegion'] : ''); ?>"/>
            <input type="hidden" name="sCity" id="sCity" value="<?php echo osc_esc_html(isset($_GET['sCity']) ? $_GET['sCity'] : ''); ?>"/>
            
            <div class="search-field query-field">
              <i class="fa fa-search"></i>
              <input type="text" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php _e("What are you looking for today?", 'starter'); ?>" autocomplete="off" />
            </div>
            
            <div class="search-field location-field">
              <i class="fa fa-map-marker"></i>
              <div class="modern-location-trigger" onclick="openLocationModal()">
                <?php
                  $p_term = isset($_GET['term']) ? $_GET['term'] : '';
                  $p_country = isset($_GET['sCountry']) ? $_GET['sCountry'] : '';
                  $p_region = isset($_GET['sRegion']) ? $_GET['sRegion'] : '';
                  $p_city = isset($_GET['sCity']) ? $_GET['sCity'] : '';
                  $term_val = starter_get_term($p_term, $p_country, $p_region, $p_city);
                  if (empty($term_val) || $term_val == 'All Sri Lanka') {
                    $term_val = __('Worldwide', 'starter');
                  }
                ?>
                <span class="selected-location-text"><?php echo osc_esc_html($term_val); ?></span>
                <i class="fa fa-caret-down"></i>
              </div>
              <input type="hidden" name="term" id="term" value="<?php echo osc_esc_html($term_val); ?>" />
            </div>
            
            <button type="submit" class="hero-search-btn">
              <i class="fa fa-search"></i>
              <span><?php _e("Search", 'starter'); ?></span>
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Categories & Locations Hub -->
    <section class="hub-section">
      <div class="hub-container">
        
        <!-- Categories Panel (Left) -->
        <div class="categories-panel">
          <h2 class="panel-title"><i class="fa fa-th-large"></i> <?php _e("Browse by Category", 'starter'); ?></h2>
          <div class="category-grid">
            <?php osc_goto_first_category(); ?>
            <?php while(osc_has_categories()) { 
               $cat_id = osc_category_id();
               $cat_name = osc_category_name();
               $cat_count = osc_category_total_items();
               
               // Match premium icon and gradient class to category
               $icon = 'fa-diamond';
               $gradient_class = 'gradient-other';
               if (stripos($cat_name, 'Artifact') !== false) {
                   $icon = 'fa-university';
                   $gradient_class = 'gradient-artifacts';
               } elseif (stripos($cat_name, 'Decor') !== false || stripos($cat_name, 'Home') !== false) {
                   $icon = 'fa-paint-brush';
                   $gradient_class = 'gradient-decor';
               } elseif (stripos($cat_name, 'Book') !== false || stripos($cat_name, 'Manuscript') !== false) {
                   $icon = 'fa-book';
                   $gradient_class = 'gradient-books';
               } elseif (stripos($cat_name, 'Collect') !== false) {
                   $icon = 'fa-diamond';
                   $gradient_class = 'gradient-collectibles';
               } elseif (stripos($cat_name, 'Furniture') !== false) {
                   $icon = 'fa-bed';
                   $gradient_class = 'gradient-furniture';
               }
            ?>
               <a href="<?php echo osc_search_url(array('sCategory' => $cat_id)); ?>" class="category-card">
                 <div class="category-icon-wrapper <?php echo $gradient_class; ?>">
                   <i class="fa <?php echo $icon; ?>"></i>
                 </div>
                 <div class="category-info">
                   <span class="category-name"><?php echo $cat_name; ?></span>
                   <span class="category-count"><?php echo $cat_count; ?> <?php _e("listings", 'starter'); ?></span>
                 </div>
                 <i class="fa fa-chevron-right arrow-icon"></i>
               </a>
            <?php } ?>
          </div>
        </div>

        <!-- Locations Panel (Right) -->
        <div class="locations-panel">
          <h2 class="panel-title"><i class="fa fa-map-marker"></i> <?php _e("Browse by Location", 'starter'); ?></h2>
          <div class="location-links">
            <?php
              $regions = array();
              if (class_exists('RegionStats')) {
                try {
                  $regions = RegionStats::newInstance()->listRegionsLimit('LK', 'i_num_items DESC, r.s_name ASC', 15);
                } catch(Throwable $e) {
                  $regions = array();
                }
              }
              if (is_array($regions) && count($regions) > 0) {
                foreach($regions as $r) {
                  $r_name = osc_location_native_name_selector($r, 's_name');
                  $r_count = $r['i_num_items'];
                  $r_url = osc_search_url(array('sRegion' => $r_name));
                  ?>
                  <a href="<?php echo $r_url; ?>" class="location-chip">
                    <span class="loc-name"><?php echo $r_name; ?></span>
                    <span class="loc-count"><?php echo $r_count; ?></span>
                  </a>
                  <?php
                }
              } else {
                // Fallback districts of Sri Lanka
                $popular_districts = array("Colombo", "Gampaha", "Kandy", "Kurunegala", "Galle", "Kalutara", "Matara", "Jaffna", "Badulla", "Anuradhapura");
                foreach($popular_districts as $pd) {
                  ?>
                  <a href="<?php echo osc_search_url(array('sRegion' => $pd)); ?>" class="location-chip">
                    <span class="loc-name"><?php echo $pd; ?></span>
                    <span class="loc-count"><i class="fa fa-angle-right"></i></span>
                  </a>
                  <?php
                }
              }
            ?>
            <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="all-locations-btn">
              <?php _e("View All Locations", 'starter'); ?> <i class="fa fa-arrow-right"></i>
            </a>
          </div>
        </div>
        
      </div>
    </section>


    <!-- Premium listings block if enabled -->
    <?php osc_get_premiums(osc_get_preference('premium_home_count', 'starter_theme')); ?>
    <?php if( osc_count_premiums() > 0 && osc_get_preference('premium_home', 'starter_theme') == 1) { ?>
      <div class="home-container hc-premiums">
        <div class="inner">
          <div class="section-header">
            <h2 class="section-title"><?php _e('Premium Listings', 'starter'); ?></h2>
            <div class="title-underline"></div>
          </div>
          <div id="latest" class="white prem">
            <div class="block">
              <div class="wrap">
                <?php $c = 1; ?>
                <?php while( osc_has_premiums() ) { ?>
                  <?php starter_draw_item($c, 'gallery', true); ?>
                  <?php $c++; ?>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
    <?php osc_run_hook('home_premium'); ?>

    <!-- Latest Listings Section -->
    <section class="latest-listings-section">
      <div class="inner">
        <div class="section-header">
          <h2 class="section-title"><?php _e("Latest Listings", 'starter'); ?></h2>
          <div class="title-underline"></div>
        </div>

        <?php View::newInstance()->_exportVariableToView('latestItems', starter_random_items()); ?>

        <?php if( osc_count_latest_items() > 0) { ?>
          <div class="block">
            <div class="wrap">
              <?php $c = 1; ?>
              <?php while( osc_has_latest_items() ) { ?>
                <?php starter_draw_item($c, 'gallery'); ?>
                <?php $c++; ?>
              <?php } ?>
            </div>
          </div>
        
          <div class="home-see-all non-resp">
            <a href="<?php echo osc_search_url(array('page' => 'search'));?>"><?php _e('See all offers', 'starter'); ?> <i class="fa fa-chevron-right"></i></a>
          </div>

          <span class="show-more-latest"><i class="fa fa-ellipsis-h"></i></span>

        <?php } else { ?>
          <div class="empty" style="text-align: center; padding: 40px; color: var(--slate-700);"><?php _e('No latest listings found', 'starter'); ?></div>
        <?php } ?>

        <?php View::newInstance()->_erase('items') ; ?>
      </div>
    </section>
    <?php osc_run_hook('home_latest'); ?>

    <!-- Trust & Security Badges Section -->
    <section class="trust-section">
      <div class="trust-container">
        <div class="trust-card">
          <div class="trust-icon-box">
            <i class="fa fa-university"></i>
          </div>
          <h3><?php _e("Authentic Sri Lankan Antiquities", 'starter'); ?></h3>
          <p><?php _e("We bring together the country's most reputable antique dealers and private collectors.", 'starter'); ?></p>
        </div>
        <div class="trust-card">
          <div class="trust-icon-box">
            <i class="fa fa-shield"></i>
          </div>
          <h3><?php _e("Safe & Reliable Platform", 'starter'); ?></h3>
          <p><?php _e("Every listing goes through moderation to ensure high-quality, genuine product descriptions.", 'starter'); ?></p>
        </div>
        <div class="trust-card">
          <div class="trust-icon-box">
            <i class="fa fa-handshake-o"></i>
          </div>
          <h3><?php _e("Direct Dealer Contact", 'starter'); ?></h3>
          <p><?php _e("Zero middleman commissions. Connect directly with sellers via call, chat, or WhatsApp.", 'starter'); ?></p>
        </div>
      </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="promo-section">
      <div class="promo-banner">
        <div class="promo-text">
          <h2><?php _e("Have valuable antiques to sell?", 'starter'); ?></h2>
          <p><?php _e("Post your listing for free today and reach thousands of passionate buyers in Sri Lanka.", 'starter'); ?></p>
        </div>
        <a href="<?php echo osc_item_post_url(); ?>" class="promo-btn"><?php _e("Post Your Ad Now", 'starter'); ?></a>
      </div>
    </section>

  </div>

  <?php echo starter_banner('home_bottom'); ?>
  <?php osc_run_hook('home_bottom'); ?>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>