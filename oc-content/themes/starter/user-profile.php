<?php
  $locales = __get('locales');
  $user = osc_user();
?>

<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-profile" class="body-ua al-account-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-account-layout">
    <aside class="al-account-side"><?php echo antique_account_nav(); ?></aside>

    <main class="al-account-main modify_profile">
      <div class="al-account-head"><h2><?php _e('Account settings', 'starter'); ?></h2></div>

      <form action="<?php echo osc_base_url(true); ?>" method="post" class="al-form">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="profile_post" />

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Profile', 'starter'); ?></h3>
          <?php osc_run_hook('user_profile_top'); ?>

          <?php if (function_exists('profile_picture_show')) { ?>
            <div class="al-avatar-row">
              <a href="#" id="pict-update" aria-label="<?php echo osc_esc_html(__('Change photo', 'starter')); ?>"><?php profile_picture_show(null, null, 72); ?></a>
              <div>
                <strong><?php _e('Profile photo', 'starter'); ?></strong>
                <span><?php _e('Shown to buyers on your listings.', 'starter'); ?></span>
                <a href="#" id="pict-update-secondary" class="al-btn al-btn-outline"><?php _e('Change photo', 'starter'); ?></a>
              </div>
            </div>
          <?php } ?>

          <div class="al-form-row al-form-2">
            <label><span><?php _e('Full name', 'starter'); ?> <em>*</em></span><?php UserForm::name_text(osc_user()); ?></label>
            <label><span><?php _e('Account type', 'starter'); ?></span><?php UserForm::is_company_select(osc_user()); ?></label>
          </div>
          <div class="al-form-row al-form-2">
            <label><span><?php _e('Mobile phone', 'starter'); ?></span><span class="input-box"><?php UserForm::mobile_text(osc_user()); ?></span></label>
            <label><span><?php _e('Other phone', 'starter'); ?></span><span class="input-box"><?php UserForm::phone_land_text(osc_user()); ?></span></label>
          </div>
          <label class="al-form-full"><span><?php _e('About you', 'starter'); ?></span><?php UserForm::multilanguage_info($locales, osc_user()); ?></label>
          <label class="al-form-full"><span><?php _e('Website', 'starter'); ?></span><?php UserForm::website_text(osc_user()); ?></label>
        </section>

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Location', 'starter'); ?></h3>
          <?php osc_run_hook('user_profile_sidebar'); ?>
          <input type="hidden" name="countryId" id="countryId" class="sCountry" value="<?php echo osc_esc_html($user['fk_c_country_code']); ?>"/>
          <input type="hidden" name="regionId" id="regionId" class="sRegion" value="<?php echo osc_esc_html($user['fk_i_region_id']); ?>"/>
          <input type="hidden" name="cityId" id="cityId" class="sCity" value="<?php echo osc_esc_html($user['fk_i_city_id']); ?>"/>
          <div class="al-form-full" id="location-picker">
            <span><?php _e('Country, region or city', 'starter'); ?></span>
            <input type="text" name="term" id="term" class="term" placeholder="<?php echo osc_esc_html(__('Start typing your city', 'starter')); ?>" value="<?php echo osc_esc_html(starter_get_term(Params::getParam('term'), starter_ajax_country(), starter_ajax_region(), starter_ajax_city())); ?>" autocomplete="off"/>
            <div class="shower-wrap"><div class="shower" id="shower"><div class="option service min-char"><?php _e('Type country, region or city', 'starter'); ?></div></div></div>
            <div class="loader"></div>
          </div>
          <div class="al-form-row al-form-3">
            <label><span><?php _e('Area', 'starter'); ?></span><?php UserForm::city_area_text(osc_user()); ?></label>
            <label><span><?php _e('Street address', 'starter'); ?></span><?php UserForm::address_text(osc_user()); ?></label>
            <label><span><?php _e('Postcode', 'starter'); ?></span><?php UserForm::zip_text(osc_user()); ?></label>
          </div>
          <?php osc_run_hook('user_form'); ?>
        </section>

        <div class="al-form-actions"><button type="submit" class="al-btn al-btn-ink"><?php _e('Save changes', 'starter'); ?></button></div>
      </form>

      <div class="al-settings-grid">
        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Email address', 'starter'); ?></h3>
          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="al-form user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_email_post" />
            <p class="al-muted"><?php _e('Current:', 'starter'); ?> <strong><?php echo osc_esc_html(osc_logged_user_email()); ?></strong></p>
            <label><span><?php _e('New email', 'starter'); ?> <em>*</em></span><input type="email" name="new_email" id="new_email" value="" autocomplete="email" /></label>
            <button type="submit" class="al-btn al-btn-outline"><?php _e('Change email', 'starter'); ?></button>
          </form>
        </section>

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Password', 'starter'); ?></h3>
          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="al-form user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_password_post" />
            <label><span><?php _e('Current password', 'starter'); ?> <em>*</em></span><input type="password" name="password" id="password" value="" autocomplete="current-password" /></label>
            <label><span><?php _e('New password', 'starter'); ?> <em>*</em></span><input type="password" name="new_password" id="new_password" value="" autocomplete="new-password" /></label>
            <label><span><?php _e('Repeat new password', 'starter'); ?> <em>*</em></span><input type="password" name="new_password2" id="new_password2" value="" autocomplete="new-password" /></label>
            <button type="submit" class="al-btn al-btn-outline"><?php _e('Change password', 'starter'); ?></button>
          </form>
        </section>
      </div>

      <section class="al-panel al-danger-zone">
        <div>
          <h3 class="al-panel-title"><?php _e('Delete account', 'starter'); ?></h3>
          <p class="al-muted"><?php _e('Permanently removes your account and all your listings.', 'starter'); ?></p>
        </div>
        <a class="al-btn al-btn-danger" href="<?php echo osc_base_url(true) . '?page=user&action=delete&id=' . osc_user_id() . '&secret=' . $user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Delete your account and all your listings? This cannot be undone.', 'starter')); ?>');"><?php _e('Delete account', 'starter'); ?></a>
      </section>
    </main>
  </div>

  <?php if(function_exists('profile_picture_upload')) { profile_picture_upload(); } ?>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>