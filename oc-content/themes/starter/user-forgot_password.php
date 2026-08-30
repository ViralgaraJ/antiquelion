<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-forgot-password">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div id="i-forms" class="content forgot">
  <h2><span><?php _e('Reset', 'starter'); ?></span> <?php _e('password', 'starter'); ?></h2>

  <div class="user_forms">
      <div class="inner">
        <form action="<?php echo osc_base_url(true) ; ?>" method="post" >
        <input type="hidden" name="page" value="login" />
        <input type="hidden" name="action" value="forgot_post" />
        <input type="hidden" name="userId" value="<?php echo osc_esc_html(Params::getParam('userId')); ?>" />
        <input type="hidden" name="code" value="<?php echo osc_esc_html(Params::getParam('code')); ?>" />
        <fieldset>
          <div class="row">
            <label for="new_password"><?php _e('New password', 'starter') ; ?></label>
            <span class="input-box"><i class="fa fa-lock"></i><input type="password" name="new_password" id="new_password" value="" /></span>
          </div>
          <div class="row">
            <label for="new_password-repeat"><?php _e('Repeat password', 'starter') ; ?></label>
            <span class="input-box"><i class="fa fa-unlock-alt"></i><input type="password" name="new_password2" id="new_password-repeat" value="" /></span>
          </div>

          <button type="submit" id="blue"><?php _e('Submit', 'starter') ; ?></button>
        </fieldset>
        </form>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>