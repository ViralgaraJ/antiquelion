<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
</head>
<body id="body-custom" class="al-custom-page">
  <?php osc_current_web_theme_path('header.php'); ?>
  <?php osc_render_file(); ?>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
