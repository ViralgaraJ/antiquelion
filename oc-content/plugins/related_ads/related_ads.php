<?php if( osc_count_items() > 0) { ?>
  <!-- Related listings -->
  <div id="related">
    <h2><?php _e('You may also like', 'related_ads'); ?></h2>

    <div class="block">
      <div class="wrap">
        <?php $c = 1; ?>
        <?php while( osc_has_items() ) { ?>
          <?php //starter_draw_item($c, 'gallery'); ?>
          <a class="one" href="<?php echo osc_item_url(); ?>">
            <div class="image">
              <div class="img">
                <?php if(osc_count_item_resources()) { ?>
                  <?php for( $i = 0; $i <= 0; $i++ ) { ?>
                    <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                  <?php } ?>
                <?php } else { ?>
                  <img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                <?php } ?>
              </div>
            </div>

            <div class="text">
              <div class="title"><?php echo osc_item_title(); ?></div>
              <div class="desc"><?php echo osc_highlight(osc_item_description(), 80); ?></div>

              <div class="price">
                <span><?php echo function_exists('antique_format_price') ? antique_format_price(osc_item_price()) : osc_item_formated_price(); ?></span>
              </div>
            </div>
          </a>

          <?php $c++; ?>
        <?php } ?>

        <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="btn btn-secondary"><?php _e('Search more', 'related_ads'); ?></a>
      </div>
    </div>
  </div>
<?php } ?>  
