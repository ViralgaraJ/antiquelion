<?php
/*
  Plugin Name: Poll & Feedback Plugin
  Plugin URI: https://osclasspoint.com
  Description: Create polls and feedback forms to improve your business.
  Version: 1.1.2
  Author: MB Themes
  Author URI: https://www.mb-themes.com
  Author Email: info@mb-themes.com
  Short Name: poll
  Plugin update URI: poll
  Support URI: https://forums.osclasspoint.com/poll-feedback-plugin/
  Product Key: Vknlbnj86NQq95u9ebph
*/

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelPOL.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


osc_enqueue_style('font-awesome47', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
osc_enqueue_style('font-open-sans', '//fonts.googleapis.com/css?family=Open+Sans:300,600&subset=latin,latin-ext');
osc_enqueue_style('pol-user-style', osc_base_url() . 'oc-content/plugins/poll/css/user.css?v=' . date('YmdHis'));
osc_register_script('pol-user', osc_base_url() . 'oc-content/plugins/poll/js/user.js?v=' . date('YmdHis'), 'jquery');
osc_enqueue_script('pol-user');

osc_register_script('tipped', osc_base_url() . 'oc-content/plugins/poll/js/tipped.js', 'jquery');
osc_enqueue_script('tipped');



// CHECKING POLL SUBMITTED
function pol_submit() {
  if(Params::getParam('pollSubmit') == 1 && Params::getParam('pollId') > 0) {
    $poll_id = Params::getParam('pollId');
    $user_id = Params::getParam('userId');
    $cookie_id = Params::getParam('cookieId');

    $post = Params::getParamsAsArray();
    foreach($post as $p => $v) {
      $name = explode('_', $p);
     
      if(@$name[0] == 'pol-val' && $v == 1) {
        $poll_value_id = @$name[1];

        if($poll_value_id > 0) {
          ModelPOL::newInstance()->insertPollResult($poll_id, $poll_value_id, $user_id, $cookie_id);
        }
      }
    }

    exit;
  }
}

osc_add_hook('init', 'pol_submit');


// CHECKING POLL CLOSED
function pol_close() {

  // User choosen to close poll
  if(Params::getParam('pollClose') == 1) {
    mb_set_cookie('pol_close', 1);
     exit;
  }

  // User has opened poll
  if(Params::getParam('pollOpen') == 1) {
    mb_set_cookie('pol_close', 2);
    exit;
  }
}

osc_add_hook('init', 'pol_close');


// ASSIGN/RE-ASSIGN COOKIES TO USER
function pol_cookie_check() {
  $id = mb_get_cookie('pol_user_id');

  if($id > 0) { 
    mb_set_cookie('pol_user_id', $id);
  } else {
    mb_set_cookie('pol_user_id', '9' . pol_generate_rand_int(10));
  }
}

osc_add_hook('init', 'pol_cookie_check');



// INSTALL FUNCTION - DEFINE VARIABLES
function pol_call_after_install() {
  ModelPOL::newInstance()->install();

  osc_set_preference('color', '#111111', 'plugin-poll', 'STRING');
  osc_set_preference('open', 1, 'plugin-poll', 'INTEGER');
  osc_set_preference('logged_only', 0, 'plugin-poll', 'INTEGER');
}


function pol_call_after_uninstall() {
  ModelPOL::newInstance()->uninstall();
}



// ADMIN MENU
function pol_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/poll/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/poll/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/poll/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="//fonts.googleapis.com/css?family=Open+Sans:300,600&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css" />';
  echo '<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/poll/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/poll/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/poll/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'poll'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Poll & Feedback Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=plugins&action=renderplugin&file=poll/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'poll') . '</span></a></li>';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=plugins&action=renderplugin&file=poll/admin/stats.php"><i class="fa fa-bar-chart"></i><span>' . __('Statistics', 'poll') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function pol_footer() {
  $pluginInfo = osc_plugin_get_info('poll/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://mb-themes.com"><img src="https://mb-themes.com/favicon.ico" alt="MB Themes" /> MB-Themes.com</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'poll') . '</a>';
  $text .= '<a target="_blank" href="https://forums.mb-themes.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'poll') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@mb-themes.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'poll') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function pol_admin_menu() {
echo '<h3><a href="#">Poll & Feedback Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'poll') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/stats.php') . '">&raquo; ' . __('Statistics', 'poll') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','pol_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function pol_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'pol_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'pol_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'pol_call_after_uninstall');

?>