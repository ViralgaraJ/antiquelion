<?php if( $view == 'gallery' ) { ?>

  <div class="simple-prod o<?php echo $c; ?><?php if(osc_item_is_premium()) { ?> is-premium<?php } ?><?php if($class <> '') { echo ' ' . $class; } ?> <?php osc_run_hook("highlight_class"); ?>">
    <div class="simple-wrap">
      <?php osc_run_hook('item_loop_top'); ?>
      <?php if(function_exists('fi_make_favorite')) { echo fi_make_favorite(); } ?>

      <div class="item-img-wrap">
        <?php 
          $root = zara_category_root( osc_item_category_id() ); 
          if( $root['s_icon'] <> '' ) {
            $icon = $root['s_icon'];
          } else {
            $def_icons = array(1 => 'fa-gavel', 2 => 'fa-car', 3 => 'fa-book', 4 => 'fa-home', 5 => 'fa-wrench', 6 => 'fa-music', 7 => 'fa-heart', 8 => 'fa-briefcase', 999 => 'fa-soccer-ball-o');
            $icon = $def_icons[$root['pk_i_id']];
          }
        ?>

        <div class="category-link"><span><i class="fa <?php echo $icon; ?>"></i> <?php echo osc_item_category(); ?></span></div>

        <?php if(osc_count_item_resources()) { ?>
          <?php if(osc_count_item_resources() == 1) { ?>
            <a class="img-link" href="<?php echo osc_item_url(); ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>
          <?php } else { ?>
            <a class="img-link" href="<?php echo osc_item_url(); ?>">
              <?php for ( $i = 0; osc_has_item_resources(); $i++ ) { ?>
                <?php if($i <= 1) { ?>
                  <img class="lazy link<?php echo $i; ?>" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                <?php } ?>
              <?php } ?>
            </a>
          <?php } ?>
        <?php } else { ?>
          <a class="img-link" href="<?php echo osc_item_url(); ?>"><img class="lazy" src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" data-original="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>
        <?php } ?>

        <a class="orange-but open-item" href="<?php echo osc_item_url(); ?>" title="<?php echo osc_esc_html(__('Go to listing', 'zara')); ?>"><i class="fa fa-link"></i></a>
        <?php if(osc_count_item_resources() >= 1) { ?>
          <a class="orange-but open-image" href="<?php echo osc_item_url(); ?>" title="<?php echo osc_esc_html(__('Pictures overview', 'zara')); ?>"><i class="fa fa-camera"></i></a>
        <?php } else { ?>
          <a class="orange-but open-image disabled" title="<?php echo osc_esc_html(__('No pictures', 'zara')); ?>" href="#"><i class="fa fa-camera"></i></a>
        <?php } ?>
      </div>

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

      <?php if(osc_item_is_premium()) { ?>
        <div class="premium-label">
          <span><?php _e('premium', 'zara'); ?></span>
        </div>
      <?php } ?>                  
      
      <a class="title" href="<?php echo osc_item_url(); ?>"><?php echo osc_highlight(osc_item_title(), 100); ?></a>
      <?php osc_run_hook('item_loop_title'); ?>

      <?php if( osc_price_enabled_at_items() ) { ?>
        <div class="price"><span><?php echo osc_item_formated_price(); ?></span></div>
      <?php } ?>
      
      <?php osc_run_hook('item_loop_description'); ?>
      <?php osc_run_hook('item_loop_bottom'); ?>
    </div>
  </div>

<?php } else { ?>

  <div class="list-prod o<?php echo $c; ?><?php if(osc_item_is_premium()) { ?> is-premium<?php } ?><?php if($class <> '') { echo ' ' . $class; } ?> <?php osc_run_hook("highlight_class"); ?>">
    <?php osc_run_hook('item_loop_top'); ?>
    <?php if(function_exists('fi_make_favorite')) { echo fi_make_favorite(); } ?>

    <div class="left">
      <h3 class="resp-title"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_highlight(osc_item_title(), 80); ?></a></h3>
      <?php osc_run_hook('item_loop_title'); ?>
      
      <?php if(osc_images_enabled_at_items() and osc_count_item_resources() > 0) { ?>
        <a class="big-img" href="<?php echo osc_item_url(); ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>

        <div class="img-bar">
          <?php osc_reset_resources(); ?>
          <?php for ( $i = 0; osc_has_item_resources(); $i++ ) { ?>
            <?php if($i < 3 && osc_count_item_resources() > 1) { ?>
              <span class="small-img<?php echo ($i==0 ? ' selected' : ''); ?>" id="bar_img_<?php echo $i; ?>"><img class="lazy" src="<?php echo osc_resource_thumbnail_url(); ?>" data-original="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></span>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } else { ?>
        <a class="big-img no-img" href="<?php echo osc_item_url(); ?>"><img class="lazy" src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" data-original="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>
      <?php } ?>
    </div>

    <div class="middle">
      <?php if(osc_item_is_premium()) { ?>
        <div class="flag"><?php _e('premium', 'zara'); ?></div>
      <?php } ?>

      <h3><a href="<?php echo osc_item_url(); ?>"><?php echo osc_highlight(osc_item_title(), 80); ?></a></h3>
      <?php osc_run_hook('item_loop_title'); ?>
      
      <div class="desc <?php if(osc_count_item_resources() > 0) { ?>has_images<?php } ?>"><?php echo osc_highlight(osc_item_description(), 300); ?></div>
      <?php osc_run_hook('item_loop_description'); ?>
      
      <div class="loc"><i class="fa fa-map-marker"></i><?php echo zara_location_format(osc_item_country(), osc_item_region(), osc_item_city()); ?></div>
      <div class="author">
        <i class="fa fa-pencil"></i><?php _e('Published by', 'zara'); ?> 
        <?php if(osc_item_user_id() <> 0) { ?>
          <a href="<?php echo osc_user_public_profile_url(osc_item_user_id()); ?>"><?php echo osc_item_contact_name(); ?></a>
        <?php } else { ?>
          <?php echo (osc_item_contact_name() <> '' ? osc_item_contact_name() : __('Anonymous', 'zara')); ?>
        <?php } ?>
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
      
      <?php osc_run_hook('item_loop_bottom'); ?>
    </div>
  </div>

<?php } ?>