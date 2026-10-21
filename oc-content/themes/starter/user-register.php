<?php
  $al_mode = View::newInstance()->_get('al_auth_mode');
  if ($al_mode != 'login') {
    $al_mode = Params::getParam('move') == 'login' ? 'login' : 'register';
  }
  View::newInstance()->_exportVariableToView('al_auth_mode', $al_mode);
  $al_social = function_exists('fl_call_after_install') || function_exists('gc_login_button') || function_exists('fjl_login_button');
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <script src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="body-user-register" class="al-auth-page">
  <?php if ($al_mode == 'register') { UserForm::js_validation(); } ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <div id="i-forms" class="al-auth">
    <div class="al-auth-card">
      <nav class="al-auth-tabs" aria-label="<?php echo osc_esc_html(__('Account', 'starter')); ?>">
        <a href="<?php echo osc_user_login_url(); ?>"<?php echo $al_mode == 'login' ? ' class="is-active" aria-current="page"' : ''; ?>><?php _e('Sign in', 'starter'); ?></a>
        <a href="<?php echo osc_register_account_url(); ?>"<?php echo $al_mode == 'register' ? ' class="is-active" aria-current="page"' : ''; ?>><?php _e('Create account', 'starter'); ?></a>
      </nav>

      <div class="al-auth-body">
        <?php if ($al_social) { ?>
          <div class="al-auth-social">
            <?php if (function_exists('fl_call_after_install')) { ?>
              <a class="al-btn al-btn-outline al-btn-block" href="<?php echo facebook_login_link(); ?>"><i class="fa fa-facebook" aria-hidden="true"></i>&nbsp; <?php _e('Continue with Facebook', 'starter'); ?></a>
            <?php } ?>
            <?php if (function_exists('gc_login_button')) { ?>
              <a class="al-btn al-btn-outline al-btn-block" href="<?php gc_login_button('link-only'); ?>"><i class="fa fa-google" aria-hidden="true"></i>&nbsp; <?php _e('Continue with Google', 'starter'); ?></a>
            <?php } ?>
            <?php if (function_exists('fjl_login_button')) { ?>
              <button type="button" class="al-btn al-btn-outline al-btn-block fjl-button" onclick="fjlCheckLoginState();"><i class="fa fa-facebook" aria-hidden="true"></i>&nbsp; <?php _e('Continue with Facebook', 'starter'); ?></button>
            <?php } ?>
            <div class="al-auth-or"><span><?php _e('or', 'starter'); ?></span></div>
          </div>
        <?php } ?>

        <?php if ($al_mode == 'login') { ?>
          <h1><?php _e('Welcome back', 'starter'); ?></h1>
          <p class="al-auth-lead"><?php _e('Sign in to bid, message sellers and manage your listings.', 'starter'); ?></p>

          <form action="<?php echo osc_base_url(true); ?>" method="post" class="al-form">
            <input type="hidden" name="page" value="login" />
            <input type="hidden" name="action" value="login_post" />

            <label class="al-form-full"><span><?php _e('Email', 'starter'); ?></span><?php UserForm::email_login_text(); ?></label>
            <label class="al-form-full">
              <span class="al-label-row"><?php _e('Password', 'starter'); ?> <a href="<?php echo osc_recover_user_password_url(); ?>"><?php _e('Forgot password?', 'starter'); ?></a></span>
              <?php UserForm::password_login_text(); ?>
            </label>
            <label class="al-check">
              <?php UserForm::rememberme_login_checkbox(); ?>
              <span><?php _e('Keep me signed in', 'starter'); ?></span>
            </label>
            <button type="submit" class="al-btn al-btn-ink al-btn-block"><?php _e('Sign in', 'starter'); ?></button>
          </form>


        <?php } else { ?>
          <h1><?php _e('Create your account', 'starter'); ?></h1>
          <p class="al-auth-lead"><?php _e('Free to join. Bid on auctions, save searches and sell to collectors worldwide.', 'starter'); ?></p>

          <form name="register" id="register" action="<?php echo osc_base_url(true); ?>" method="post" class="al-form">
            <input type="hidden" name="page" value="register" />
            <input type="hidden" name="action" value="register_post" />
            <ul id="error_list"></ul>

            <label class="al-form-full"><span><?php _e('Full name', 'starter'); ?> <em>*</em></span><?php UserForm::name_text(); ?></label>
            <label class="al-form-full"><span><?php _e('Email', 'starter'); ?> <em>*</em></span><?php UserForm::email_text(); ?></label>
            <label class="al-form-full"><span><?php _e('Mobile phone', 'starter'); ?></span><span class="input-box"><?php UserForm::mobile_text(osc_user()); ?></span></label>
            <div class="al-form-row al-form-2">
              <label><span><?php _e('Password', 'starter'); ?> <em>*</em></span><?php UserForm::password_text(); ?></label>
              <label><span><?php _e('Confirm password', 'starter'); ?> <em>*</em></span><?php UserForm::check_password_text(); ?></label>
            </div>

            <?php osc_run_hook('user_register_form'); ?>
            <?php starter_show_recaptcha('register'); ?>

            <button type="submit" class="al-btn al-btn-brass al-btn-block"><?php _e('Create account', 'starter'); ?></button>
            <p class="al-fineprint"><?php echo sprintf(__('By creating an account you agree to our <a href="%1$s">Terms of use</a> and <a href="%2$s">Privacy policy</a>. We will send you an email to confirm your address.', 'starter'), antique_info_page_url('al-terms'), antique_info_page_url('al-privacy')); ?></p>
          </form>

        <?php } ?>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
