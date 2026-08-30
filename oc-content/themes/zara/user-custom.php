<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-custom">
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

    <div id="main" class="ad_list">
      <?php osc_render_file(); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>