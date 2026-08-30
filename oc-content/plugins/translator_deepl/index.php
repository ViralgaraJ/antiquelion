<?php
/*
  Plugin Name: DeepL Translator Plugin
  Plugin URI: https://osclasspoint.com/
  Description: Translate your classifieds using DeepL AI service
  Version: 1.1.1
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: translator_deepl
  Plugin update URI: translator_deepl
  Support URI: https://forums.osclasspoint.com/
  Product Key: 7AYUry3o2myRpzbzeo4H
*/

define('TRD_VERSION_ID', 100);        // Version of DB state
define('TRD_BATCH_PROCESS', true);

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelTRD.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';



// INSTALL FUNCTION - DEFINE VARIABLES
function trd_call_after_install() {
  osc_set_preference('api_key', '', 'plugin-translator_deepl', 'STRING');
  osc_set_preference('translated_chars', 0, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('limit', 500000, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('limit_spent', 0, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('limit_last_use', '', 'plugin-translator_deepl', 'STRING');
  osc_set_preference('glossary_id', '', 'plugin-translator_deepl', 'STRING');
  osc_set_preference('override_translation', 0, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('use_cache', 1, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('enable_beta', 1, 'plugin-translator_deepl', 'INTEGER');
  osc_set_preference('context', '', 'plugin-translator_deepl', 'STRING');

  osc_set_preference('version', TRD_VERSION_ID, 'plugin-translator_deepl', 'INTEGER');

  ModelTRD::newInstance()->install();
}



// AUTOMATIC PLUGIN UPDATE
// Version ID is number greater than 100 and reference to "version of database state" for plugin
function trd_install_plugin_update() {
  $plugin = 'translator_deepl';
  
  if(!in_array(Params::getParam('action'), array('widget','add_post','add','enable','disable','install','uninstall')) && !in_array(Params::getParam('page'), array('ajax','login','market','upgrade','appearance'))) { 
    $installed_version = (int)trd_param('version');
    $current_version = (int)TRD_VERSION_ID;
    
    if($installed_version > 0 && $current_version > $installed_version) {
      $ignore_error = (Params::getParam('forceupdateplugin') == $plugin ? true : false);
      trd_update_version($ignore_error);
    }
  }
}

osc_add_hook('init_admin', 'trd_install_plugin_update', 10);


// PLUGIN UPDATE
function trd_update_version($ignore_error = false) {
  $result = ModelTRD::newInstance()->versionUpdate($ignore_error);
  
  // if failed, do not update version and try DB update again
  if($result !== false || $ignore_error !== false) {
    osc_set_preference('version', TRD_VERSION_ID, 'plugin-translator_deepl', 'INTEGER');
    osc_reset_preferences();
  }
  
  // ignore error and force version update of plugin
  if($ignore_error === true) {
    osc_add_flash_ok_message(sprintf(__('Force update of "%s" completed! Verify plugin functionality, in case of problems reinstall plugin.', 'translator_deepl'), __('DeepL Translator Plugin', 'translator_deepl')), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins');
    exit;
  }
}

osc_add_hook(osc_plugin_path(__FILE__) . '_enable', 'trd_update_version');


// UNINSTALL PLUGIN
function trd_call_after_uninstall() {
  ModelTRD::newInstance()->uninstall();
}


// ADMIN MENU
function trd_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/translator_deepl/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/translator_deepl/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/translator_deepl/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/translator_deepl/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/translator_deepl/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/translator_deepl/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'translator_deepl'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>DeepL Translator Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=translator_deepl/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'translator_deepl') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=translator_deepl/admin/translations.php"><i class="fa fa-language"></i><span>' . __('Translations Cache', 'translator_deepl') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function trd_footer() {
  $pluginInfo = osc_plugin_get_info('translator_deepl/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'translator_deepl') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'translator_deepl') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'translator_deepl') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}


// ADD MENU LINK TO PLUGIN LIST
function trd_admin_menu() {
echo '<h3><a href="#">DeepL Translator Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'translator_deepl') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/translations.php') . '">&raquo; ' . __('Translations Cache', 'translator_deepl') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','trd_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function trd_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'trd_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'trd_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'trd_call_after_uninstall');

?>