<?php
  $address = '';
  if(osc_user_address()!='') {
    $address = osc_user_address();
  }

  $location = starter_get_full_loc(osc_user_field('fk_c_country_code'), osc_user_region_id(), osc_user_city_id());

  if(osc_user_zip() <> '') {
    $location .= ' ' . osc_user_zip();
  }
  
  $mobile_found = false;
  
  if(osc_user_phone_land() != '') {
    $mobile_found = true;
    $mobile = osc_user_phone_land();
  }
  
  if(osc_user_phone_mobile() != '') {
    $mobile_found = true;
    $mobile = osc_user_phone_mobile();
  }

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
  
  $user_keep = osc_user();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
</head>
<body id="body-user-public-profile">
  <?php View::newInstance()->_exportVariableToView('user', $user_keep); ?>
  <?php osc_current_web_theme_path('header.php') ; ?>

  <div class="content user_public_profile">

    <h1><?php echo sprintf(__('Latest %s\'s listings', 'starter'), osc_user_name()); ?></h1>

    <!-- Item Banner #1 -->
    <?php echo starter_banner('public', 1); ?>


    <!-- LISTINGS OF SELLER -->
    <div id="public-items" class="white">
      <?php osc_run_hook('user_public_profile_items_top'); ?>
      
      <?php if( osc_count_items() > 0) { ?>
        <div class="block">
          <div class="wrap">
            <?php $c = 1; ?>
            <?php while( osc_has_items() ) { ?>
              <?php starter_draw_item($c, 'gallery'); ?>
        
              <?php $c++; ?>
            <?php } ?>
          </div>
        </div>
      <?php } else { ?>
        <div class="empty"><?php _e('No listings posted by this seller', 'starter'); ?></div>
      <?php } ?>
    </div>


    <!-- SELLER INFORMATION -->
    <div id="side-right" class="pp-seller">
        <!-- SELLER INFO -->
        <div id="seller" class="item-block <?php if(osc_user_id() == 0) { ?> unreg<?php } ?>" data-block="detail">
          <div class="sc-block body">
            <div class="inside">
              <?php osc_run_hook('user_public_profile_sidebar_top'); ?>

              <!-- USER IS NOT OWNER OF LISTING -->
              <div class="side-photo">
                <div class="img">
                  <img src="<?php echo starter_profile_picture(osc_user_id(), 'medium'); ?>" />
                </div>


                <div class="name">
                  <?php
                    $c_name = osc_user_name();
                  ?>

                  <?php if(osc_user_id() <> 0 and osc_user_id() <> '') { ?>
                    <a href="<?php echo osc_user_public_profile_url(osc_user_id()); ?>" title="<?php echo osc_esc_html(__('Check profile of this user', 'starter')); ?>">
                      <?php echo $c_name; ?>
                    </a>
                  <?php } else { ?>
                    <?php echo $c_name; ?>
                  <?php } ?>
                </div>


                <div class="elem regdate">
                  <?php if(osc_user_id() <> 0) { ?>
                    <?php $get_user = User::newInstance()->findByPrimaryKey( osc_user_id() ); ?>

                    <?php if(isset($get_user['dt_reg_date']) AND $get_user['dt_reg_date'] <> '') { ?>
                      <?php echo __('Registered on', 'starter') . ' ' . osc_format_date( $get_user['dt_reg_date'] ); ?>
                    <?php } else { ?>
                      <?php echo __('Unknown registration date', 'starter'); ?>
                    <?php } ?>
                  <?php } else { ?>
                    <?php echo __('Unregistered user', 'starter'); ?>
                  <?php } ?>
                </div>
              </div>
            </div>


            <div class="item-actions">
              <?php if($mobile_found) { ?>
                <div class="row phone">
                  <i class="fa fa-phone"></i>

                  <a href="#" class="phone-block has-tooltip" data-item-id="<?php echo osc_item_id(); ?>" data-item-user-id="<?php echo osc_user_id(); ?>" title="<?php echo osc_esc_js(__('Click to show phone number', 'starter')); ?>">
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

              <?php if(1==2) { ?>
              <div class="row">
                <?php if(function_exists('im_manage_contact_seller') && osc_get_preference('contact_seller','plugin-instant_messenger') == 1) { ?>
                  <i class="fa fa-paper-plane-o"></i> 
                  <a class="send-message" href="<?php echo osc_route_url('im-create-thread', array('item-id' => osc_item_id())); ?>"><?php _e('Send message', 'starter'); ?></a>
                <?php } else { ?>
                  <i class="fa fa-envelope-o"></i> 
                  <a class="item-contact" href="<?php echo osc_item_send_friend_url(); ?>"><?php _e('Contact seller', 'starter'); ?></a>
                <?php } ?>
              </div>
              <?php } ?>

              <?php if(function_exists('im_manage_contact_seller') && 1==2) { ?>
                <?php if(osc_get_preference('contact_seller','plugin-instant_messenger') <> 1) { ?>
                  <div class="row im-send-msg">
                    <i class="fa fa-paper-plane-o"></i> 
                    <a class="send-message" href="<?php echo osc_route_url('im-create-thread', array('item-id' => osc_item_id())); ?>"><?php _e('Send message', 'starter'); ?></a>
                  </div>
                <?php } ?>
              <?php } ?>

              <?php if(function_exists('show_feedback_overall') && osc_user_id() > 0) { ?>
                <div class="row feedback">
                  <i class="fa fa-thumbs-o-up"></i>
                  <?php echo show_feedback_overall(); ?>
                  <?php echo leave_feedback(); ?>
                </div>
              <?php } ?>

              <?php if(function_exists('ur_hook_show_rating_stars') && osc_user_id() > 0) { ?>
                <div class="row user-rating">
                  <i class="fa fa-thumbs-o-up"></i>
                  <?php echo ur_show_rating_stars(); ?>
                  <?php echo ur_add_rating_link(); ?>
                </div>
              <?php } ?>

              <?php if(osc_user_id() <> 0) { ?>
                <div class="row dash">
                  <i class="fa fa-dashboard"></i>

                  <?php if(function_exists('seller_post')) { ?>
                    <?php seller_post(osc_user_id()); ?>
                  <?php } else { ?>
                    <a href="<?php echo osc_user_public_profile_url(osc_user_id()); ?>"><?php _e('Dashboard', 'starter'); ?></a>
                  <?php } ?>
                </div>
              <?php } ?>


              <?php if(osc_user_id() <> 0) { ?>
                <div class="row type">
                  <?php $user = User::newInstance()->findByPrimaryKey( osc_user_id() ); ?>
                  <?php if($user['b_company'] == 1) { ?>
                    <i class="fa fa-briefcase"></i> <?php _e('Company', 'starter'); ?>
                  <?php } else { ?>
                    <i class="fa fa-user-o"></i> <?php _e('Private person', 'starter'); ?>
                  <?php } ?>
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

              <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
            </div>
          </div>
        </div>


        <!-- CONTACT SELLER BLOCK -->
        <div class="pub-contact-wrap">
          <div class="ins">
            <?php if(@$user_keep['pk_i_id'] == osc_logged_user_id() && osc_logged_user_id() > 0) { ?>
              <div class="empty"><?php _e('This is your public profile, contact form is disabled for you', 'starter'); ?></div>
            <?php } else { ?>
              <?php if(osc_reg_user_can_contact() && osc_is_web_user_logged_in() || !osc_reg_user_can_contact() ) { ?>
                <a id="pub-contact" href="<?php echo osc_item_send_friend_url(); ?>" class="btn btn-primary" rel="<?php echo osc_user_id(); ?>"><?php _e('Contact seller', 'starter'); ?></a>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    $(document).ready(function(){
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
    });
  </script>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>