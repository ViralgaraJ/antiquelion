<?php
/*
  Plugin Name: OSC Slider
  Plugin URI: http://www.osclass.org/
  Description: This plugin shows the JQuery Slideshow Where Ever You Want.
  Version: 5.0
  Author: Rajasekar & Trains modified by MB Themes
  Author URI: http://www.trickfree.com/
  Short Name: OSC Slider
  Plugin update URI: http://www.osclass.org/files/plugins
*/

require_once( osc_plugins_path() . 'slider/ModelSlider.php' ) ;
function slider_install() {
  ModelSlider::newInstance()->import('slider/struct.sql') ;
  $aFields = array(
  's_internal_name' => 'slider'
  );
  osc_set_preference('backgroundcolor', '#FFFFFF','Slider','STRING');
  osc_set_preference('bordercolor', '#FFFFFF','Slider','STRING');
  osc_set_preference('borderwidth', '10','Slider','STRING');
  osc_set_preference('shadowcolor', 'transparent','Slider','STRING');
  osc_set_preference('width', '0','Slider','STRING');
  osc_set_preference('height', '0','Slider','STRING');
  osc_set_preference('caption', '0','Slider','BOOLEAN');
  osc_set_preference('link', '1','Slider','BOOLEAN');
  osc_set_preference('openin', '1','Slider','BOOLEAN');    
  osc_set_preference('auto', '1','Slider','BOOLEAN');
  osc_set_preference('pager', '1','Slider','BOOLEAN');
  osc_set_preference('navigation', '1','Slider','BOOLEAN');
  osc_set_preference('speed', '500','Slider','STRING');
}

function slider_uninstall() {
  try {
    Page::newInstance()->deleteByInternalName('slider');
    ModelSlider::newInstance()->uninstall();
  } catch (Exception $e) {
    echo $e->getMessage();
  }
  osc_delete_preference('backgroundcolor','Slider');
  osc_delete_preference('bordercolor','Slider');
  osc_delete_preference('borderwidth','Slider');
  osc_delete_preference('shadowcolor','Slider');
  osc_delete_preference('width','Slider');
  osc_delete_preference('height','Slider');
  osc_delete_preference('caption','Slider');
  osc_delete_preference('link','Slider');
  osc_delete_preference('openin','Slider');
  osc_delete_preference('auto','Slider');
  osc_delete_preference('pager','Slider');
  osc_delete_preference('navigation','Slider');
  osc_delete_preference('speed','Slider');
}

function slider_admin_menu() {
  echo '<h3><a href="#">Slider</a></h3>
  <ul>
    <li><a href="'.osc_admin_render_plugin_url("slider/create.php").'">&raquo; ' . __('Create', 'Slider') . '</a></li>
    <li><a href="'.osc_admin_render_plugin_url("slider/list.php").'">&raquo; ' . __('Manage', 'Slider') . '</a></li>
    <li><a href="'.osc_admin_render_plugin_url("slider/settings.php").'">&raquo; ' . __('Settings', 'Slider') . '</a></li>
    <li><a href="'.osc_admin_render_plugin_url("slider/help.php").'">&raquo; ' . __('F.A.Q. / Help', 'Slider') . '</a></li>
  </ul>';
}

function slider_admin_head() {
  ?>
  <style>
  .ico-Slider{
    background-image: url('<?php echo osc_base_url().'oc-content/plugins/slider/slider-thumb.png'; ?>') !important;
  }
  body.compact .ico-Slider{
    background-image: url('<?php echo osc_base_url().'oc-content/plugins/slider/slider-thumb.png'; ?>') !important;
    background-size: 35px, 35px;
  }
  </style>
  <?php
}

function getSliderBackgroundColor() {
  return(osc_get_preference('backgroundcolor', 'Slider')) ;
}

function getSliderBorderColor() {
  return(osc_get_preference('bordercolor', 'Slider')) ;
}

function getSliderBorderWidth() {
  return(osc_get_preference('borderwidth', 'Slider')) ;
}

function getSliderShadowColor() {
  return(osc_get_preference('shadowcolor', 'Slider')) ;
}  

