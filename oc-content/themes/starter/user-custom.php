<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-custom" class="body-ua al-account-page">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="al-wrap al-account-layout">
    <aside class="al-account-side"><?php echo antique_account_nav(); ?></aside>

    <main class="al-account-main">
      <?php osc_render_file(); ?>
    </main>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>