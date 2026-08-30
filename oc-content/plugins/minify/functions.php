<?php

use MatthiasMullie\Minify;


// MINIFY CSS
function mnf_minify_css() {
  $css = Styles::newInstance()->getStyles();
  $used = array();

  $minifier = new Minify\CSS('');

  if(count($css) > 0) {
    foreach($css as $name => $url) {
      if(strpos($url, osc_base_url()) !== false) {
        if($name <> 'mnf_css') {
          if (strpos($url, '?v=') !== false) {
            $url = substr($url, 0, strpos($url, '?v='));
          }

          $path = str_replace(osc_base_url(), osc_base_path(), $url);
          $minifier->add($path);
          $used[] = $name;

          //$content = file_get_contents($url);
          //$minifier->add($content);
          $minifier->add('.mnf_file_delimiter{content:"END OF FILE: ' . $name . '";}');
        }
      }
    }
  }

  mnf_remove_files('css');
  $css_name = mb_generate_rand_string();

  $save_path = osc_base_path() . 'oc-content/plugins/minify/generated/css/' . $css_name . '.css';
  osc_set_preference('css_name', $css_name, 'plugin-minify', 'STRING'); 
  osc_set_preference('css_used', implode('|', $used), 'plugin-minify', 'STRING'); 

  $minifier->minify($save_path);
}


// MINIFY JS
function mnf_minify_js() {
  $type = (osc_get_preference('js_all', 'plugin-minify') == 1 ? 'ALL' : 'RESOLVED');
  $js = mnf_get_scripts($type);

  $used = array();

  $minifier = new Minify\JS('');

  if(count($js) > 0) {
    foreach($js as $name => $url) {
      if(strpos($url, osc_base_url()) !== false) {
        if($name <> 'mnf_js') {
          if (strpos($url, '?v=') !== false) {
            $url = substr($url, 0, strpos($url, '?v='));
          }

          $path = str_replace(osc_base_url(), osc_base_path(), $url);
          $minifier->add($path);
          $used[] = $name;

          //$content = file_get_contents($url);
          //$minifier->add($content);
        }
      }
    }
  }

  mnf_remove_files('js');
  $js_name = mb_generate_rand_string();

  $save_path = osc_base_path() . 'oc-content/plugins/minify/generated/js/' . $js_name . '.js';
  osc_set_preference('js_name', $js_name, 'plugin-minify', 'STRING'); 
  osc_set_preference('js_used', implode('|', $used), 'plugin-minify', 'STRING'); 

  $minifier->minify($save_path);
}



// MINIFY CSS & JS
function mnf_minify() {
  if(osc_get_preference('css_enabled', 'plugin-minify') == 1) {
    mnf_minify_css();
  }
 
  if(osc_get_preference('js_enabled', 'plugin-minify') == 1) {
    mnf_minify_js();
  }
}


// LOAD STYLES AND JS
function mnf_load() {
  if(osc_get_preference('css_name', 'plugin-minify') <> '' && osc_get_preference('css_enabled', 'plugin-minify') == 1) {
    //Styles::newInstance()->styles = array();

    $css_used = explode('|', osc_get_preference('css_used', 'plugin-minify'));

    if(count($css_used) > 0) {
      foreach($css_used as $style) {
        osc_remove_style($style);
      }
    }

    if(osc_get_preference('css_footer', 'plugin-minify') <> 1) {
      osc_enqueue_style('mnf_css', osc_base_url() . 'oc-content/plugins/minify/generated/css/' . osc_get_preference('css_name', 'plugin-minify') . '.css');
    }
  }

  if(osc_get_preference('js_name', 'plugin-minify') <> '' && osc_get_preference('js_enabled', 'plugin-minify') == 1) {
    //Scripts::newInstance()->queue = array();            // remove scripts

    $js_used = explode('|', osc_get_preference('js_used', 'plugin-minify'));

    if(count($js_used) > 0) {
      foreach($js_used as $script) {
        osc_remove_script($script);
      }
    }

    osc_register_script('mnf_js', osc_base_url() . 'oc-content/plugins/minify/generated/js/' . osc_get_preference('js_name', 'plugin-minify') . '.js');
    osc_enqueue_script('mnf_js');
  }
}

osc_add_hook('header', 'mnf_load', 3);


function mnf_css_footer() {
  if(osc_get_preference('css_name', 'plugin-minify') <> '' && osc_get_preference('css_enabled', 'plugin-minify') == 1 && osc_get_preference('css_footer', 'plugin-minify') == 1) {
    echo '<link href="' . osc_base_url() . 'oc-content/plugins/minify/generated/css/' . osc_get_preference('css_name', 'plugin-minify') . '.css' . '" rel="stylesheet" type="text/css" />';
  } 
}

osc_add_hook('footer', 'mnf_css_footer', 1);



// REFRESH MANUAL
function mnf_refresh_manual() {
  if(Params::getParam('mnfRefresh') == 1) {
    mnf_minify();

    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=minify/admin/configure.php&refresh=1');
    exit;
  }
}

osc_add_hook('header', 'mnf_refresh_manual', 1);


