<?php
  $locales = __get('locales');
  $user = osc_user();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-profile">
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

    <div id="main" class="modify_profile">
      <?php //UserForm::location_javascript(); ?>
      <form action="<?php echo osc_base_url(true) ; ?>" method="post">
      <input type="hidden" name="page" value="user" />
      <input type="hidden" name="action" value="profile_post" />

      <div id="left-user">
        <h3 class="title_block"><?php _e('Personal information', 'zara'); ?></h3>
        
        <?php osc_run_hook('user_profile_top'); ?>
        
        <div class="row">
          <label for="name"><span><?php _e('Name', 'zara') ; ?></span><span class="req">*</span></label>
          <?php UserForm::name_text(osc_user()) ; ?>
        </div>

        <div class="row">
          <label for="email"><span><?php _e('E-mail', 'zara') ; ?></span><span class="req">*</span></label>
          <span class="update">
            <span><?php echo osc_user_email(); ?></span>
            <a href="<?php echo osc_change_user_email_url(); ?>" id="user-change-email"><?php _e('Modify e-mail', 'zara') ; ?></a> <a href="<?php echo osc_change_user_password_url(); ?>" id="user-change-password"><?php _e('Modify password', 'zara') ; ?></a>
          </span>
        </div>

        <div class="row">
          <label for="phoneMobile"><span><?php _e('Mobile phone', 'zara'); ?></span><span class="req">*</span></label>
          <?php UserForm::mobile_text(osc_user()) ; ?>
        </div>

        <div class="row">
          <label for="phoneLand"><?php _e('Land Phone', 'zara') ; ?></label>
          <?php UserForm::phone_land_text(osc_user()) ; ?>
        </div>                        

        <div class="row">
          <label for="info"><?php _e('Some info about you', 'zara') ; ?></label>
          <?php UserForm::multilanguage_info($locales, osc_user()); ?>
        </div>
      </div>

      <div id="right-user">
        <h3 class="title_block"><?php _e('Business information & location', 'zara'); ?></h3>
        
        <?php osc_run_hook('user_profile_sidebar'); ?>
        
        <div class="row">
          <label for="user_type"><?php _e('User type', 'zara') ; ?></label>
          <?php UserForm::is_company_select(osc_user()) ; ?>
        </div>

        <div class="row">
          <label for="webSite"><?php _e('Website', 'zara') ; ?></label>
          <?php UserForm::website_text(osc_user()) ; ?>
        </div>

        <?php $user = osc_user(); ?>
        <?php $country = Country::newInstance()->listAll(); ?>

        <?php 
          if(count($country) <= 1) {
            $u_country = Country::newInstance()->listAll();
            $u_country = $u_country[0];
            $user['fk_c_country_code'] = $u_country['pk_c_code'];
          }
        ?>

        <div class="row">
          <label for="country"><span><?php _e('Country', 'zara') ; ?></span><span class="req">*</span></label>
          <?php UserForm::country_select(Country::newInstance()->listAll(), osc_user()); ?>
        </div>
        

        <div class="row">
          <label for="region"><span><?php _e('Region', 'zara') ; ?></span><span class="req">*</span></label>
          <?php UserForm::region_select($user['fk_c_country_code'] <> '' ? osc_get_regions($user['fk_c_country_code']) : '', osc_user()) ; ?>
        </div>

        <div class="row">
          <label for="city"><span><?php _e('City', 'zara') ; ?></span><span class="req">*</span></label>
          <?php UserForm::city_select($user['fk_i_region_id'] <> '' ? osc_get_cities($user['fk_i_region_id']) : '', osc_user()) ; ?>
        </div>


        <div class="row">
          <label for="address"><?php _e('Address', 'zara') ; ?></label>
          <?php UserForm::address_text(osc_user()) ; ?>
        </div>

        <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>
      </div>
           
      <?php osc_run_hook('user_form') ; ?>

      <div class="row user-buttons">
        <button type="submit" class="btn btn-primary"><?php _e('Update profile', 'zara') ; ?></button>
        <a class="btn btn-secondary" href="<?php echo osc_base_url(true).'?page=user&action=delete&id='.osc_user_id().'&secret='.$user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete your account? This action cannot be undone', 'zara')); ?>?')"><span><?php _e('Delete account', 'zara'); ?></span></a>
      </div>

      </form>
    </div>
  </div>


  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>