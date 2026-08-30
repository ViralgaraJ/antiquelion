<?php
/*
  Plugin Name: Cookie Language
  Plugin URI: http://www.mb-themes.com
  Description: Save the language selected by user into cookies so when user return back to your site, language is selected
  Version: 1.0.1
  Author: MB Themes
  Author URI: http://www.mb-themes.com
  Author Email: info@mb-themes.com
  Short Name: cookie_language
  Plugin update URI: cookie-language
*/

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

function cl_check_language() {
  $lang_active = osc_current_user_locale();
  $lang_stored = mb_get_cookie('cl_locale');
  $lang_active_stored = Session::newInstance()->_get('cl_locale_stored');

  //echo $lang_active . ' - ' . $lang_stored . ' // ' . $lang_active_stored;

  if ($lang_stored == '') {
    mb_set_cookie('cl_locale', $lang_active);
  } else {
    if ($lang_stored <> $lang_active) {
      if($lang_active_stored <> '') {
        mb_set_cookie('cl_locale', $lang_active);
        Session::newInstance()->_set('userLocale', $lang_active);
        header("Location:".osc_base_url()."index.php?page=language&locale=".$lang_active);
      } else {
        mb_set_cookie('cl_locale', $lang_stored);
        Session::newInstance()->_set('userLocale', $lang_stored);
        header("Location:".osc_base_url()."index.php?page=language&locale=".$lang_stored);
      }
    }
  }

  Session::newInstance()->_set('cl_locale_stored', $lang_stored);
}

osc_add_hook('header', 'cl_check_language');
?>