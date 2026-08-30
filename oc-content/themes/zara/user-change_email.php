<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<body id="body-user-change-email" class="fw-supporting">
  <div style="display:none!important;"><?php osc_current_web_theme_path('header.php'); ?></div></div></div>

  <div id="user-change-email-form" class="fw-box" style="display:block;">
    <div class="head">
      <h2><?php _e('Change your e-mail', 'zara'); ?></h2>
      <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
    </div>

    <div class="left">
      <img src="<?php echo osc_base_url(); ?>oc-content/themes/zara/images/change-email-form.jpg" />
    </div>

    <div class="middle">
      <form target="_top" action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="user-change">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="change_email_post" />
      
        <fieldset>
          <div>
            <label for="email"><?php _e('Current e-mail', 'zara'); ?></label>
            <span class="bold current_email"><?php echo osc_logged_user_email(); ?></span>
          </div>

          <div class="clear"></div>

          <div class="limit">
            <label for="new_email"><?php _e('New e-mail', 'zara'); ?> *</label>
            <input type="text" name="new_email" id="new_email" value="" />
          </div>

          <div class="clear"></div>

          <button type="submit" id="blue"><?php _e('Update', 'zara'); ?></button>
        </fieldset>
      </form>
    </div>
  </div>
</body>
</html>