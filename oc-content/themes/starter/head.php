<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title><?php echo meta_title(); ?></title>
<meta name="title" content="<?php echo osc_esc_html(meta_title()); ?>" />
<?php if( meta_description() != '' ) { ?><meta name="description" content="<?php echo osc_esc_html(meta_description()); ?>" /><?php } ?>
<?php if( osc_get_canonical() != '' ) { ?><link rel="canonical" href="<?php echo osc_get_canonical(); ?>"/><?php } ?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 01 Jul 1970 00:00:00 GMT" />
<?php if( !osc_is_search_page() )  { ?>
<meta name="robots" content="index, follow" />
<meta name="googlebot" content="index, follow" />
<?php } ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<?php 
  if(!function_exists('pwa_call_after_install')) {
    osc_current_web_theme_path('head-favicon.php');
  }

  $current_locale = osc_get_current_user_locale();
  $dimNormal = explode('x', osc_get_preference('dimNormal', 'osclass')); 

  if(osc_is_ad_page()) {
    osc_get_item_resources();
    $image_count = max(osc_count_item_resources(), 1); 
  } else {
    $image_count = 1; 
  }

  $ios = false;
  if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod')) {
    $ios = true;
  }
  
  if (!defined('JQUERY_VERSION') || JQUERY_VERSION == '1') {
    $jquery_version = '1';
  } else {
    $jquery_version = JQUERY_VERSION;
  }
?>

<script type="text/javascript">
  var starterCurrentLocale = '<?php echo osc_esc_js($current_locale['s_name']); ?>';
  var fileDefaultText = '<?php echo osc_esc_js(__('No file selected', 'starter')); ?>';
  var fileBtnText     = '<?php echo osc_esc_js(__('Choose File', 'starter')); ?>';
  var starterHeaderImg = '<?php echo osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/header-icons.png'; ?>';
  var baseDir = "<?php echo osc_base_url(); ?>";
  var baseSearchUrl = '<?php echo osc_search_url(array('page' => 'search')); ?>';
  var baseAjaxUrl = '<?php echo zara_ajax_url(); ?>';
  var baseAdminDir = '<?php echo osc_admin_base_url(true); ?>';
  var currentLocation = '<?php echo osc_get_osclass_location(); ?>';
  var currentSection = '<?php echo osc_get_osclass_section(); ?>';
  var adminLogged = '<?php echo osc_is_admin_user_logged_in() ? 1 : 0; ?>';
  var starterItemStick = '<?php echo (osc_get_preference('stick_item', 'starter_theme') == 1 ? '1' : '0'); ?>';
  var starterSearchStick = '<?php echo (osc_get_preference('stick_search', 'starter_theme') == 1 ? '1' : '0'); ?>';
  var starterLazy = '<?php echo ($ios ? '0' : '1'); ?>';
  var starterBxSlider = '<?php echo (osc_is_ad_page() ? '1' : '0'); ?>';
  var starterBxSliderSlides = '<?php echo min(osc_get_preference('item_images', 'starter_theme'), $image_count); ?>';
  var starterMasonry = '<?php echo osc_get_preference('force_aspect_image', 'osclass') == 1 ? 1 : 0; ?>';
  var dimNormalWidth = <?php echo $dimNormal[0]; ?>;
  var dimNormalHeight = <?php echo $dimNormal[1]; ?>;
  var searchRewrite = '/<?php echo osc_get_preference('rewrite_search_url', 'osclass'); ?>';
  var ajaxSearch = '<?php echo (osc_get_preference('search_ajax', 'starter_theme') == 1 ? '1' : '0'); ?>';
  var ajaxForms = '<?php echo (osc_get_preference('forms_ajax', 'starter_theme') == 1 ? '1' : '0'); ?>';
  var starterClickOpen = '<?php echo osc_esc_js(__('Click to open listing', 'starter')); ?>';
  var starterNoMatch = '<?php echo osc_esc_js(__('No listing match to your criteria', 'starter')); ?>';
  var jqueryVersion = '<?php echo $jquery_version; ?>';
</script>



<?php


osc_remove_style('font-open-sans');
osc_remove_style('open-sans');
osc_remove_style('responsiveslides');
osc_remove_style('font-awesome');
osc_remove_style('cookiecuttr-style');
osc_remove_style('fi_font-awesome');
osc_remove_style('font-awesome44');
osc_remove_style('font-awesome45');
osc_remove_style('font-awesome47');