// UPDATE DEPENDENCIES TO SHOW MINIFIED SCRIPT FIRST
function mnf_update_dependencies() {
  if(Params::getParam('mnfRefresh') <> 1 && osc_get_preference('js_all', 'plugin-minify') != 1 && osc_get_preference('js_enabled', 'plugin-minify') == 1 && osc_get_preference('js_name', 'plugin-minify') <> '') {
    $new = array();
    $registered = Scripts::newInstance()->registered;

    foreach($registered as $r) {
      if($r['key'] == 'mnf_js') {
        $new[$r['key']] = $r;
      } else {
        $new[$r['key']] = $r;
        $new[$r['key']]['dependencies'] = 'mnf_js';
      }
    }

    Scripts::newInstance()->registered = $new;
  }
}

osc_add_hook('header', 'mnf_update_dependencies', 2);


// SUPPORT AUTHOR
function mnf_support() {
  if(osc_is_static_page()) {
    if(osc_get_preference('support', 'plugin-minify') == 1) {
      echo '<div class="mnf-support"><a href="https://osclasspoint.com/" style="display:block;width:auto;margin:15px auto;clear:both;text-align:center;font-size:11px;color:#999;">Osclass Market - Best themes and plugins</a></div>';
    }
  }
}

osc_add_hook('footer', 'mnf_support');


// REMOVE GENERATED
function mnf_remove_files($type) {
  $files = glob(osc_base_path() . 'oc-content/plugins/minify/generated/' . $type . '/*');

  foreach($files as $file){
    if(is_file($file)) {
      unlink($file);
    }
  }
}


function mnf_get_scripts($type = 'ALL') {
  $resolved = array();

  if($type == 'ALL') {
    $registered = Scripts::newInstance()->registered;

    // no dependencies
    foreach($registered as $r) {
      if($r['dependencies']==null) {
        $resolved[$r['key']] = $r['url'];
      }
    }

    // one dependency
    foreach($registered as $r) {
      if(count($r['dependencies']) == 1) {
        $resolved[$r['key']] = $r['url'];
      }
    }

    // more dependencies
    foreach($registered as $r) {
      if(count($r['dependencies']) > 1) {
        $resolved[$r['key']] = $r['url'];
      }
    }
  } else {
    $queue = Scripts::newInstance()->queue;
    $registered = Scripts::newInstance()->registered;

    // no dependencies
    foreach($queue as $q) {
      $r = $registered[$q];
      if($r['dependencies']==null) {
        $resolved[$r['key']] = $r['url'];
      }
    }

    // one dependency
    foreach($queue as $q) {
      $r = $registered[$q];
      if(count($r['dependencies']) == 1) {
        $resolved[$r['key']] = $r['url'];
      }
    }

    // more dependencies
    foreach($queue as $q) {
      $r = $registered[$q];
      if(count($r['dependencies']) > 1) {
        $resolved[$r['key']] = $r['url'];
      }
    }
  }

  return $resolved;
}





// CHECK IF RUNNING ON DEMO
function mnf_is_demo() {
  if(osc_logged_admin_username() == 'admin') {
    return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


// CORE FUNCTIONS
if(!function_exists('mb_param_update')) {
  function mb_param_update( $param_name, $update_param_name, $type = NULL, $plugin_var_name ) {
  
    $val = '';
    if( $type == 'check') {

      // Checkbox input
      if( Params::getParam( $param_name ) == 'on' ) {
        $val = 1;
      } else {
        if( Params::getParam( $update_param_name ) == 'done' ) {
          $val = 0;
        } else {
          $val = ( osc_get_preference( $param_name, $plugin_var_name ) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
        }
      }
    } else {

      // Other inputs (text, password, ...)
      if( Params::getParam( $update_param_name ) == 'done' && Params::existParam($param_name)) {
        $val = Params::getParam( $param_name );
      } else {
        $val = ( osc_get_preference( $param_name, $plugin_var_name) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
      }
    }


    // If save button was pressed, update param
    if( Params::getParam( $update_param_name ) == 'done' ) {

      if(osc_get_preference( $param_name, $plugin_var_name ) == '') {
        osc_set_preference( $param_name, $val, $plugin_var_name, 'STRING');  
      } else {
        $dao_preference = new Preference();
        $dao_preference->update( array( "s_value" => $val ), array( "s_section" => $plugin_var_name, "s_name" => $param_name ));
        osc_reset_preferences();
        unset($dao_preference);
      }
    }

    return $val;
  }
}



if(!function_exists('message_ok')) {
  function message_ok( $text ) {
    $final  = '<div class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
    $final  = '<div class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if( !function_exists('osc_is_contact_page') ) {
  function osc_is_contact_page() {
    $location = Rewrite::newInstance()->get_location();
    $section = Rewrite::newInstance()->get_section();
    if( $location == 'contact' ) {
      return true ;
    }

    return false ;
  }
}


// COOKIES WORK
if(!function_exists('mb_set_cookie')) {
  function mb_set_cookie($name, $val) {
    Cookie::newInstance()->set_expires( 86400 * 30 );
    Cookie::newInstance()->push($name, $val);
    Cookie::newInstance()->set();
  }
}


if(!function_exists('mb_get_cookie')) {
  function mb_get_cookie($name) {
    return Cookie::newInstance()->get_value($name);
  }
}

if(!function_exists('mb_drop_cookie')) {
  function mb_drop_cookie($name) {
    Cookie::newInstance()->pop($name);
  }
}


if(!function_exists('mb_generate_rand_string')) {
  function mb_generate_rand_string($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
  }
}

?>