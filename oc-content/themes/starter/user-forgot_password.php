<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
</head>
<body id="body-user-forgot_password" class="al-auth-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div id="i-forms" class="al-auth">
    <div class="al-auth-card">
      <div class="al-auth-body">
        <h1><?php _e('Choose a new password', 'starter'); ?></h1>
        <p class="al-auth-lead"><?php _e('Use at least 6 characters. You will sign in with it from now on.', 'starter'); ?></p>

        <form action="<?php echo osc_base_url(true); ?>" method="post" class="al-form" id="al-new-password">
          <input type="hidden" name="page" value="login" />
          <input type="hidden" name="action" value="forgot_post" />
          <input type="hidden" name="userId" value="<?php echo osc_esc_html(Params::getParam('userId')); ?>" />
          <input type="hidden" name="code" value="<?php echo osc_esc_html(Params::getParam('code')); ?>" />
          <label class="al-form-full"><span><?php _e('New password', 'starter'); ?></span><input type="password" name="new_password" id="new_password" required minlength="6" autocomplete="new-password" value="" /></label>
          <label class="al-form-full"><span><?php _e('Repeat new password', 'starter'); ?></span><input type="password" name="new_password2" id="new_password-repeat" required minlength="6" autocomplete="new-password" value="" /></label>
          <button type="submit" class="al-btn al-btn-ink al-btn-block"><?php _e('Save new password', 'starter'); ?></button>
        </form>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var f = document.getElementById('al-new-password');
      var a = f.querySelector('[name=new_password]'), b = f.querySelector('[name=new_password2]');
      function check() { b.setCustomValidity(b.value && a.value !== b.value ? '<?php echo osc_esc_js(__('Passwords do not match.', 'starter')); ?>' : ''); }
      a.addEventListener('input', check);
      b.addEventListener('input', check);
    })();
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
