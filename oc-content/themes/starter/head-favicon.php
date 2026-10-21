<?php
  if (!defined('ABS_PATH')) {
    define('ABS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
  }

  require_once ABS_PATH . 'oc-load.php';
  require_once ABS_PATH . 'oc-content/themes/starter/functions.php';

  $path = osc_base_path() . 'oc-content/themes/starter/images/favicons/';
  $url = osc_base_url() . 'oc-content/themes/starter/images/favicons/';
  // bump with the icon files so browsers drop the cached old icon
  $v = '?v=' . (file_exists($path . 'favicon-32x32.png') ? filemtime($path . 'favicon-32x32.png') : '1');
?>
<?php if(file_exists($path . 'favicon.ico')) { ?>
<link rel="icon" href="<?php echo $url; ?>favicon.ico<?php echo $v; ?>" sizes="48x48" />
<?php } ?>
<?php if(file_exists($path . 'favicon-32x32.png')) { ?>
<link rel="icon" href="<?php echo $url; ?>favicon-32x32.png<?php echo $v; ?>" sizes="32x32" type="image/png" />
<?php } ?>
<?php if(file_exists($path . 'favicon-16x16.png')) { ?>
<link rel="icon" href="<?php echo $url; ?>favicon-16x16.png<?php echo $v; ?>" sizes="16x16" type="image/png" />
<?php } ?>
<?php if(file_exists($path . 'apple-touch-icon.png')) { ?>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $url; ?>apple-touch-icon.png<?php echo $v; ?>">
<?php } ?>
<?php if(file_exists($path . 'site.webmanifest')) { ?>
<link rel="manifest" href="<?php echo $url; ?>site.webmanifest<?php echo $v; ?>">
<?php } ?>
<?php if(file_exists($path . 'browserconfig.xml')) { ?>
<meta name="msapplication-config" content="<?php echo $url; ?>browserconfig.xml">
<meta name="msapplication-TileColor" content="#14213D">
<?php } ?>
<meta name="theme-color" content="#14213D">
