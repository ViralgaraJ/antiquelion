<?php

function starter_backoffice_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/themes/starter/fonts/fa/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/admin.css') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/tipped.css') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/bootstrap-switch.css') . '" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_current_web_theme_url('admin/js/admin.js') . '"></script>';
  echo '<script src="' . osc_current_web_theme_url('admin/js/tipped.js') . '"></script>';
  echo '<script src="' . osc_current_web_theme_url('admin/js/bootstrap-switch.js') . '"></script>';


  echo '<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_current_web_theme_url('admin/css/al-admin.css') . '?v=' . (int)@filemtime(osc_themes_path() . osc_current_web_theme() . '/admin/css/al-admin.css') . '" rel="stylesheet" type="text/css" />';

  if( $title == '') { $title = __('Settings', 'starter'); }

  // one tab per Theme Setting page; the current one is taken from the rendered file name
  $base = osc_admin_base_url(true) . '?page=appearance&action=render&file=oc-content/themes/' . osc_current_web_theme() . '/admin/';
  $file = basename((string)Params::getParam('file'));
  $tabs = array(
    'marketplace.php' => array(function_exists('antique_marketplace_admin_url') ? antique_marketplace_admin_url('overview') : $base . 'marketplace.php', 'fa-line-chart', __('Marketplace', 'starter')),
    'integrations.php' => array(function_exists('antique_integrations_admin_url') ? antique_integrations_admin_url() : $base . 'integrations.php', 'fa-shield', __('Integrations & security', 'starter')),
    'settings.php' => array($base . 'settings.php', 'fa-sliders', __('Theme settings', 'starter')),
    'header.php' => array($base . 'header.php', 'fa-picture-o', __('Header logo', 'starter'))
  );

  $text  = '<div class="al-adm-head">';
  $text .= '<div class="al-adm-title"><small>' . osc_esc_html(osc_page_title()) . '</small><h1>' . osc_esc_html($title) . '</h1></div>';
  $text .= '<nav class="al-adm-nav" aria-label="' . osc_esc_html(__('Theme Setting', 'starter')) . '">';
  foreach ($tabs as $f => $t) {
    $text .= '<a href="' . osc_esc_html($t[0]) . '"' . ($f === $file ? ' class="is-active" aria-current="page"' : '') . '><i class="fa ' . $t[1] . '"></i>' . osc_esc_html($t[2]) . '</a>';
  }
  $text .= '</nav>';
  $text .= '</div>';

  echo $text;
}



function starter_footer() {
  $themeInfo = starter_theme_info();
  return '<div class="al-adm-foot">' . osc_esc_html(osc_page_title()) . ' &middot; ' . sprintf(__('Theme %s', 'starter'), osc_esc_html($themeInfo['version'])) . ' &middot; <a href="https://www.viralgaraj.com/" target="_blank" rel="noopener">ViralGaraj</a></div>';
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
function starter_has_subcategories_special($categories, $deep = 0) {
  $upload_dir_small = osc_themes_path() . osc_current_web_theme() . '/images/small_cat/';
  $upload_dir_large = osc_themes_path() . osc_current_web_theme() . '/images/large_cat/';


  $i = 1;
  foreach($categories as $c) {
    $c_extra = starter_category_extra($c['pk_i_id']);

    echo '<div class="mb-table-row ' . ($deep == 0 ? 'parent' . ' o' . $i : '')  . '">';
    echo '<div class="mb-col-1_2 id">' . $c['pk_i_id'] . '</div>';
    echo '<div class="mb-col-2_1_2 mb-align-left sub' . $deep . ' name">' . $c['s_name'] . '</div>';

    if (file_exists(osc_themes_path() . osc_current_web_theme() . '/images/small_cat/' . $c['pk_i_id'] . '.png')) { 
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_yes.png" alt="Has Image" /></div>';  
    } else {
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_no.png" alt="Has not Image" rel="' .$upload_dir_large . $c['pk_i_id'] . '.png'. '" /></div>';  
    }

    echo '<div class="mb-col-1_1_2"><a class="add_img" id="small' . $c['pk_i_id'] . '" href="#">' . __('Add small image', 'starter') . '</a></div>';

    if (file_exists(osc_themes_path() . osc_current_web_theme() . '/images/large_cat/' . $c['pk_i_id'] . '.jpg')) { 
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_yes.png" alt="Has Image" /></div>';  
    } else {
      echo '<div class="mb-col-1_1_2 icon"><img src="' . osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/img_no.png" alt="Has not Image" /></div>';  
    }

    echo '<div class="mb-col-1_1_2"><a class="add_img" id="large' . $c['pk_i_id'] . '" href="#">' . __('Add large image', 'starter') . '</a></div>';
    echo '<div class="mb-col-1_1_2 mb-align-left fa-icon"><a class="add_fa" id="fa-icon' . $c['pk_i_id'] . '" href="#" title="To remove icon click on link and leave input empty.">' . __('Add / remove icon', 'starter') . '</a>';
 
    if( ($c_extra['s_icon'] <> '' && !isset($_POST['fa-icon' .$c['pk_i_id']])) || (isset($_POST['fa-icon' .$c['pk_i_id']]) && $_POST['fa-icon' .$c['pk_i_id']] <> '') ) {
      if(isset($_POST['fa-icon' .$c['pk_i_id']]) && $_POST['fa-icon' .$c['pk_i_id']] <> '') {
        $ico = $_POST['fa-icon' .$c['pk_i_id']];
      } else {
        $ico = $c_extra['s_icon'];
      }

      echo '<span><i class="fa ' . $ico . '"></i></span>';
    }
    
    echo '</div>';

    echo '<div class="mb-col-1_1_2 mb-align-left color"><a class="add_color" id="color' . $c['pk_i_id'] . '" href="#" title="To remove color click on link and leave input empty.">';

    if(isset($_POST['color' .$c['pk_i_id']]) and $_POST['color' .$c['pk_i_id']] == '') { 
      echo  __('Add / remove color', 'starter');
    } else if (!isset($_POST['color' .$c['pk_i_id']]) && $c_extra['s_color'] == '') { 
      echo  __('Add / remove color', 'starter');
    } else {
      if( isset($_POST['color' .$c['pk_i_id']]) && $_POST['color' .$c['pk_i_id']] <> '' ) {
        $color = $_POST['color' .$c['pk_i_id']];
      } else {
        $color = $c_extra['s_color'];
      }

      echo __('Color', 'starter') . ': ' . $color . '<span class="show-color" style="background:' . $color . '"></span>';
    }

    echo '</a></div>';
    echo '</div>';

    if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
      starter_has_subcategories_special($c['categories'], $deep+1);
    }   

    $i++;
  }
}

?>