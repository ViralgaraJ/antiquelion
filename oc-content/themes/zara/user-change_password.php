<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<body id="body-user-change-password" class="fw-supporting">
  <div style="display:none!important;"><?php osc_current_web_theme_path('header.php'); ?></div></div></div>

  <div id="user-change-password-form" class="fw-box" style="display:block;">
    <div class="head">
      <h2><?php _e('Change your password', 'zara'); ?></h2>
      <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
    </div>

    <div class="left">
      <img src="<?php echo osc_base_url(); ?>oc-content/themes/zara/images/change-password-form.jpg" />
    </div>

    <div class="middle">
      <form target="_top" action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="user-change">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="change_password_post" />
      
        <fieldset>
          <div class="limit">
            <label for="password"><?php _e('Current password', 'zara'); ?> *</label>
            <input type="password" name="password" id="password" value="" />
          </div>

          <div class="limit">
            <label for="new_password"><?php _e('New password', 'zara'); ?> *</label>
            <input type="password" name="new_password" id="new_password" value="" />
          </div>

          <div class="limit">
            <label for="new_password2"><?php _e('Repeat new password', 'zara'); ?> *</label>
            <input type="password" name="new_password2" id="new_password2" value="" />
          </div>

          <button type="submit" id="blue"><?php _e('Update', 'zara'); ?></button>
        </fieldset>
      </form>
    </div>
  </div>
</body>
</html>