osc_enqueue_style('style', osc_current_web_theme_url('css/style.css?v=' . time()));
osc_enqueue_style('responsive', osc_current_web_theme_url('css/responsive.css?v=' . time()));
osc_enqueue_style('google-font-nunito', 'https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400;1,600&display=swap');
echo '<style>body, html, button, input, select, textarea, h1, h2, h3, h4, h5, h6, .category-name, .hero-title, .hero-subtitle, .product-title { font-family: "Nunito", sans-serif !important; }</style>';
osc_enqueue_style('custom-home', osc_current_web_theme_url('css/custom-home.css') . '?v=' . time());
osc_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
echo '<style>
  html body #contact-wrap .input-box input,
  html body #contact-ins .input-box input,
  html body #contact_form input,
  html body #contact input,
  html body form#contact input,
  html body form#contact span.input-box input,
  html body form#contact input#subject,
  html body #body-contact input,
  html body #sendfriend input,
  html body #comment_form input,
  html body #sidebar-search input,
  html body #user-profile input,
  html body .add_item .input-box input,
  html body #location-picker input,
  html body #location-picker input.term,
  html body input#subject,
  html body input#yourSubject,
  html body input#yourName,
  html body input#yourEmail {
    padding-left: 48px !important;
  }

  html body #contact-wrap .input-box i,
  html body #contact-ins .input-box i,
  html body #contact_form .input-box i,
  html body form#contact span.input-box i,
  html body form#contact .input-box i,
  html body #body-contact .input-box i,
  html body #sendfriend .input-box i,
  html body #comment_form .input-box i,
  html body #sidebar-search .input-box i,
  html body #user-profile .input-box i,
  html body #location-picker:before,
  html body #location-picker:after,
  html body #location-picker i {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: absolute !important;
    left: 14px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 20px !important;
    height: 20px !important;
    font-size: 15px !important;
    color: #64748b !important;
    z-index: 10 !important;
    pointer-events: none !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  html body #header-line,
  html body #body-search #header-line,
  html body #sub-nav,
  html body #body-search #sub-nav,
  html body #sub-nav .inside,
  html body #body-search #sub-nav .inside,
  html body #sub-nav .control,
  html body #body-search #sub-nav .control,
  html body .search-header,
  html body #search-items,
  html body .content.list {
    border: none !important;
    border-bottom: none !important;
    border-top: none !important;
    box-shadow: none !important;
    outline: none !important;
  }

  /* Perfect Unified Price Input Bar Override */
  html body .price-wrap .price-label,
  html body .add_item .price-wrap .price-label,
  html body label.price-label {
    text-transform: capitalize !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #334155 !important;
    letter-spacing: normal !important;
    margin-bottom: 6px !important;
    display: block !important;
  }

  html body .price-input-group {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    width: 100% !important;
    height: 48px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    background: #f8fafc !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
    position: relative !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
  }

  /* BEAT SPECIFICITY WITH UNIFIED SINGLE BAR */
  html body .price-input-group input#price,
  html body .price-input-group input[name="price"],
  html body #body-item-post .price-input-group input#price,
  html body #body-item-edit .price-input-group input#price,
  html body .add_item .price-wrap input#price {
    flex: 1 !important;
    width: 100% !important;
    height: 48px !important;
    min-height: 48px !important;
    border: none !important;
    border-radius: 0 !important;
    background: transparent !important;
    padding: 0 14px !important;
    margin: 0 !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    text-align: left !important;
    box-sizing: border-box !important;
    outline: none !important;
    box-shadow: none !important;
  }

  html body .price-input-group .price-tag-icon {
    width: 44px !important;
    min-width: 44px !important;
    height: 48px !important;
    background: #f1f5f9 !important;
    border: none !important;
    border-right: 1px solid #cbd5e1 !important;
    border-radius: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #d97706 !important;
    font-size: 15px !important;
    flex-shrink: 0 !important;
    position: static !important;
    transform: none !important;
  }

  html body .price-input-group .price-currency-select {
    height: 48px !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    background: transparent !important;
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
  }

  html body .price-input-group .simple-currency {
    width: auto !important;
    height: 48px !important;
    min-height: 48px !important;
    border: none !important;
    border-left: 1px solid #cbd5e1 !important;
    border-radius: 0 !important;
    background: #f1f5f9 !important;
    margin: 0 !important;
    padding: 0 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    box-shadow: none !important;
  }

  html body .price-input-group .simple-currency .text,
  html body #body-item-post .price-input-group .simple-currency .text,
  html body #body-item-edit .price-input-group .simple-currency .text,
  html body .price-currency-single-box .simple-currency .text {
    width: 100% !important;
    height: 48px !important;
    min-height: 48px !important;
    line-height: 48px !important;
    padding: 0 38px 0 14px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    cursor: pointer !important;
    background: transparent !important;
    background-color: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
  }

  html body .price-input-group .simple-currency .text i.fa-angle-down,
  html body .price-currency-single-box .simple-currency .text i.fa-angle-down {
    position: absolute !important;
    right: 14px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #64748b !important;
    font-size: 14px !important;
    pointer-events: none !important;
  }
