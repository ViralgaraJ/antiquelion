<?php if( $view == 'gallery' ) { ?>

  <div class="simple-prod premium o<?php echo $c; ?><?php if($class <> '') { echo ' ' . $class; } ?> <?php osc_run_hook("highlight_class"); ?>">
    <div class="simple-wrap">
      <?php osc_run_hook('item_loop_top', true); ?>
      <?php if(function_exists('fi_make_favorite')) { echo fi_make_favorite(); } ?>

      <div class="item-img-wrap">
        <?php 
          $root = zara_category_root( osc_premium_category_id() ); 
          if( $root['s_icon'] <> '' ) {
            $icon = $root['s_icon'];
          } else {
            $def_icons = array(1 => 'fa-gavel', 2 => 'fa-car', 3 => 'fa-book', 4 => 'fa-home', 5 => 'fa-wrench', 6 => 'fa-music', 7 => 'fa-heart', 8 => 'fa-briefcase', 999 => 'fa-soccer-ball-o');
            $icon = $def_icons[$root['pk_i_id']];
          }
        ?>

        <div class="category-link"><span><i class="fa <?php echo $icon; ?>"></i> <?php echo osc_premium_category(); ?></span></div>

        <?php if(osc_count_premium_resources()) { ?>
          <?php if(osc_count_premium_resources() == 1) { ?>
            <a class="img-link" href="<?php echo osc_premium_url(); ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>
          <?php } else { ?>
            <a class="img-link" href="<?php echo osc_premium_url(); ?>">
              <?php for ( $i = 0; osc_has_premium_resources(); $i++ ) { ?>
                <?php if($i <= 1) { ?>
                  <img class="lazy link<?php echo $i; ?>" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" />
                <?php } ?>
              <?php } ?>
            </a>
          <?php } ?>
        <?php } else { ?>
          <a class="img-link" href="<?php echo osc_premium_url(); ?>"><img class="lazy" src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" data-original="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>
        <?php } ?>

        <a class="orange-but open-item" href="<?php echo osc_premium_url(); ?>" title="<?php echo osc_esc_html(__('Go to listing', 'zara')); ?>"><i class="fa fa-link"></i></a>
        <?php if(osc_count_premium_resources() >= 1) { ?>
          <a class="orange-but open-image" href="<?php echo osc_premium_url(); ?>" title="<?php echo osc_esc_html(__('Pictures overview', 'zara')); ?>"><i class="fa fa-camera"></i></a>
        <?php } else { ?>
          <a class="orange-but open-image disabled" title="<?php echo osc_esc_html(__('No pictures', 'zara')); ?>" href="#"><i class="fa fa-camera"></i></a>
        <?php } ?>
      </div>

      <?php
        $now = time();
        $your_date = strtotime(osc_premium_pub_date());
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

      <div class="premium-label">
        <span><?php _e('premium', 'zara'); ?></span>
      </div>
      
      <a class="title" href="<?php echo osc_premium_url(); ?>"><?php echo osc_highlight(osc_premium_title(), 100); ?></a>
      <?php osc_run_hook('item_loop_title', true); ?>
      
      <?php if( osc_price_enabled_at_items() ) { ?>
        <div class="price"><span><?php echo zara_premium_format_price(osc_premium_price()); ?></span></div>
      <?php } ?>
      
      <?php osc_run_hook('item_loop_description', true); ?>
      <?php osc_run_hook('item_loop_bottom', true); ?>
    </div>
  </div>

<?php } else { ?>

  <div class="list-prod premium o<?php echo $c; ?><?php if($class <> '') { echo ' ' . $class; } ?> <?php osc_run_hook("highlight_class"); ?>">
    <?php osc_run_hook('item_loop_top', true); ?>
    <?php if(function_exists('fi_make_favorite')) { echo fi_make_favorite(); } ?>

    <div class="left">
      <h3 class="resp-title"><a href="<?php echo osc_premium_url(); ?>"><?php echo osc_highlight(osc_premium_title(), 80); ?></a></h3>

      <?php if(osc_images_enabled_at_items() and osc_count_premium_resources() > 0) { ?>
        <a class="big-img" href="<?php echo osc_premium_url(); ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>

        <div class="img-bar">
          <?php osc_reset_resources(); ?>
          <?php for ( $i = 0; osc_has_premium_resources(); $i++ ) { ?>
            <?php if($i < 3 && osc_count_premium_resources() > 1) { ?>
              <span class="small-img<?php echo ($i==0 ? ' selected' : ''); ?>" id="bar_img_<?php echo $i; ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></span>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } else { ?>
        <a class="big-img no-img" href="<?php echo osc_premium_url(); ?>"><img class="lazy" src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" data-original="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_premium_title()); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>
      <?php } ?>
    </div>

    <div class="middle">
      <div class="flag"><?php _e('premium', 'zara'); ?></div>

      <h3><a href="<?php echo osc_premium_url(); ?>"><?php echo osc_highlight(osc_premium_title(), 80); ?></a></h3>
      <?php osc_run_hook('item_loop_title', true); ?>
      
      <div class="desc <?php if(osc_count_premium_resources() > 0) { ?>has_images<?php } ?>"><?php echo osc_highlight(osc_premium_description(), 300); ?></div>
      <?php osc_run_hook('item_loop_description', true); ?>
      
      <div class="loc"><i class="fa fa-map-marker"></i><?php echo zara_location_format(osc_premium_country(), osc_premium_region(), osc_premium_city()); ?></div>
      <div class="author">
        <i class="fa fa-pencil"></i><?php _e('Published by', 'zara'); ?> 
        <?php if(osc_premium_user_id() <> 0) { ?>
          <a href="<?php echo osc_user_public_profile_url(osc_premium_user_id()); ?>"><?php echo osc_premium_contact_name(); ?></a>
        <?php } else { ?>
          <?php echo (osc_premium_contact_name() <> '' ? osc_premium_contact_name() : __('Anonymous', 'zara')); ?>
        <?php } ?>
      </div>
    </div>

    <div class="right">
      <?php if( osc_price_enabled_at_items() ) { ?>
        <div class="price"><?php echo zara_premium_format_price(osc_premium_price()); ?></div>
      <?php } ?>

      <a class="view round2" href="<?php echo osc_premium_url(); ?>"><?php _e('view', 'zara'); ?></a>
      <a class="category" href="<?php echo osc_search_url(array('sCategory' => osc_premium_category_id())); ?>"><?php echo osc_premium_category(); ?></a>

      <?php
        $now = time();
        $your_date = strtotime(osc_premium_pub_date());
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
        <?php echo __('viewed', 'zara') . ' <span>' . osc_premium_views() . 'x' . '</span>'; ?>
      </span>
      
      <?php osc_run_hook('item_loop_bottom', true); ?>
    </div>
  </div>

<?php } ?>