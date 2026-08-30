<?php
  $address = '';
  if(osc_user_address()!='') {
    $address = osc_user_address();
  }

  $location_array = array();
  if(trim(osc_user_city()." ".osc_user_zip())!='') {
    $location_array[] = trim(osc_user_city()." ".osc_user_zip());
  }

  if(osc_user_region()!='') {
    $location_array[] = osc_user_region();
  }

  if(osc_user_country()!='') {
    $location_array[] = osc_user_country();
  }

  $location = implode(", ", $location_array);
  unset($location_array);

  $user_keep = osc_user();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
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
    <!-- RIGHT BLOCK -->
    <div id="right-block">
      <?php osc_run_hook('user_public_profile_sidebar_top'); ?>
      
      <!-- SELLER INFORMATION -->
      <div id="description">
        <?php if(function_exists('profile_picture_show')) { profile_picture_show(200); } ?>

        <ul id="user_data">
          <li class="name"><?php echo osc_user_name(); ?></li>
          <?php if ( osc_user_phone_mobile() != "" ) { ?><li><span class="left"><?php _e('Mobile', 'zara'); ?></span><span class="right"><?php echo osc_user_phone_mobile() ; ?></span></li><?php } ?>
          <?php if ( osc_user_phone() != "" && osc_user_phone() != osc_user_phone_mobile() ) { ?><li><span class="left"><?php _e('Phone', 'zara'); ?></span><span class="right"><?php echo osc_user_phone() ; ?></span></li><?php } ?>                    
          <?php if ($address != '') { ?><li><span class="left"><?php _e('Address', 'zara'); ?></span><span class="right"><?php echo $address; ?></span></li><?php } ?>
          <?php if ($location != '') { ?><li><span class="left"><?php _e('Location', 'zara'); ?></span><span class="right"><?php echo $location; ?></span></li><?php } ?>
          <?php if (osc_user_website() != '') { ?><li><span class="left"><?php _e('Website', 'zara'); ?></span><span class="right"><a href="<?php echo osc_user_website(); ?>" target="_blank" rel="nofollow"><?php echo osc_user_website(); ?></a></span></li><?php } ?>
          <?php if(osc_user_info() <> '') { ?><li class="desc"><span class="left"><?php _e('Description', 'zara'); ?></span><span class="right"><?php echo osc_user_info(); ?></span></li><?php } ?>
        </ul>
      </div>


      <!-- CONTACT SELLER BLOCK -->
      <div class="pub-contact-wrap">
        <div class="ins">
          <?php if(osc_user_id() == osc_logged_user_id()) { ?>
            <div class="empty"><?php _e('This is your public profile and therefore contact form is disabled for you', 'zara'); ?></div>
          <?php } else { ?>
            <?php if(osc_reg_user_can_contact() && osc_is_web_user_logged_in() || !osc_reg_user_can_contact() ) { ?>
              <a id="pub-contact" href="<?php echo osc_item_send_friend_url(); ?>" class="tr1 round3" rel="<?php echo osc_user_id(); ?>"><?php _e('Contact seller', 'zara'); ?></a>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
      
      <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
    </div>


    <!-- LISTINGS OF SELLER -->
    <div id="public-items" class="white">
      <h1><?php _e('Latest items of seller', 'zara'); ?></h1>

      <?php osc_run_hook('user_public_profile_items_top'); ?>
      
      <?php if( osc_count_items() > 0) { ?>
        <div class="block">
          <div class="wrap">
            <?php $c = 1; ?>
            <?php while( osc_has_items() ) { ?>
              <?php zara_draw_item($c, 'gallery'); ?>
        
              <?php $c++; ?>
            <?php } ?>
          </div>
        </div>
      <?php } else { ?>
        <div class="empty"><?php _e('No listings posted by this seller', 'zara'); ?></div>
      <?php } ?>
    </div>
  </div>


  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>