<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
<?php osc_current_web_theme_path('head.php') ; ?>

<?php 
  // GET IF PAGE IS LOADED VIA QUICK VIEW
  $content_only = (Params::getParam('contentOnly') == 1 ? 1 : 0);


  if(osc_item_price() == '') {
    $og_price = __('Check with seller', 'starter');
  } else if(osc_item_price() == 0) {
    $og_price = __('Free', 'starter');
  } else {
    $og_price = osc_item_price(); 
  }


  $user_id = osc_item_user_id();
  $item_extra = starter_item_extra(osc_item_id());


  $ios = false;
  if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod')) {
    $ios = true;
  }


  $location_array = array(osc_item_country(), osc_item_region(), osc_item_city());
  $location_array = array_filter($location_array);
  $item_loc = implode(', ', $location_array);


  if(osc_item_user_id() <> 0) {
    $item_user = User::newInstance()->findByPrimaryKey(osc_item_user_id());
  }

  $mobile_found = true;
  $mobile = $item_extra['s_phone'];

  if($mobile == '' && function_exists('bo_mgr_show_mobile')) { $mobile = bo_mgr_show_mobile(); }
  if($mobile == '' && osc_item_user_id() <> 0) { $mobile = $item_user['s_phone_mobile']; }      
  if($mobile == '' && osc_item_user_id() <> 0) { $mobile = $item_user['s_phone_land']; } 
 
  $mobile_login_required = false;

  if(osc_item_show_phone() == 0 && 1==2) {   // this is not enabled on publish page
    $mobile = __('No phone number', 'starter');
    $mobile_found = false;
  } else if(osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in() && strlen(trim($mobile)) >= 4) {
    $mobile = __('Login to see phone number', 'starter');
    $mobile_found = true;
    $mobile_login_required = true;
  } else if(trim($mobile) == '' || strlen(trim($mobile)) < 4) { 
    $mobile = __('No phone number', 'starter');
    $mobile_found = false;
  }  
?> 


  <?php if($content_only == 0) { ?>

    <!-- FACEBOOK OPEN GRAPH TAGS -->
    <?php osc_get_item_resources(); ?>
    <meta property="og:title" content="<?php echo osc_esc_html(osc_item_title()); ?>" />
    <?php if(osc_count_item_resources() > 0) { ?><meta property="og:image" content="<?php echo osc_resource_url(); ?>" /><?php } ?>
    <meta property="og:site_name" content="<?php echo osc_esc_html(osc_page_title()); ?>"/>
    <meta property="og:url" content="<?php echo osc_item_url(); ?>" />
    <meta property="og:description" content="<?php echo osc_esc_html(osc_highlight(osc_item_description(), 500)); ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:locale" content="<?php echo osc_current_user_locale(); ?>" />
    <meta property="product:retailer_item_id" content="<?php echo osc_item_id(); ?>" /> 
    <meta property="product:price:amount" content="<?php echo $og_price; ?>" />
    <?php if(osc_item_price() <> '' and osc_item_price() <> 0) { ?><meta property="product:price:currency" content="<?php echo osc_item_currency(); ?>" /><?php } ?>



    <!-- GOOGLE RICH SNIPPETS -->

    <span itemscope itemtype="http://schema.org/Product">
      <meta itemprop="name" content="<?php echo osc_esc_html(osc_item_title()); ?>" />
      <meta itemprop="description" content="<?php echo osc_esc_html(osc_highlight(osc_item_description(), 500)); ?>" />
      <?php if(osc_count_item_resources() > 0) { ?><meta itemprop="image" content="<?php echo osc_resource_url(); ?>" /><?php } ?>
    </span>
  <?php } ?>
</head>

