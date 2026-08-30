<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-recover">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="clear"></div>
  <div id="i-forms" class="content recover">

    <div class="user_forms round5">
      <div class="inner">
        <form action="<?php echo osc_base_url(true) ; ?>" method="post" >
          <h2><span><?php _e('Recover password', 'starter'); ?></span></h2>

          <input type="hidden" name="page" value="login" />
          <input type="hidden" name="action" value="recover_post" />
          <fieldset>
            <span class="input-box"><i class="fa fa-envelope"></i><?php UserForm::email_text() ; ?></span>
            <?php osc_show_recaptcha('recover_password'); ?>
            <button type="submit" id="blue"><?php _e('Send a new password', 'starter') ; ?></button>
          </fieldset>
        </form>
      </div>
    </div>
  </div>


  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>