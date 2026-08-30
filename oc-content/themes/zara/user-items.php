<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-items">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="content user_account">
    <div class="user-menu-sh resp is767 sc-click"><?php _e('User menu', 'zara'); ?></div>

    <div id="sidebar" class="sc-block">
      <?php if(function_exists('profile_picture_show')) { ?>
        <div class="user-side-img">
          <a href="#" id="pict-update">
            <?php profile_picture_show(null, null, 80); ?>
          </a>
        </div>
      <?php } ?>

      <?php echo osc_private_user_menu(); ?>
      <?php if(function_exists('profile_picture_upload')) { profile_picture_upload(); } ?>
    </div>

    <div id="main" class="ad_list">
      <?php osc_run_hook('user_items_top'); ?>
      
      <h3 class="user-items">
        <span>
          <?php _e('Showing listings', 'zara'); ?>
          <span><?php echo 20*(osc_search_page())+1;?> - <?php echo 20*(osc_search_page()+1)+osc_count_items()-20;?> <?php echo ' ' . __('from', 'zara') . ' '; ?> <?php echo osc_search_total_items(); ?>
        </span>
      </h3>
      <div class="clear"></div>

      <?php if(osc_count_items() == 0) { ?>
        <div class="empty"><?php _e("You did not publish any listings yet", 'zara'); ?></div>
      <?php } else { ?>
        <div id="list-view">
          <?php while(osc_has_items()) { ?>
            <div class="list-prod">

              <?php if( osc_item_is_expired() ) { ?>
                <div class="user-item-expired"><i class="fa fa-warning"></i><?php echo __('Expired on', 'zara') . ': ' . osc_item_field('dt_expiration'); ?></div>
              <?php } ?>

              <div class="left">
                <?php if(osc_images_enabled_at_items() and osc_count_item_resources() > 0) { ?>
                  <a class="big-img" href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>

                  <div class="img-bar">
                    <?php osc_reset_resources(); ?>
                    <?php for ( $i = 0; osc_has_item_resources(); $i++ ) { ?>
                      <?php if($i < 3) { ?>
                        <span class="small-img" id="bar_img_<?php echo $i; ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" <?php echo ($i==0 ? 'class="selected"' : ''); ?> /></span>
                      <?php } ?>
                    <?php } ?>
                  </div>
                <?php } else { ?>
                  <a class="big-img no-img" href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>
                <?php } ?>
              </div>

              <div class="middle">
                <?php if(osc_item_is_premium()) { ?>
                  <div class="flag"><?php _e('top', 'zara'); ?></div>
                <?php } ?>

                <h3><a href="<?php echo osc_item_url(); ?>"><?php echo osc_highlight(osc_item_title(), 80); ?></a></h3>
                <div class="desc"><?php echo osc_highlight(osc_item_description(), 300); ?></div>
                <div class="loc"><i class="fa fa-map-marker"></i><?php echo zara_location_format(osc_item_country(), osc_item_region(), osc_item_city()); ?></div>

                <?php osc_run_hook('user_items_body', osc_item_id()); ?>
                
                <div class="edit-delete">
                  <span class="owner"><?php _e('Author tools', 'zara'); ?></span>
                  <a class="first" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><i class="fa fa-wrench"></i>&nbsp;<?php _e('Edit', 'zara'); ?></a>
                  <a class="second" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this listing? This action cannot be undone.', 'zara')); ?>')" href="<?php echo osc_item_delete_url(); ?>" rel="nofollow"><i class="fa fa-trash-o"></i>&nbsp;<?php _e('Delete', 'zara'); ?></a>
 
                  <?php osc_run_hook('user_items_action', osc_item_id()); ?>
               </div>
              </div>

              <div class="right">
                <?php if( osc_price_enabled_at_items() ) { ?>
                  <div class="price"><?php echo osc_item_formated_price(); ?></div>
                <?php } ?>

                <a class="view round2" href="<?php echo osc_item_url(); ?>"><?php _e('view', 'zara'); ?></a>
                <a class="category" href="<?php echo osc_search_url(array('sCategory' => osc_item_category_id())); ?>"><?php echo osc_item_category(); ?></a>

                <?php
                  $now = time();
                  $your_date = strtotime(osc_item_pub_date());
                  $datediff = $now - $your_date;
                  $item_d = floor($datediff/(60*60*24));

                  if($item_d == 0) {
                    $item_date = __('today', 'zara');
                  } else if($item_d == 1) {
                    $item_date = __('yesterday', 'zara');
                  } else {
                    $item_date = date(osc_get_preference('date_format', 'zara_theme'), $your_date);
                  }
                ?>
                <span class="date">
                  <?php 
                    if($item_d == 0 or $item_d  == 1) {
                      echo __('published', 'zara') . ' <span>' . $item_date . '</span>'; 
                    } else {
                      echo __('published on', 'zara') . ' <span>' . $item_date . '</span>'; 
                    }
                  ?>
                </span>

                <span class="viewed">
                  <?php echo __('viewed', 'zara') . ' <span>' . osc_item_views() . 'x' . '</span>'; ?>
                </span>

                <div class="edit-delete resp">
                  <span class="owner"><?php _e('Author tools', 'zara'); ?></span>
                  <a class="first" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><i class="fa fa-wrench"></i>&nbsp;<?php _e('Edit', 'zara'); ?></a>
                  <a class="second" onclick="return confirm('<?php echo osc_esc_html(__('Are you sure you want to delete this listing? This action cannot be undone.', 'zara')); ?>')" href="<?php echo osc_item_delete_url(); ?>" rel="nofollow"><i class="fa fa-trash-o"></i>&nbsp;<?php _e('Delete', 'zara'); ?></a>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>

        <?php osc_run_hook('user_items_bottom'); ?>

        <div class="clear"></div>

        <div class="paginate">
          <?php for($i = 0 ; $i < osc_list_total_pages() ; $i++) {
            if($i == osc_list_page()) {
              printf('<a class="searchPaginationSelected" href="%s">%d</a>', osc_user_list_items_url($i + 1), ($i + 1));
            } else { 
              printf('<a class="searchPaginationNonSelected" href="%s">%d</a>', osc_user_list_items_url($i + 1), ($i + 1));
            }
          } ?>
        </div>
      <?php } ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>