<?php

function zara_backoffice_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/themes/zara/fonts/fa/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/admin.css') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/tipped.css') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/bootstrap-switch.css') . '" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_current_web_theme_url('admin/js/admin.js') . '"></script>';
  echo '<script src="' . osc_current_web_theme_url('admin/js/tipped.js') . '"></script>';
  echo '<script src="' . osc_current_web_theme_url('admin/js/bootstrap-switch.js') . '"></script>';


  if( $title == '') { $title = __('Settings', 'zara'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Zara Theme</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=appearance&action=render&file=oc-content/themes/zara/admin/settings.php"><i class="fa fa-wrench"></i><span>' . __('Settings', 'zara') . '</span></a></li>';
  $text .= '<li><a href="' . osc_base_url() . 'oc-admin/index.php?page=appearance&action=render&file=oc-content/themes/zara/admin/header.php"><i class="fa fa-desktop"></i><span>' . __('Header', 'zara') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



function zara_footer() {
  $themeInfo = zara_theme_info();
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="http://mb-themes.com"><img src="http://mb-themes.com/favicon.ico" alt="MB Themes" /> MB-Themes.com</a>';
  $text .= '<a target="_blank" href="' . $themeInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'zara') . '</a>';
  $text .= '<a target="_blank" href="http://forums.mb-themes.com/"><i class="fa fa-comments"></i> ' . __('Support Forums', 'zara') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@mb-themes.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'zara') . '</a>';
  $text .= '<span class="mb-version">v' . $themeInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
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


// List of categories
function zara_has_subcategories_special($categories, $deep = 0) {
  $i = 1;
  foreach($categories as $c) {
    echo '<div class="mb-table-row ' . ($deep == 0 ? 'parent' . ' o' . $i : '')  . '">';
    echo '<div class="mb-col-1_2 id">' . $c['pk_i_id'] . '</div>';
    echo '<div class="mb-col-2_1_2 mb-align-left sub' . $deep . ' name">' . $c['s_name'] . '</div>';

    if (file_exists(osc_themes_path() . osc_current_web_theme() . '/images/small_cat/' . $c['pk_i_id'] . '.png')) { 
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_yes.png" alt="Has Image" /></div>';  
    } else {
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_no.png" alt="Has not Image" rel="' . $c['pk_i_id'] . '.png'. '" /></div>';  
    }

    echo '<div class="mb-col-1_1_2"><a class="add_img" id="small' . $c['pk_i_id'] . '" href="#">' . __('Add small image', 'zara') . '</a></div>';

    if (file_exists(osc_themes_path() . osc_current_web_theme() . '/images/large_cat/' . $c['pk_i_id'] . '.jpg')) { 
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_yes.png" alt="Has Image" /></div>';  
    } else {
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_no.png" alt="Has not Image" /></div>';  
    }

    echo '<div class="mb-col-1_1_2"><a class="add_img" id="large' . $c['pk_i_id'] . '" href="#">' . __('Add large image', 'zara') . '</a></div>';
    echo '<div class="mb-col-1_1_2 mb-align-left fa-icon"><a class="add_fa" id="fa-icon' . $c['pk_i_id'] . '" href="#" title="To remove icon click on link and leave input empty.">' . __('Add / remove icon', 'zara') . '</a>';
 
    if((@$c['s_icon'] == '' and @$_POST['fa-icon' .$c['pk_i_id']] == '') or (isset($_POST['fa-icon' .$c['pk_i_id']]) and $_POST['fa-icon' .$c['pk_i_id']] == '')) { } else {
      echo '<span><i class="fa ' . (@$_POST['fa-icon' .$c['pk_i_id']] <> '' ? @$_POST['fa-icon' .$c['pk_i_id']] : @$c['s_icon']) . '"></i></span>';
    }
    
    echo '</div>';

    echo '<div class="mb-col-1_1_2 mb-align-left color"><a class="add_color" id="color' . $c['pk_i_id'] . '" href="#" title="To remove color click on link and leave input empty.">';

    if((@$c['s_color'] == '' and @$_POST['color' .$c['pk_i_id']] == '') or (isset($_POST['color' .$c['pk_i_id']]) and @$_POST['color' .$c['pk_i_id']] == '')) { 
      echo  __('Add / remove color', 'zara');
    } else {
      echo __('Color', 'zara') . ': ' . (@$_POST['color' .$c['pk_i_id']] <> '' ? @$_POST['color' .$c['pk_i_id']] : @$c['s_color']) . '<span class="show-color" style="background:' . (@$_POST['color' .$c['pk_i_id']] <> '' ? @$_POST['color' .$c['pk_i_id']] : @$c['s_color']) . '"></span>';
    }

    echo '</a></div>';
    echo '</div>';

    if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
      zara_has_subcategories_special($c['categories'], $deep+1);
    }   

    $i++;
  }
}

?>