<body id="body-item" class="page-body<?php if($content_only == 1) { ?> content_only<?php } ?><?php if ($ios) { ?> ios<?php } ?>">
  <?php if($content_only == 0) { ?>
    <?php osc_current_web_theme_path('header.php') ; ?>
    <?php if( osc_item_is_expired () ) { ?>
      <div class="exp-box">
        <div class="exp-mes round3"><?php _e('This listing is expired.', 'starter'); ?></div>
      </div>
    <?php } ?>
  <?php } ?>


  <div id="listing" class="content list">
    <?php osc_run_hook('item_top'); ?>
    <?php echo starter_banner('item_top'); ?>

    <!-- LISTING BODY -->
    <div id="main">

      <!-- Image Block -->
      <div id="left">
        <?php if( osc_images_enabled_at_items() ) { ?> 
          <?php osc_get_item_resources(); ?>

          <?php if( osc_count_item_resources() > 0 ) { ?> 
            <div class="photo-block item-block" data-block="detail">
              <div id="images">
                <?php $at_once = min(osc_get_preference('item_images', 'starter_theme'), osc_count_item_resources()); ?>

                <div id="pictures" class="item-pictures">
                  <ul class="item-bxslider">
                    <?php osc_reset_resources(); ?>
                    <?php for( $i = 0; osc_has_item_resources(); $i++ ) { ?>
                      <li>
                        <?php if($content_only == 0) { ?>
                          <a rel="image_group" data-fancybox="gallery" href="<?php echo osc_resource_url(); ?>" title="<?php echo osc_esc_html(osc_item_title()); ?> - <?php _e('Image', 'starter'); ?> <?php echo $i+1;?>/<?php echo osc_count_item_resources();?>">
                            <img src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo $i+1;?>/<?php echo osc_count_item_resources();?>"/>
                          </a>
                        <?php } else { ?>
                          <img src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo $i+1;?>/<?php echo osc_count_item_resources();?>"/>
                        <?php } ?>
                      </li>
                    <?php } ?>
                  </ul>

                  <div id="photo-count" class="round2">
                    <div class="top"><i class="fa fa-camera"></i></div>
                    <div class="bottom">
                      <?php if(osc_count_item_resources() == 1) { ?>
                        <span class="p-total"><?php echo osc_count_item_resources(); ?></span> <?php _e('photo', 'starter'); ?>
                      <?php } else { ?>
                        <span class="p-from">1</span> <span class="p-del">-</span> <span class="p-to"><?php echo $at_once; ?></span> <?php _e('of', 'starter'); ?> <span class="p-total"><?php echo osc_count_item_resources(); ?></span>
                      <?php } ?>
                    </div>
                  </div>

                  <?php if(osc_count_item_resources() > 1 && osc_get_preference('item_pager', 'starter_theme') == 1) { ?>
                    <div id="item-bx-pager">
                      <?php osc_reset_resources(); ?>
                      <?php $c = 0; ?>
                      <?php for( $i = 1; osc_has_item_resources(); $i++ ) { ?>

                        <?php if($i - 1 + $at_once <= osc_count_item_resources()) { ?>
                          <a data-slide-index="<?php echo $c; ?>" href="" class="bx-navi<?php if($i - 1 + $at_once == osc_count_item_resources()) { ?> last<?php } ?>"<?php if($i - 1 + $at_once == osc_count_item_resources()) { ?> style="width:<?php echo $at_once * 128; ?>px"<?php } ?>>
                        <?php } ?>

                        <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php _e('Image', 'starter'); ?> <?php echo $i;?>/<?php echo osc_count_item_resources();?>"/>

                        <?php if($i == osc_count_item_resources()) { ?>
                          </a>
                        <?php } ?>

                        <?php $c++; ?>
                      <?php } ?>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } ?>
        
        <?php osc_run_hook('item_images'); ?> 

        <?php if($content_only == 0) { ?>
          <div class="item-desc item-block" data-block="detail">

            <div id="price">
              <span><?php echo antique_format_price(osc_item_price()); ?></span>
            </div>
            
            <?php osc_run_hook('item_title'); ?>


            <div id="item-basics">
              <div class="elem"><?php _e('ID', 'starter'); ?> #<?php echo osc_item_id(); ?></div>

              <div class="elem">
                <?php if (osc_item_mod_date() == '') { ?>
                  <span title="<?php echo osc_format_date(osc_item_pub_date()); ?>"><?php _e('Published', 'starter'); ?> <?php echo starter_smart_date(osc_item_pub_date()); ?></span>
                <?php } else { ?>
                  <span title="<?php echo osc_format_date(osc_item_pub_date()); ?>"><?php _e('Modified', 'starter'); ?> <?php echo starter_smart_date(osc_item_mod_date()); ?></span>
                <?php } ?>
              </div>   
            </div>

            <div class="mobile-labels is767">
              <?php
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
              ?>
            </div>

            <div class="text">
              <?php echo osc_item_description(); ?>
            </div>
            
            <?php osc_run_hook('item_description'); ?>


            <h2 class="attribute"><?php _e('Attributes', 'starter'); ?></h2>
            <?php $has_custom = false; ?>
            <?php if( osc_count_item_meta() >= 1 ) { ?>
              <div id="custom_fields">
                <div class="meta_list">
                  <?php $class = 'odd'; ?>
                  <?php while( osc_has_item_meta() ) { ?>
                    <?php if(osc_item_meta_value()!='') { ?>
                      <?php $has_custom = true; ?>
                      <div class="meta <?php echo $class; ?>">
                        <div class="ins">
                          <span><?php echo osc_item_meta_name(); ?>:</span> <?php echo osc_item_meta_value(); ?>
                        </div>
                      </div>
                    <?php } ?>

                    <?php $class = ($class == 'even') ? 'odd' : 'even'; ?>
                  <?php } ?>
                </div>

              </div>
            <?php } ?>
            
            <?php osc_run_hook('item_meta'); ?>  

            <div id="plugin-details">
              <?php osc_run_hook('item_detail', osc_item() ); ?>  
            </div>
          </div>
        <?php } ?>


        <?php if($content_only == 0) { ?>
          <div class="item-map item-block" data-block="location">
            <div class="map">
              <?php osc_run_hook('location') ; ?>
            </div>  
          </div>
        <?php } ?>
      </div>


      <?php echo starter_banner('item_description'); ?>


      <?php if($content_only == 0) { ?>
        <div id="more-info" class="contact-seller item-block" data-block="contact">
          <h1>&nbsp;</h1>

          <h2><span><?php _e('Contact seller', 'starter'); ?></span></h2>
      
          <div class="inside">
            <ul id="error_list"></ul>
            <?php ContactForm::js_validation(); ?>

            <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact_form" <?php if(osc_item_attachment()) { echo 'enctype="multipart/form-data"'; };?>>
              <input type="hidden" name="action" value="contact_post" />
              <input type="hidden" name="page" value="item" />
              <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />

              <?php osc_prepare_user_info() ; ?>

              <fieldset>
                <div class="message-block">
                  <?php if( osc_item_is_expired () ) { ?>
                    <div class="empty">
                      <?php _e('This listing expired, you cannot contact seller.', 'starter') ; ?>
                    </div>
                  <?php } else if( (osc_logged_user_id() == osc_item_user_id()) && osc_logged_user_id() != 0 ) { ?>
                    <div class="empty">
                      <?php _e('It is your own listing, you cannot contact yourself.', 'starter') ; ?>
                    </div>
                  <?php } else if( osc_reg_user_can_contact() && !osc_is_web_user_logged_in() ) { ?>
                    <div class="empty">
                      <?php _e('You must log in or register a new account in order to contact the advertiser.', 'starter') ; ?>
                    </div>
                  <?php } else { ?> 
                    <div class="row first">
                      <label for="yourName"><?php _e('Name', 'starter') ; ?></label>
                      <?php ContactForm::your_name(); ?>
                    </div>

                    <div class="row second">
                      <label for="yourEmail"><span><?php _e('E-mail', 'starter'); ?></span><span class="req">*</span></label>
                      <?php ContactForm::your_email(); ?>
                    </div>

                    <div class="row third">
                      <label for="phoneNumber"><span><?php _e('Phone', 'starter'); ?></span></label>
                      <?php ContactForm::your_phone_number(); ?>
                    </div>

                    <div class="row full">
                      <label for="message"><span><?php _e('Message', 'starter'); ?></span></label>
                      <?php ContactForm::your_message(); ?>
                    </div>
                    
                    <?php if(osc_item_attachment()) { ?>
                      <div class="att-wrap">
                        <label for="attachment"><span><?php _e('Attachment', 'starter'); ?></span></label>

                        <div class="attachment">
                          <div class="att-box">
                            <label class="status">
                              <span class="wrap"><i class="fa fa-paperclip"></i> <span data-original="<?php echo osc_esc_html(__('Upload file', 'starter')); ?>"><?php _e('Upload file', 'starter'); ?></span></span>
                              <?php ContactForm::your_attachment(); ?>
                            </label>
                          </div>

                          <div class="text"><?php _e('Allowed extensions:', 'starter'); ?> <?php echo osc_allowed_extension(); ?>.</div>
                          <div class="text"><?php _e('Maximum size:', 'starter'); ?> <?php echo round(osc_max_size_kb()/1000, 1); ?>Mb.</div>
                        </div>
                      </div>
                    <?php } ?>


                    <?php osc_run_hook('item_contact_form', osc_item_id()); ?>
                    <?php osc_show_recaptcha(); ?>

                    <button type="<?php echo (osc_get_preference('forms_ajax', 'starter_theme') == 1 ? 'button' : 'submit'); ?>" class="send" id="item-message"><?php _e('Send message', 'starter') ; ?></button>
                  <?php } ?>
                </div>

                <div class="message-status message-sent">
                  <div class="icon"><i class="fa fa-check-circle"></i></div>
                  <div class="title"></div>
                  <div class="link"><a href="#" class="next-message"><?php _e('Send next message', 'starter'); ?></a></div>
                </div>

                <div class="message-status message-not-sent">
                  <div class="icon"><i class="fa fa-times-circle"></i></div>
                  <div class="title"></div>
                  <div class="link"><a href="#" class="next-message"><?php _e('Send next message', 'starter'); ?></a></div>
                </div>
              </fieldset>
            </form>
          </div>
        </div>


        <!-- Comments-->
        <div id="more-info" class="comments item-block" data-block="comments">
          <?php if( osc_comments_enabled()) { ?>
            <div class="item-comments">
              <h2 class="sc-click">
                <span><?php _e('Comments', 'starter'); ?></span>
              </h2>


              <!-- LIST OF COMMENTS -->
              <div id="comments" class="sc-block">
                <div class="comments_list">
                  <?php $class = 'even'; ?>
                  <?php $i = 1; ?>
                  <?php while ( osc_has_item_comments() ) { ?>
                    <div class="comment-wrap <?php echo $class; ?>">
                      <div class="ins">
                        <div class="comment-image">
                          <img src="<?php echo starter_profile_picture(osc_comment_user_id(), 'medium'); ?>"/>
                        </div>

                        <div class="comment">
                          <h4><span class="bold"><?php if(osc_comment_title() == '') { _e('Review', 'starter'); } else { echo osc_comment_title(); } ?></span> <?php _e('by', 'starter') ; ?> <?php if(osc_comment_author_name() == '') { _e('Anonymous', 'starter'); } else { echo osc_comment_author_name(); } ?>:</h4>
                          <div class="body"><?php echo osc_comment_body() ; ?></div>

                          <?php if ( osc_comment_user_id() && (osc_comment_user_id() == osc_logged_user_id()) ) { ?>
                            <a rel="nofollow" class="remove" href="<?php echo osc_delete_comment_url(); ?>" title="<?php echo osc_esc_html(__('Delete your comment', 'starter')); ?>">
                              <span class="not767"><?php _e('Delete', 'starter'); ?></span>
                              <span class="is767"><i class="fa fa-trash-o"></i></span>
                            </a>
                          <?php } ?>
                        </div>
                      </div>
                    </div>

                    <div class="clear"></div>
                    <?php $class = ($class == 'even') ? 'odd' : 'even'; ?>

                    <?php
                      $i++;
                      $i = $i % 3 + 1;
                    ?>
                  <?php } ?>

                  <?php if($i == 1) { ?>
                    <div class="no-comments"><?php _e('This listing has not been commented yet', 'starter'); ?></div>
                  <?php } ?>

                  <div class="paginate"><?php echo osc_comments_pagination(); ?></div>

                </div>
              </div>

              <?php if( osc_reg_user_post_comments () && osc_is_web_user_logged_in() || !osc_reg_user_post_comments() ) { ?>
                <a class="add-com btn btn-primary" href="<?php echo osc_item_send_friend_url(); ?>"><?php _e('Add comment', 'starter'); ?></a>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
        
        <?php osc_run_hook('item_comment'); ?> 
      <?php } ?>


      <?php echo starter_banner('item_bottom'); ?>
    </div>


    <!-- RIGHT SIDEBAR -->
    <?php if($content_only == 0) { ?>
      <div id="side-right">
        <?php osc_run_hook('item_sidebar_top'); ?>
        <?php echo starter_banner('item_sidebar'); ?>


        <!-- SELLER INFO -->
        <div id="seller" class="item-block <?php if(osc_item_user_id() == 0) { ?> unreg<?php } ?>" data-block="detail">
          <div class="sc-block body">
            <div class="inside">

              <!-- USER IS NOT OWNER OF LISTING -->
              <div class="side-photo">
                <div class="img">
                  <img src="<?php echo starter_profile_picture(osc_item_user_id(), 'medium'); ?>" />
                </div>


                <div class="name">
                  <?php
                    $c_name = '';
                    if(osc_item_contact_name() <> '' and osc_item_contact_name() <> __('Anonymous', 'starter')) {
                      $c_name = osc_item_contact_name();
                    }

                    if($c_name == '' and $item_user['s_name'] <> '') { 
                      $c_name = $item_user['s_name'];
                    }

                    if($c_name == '') {
                      $c_name = __('Anonymous', 'starter');
                    }
                  ?>

                  <?php if(osc_item_user_id() <> 0 and osc_item_user_id() <> '') { ?>
                    <a href="<?php echo osc_user_public_profile_url(osc_item_user_id()); ?>" title="<?php echo osc_esc_html(__('Check profile of this user', 'starter')); ?>">
                      <?php echo $c_name; ?>
                    </a>
                  <?php } else { ?>
                    <?php echo $c_name; ?>
                  <?php } ?>
                </div>


                <div class="elem regdate">
                  <?php if(osc_item_user_id() <> 0) { ?>
                    <?php $get_user = User::newInstance()->findByPrimaryKey( osc_item_user_id() ); ?>

                    <?php if(isset($get_user['dt_reg_date']) AND $get_user['dt_reg_date'] <> '') { ?>
                      <?php echo __('Registered on', 'starter') . ' ' . osc_format_date( $get_user['dt_reg_date'] ); ?>
                    <?php } else { ?>
                      <?php echo __('Unknown registration date', 'starter'); ?>
                    <?php } ?>
                  <?php } else { ?>
                    <?php echo __('Unregistered user', 'starter'); ?>
                  <?php } ?>
                </div>

                <!-- GLOBAL SELLER BADGE -->
                <div class="global-seller-badge" style="display:inline-flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;margin-top:8px;font-size:12px;font-weight:600;color:#0f172a;">
                  <i class="fa fa-globe" style="color:var(--gold-600);"></i>
                  <span><?php echo osc_item_country() <> '' ? osc_item_country() : __('Global Seller', 'starter'); ?></span>
                </div>
              </div>




              <!-- IF USER OWN THIS LISTING, SHOW SELLER TOOLS -->
              <?php if(osc_is_web_user_logged_in() && osc_item_user_id() == osc_logged_user_id()) { ?>
                <div id="s-tools">
                  <a href="<?php echo osc_item_edit_url(); ?>" class="tr1 round2"><i class="fa fa-edit tr1"></i><span><?php _e('Edit', 'starter'); ?></span></a>
                  <a href="<?php echo osc_item_delete_url(); ?>" class="tr1 round2" onclick="return confirm('<?php _e('Are you sure you want to delete this listing? This action cannot be undone.', 'starter'); ?>?')"><i class="fa fa-trash-o tr1"></i><span><?php _e('Remove', 'starter'); ?></span></a>

                  <?php 
                    if (osc_rewrite_enabled()) { 
                      if( $item_extra['i_sold'] == 0 ) {
                        $sold_url = '?itemId=' . osc_item_id() . '&markSold=1&secret=' . osc_item_field('s_secret') . '&itemType=active';
                        $reserved_url = '?itemId=' . osc_item_id() . '&markSold=2&secret=' . osc_item_field('s_secret') . '&itemType=active';
                      } else {
                        $sold_url = '?itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=active';
                        $reserved_url = '?itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=active';
                      }
                    } else {
                      if( $item_extra['i_sold'] == 0 ) {
                        $sold_url = '&itemId=' . osc_item_id() . '&markSold=1&secret=' . osc_item_field('s_secret') . '&itemType=active';
                        $reserved_url = '&itemId=' . osc_item_id() . '&markSold=2&secret=' . osc_item_field('s_secret') . '&itemType=active';
                      } else {
                        $sold_url = '&itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=active';
                        $reserved_url = '&itemId=' . osc_item_id() . '&markSold=0&secret=' . osc_item_field('s_secret') . '&itemType=active';
                      }
                    }
                  ?>


                  <?php if(!in_array(osc_item_category_id(), starter_extra_fields_hide())) { ?>
                    <a target="_blank" class="tr1 round2 sold" href="<?php echo osc_user_list_items_url() . $sold_url; ?>"><i class="fa fa-gavel"></i> <span><?php echo ($item_extra['i_sold'] == 1 ? __('Not sold', 'starter') : __('Sold', 'starter')); ?></span></a>
                    <a target="_blank" class="tr1 round2 reserved" href="<?php echo osc_user_list_items_url() . $reserved_url; ?>"><i class="fa fa-flag-o"></i> <span><?php echo ($item_extra['i_sold'] == 2 ? __('Unreserve', 'starter') : __('Reserved', 'starter')); ?></span></a>
                  <?php } ?>
                </div>
              <?php } ?>

            </div>


            <div class="item-actions">
              <?php if($mobile_found) { ?>
                <div class="row phone">
                  <i class="fa fa-phone"></i>

                  <?php if($mobile_login_required) { ?>
                    <a href="<?php echo osc_user_login_url(); ?>" class="phone-block login-required has-tooltip" data-item-id="<?php echo osc_item_id(); ?>" data-item-user-id="<?php echo osc_item_user_id(); ?>" title="<?php echo osc_esc_js(__('Login to show phone number', 'starter')); ?>">
                      <span>
                        <?php echo __('Login to show number', 'starter'); ?>
                      </span>
                    </a>
                  <?php } else { ?>
                    <a href="#" class="phone-block has-tooltip" data-item-id="<?php echo osc_item_id(); ?>" data-item-user-id="<?php echo osc_item_user_id(); ?>" title="<?php echo osc_esc_js(__('Click to show phone number', 'starter')); ?>">
                      <span>
                        <?php 
                          if(strlen($mobile) >= 4 && $mobile <> __('No phone number', 'starter')) {
                            echo substr($mobile, 0, strlen($mobile) - 4) . 'xxxx'; 
                          } else {
                            echo $mobile;
                          }
                        ?>
                      </span>
                    </a>
                  <?php } ?>
                </div>
              <?php } ?>


              <?php if(osc_item_show_email()) { ?>
                <div class="row">
                  <i class="fa fa-at"></i>

                  <?php
                    $mail = osc_item_contact_email();
                    $mail_start = substr($mail, 0, 3);
                  ?>

                  <a href="#" class="mail-block has-tooltip" rel="<?php echo substr($mail, 3); ?>" title="<?php echo osc_esc_js(__('Click to show email address', 'starter')); ?>">
                    <span>
                      <?php echo $mail_start . 'xxxx@xxxx.xxxx'; ?>
                    </span>
                  </a>
                </div>
              <?php } ?>


              <?php if(function_exists('im_manage_contact_seller')) { ?>
                <div class="row">
                  <i class="fa fa-paper-plane-o"></i>
                  <a class="send-message" href="<?php echo osc_route_url('im-create-thread', array('item-id' => osc_item_id())); ?>"><?php _e('Send message', 'starter'); ?></a>
                </div>
              <?php } ?>


              <?php if(function_exists('show_feedback_overall') && osc_item_user_id() > 0 && osc_get_preference('bo_mgr_allow_feedback', 'plugin-bo_mgr') == 1) { ?>
                <div class="row feedback">
                  <i class="fa fa-thumbs-o-up"></i>
                  <?php echo show_feedback_overall(); ?>
                  <?php echo leave_feedback(); ?>
                </div>
              <?php } ?>

              <?php if(function_exists('ur_hook_show_rating_stars') && osc_item_user_id() > 0) { ?>
                <div class="row user-rating">
                  <i class="fa fa-thumbs-o-up"></i>
                  <?php echo ur_show_rating_stars(); ?>
                  <?php echo ur_add_rating_link(); ?>
                </div>
              <?php } ?>

              <?php if(osc_item_user_id() <> 0) { ?>
                <div class="row dash">
                  <i class="fa fa-dashboard"></i>

                  <?php if(function_exists('seller_post')) { ?>
                    <?php seller_post(); ?>
                  <?php } else { ?>
                    <a href="<?php echo osc_user_public_profile_url(osc_item_user_id()); ?>"><?php _e('Dashboard', 'starter'); ?></a>
                  <?php } ?>
                </div>
              <?php } ?>


              <?php if(osc_item_user_id() <> 0) { ?>
                <div class="row type">
                  <?php $user = User::newInstance()->findByPrimaryKey( osc_item_user_id() ); ?>
                  <?php if($user['b_company'] == 1) { ?>
                    <i class="fa fa-briefcase"></i> <?php _e('Company', 'starter'); ?>
                  <?php } else { ?>
                    <i class="fa fa-user-o"></i> <?php _e('Private person', 'starter'); ?>
                  <?php } ?>
                </div>
              <?php } ?>


              <?php if(function_exists('fi_save_favorite')) { ?>
                <div class="row favorite">
                  <?php echo fi_save_favorite(); ?>
                </div>
              <?php } ?>

              <?php if(function_exists('mo_hook_button') && (osc_item_price() <> 0)) { ?>
                <?php
                  $hook = osc_get_preference('add_hook', 'plugin-make_offer');
                  $hook = ($hook <> '' ? $hook : 1);
                ?>

                <?php if($hook == 1 && mo_show_offer_link()) { ?>
                  <div class="row make-offer">
                    <i class="fa fa-gavel"></i>
                    <?php echo mo_show_offer_link(); ?>
                  </div>
                <?php } ?>
              <?php } ?>


              <div class="row friend">
                <i class="fa fa-users"></i>
                <a id="send-friend" href="<?php echo osc_item_send_friend_url(); ?>"><?php _e('Send to friend', 'starter'); ?></a>
              </div>

              <?php if (function_exists('print_ad')) { ?>
                <div class="row print">
                  <i class="fa fa-print"></i>
                  <?php print_ad(); ?>
                </div>
              <?php } ?>

              <?php if (function_exists('show_printpdf')) { ?>
                <div class="row pdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <a id="print_pdf" target="_blank" href="<?php echo osc_base_url(); ?>oc-content/plugins/printpdf/download.php?item=<?php echo osc_item_id(); ?>" ><?php _e('Download PDF', 'starter'); ?></a>
                </div>
              <?php } ?>
            </div>
          </div>

          <?php osc_run_hook('item_contact'); ?> 
        </div>


        <!-- ITEM LOCATION -->
        <div id="location" class="item-block" data-block="location">
          <h2 class="sc-click">
            <?php _e('Location', 'starter') ; ?>
          </h2>

          <div class="body sc-block">
            <div class="loc-text">
              <?php if(trim(osc_item_country() . osc_item_region() . osc_item_city()) == '') {?>
                <div class="box-empty">
                  <span><?php _e('Location has not been specified', 'starter'); ?></span>
                </div>
              <?php } ?>

              <?php if(count($location_array) > 0) { ?>
                <?php foreach($location_array as $l) { ?>
                  <div class="elem"><?php echo $l; ?></div>
                <?php } ?>
              <?php } ?>

              <?php if(osc_item_address() <> '') { ?>
                <div class="elem"><?php echo osc_item_address(); ?></div>
              <?php } ?>
            </div>

            <?php if($item_loc <> '') { ?>
              <a class="btn btn-primary" target="_blank" href="https://www.google.com/maps?daddr=<?php echo urlencode($item_loc); ?>"><?php _e('Get directions', 'starter'); ?></a>
            <?php } ?>
          </div>  
        </div>


        <!-- USER PROFILE FOR CONTACT -->
        <div id="user-card" class="item-block" data-block="contact">
          <?php $user = User::newInstance()->findByPrimaryKey( osc_item_user_id() ); ?>

          <div class="side-photo">
            <div class="img">
              <img src="<?php echo starter_profile_picture(osc_item_user_id(), 'medium'); ?>" />
            </div>

            <div class="name"><?php echo (osc_item_contact_name() <> '' ? osc_item_contact_name() :  __('Anonymous', 'starter')); ?></div>
          </div>

          <?php if(isset($user['locale'][osc_current_user_locale()]['s_info']) && $user['locale'][osc_current_user_locale()]['s_info'] <> '') { ?>
            <div class="about"><?php echo $user['locale'][osc_current_user_locale()]['s_info']; ?></div>
          <?php } ?>

          <div class="body sc-block">

            <div class="elem">
              <strong><?php _e('Country', 'starter'); ?></strong> 
              <span><?php echo (@$user['s_country'] <> '' ? $user['s_country'] : (osc_item_country() <> '' ? osc_item_country() : '-')); ?></span>
            </div>

            <div class="elem">
              <strong><?php _e('Region', 'starter'); ?></strong> 
              <span><?php echo (@$user['s_region'] <> '' ? $user['s_region'] : (osc_item_region() <> '' ? osc_item_region() : '-')); ?></span>
            </div>

            <div class="elem">
              <strong><?php _e('Address', 'starter'); ?></strong> 
              <span><?php echo (@$user['s_city'] <> '' ? $user['s_city'] : (osc_item_city() <> '' ? osc_item_city() : '-')); ?></span>
            </div>

            <div class="elem">
              <strong><?php _e('Type', 'starter'); ?></strong> 
              <span><?php echo (@$user['s_country'] == 1 ? __('Company', 'starter') : __('Private', 'starter')); ?></span>
            </div>

            <div class="elem">
              <strong><?php _e('Phone', 'starter'); ?></strong> 

              <?php
                $user_phone = $mobile;
                $user_phone = ($user_phone == '' ? @$user['s_phone_mobile'] : $user_phone);
                $user_phone = ($user_phone == '' ? @$user['s_phone_land'] : $user_phone);
                
                if(osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in() && strlen(trim($mobile)) >= 4) {
                  $user_phone = __('Login to see phone number', 'starter');
                }
              ?>

              <a href="#" class="phone-block" data-item-id="<?php echo osc_item_id(); ?>" data-item-user-id="<?php echo osc_item_user_id(); ?>">
                <span>
                  <?php 
                    if(strlen($user_phone) >= 4 && $user_phone <> __('No phone number', 'starter') && $user_phone <> __('Login to see phone number', 'starter')) {
                      echo substr($user_phone, 0, strlen($user_phone) - 4) . 'xxxx'; 
                    } else {
                      echo $user_phone;
                    }
                  ?>
                </span>
              </a>
            </div>

            <?php if(osc_item_show_email()) { ?>
              <div class="elem">
                <strong><?php _e('Email', 'starter'); ?></strong> 

                <?php
                  $mail = osc_item_contact_email();
                  $mail_start = substr($mail, 0, 3);
                ?>

                <a href="#" class="mail-block" rel="<?php echo substr($mail, 3); ?>">
                  <span>
                    <?php echo $mail_start . 'xxxx@xxxx.xxxx'; ?>
                  </span>
                </a>
              </div>
            <?php } ?>

          </div>  
        </div>

        <!-- ITEM PREVIEW WHEN COMMENT PAGE -->
        <div id="comment-card" class="item-block" data-block="comments">
          <div class="pictures">
            <?php osc_reset_resources(); ?>
            <?php for( $i = 0; osc_has_item_resources(); $i++ ) { ?>
              <div class="img">
                <div class="wr">
                  <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo $i+1;?>/<?php echo osc_count_item_resources();?>"/>
                </div>
              </div>
            <?php } ?>
          </div> 

          <div class="title"><?php echo osc_item_title(); ?></div>         
          <div class="description"><?php echo osc_highlight(osc_item_description(), 300); ?></div>     
          <div class="price"><span><?php echo antique_format_price(osc_item_price()); ?></span></div>
        </div>

 
        <?php if(function_exists('sp_buttons') && $content_only == 0) { ?>
          <div class="sms-payments item-block" data-block="detail">
            <?php echo sp_buttons( osc_item_id() );?>
          </div>
        <?php } ?>


        <?php if($content_only == 0) { ?>
          <div id="related-block" class="item-block" data-block="detail">
            <?php if(function_exists('related_ads_start')) { related_ads_start(); } ?>
          </div>
        <?php } ?>
        
        <?php osc_run_hook('item_sidebar_bottom'); ?>
      </div>
    <?php } ?>
    
    <?php osc_run_hook('item_bottom'); ?>
  </div>



  <?php if($content_only == 0) { ?>
    <script type="text/javascript">
      $(document).ready(function(){
        // WRAP TEXT IN H2 & H3 IN ATTRIBUTES PLUGIN INTO SPAN
        $('#plugin-details h2, #plugin-details h3').each(function(){
          $(this).html('<span>' + $(this).html() + '</span>');
        });

        // SHOW PHONE NUMBER ON CLICK
        <?php if($mobile <> __('No phone number', 'starter') && !$mobile_login_required) { ?>  
          $('.phone-show, .phone-block').click(function(e){
            e.preventDefault();
            var mobile = "<?php echo $mobile; ?>";

            if($('.phone-block').attr('href') == '#') {
              $('.phone-block, .phone-show').attr('href', 'tel:' + mobile).addClass('shown');
              $('.phone-block span').text(mobile).css('font-weight', 'bold');
              $('#side-right .btn.contact-button .bot').text(mobile);
              $('.phone-show').text('<?php echo osc_esc_js(__('Click to call', 'starter')); ?>');

              return false;
            }
          });
        <?php } else { ?>
          $('.phone-show, .phone-block').click(function(){
            return false;
          });
        <?php } ?>


        // SHOW EMAIL
        <?php if(osc_item_show_email()) { ?>  
          $('.mail-show, .mail-block').click(function(){
            var mail_start = $('.mail-block > span').text();
            mail_start = mail_start.trim();
            mail_start = mail_start.substring(0, 3);
            var mail_end = $('.mail-block').attr('rel');
            var mail = mail_start + mail_end;

            if($('.mail-block').attr('href') == '#') {
              $('.mail-block, .mail-show').attr('href', 'mailto:' + mail);
              $('.mail-block span').text(mail).css('font-weight', 'bold');
              $('.mail-show').text('<?php echo osc_esc_js(__('Click to mail', 'starter')); ?>');

              return false;
            }
          });
        <?php } else { ?>
          $('.phone-show, .phone-block').click(function(){
            return false;
          });
        <?php } ?>
      });
    </script>

       
    <!-- Scripts -->
    <script type="text/javascript">
    $(document).ready(function(){
      $('.comment-wrap').hover(function(){
        $(this).find('.hide').fadeIn(200);}, 
        function(){
        $(this).find('.hide').fadeOut(200);
      });

      $('.comment-wrap .hide').click(function(){
        $(this).parent().fadeOut(200);
      });

      $('#but-con').click(function(){
        $(".inner-block").slideToggle();
        $("#rel_ads").slideToggle();
      }); 

      
      <?php if(!$has_custom) { echo '$("#custom_fields").hide();';} ?>
    });
    </script>


    <!-- CHECK IF PRICE IN THIS CATEGORY IS ENABLED -->
    <script>
    $(document).ready(function(){
      var cat_id = <?php echo osc_item_category_id(); ?>;
      var catPriceEnabled = new Array();

      <?php
        $categories = Category::newInstance()->listAll( false );
        foreach( $categories as $c ) {
          if( $c['b_price_enabled'] != 1 ) {
            echo 'catPriceEnabled[ '.$c['pk_i_id'].' ] = '.$c[ 'b_price_enabled' ].';';
          }
        }
      ?>

      if(catPriceEnabled[cat_id] == 0) {
        $(".item-details .price.elem").hide(0);
      }
    });
    </script>
  <?php } ?>


  <?php if($content_only == 0) { ?>
    <?php osc_current_web_theme_path('footer.php') ; ?>
  <?php } ?>
</body>
</html>				