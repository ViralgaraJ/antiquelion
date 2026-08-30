<?php 
  $item_extra = starter_item_extra( osc_premium_id() ); 

  $location = array_filter(array(osc_premium_city() <> '' ? osc_premium_city() : osc_premium_region() ,osc_premium_country_code()));
  $location = implode(', ', $location);
?>


<div class="simple-prod o<?php echo $c; ?> is-premium<?php if($class <> '') { echo ' ' . $class; } ?> <?php osc_run_hook("highlight_class"); ?>">
  <div class="simple-wrap">
    <?php osc_run_hook('item_loop_top', true); ?>

    <div class="item-img-wrap">
      <div class="labels">
        <?php if($item_extra['i_sold'] == 1) { ?>
          <a class="sold-label label" href="<?php echo osc_premium_url(); ?>">
            <span><?php _e('sold', 'starter'); ?></span>
          </a>
        <?php } else if($item_extra['i_sold'] == 2) { ?>
          <a class="reserved-label label" href="<?php echo osc_premium_url(); ?>">
            <span><?php _e('reserved', 'starter'); ?></span>
          </a>
        <?php } ?>

        <a class="premium-label label" href="<?php echo osc_premium_url(); ?>">
          <span><?php _e('premium', 'starter'); ?></span>
        </a>
      </div>

      <?php if(osc_count_premium_resources()) { ?>
        <a class="img-link" href="<?php echo osc_premium_url(); ?>"><img src="<?php echo osc_resource_preview_url(); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>
      <?php } else { ?>
        <a class="img-link no-img" href="<?php echo osc_premium_url(); ?>"><img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" alt="<?php echo osc_esc_html(osc_premium_title()); ?>" /></a>
      <?php } ?>

      <?php if(function_exists('fi_save_favorite')) { ?>
        <div class="img-bottom">
          <?php echo fi_save_favorite(); ?>
        </div>
      <?php } ?>
    </div>

    <div class="item-details-wrap">
      <div class="category"><a href="<?php echo osc_search_url(array('sCategory' => osc_premium_category_id())); ?>"><span><?php echo osc_premium_category(); ?></span></a></div>
      <a class="title" href="<?php echo osc_premium_url(); ?>"><?php echo osc_highlight(osc_premium_title(), 100); ?></a>
      <?php osc_run_hook('item_loop_title', true); ?> 

      <div class="middle">
        <?php if($location <> '') { ?>
          <div class="location"><?php echo $location; ?></div>
        <?php } ?>
      </div>

      <div class="description<?php if(osc_premium_user_id() > 0) { ?> registered<?php } ?>">
        <?php if(osc_premium_user_id() > 0) { ?>
          <a class="img" href="<?php echo osc_user_public_profile_url(osc_premium_user_id()); ?>">
            <img src="<?php echo starter_profile_picture(osc_premium_user_id(), 'small'); ?>" alt="<?php echo osc_esc_html(osc_premium_contact_name()); ?>"/>
          </a>
        <?php } ?>

        <div class="text"><?php echo osc_highlight(strip_tags(osc_premium_description()), 220); ?></div>
        <?php osc_run_hook('item_loop_description', true); ?> 
      </div>

      <div class="bottom">
        <?php if( osc_price_enabled_at_items() ) { ?>
          <div class="price"><span><?php echo antique_premium_format_price(osc_premium_price()); ?></span></div>
        <?php } ?>
      </div>
    </div>
    
    <?php osc_run_hook('item_loop_bottom', true); ?>
  </div>
</div>