function getSliderWidth() {
  return(osc_get_preference('width', 'Slider')) ;
}

function getSliderHeight() {
  return(osc_get_preference('height', 'Slider')) ;
}  

function getSliderCaption() {
  return(osc_get_preference('caption', 'Slider')) ;
}

function getSliderLink() {
  return(osc_get_preference('link', 'Slider')) ;
}

function getSliderOpenIn() {
  return(osc_get_preference('openin', 'Slider')) ;
}

function getSliderAuto() {
  return(osc_get_preference('auto', 'Slider')) ;
}  

function getSliderPager() {
  return(osc_get_preference('pager', 'Slider')) ;
}  

function getSliderNavigation() {
  return(osc_get_preference('navigation', 'Slider')) ;
}

function getSliderSpeed() {
  return(osc_get_preference('speed', 'Slider')) ;
}

function osc_slider() { 
  ?>
  <div id="sliders">  
    <div class="rslides_container">
      <ul class="rslides" id="slider">
        <?php $slides = ModelSlider::newInstance()->getSlider(); ?>
        <?php foreach($slides as $slide) { ?>
          <li>
            <?php if(getSliderLink() ==1){ ?>
              <a href="<?php echo $slide['link'];?>" <?php if(getSliderOpenIn() ==1) { echo 'target="_blank"'; }  ?>>
                <img src="<?php echo osc_base_url().'oc-content/plugins/slider/images/'.$slide['uniqname']; ?>" alt="<?php echo $slide['imagename']; ?>">
              </a>
            <?php } else {?>   
              <img src="<?php echo osc_base_url().'oc-content/plugins/slider/images/'.$slide['uniqname']; ?>" alt="<?php echo $slide['imagename']; ?>">
            <?php } ?>
            
            <?php if(getSliderCaption() ==1){ ?>
              <p class="caption"><?php echo $slide['caption']; ?></p>
            <?php } ?>
          </li>
        <?php } ?>
      </ul>
    </div>
      
    <ul class="side-pager" id="side-pager">
      <?php foreach($slides as $slide) { ?>
          <li><a href="#" class="side-row"><span><?php echo $slide['caption']; ?></span></a></li>
        <?php } ?>
        
    </ul>



  </div>
  <?php
}

function slider_head() {
  if(osc_is_home_page()) {
    //osc_enqueue_style('responsiveslides', osc_base_url().'oc-content/plugins/slider/responsiveslides.css');  
    osc_register_script('responsiveslides.min',osc_base_url().'oc-content/plugins/slider/responsiveslides.min.js');
    osc_enqueue_script('responsiveslides.min');
  }
}

function slider_js_Head() {
  if(osc_is_home_page()) {
  ?>
  <script type='text/javascript'>
  jQuery(document).ready(function() {
    $("#slider").responsiveSlides({
      auto: true,
      speed: 1200,      // Smooth fade transition speed (1.2 seconds)
      timeout: 5000,    // Duration between slides (5 seconds)
      pager: false,
      nav: false
    });
  });

  </script>
  <?php 
  }
}    

/** ADD HOOKS **/
osc_register_plugin(osc_plugin_path(__FILE__), 'slider_install');
osc_add_hook(osc_plugin_path(__FILE__)."_uninstall", 'slider_uninstall');

// Header
osc_add_hook('admin_header', 'slider_admin_head');
osc_add_hook('header', 'slider_head');
osc_add_hook('header', 'slider_js_Head', 10);

// Admin menu
if(osc_version() <= 241) {
  osc_add_hook('admin_menu', 'slider_admin_menu');
} else {
  osc_add_admin_menu_page(__('OSC Slider', 'Slider'), '#', 'Slider');
  osc_add_admin_submenu_page('Slider', __('Create','Slider'), osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/create.php'), 'Slider_Create');
  osc_add_admin_submenu_page('Slider', __('Manage','Slider'), osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/list.php'), 'Slider_Manage');
  osc_add_admin_submenu_page('Slider', __('Settings','Slider'), osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/settings.php'), 'Slider_Settings');
  osc_add_admin_submenu_page('Slider', __('F.A.Q. / Help','Slider'), osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/help.php'), 'Slider_Help');
}?>