<?php
/*
  Plugin Name: Password Strength Plugin
  Plugin URI: https://osclasspoint.com/
  Description: Shows color box on registration page that indicates strength of entered password
  Version: 1.1.0
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: pass_strength
  Plugin update URI: pass_strength
  Support URI: https://forums.osclasspoint.com/general-plugins-discussion/
  Product Key: Vc9ueVtA4wD3G3cLFRaE
*/

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelPSS.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


// Add scripts to register page only
osc_add_hook('init', function() {
  if(osc_get_osclass_location() == 'register' && osc_get_osclass_section() == 'register') {
    osc_enqueue_style('pss-user-style', osc_base_url() . 'oc-content/plugins/pass_strength/css/user.css?v=' . date('YmdHis'));
    osc_register_script('pss-user', osc_base_url() . 'oc-content/plugins/pass_strength/js/user.js?v=' . date('YmdHis'), array('jquery'));
    osc_enqueue_script('pss-user');
  }
}, 6);


// Add labels those can be translated
osc_add_hook('header', function() {
  if(osc_get_osclass_location() == 'register' && osc_get_osclass_section() == 'register') {
  ?><script type="text/javascript">var pssLabels = []; pssLabels['veryweak'] = '<?php echo osc_esc_js(__('Very weak', 'pass_strength')); ?>'; pssLabels['weak'] = '<?php echo osc_esc_js(__('Weak', 'pass_strength')); ?>'; pssLabels['fair'] = '<?php echo osc_esc_js(__('Fair', 'pass_strength')); ?>'; pssLabels['strong'] = '<?php echo osc_esc_js(__('Strong', 'pass_strength')); ?>'; pssLabels['verystrong'] = '<?php echo osc_esc_js(__('Very strong!', 'pass_strength')); ?>';</script><?php 
  }
}, 8);


// INSTALL FUNCTION - DEFINE VARIABLES
function pss_call_after_install() {
  // osc_set_preference('selector', '', 'plugin-pass_strength', 'INTEGER');
  
  ModelPSS::newInstance()->install();
}


function pss_call_after_uninstall() {
  ModelPSS::newInstance()->uninstall();
}


// ADMIN MENU
function pss_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/pass_strength/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/pass_strength/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/pass_strength/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/pass_strength/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/pass_strength/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/pass_strength/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'pass_strength'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Password Strength Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=plugins&action=renderplugin&file=pass_strength/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'pass_strength') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function pss_footer() {
  $pluginInfo = osc_plugin_get_info('pass_strength/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'pass_strength') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'pass_strength') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'pass_strength') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function pss_admin_menu() {
echo '<h3><a href="#">Password Strength Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'pass_strength') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','pss_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function pss_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'pss_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'pss_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'pss_call_after_uninstall');

?>