</style>';

if ($jquery_version == '1') {
  osc_enqueue_style('fancy', osc_current_web_theme_js_url('fancybox/jquery.fancybox.css'));
  osc_enqueue_style('jquery-ui', osc_current_web_theme_url('css/jquery-ui.min.css'));
} else {
  osc_enqueue_style('fancy', osc_assets_url('css/jquery.fancybox.min.css'));
  osc_enqueue_style('jquery-ui', osc_assets_url('js/jquery3/jquery-ui/jquery-ui.min.css'));
}

if(starter_is_rtl()) {
  osc_enqueue_style('rtl', osc_current_web_theme_url('css/rtl.css')  . '?v=' . date("Ymdhis"));
}


if(!osc_is_search_page() && !osc_is_home_page() && !osc_is_ad_page()) {
  osc_enqueue_style('tabs', osc_current_web_theme_url('css/tabs.css'));
}

if(osc_is_ad_page()) {
  osc_enqueue_style('bxslider', osc_current_web_theme_url('css/bxslider/jquery.bxslider.css'));
}


if(starter_ajax_image_upload() && (osc_is_publish_page() || osc_is_edit_page())) {
  osc_enqueue_style('fine-uploader-css', osc_assets_url('js/fineuploader/fineuploader.css'));
}


osc_register_script('jquery-drag', osc_current_web_theme_js_url('jquery.drag.min.js'), 'jquery');
osc_register_script('global', osc_current_web_theme_js_url('global.js?v=' . date("Ymdhis")));

if ($jquery_version == '1') {
  osc_register_script('fancybox', osc_current_web_theme_url('js/fancybox/jquery.fancybox.pack.js'), array('jquery'));
  osc_register_script('validate', osc_current_web_theme_js_url('jquery.validate.min.js'), array('jquery'));
} else {
  osc_register_script('validate', osc_assets_url('js/jquery.validate.min.js'), array('jquery'));
}

osc_register_script('date', osc_base_url() . 'oc-includes/osclass/assets/js/date.js');
//osc_register_script('priceFormat', osc_current_web_theme_js_url('jquery.priceFormat.js'));
//osc_register_script('bxslider', osc_current_web_theme_js_url('jquery.bxslider.js'));
osc_register_script('bxslider', 'https://cdnjs.cloudflare.com/ajax/libs/bxslider/4.2.15/jquery.bxslider.min.js');
osc_register_script('lazyload', osc_current_web_theme_js_url('jquery.lazyload.js'));
//osc_register_script('sticky', osc_current_web_theme_js_url('jquery.sticky-kit.min.js'));
osc_register_script('google-maps', 'https://maps.google.com/maps/api/js?key='.osc_get_preference('maps_key', 'google_maps'));
osc_register_script('images-loaded', osc_current_web_theme_js_url('jquery.imagesloaded.pkgd.min.js'));
osc_register_script('masonry', osc_current_web_theme_js_url('jquery.masonry.pkgd.min.js'));


osc_enqueue_script('jquery');
osc_enqueue_script('fancybox');
//osc_enqueue_script('priceFormat');
//osc_enqueue_script('sticky');


if(osc_get_preference('force_aspect_image', 'osclass') <> 1) {
  osc_enqueue_script('lazyload');
}

if(!osc_is_search_page() && !osc_is_home_page()) {
  osc_enqueue_script('validate');
}

if(osc_is_publish_page() || osc_is_edit_page() || osc_is_search_page()) {
  osc_enqueue_script('date');
}

if(osc_is_publish_page() || osc_is_edit_page()){
  osc_enqueue_script('date');
}


if( osc_is_ad_page() ) {
  osc_enqueue_script('bxslider');
}

if( osc_is_ad_page() && function_exists('google_maps_location') && osc_get_preference('include_maps_js', 'google_maps') != '0' ) {
  osc_enqueue_script('google-maps');
}

if( osc_get_preference('force_aspect_image', 'osclass') == 1 ) {
  osc_enqueue_script('masonry');
  osc_enqueue_script('images-loaded');
}

if(!osc_is_search_page() && !osc_is_home_page() && !osc_is_ad_page()) {
  osc_enqueue_script('tabber');
}

if(starter_ajax_image_upload() && (osc_is_publish_page() || osc_is_edit_page())) {
  osc_enqueue_script('jquery-fineuploader');
}


osc_enqueue_script('jquery-ui');
osc_enqueue_script('global');

?>


<?php 
  if(osc_get_preference('search_cookies', 'starter_theme') == 1) {
    starter_manage_cookies(); 
  }
?>

<?php osc_run_hook('header'); ?>