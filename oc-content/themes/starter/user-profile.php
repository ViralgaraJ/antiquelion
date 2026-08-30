<?php
  $locales = __get('locales');
  $user = osc_user();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-profile" class="body-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="content user_account">
    <div id="sidebar" class="sc-block">
      <?php echo starter_user_menu(); ?>
    </div>

    <div id="main" class="modify_profile">
      <div class="inside">

        <form action="<?php echo osc_base_url(true); ?>" method="post">
          <input type="hidden" name="page" value="user" />
          <input type="hidden" name="action" value="profile_post" />

          <div id="left-user" class="box">
            <h3 class="title_block"><?php _e('Personal information', 'starter'); ?></h3>
            
            <?php osc_run_hook('user_profile_top'); ?>
            
            <div class="row">
              <label for="name"><span><?php _e('Name', 'starter'); ?></span><span class="req">*</span></label>
              <div class="input-box"><i class="fa fa-user"></i><?php UserForm::name_text(osc_user()); ?></div>
            </div>

            <?php if(function_exists('profile_picture_show')) { ?>
              <div class="row">
                <label class="picture"><span><?php _e('Avatar', 'starter'); ?></span></label>
                <a href="#" id="pict-update">
                  <?php profile_picture_show(null, null, 80); ?>
                </a>

                <a href="#" id="pict-update-secondary" class="btn btn-primary"><?php _e('Update avatar', 'starter'); ?></a>
              </div>
            <?php } ?>

            <div class="row">
              <label for="email"><span><?php _e('E-mail', 'starter'); ?></span><span class="req">*</span></label>
              <span class="update current_email">
                <span><?php echo osc_user_email(); ?></span>
              </span>
            </div>

            <div class="row">
              <label for="phoneMobile"><span><?php _e('Mobile phone', 'starter'); ?></span><span class="req">*</span></label>
              <div class="input-box"><i class="fa fa-mobile"></i><?php UserForm::mobile_text(osc_user()); ?></div>
            </div>

            <div class="row">
              <label for="phoneLand"><?php _e('Land Phone', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-phone"></i><?php UserForm::phone_land_text(osc_user()); ?></div>
            </div>                        

            <div class="row">
              <label for="info"><?php _e('Some info about you', 'starter'); ?></label>
              <?php UserForm::multilanguage_info($locales, osc_user()); ?>
            </div>

            <div class="row user-buttons">
              <button type="submit" class="btn btn-primary"><?php _e('Update', 'starter'); ?></button>
            </div>
          </div>

          <div id="right-user" class="box">
            <h3 class="title_block"><?php _e('Business & location', 'starter'); ?></h3>

            <?php osc_run_hook('user_profile_sidebar'); ?>
            
            <div class="row">
              <input type="hidden" name="countryId" id="countryId" class="sCountry" value="<?php echo $user['fk_c_country_code']; ?>"/>
              <input type="hidden" name="regionId" id="regionId" class="sRegion" value="<?php echo $user['fk_i_region_id']; ?>"/>
              <input type="hidden" name="cityId" id="cityId" class="sCity" value="<?php echo $user['fk_i_city_id']; ?>"/>

              <label for="term"><?php _e('Location', 'starter'); ?></label>

              <div id="location-picker">
                <input type="text" name="term" id="term" class="term" placeholder="<?php _e('Country, Region or City', 'starter'); ?>" value="<?php echo starter_get_term(Params::getParam('term'), starter_ajax_country(), starter_ajax_region(), starter_ajax_city()); ?>" autocomplete="off"/>
                <div class="shower-wrap">
                  <div class="shower" id="shower">
                    <div class="option service min-char"><?php _e('Type country, region or city', 'starter'); ?></div>
                  </div>
                </div>

                <div class="loader"></div>
              </div>
            </div>

            <div class="row">
              <label for="cityArea"><?php _e('City Area', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-map-pin"></i><?php UserForm::city_area_text(osc_user()); ?></div>
            </div>

            <div class="row">
              <label for="address"><?php _e('Street', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-map-signs"></i><?php UserForm::address_text(osc_user()); ?></div>
            </div>

            <div class="row">
              <label for="address"><?php _e('ZIP', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-location-arrow"></i><?php UserForm::zip_text(osc_user()); ?></div>
            </div>

            <div class="row">
              <label for="user_type"><?php _e('User type', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-industry"></i><?php UserForm::is_company_select(osc_user()); ?></div>
            </div>

            <div class="row">
              <label for="webSite"><?php _e('Website', 'starter'); ?></label>
              <div class="input-box"><i class="fa fa-link"></i><?php UserForm::website_text(osc_user()); ?></div>
            </div>

            <?php osc_run_hook('user_form'); ?>

            <div class="row user-buttons">
              <button type="submit" class="btn btn-primary"><?php _e('Update', 'starter'); ?></button>
            </div>
          </div>
        </form>


        <!-- CHANGE EMAIL FORM -->
        <div class="box">
          <h3 class="title_block"><?php _e('Change email', 'starter'); ?></h3>

          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_email_post" />
      
            <div class="row">
              <label for="email"><?php _e('Current e-mail', 'starter'); ?></label>
              <span class="bold current_email"><?php echo osc_logged_user_email(); ?></span>
            </div>

            <div class="row">
              <label for="new_email"><?php _e('New e-mail', 'starter'); ?> *</label>
              <div class="input-box"><i class="fa fa-at"></i><input type="text" name="new_email" id="new_email" value="" /></div>
            </div>

            <div class="row user-buttons">
              <button type="submit" class="btn btn-primary"><?php _e('Update', 'starter'); ?></button>
            </div>
          </form>
        </div>


        <!-- CHANGE PASSWORD FORM -->
        <div class="box last">
          <h3 class="title_block"><?php _e('Change password', 'starter'); ?></h3>

          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_password_post" />
      
            <div class="row">
              <label for="password"><?php _e('Current password', 'starter'); ?> *</label>
              <div class="input-box"><i class="fa fa-lock"></i><input type="password" name="password" id="password" value="" /></div>
            </div>

            <div class="row">
              <label for="new_password"><?php _e('New password', 'starter'); ?> *</label>
              <div class="input-box"><i class="fa fa-unlock-alt"></i><input type="password" name="new_password" id="new_password" value="" /></div>
            </div>

            <div class="row">
              <label for="new_password2"><?php _e('Repeat new password', 'starter'); ?> *</label>
              <div class="input-box"><i class="fa fa-unlock-alt"></i><input type="password" name="new_password2" id="new_password2" value="" /></div>
            </div>


            <div class="row user-buttons">
              <button type="submit" class="btn btn-primary"><?php _e('Update', 'starter'); ?></button>
            </div>
          </form>
        </div>
      </div>

      <a class="btn-remove-account btn" href="<?php echo osc_base_url(true).'?page=user&action=delete&id='.osc_user_id().'&secret='.$user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete your account? This action cannot be undone', 'starter')); ?>?')"><span><i class="fa fa-times"></i> <?php _e('Delete account', 'starter'); ?></span></a>

    </div>
  </div>

  <?php if(function_exists('profile_picture_upload')) { profile_picture_upload(); } ?>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>