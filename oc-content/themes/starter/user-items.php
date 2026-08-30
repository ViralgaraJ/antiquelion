<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-items" class="body-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="content user_account">
    <div id="sidebar" class="sc-block">
      <?php echo starter_user_menu(); ?>
    </div>


    <?php
      $item_type = Params::getParam('itemType');

      if($item_type == 'active') {
        $title = __('Active listings', 'starter');
        $status = __('Active', 'starter');
      } else if ($item_type == 'pending_validate') {
        $title = __('Not validated listings', 'starter');
        $status = __('Not validated', 'starter');
      } else if ($item_type == 'expired') {
        $title = __('Expired listings', 'starter');
        $status = __('Expired', 'starter');
      } else {
        $title = __('Your listings', 'starter');
        $status = '';
      }


      // ENSURE USER ITEMS ARE PROPERLY EXPORTED TO VIEW
      if(!View::newInstance()->_exists('items') || osc_count_items() == 0) { 
        $active_items = Item::newInstance()->findItemTypesByUserID(osc_logged_user_id(), 0, null, $item_type); 
        if(!empty($active_items)) {
          View::newInstance()->_exportVariableToView('items', $active_items); 
        }
      } 
    ?>




    <div id="main" class="items">
      <div class="inside">
        <?php osc_run_hook('user_items_top'); ?>
        
        <?php if(osc_count_items() > 0) { ?>
          <?php while(osc_has_items()) { ?>
            <div class="us-item round3 tr1">
              <?php
                $item_extra = starter_item_extra( osc_item_id() );

                if($item_type == '') {
                  if(osc_item_is_expired()) {
                    $type = __('Expired', 'starter');
                    $type_raw = 'expired';
                  } else if (osc_item_is_inactive()) {
                    $type = __('Not validated', 'starter');
                    $type_raw = 'pending_validate';
                  } else if (osc_item_is_active()) {
                    $type = __('Active', 'starter');
                    $type_raw = 'active';
                  } else {
                    $type = '';
                    $type_raw = '';
                  }
                } else {
                  $type = '';
                }
              ?>

              <?php if(osc_images_enabled_at_items()) { ?>
                <a href="<?php echo osc_item_url(); ?>" class="image tr1<?php echo (osc_count_item_resources() <= 0 ? ' no-img' : ''); ?> ?>">
                  <span class="image-count"><i class="fa fa-camera"></i> <?php echo osc_count_item_resources(); ?>x</span>

                  <?php if(osc_count_item_resources() > 0) { ?>
                    <img src="<?php echo osc_resource_thumbnail_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" class="tr1" />
                  <?php } else { ?>
                    <img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" class="tr1" />
                  <?php } ?>

                  <?php if($item_type == '') { ?>
                    <div class="type <?php echo $type_raw; ?>"><span class="round2"><?php echo $type; ?></span></div>
                  <?php } ?>

                  <?php if(osc_item_is_premium()) { ?>
                    <div class="premium"><?php _e('Premium', 'starter'); ?></div>
                  <?php } ?>
                </a>
              <?php } ?>

              <div class="right">
                <a href="<?php echo osc_item_url(); ?>" class="title"><?php echo osc_highlight(osc_item_title(), 80); ?></a>

                <div class="buttons">
                  <a class="view round2 tr1" href="<?php echo osc_item_url(); ?>"><?php _e('Open', 'starter'); ?></a>
                  <a class="edit round2 tr1" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><?php _e('Edit', 'starter'); ?></a>

                  <?php if(osc_item_is_inactive()) {?>
                    <a class="activate round2 tr1" href="<?php echo osc_item_activate_url(); ?>"><?php _e('Validate', 'starter'); ?></a>
                  <?php } else { ?>
                    <?php 
                      if (osc_rewrite_enabled()) { 
                        if( $item_extra['i_sold'] == 0 ) {
                          $sold_url = '?itemId=' . osc_item_id() . '&markSold=1&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                          $reserved_url = '?itemId=' . osc_item_id() . '&markSold=2&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                        } else {
                          $sold_url = '?itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                          $reserved_url = '?itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                        }
                      } else {
                        if( $item_extra['i_sold'] == 0 ) {
                          $sold_url = '&itemId=' . osc_item_id() . '&markSold=1&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                          $reserved_url = '&itemId=' . osc_item_id() . '&markSold=2&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                        } else {
                          $sold_url = '&itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                          $reserved_url = '&itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=' . $item_type;
                        }
                      }
                    ?>

                    <?php if(!in_array(osc_item_category_id(), starter_extra_fields_hide())) { ?>
                      <a class="sold round2 tr1" href="<?php echo osc_user_list_items_url() . $sold_url; ?>"><?php echo ($item_extra['i_sold'] == 1 ? __('Unmark as sold', 'starter') : __('Mark as sold', 'starter')); ?></a>
                      <a class="reserved round2 tr1" href="<?php echo osc_user_list_items_url() . $reserved_url; ?>"><?php echo ($item_extra['i_sold'] == 2 ? __('Cancel reservation', 'starter') : __('Reserve', 'starter')); ?></a>
                    <?php } ?>                  

                  <?php } ?>

                  <?php if(function_exists('republish_link_raw') && republish_link_raw(osc_item_id())) { ?>
                    <a class="republish round2 tr1" href="<?php echo republish_link_raw(osc_item_id()); ?>" rel="nofollow"><?php _e('Republish', 'starter'); ?></a>
                  <?php } ?>

                  <a class="delete round2 tr1" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this listing? This action cannot be undone.', 'starter')); ?>')" href="<?php echo osc_item_delete_url(); ?>" rel="nofollow"><?php _e('Delete', 'starter'); ?></a>

                  <?php osc_run_hook('user_items_action', osc_item_id()); ?>
                </div>

                <?php osc_run_hook('user_items_body', osc_item_id()); ?>

                <div class="middle">
                  <?php if( osc_price_enabled_at_items() ) { ?>
                    <div class="price"><?php echo antique_format_price(osc_item_price()); ?></div>
                  <?php } ?>

                  <div class="category round2"><i class="fa fa-cog"></i> <?php echo osc_item_category(); ?></div>

                  <?php if($item_extra['i_sold'] == 1) { ?>
                    <div><?php _e('Sold!', 'starter'); ?></div>
                  <?php } else if($item_extra['i_sold'] == 2) { ?>
                    <div><?php _e('Reserved!', 'starter'); ?></div>
                  <?php } ?>

                  <?php if(!in_array(osc_item_category_id(), starter_extra_fields_hide())) { ?>
                    <?php if(starter_condition_name($item_extra['i_condition'])) { ?>
                      <div class="condition has-tooltip" title="<?php echo osc_esc_html(__('Condition', 'starter')); ?>"><span><?php echo starter_condition_name($item_extra['i_condition']); ?></span></div>
                    <?php } ?>

                    <?php if(starter_transaction_name($item_extra['i_transaction'])) { ?>
                      <div class="transaction has-tooltip" title="<?php echo osc_esc_html(__('Transaction', 'starter')); ?>"><span><?php echo starter_transaction_name($item_extra['i_transaction']); ?></span></div>
                    <?php } ?>
                  <?php } ?>

                  <div><span class="label"><?php _e('Exp', 'starter'); ?>:</span> <?php echo (date('Y', strtotime(osc_item_field('dt_expiration'))) > 3000 ? __('Never', 'starter') : date('Y/m/d', strtotime(osc_item_field('dt_expiration')))); ?></div>
                  <div><span class="label"><?php _e('Pub', 'starter'); ?>:</span> <?php echo date('Y/m/d', strtotime(osc_item_pub_date())); ?></div>
                  <?php if(osc_item_mod_date() <> '') { ?>
                    <div>
                      <span class="label"><?php _e('Mod', 'starter'); ?>:</span> <?php echo date('Y/m/d', strtotime(osc_item_mod_date())); ?>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="ua-items-empty"><span><?php echo sprintf(__('%s listings not found', 'starter'), $status); ?></span></div>
        <?php } ?>

        <?php osc_run_hook('user_items_bottom'); ?>
        
        <?php if(osc_list_total_pages() > 1)  { ?>
          <div class="paginate">
            <?php for($i = 0 ; $i < osc_list_total_pages() ; $i++) { ?>
              <a class="<?php if($i == osc_list_page()) { ?>searchPaginationSelected<?php } else { ?>searchPaginationNonSelected<?php } ?>" href="<?php echo osc_user_list_items_url($i + 1) . '&itemType=' . $item_type; ?>"><?php echo ($i + 1); ?></a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>