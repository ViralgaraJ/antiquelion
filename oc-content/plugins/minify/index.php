<?php
/*
  Plugin Name: CSS & JS Minify Plugin
  Plugin URI: https://osclasspoint.com
  Description: Compress CSS and JavaScript files into single minified file
  Version: 1.0.3
  Author: MB Themes
  Author URI: https://www.mb-themes.com
  Author Email: info@mb-themes.com
  Short Name: minify
  Plugin update URI: minifyxy
  Support URI: https://forums.mb-themes.com/css-js-minify-plugin/
  Product Key: xQGEDiExjTR5UwJUmbRx
*/



require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/Minify.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/CSS.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/JS.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/Exception.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/Exceptions/BasicException.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/Exceptions/FileImportException.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/minify/src/Exceptions/IOException.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/path-converter/src/ConverterInterface.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'lib/path-converter/src/Converter.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


//osc_add_hook('header', 'mnf_minify');


// INSTALL FUNCTION - DEFINE VARIABLES
function mnf_call_after_install() {
  osc_set_preference('support', 1, 'plugin-minify', 'INTEGER');
  osc_set_preference('css_enabled', 0, 'plugin-minify', 'INTEGER');
  osc_set_preference('js_enabled', 0, 'plugin-minify', 'INTEGER');

  osc_set_preference('css_name', '', 'plugin-minify', 'STRING');
  osc_set_preference('js_name', '', 'plugin-minify', 'STRING');

  osc_set_preference('css_used', '', 'plugin-minify', 'STRING');
  osc_set_preference('js_used', '', 'plugin-minify', 'STRING');

  osc_set_preference('css_footer', 0, 'plugin-minify', 'INTEGER');
  osc_set_preference('js_all', 1, 'plugin-minify', 'INTEGER');


}


// UNINSTALL FUNCTION
function mnf_call_after_uninstall() {
  osc_delete_preference('css_enabled', 'plugin-minify');
  osc_delete_preference('js_enabled', 'plugin-minify');
  osc_delete_preference('css_name', 'plugin-minify');
  osc_delete_preference('js_name', 'plugin-minify');
  osc_delete_preference('css_footer', 'plugin-minify');
  osc_delete_preference('js_all', 'plugin-minify');
}



// ADMIN MENU
function mnf_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/minify/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/minify/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/minify/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="//fonts.googleapis.com/css?family=Open+Sans:300,600&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css" />';
  echo '<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/minify/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/minify/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/minify/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'minify'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>CSS & JS Minify Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=plugins&action=renderplugin&file=minify/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'minify') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function mnf_footer() {
  $pluginInfo = osc_plugin_get_info('minify/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://mb-themes.com"><img src="https://mb-themes.com/favicon.ico" alt="MB Themes" /> MB-Themes.com</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'minify') . '</a>';
  $text .= '<a target="_blank" href="https://forums.mb-themes.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'minify') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@mb-themes.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'minify') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function mnf_admin_menu() {
echo '<h3><a href="#">CSS & JS Minify Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'minify') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','mnf_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function mnf_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'mnf_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'mnf_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'mnf_call_after_uninstall');

?>