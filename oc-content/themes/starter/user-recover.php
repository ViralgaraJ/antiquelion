<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
</head>
<body id="body-user-recover" class="al-auth-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div id="i-forms" class="al-auth">
    <div class="al-auth-card">
      <div class="al-auth-body">
        <h1><?php _e('Reset your password', 'starter'); ?></h1>
        <p class="al-auth-lead"><?php _e('Enter the email you signed up with and we will send you a link to choose a new password.', 'starter'); ?></p>

        <form action="<?php echo osc_base_url(true); ?>" method="post" class="al-form">
          <input type="hidden" name="page" value="login" />
          <input type="hidden" name="action" value="recover_post" />
          <label class="al-form-full"><span><?php _e('Email', 'starter'); ?></span><input type="email" name="s_email" id="s_email" required autocomplete="email" value="" /></label>
          <?php osc_show_recaptcha('recover_password'); ?>
          <button type="submit" class="al-btn al-btn-ink al-btn-block"><?php _e('Send reset link', 'starter'); ?></button>
        </form>

        <p class="al-auth-foot"><a href="<?php echo osc_user_login_url(); ?>"><?php _e('Back to sign in', 'starter'); ?></a></p>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
