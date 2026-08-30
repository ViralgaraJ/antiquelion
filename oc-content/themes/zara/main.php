<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()) ; ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>

<body id="body-home">
  <?php osc_current_web_theme_path('header.php'); ?>
  <?php osc_run_hook('home_top'); ?>
  <?php echo zara_banner('home_top'); ?>

  <?php 
    // Get positioning
    if(zara_current('zc_home_sort') <> '' && zara_current('zc_home_sort') <> '1') {
      $zc_position_home = explode(',', zara_current('zc_home_sort')); 
    } else {
      $zc_position_home = array(1,2,3,4,5); 
    }
  ?>


  <?php if (zara_current('zc_video') == 1 ) { ?>
    <?php ob_start(); ?>
      <?php osc_run_hook('home_search_pre'); ?>

      <!-- Video Search Block #1 -->
      <div id="video-block">

        <!--[if lt IE 9]>
          <script type="text/javascript">
            document.createElement('video');
          </script>
        <![endif]-->


        <div class="video">
          <video playsinline autoplay muted loop poster="<?php echo osc_base_url(); ?>oc-content/themes/zara/video/poster.jpg" id="video-play">
            <!-- MP4 video -->
            <?php if(file_exists( osc_base_path() . 'oc-content/themes/zara/video/video.mp4' )) { ?>
              <source src="<?php echo osc_base_url(); ?>oc-content/themes/zara/video/video.mp4" type="video/mp4" />
            <?php } ?>

            <!-- WEBM video -->
            <?php if(file_exists( osc_base_path() . 'oc-content/themes/zara/video/video.webm' )) { ?>
              <source src="<?php echo osc_base_url(); ?>oc-content/themes/zara/video/video.webm" type="video/webm" />
            <?php } ?>
          </video>

          <div id="video-play-alt"></div>

          <div class="overlay"></div>
        </div>

        <div id="video-search">
          <div class="inside">
            
            
            <div class="top">
              <h2><?php _e('Discover new products', 'zara'); ?></h2>
              <h4><?php _e('Browse our classifieds and find best deal for you - buy, sell or exchange items', 'zara'); ?></h4>
            </div>

            <div class="bottom">
              <form action="<?php echo osc_base_url(true); ?>" method="get" class="search nocsrf" >
                <input type="hidden" name="page" value="search" />
                
                <?php osc_run_hook('home_search_top'); ?>
                
                <input type="text" name="sPattern" placeholder="<?php _e('What are you looking for ?', 'zara'); ?>"/>

                <button type="submit" id="video-button"><?php _e('Search', 'zara'); ?></button>
                
                <?php osc_run_hook('home_search_bottom'); ?>
              </form>
            </div>

          </div>
        </div>
      </div>

    <?php $home1 = ob_get_clean(); ?>
  <?php } ?>

  <?php osc_run_hook('home_search_after'); ?>


  <?php if( zara_current('zc_home_map') == 1 && function_exists('zc_home_map')) { ?>
    <?php ob_start(); ?>

      <!-- Map Block #2 -->
      <div class="home-container hc-map">
        <div id="home-map"><?php zc_home_map(); ?></div>
      </div>

    <?php $home2 = ob_get_clean(); ?>
  <?php } ?>




  <?php if( zara_current('zc_slider') == 1 ) { ?>
    <?php ob_start(); ?>

      <!-- Slider Block #3 -->
      <div class="home-container hc-slider">

        <?php if(zara_current('zc_slider_full_width') <> 1) { ?>
          <div class="inner">
        <?php } ?>

          <?php if(function_exists('osc_slider')) { ?>
            <div id="home-slider">
              <?php osc_slider(); ?>
            </div>
          <?php } ?>

        <?php if(zara_current('zc_slider_full_width') == 1) { ?>
          </div>
        <?php } ?>

      </div>

    <?php $home3 = ob_get_clean(); ?>
  <?php } ?>




  <?php if( zara_current('zc_home_premium') == 1 ) { ?>
    <?php ob_start(); ?>

      <!-- Extra Premiums Block #4 -->
      <div class="home-container hc-premiums">
        <div class="inner">

          <div id="latest" class="white prem">
            <h2 class="home">
              <?php _e('Premium listings', 'zara'); ?>
            </h2>

            <?php 
              if( function_exists('zc_current') && zara_current('zc_home_premium_count') <> '' && zara_current('zc_home_premium_count') > 0) {
                $premium_count = zara_current('zc_home_premium_count');
              } else {
                $premium_count = 6;
              }

              osc_get_premiums( $premium_count ); 
            ?>

            <?php if( osc_count_premiums() > 0) { ?>
              <div class="block">
                <div class="wrap">
                  <?php $c = 1; ?>
                  <?php while( osc_has_premiums() ) { ?>
                    <?php zara_draw_item($c, 'gallery', true); ?>
                    
                    <?php $c++; ?>
                  <?php } ?>
                </div>
              </div>
            <?php } else { ?>
              <div class="empty"><?php _e('No premium listings', 'zara'); ?></div>
            <?php } ?>

            <?php //View::newInstance()->_erase('items') ; ?>
          </div>
        </div>
      </div>
      
      <?php osc_run_hook('home_premium'); ?>
      
    <?php $home4 = ob_get_clean(); ?>
  <?php } ?>





  <?php if( zara_current('zc_latest') == 1 ) { ?>
    <?php ob_start(); ?>

      <!-- Latest Listings Block #5 -->
      <div class="home-container hc-latest">
        <div class="inner">

          <div id="latest" class="white">
            <h2 class="home">
              <?php _e('Latest listings', 'zara'); ?>
            </h2>

            <?php View::newInstance()->_exportVariableToView('latestItems', zara_random_items()); ?>

            <?php if( osc_count_latest_items() > 0) { ?>
              <div class="block">
                <div class="wrap">
                  <?php $c = 1; ?>
                  <?php while( osc_has_latest_items() ) { ?>
                    <?php zara_draw_item($c, 'gallery'); ?>
                    
                    <?php $c++; ?>
                  <?php } ?>
                </div>
              </div>
            
              <div class="home-see-all">
                <a href="<?php echo osc_search_url(array('page' => 'search'));?>"><?php _e('See all offers', 'zara'); ?></a>
                <i class="fa fa-angle-down"></i>
              </div>
            <?php } else { ?>
              <div class="empty"><?php _e('No latest listings', 'zara'); ?></div>
            <?php } ?>

            <?php View::newInstance()->_erase('items') ; ?>
          </div>
        </div>
      </div>

      <?php osc_run_hook('home_latest'); ?>
      
    <?php $home5 = ob_get_clean(); ?>
  <?php } ?>

  


  <?php
    // Print body of home
    // Hook section
    foreach( $zc_position_home as $i ) {
      echo isset(${"home" . $i}) ? ${"home" . $i} : '';
    }
  ?>
  

  <?php echo zara_banner('home_bottom'); ?>
  <?php osc_run_hook('home_bottom'); ?>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>	