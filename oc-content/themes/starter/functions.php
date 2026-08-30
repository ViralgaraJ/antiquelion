<?php
define('STARTER_THEME_VERSION', '1.5.1');
define('USER_MENU_ICONS', 1);
define('THEME_COMPATIBLE_WITH_OSCLASS_HOOKS', 820);     // Compatibility with new hooks up to version


function starter_theme_info() {
  return array(
    'name'    => 'Osclass Starter Premium Theme',
    'version'   => '1.5.01',
    'description' => 'Mobile ready responsive premium theme for any kind of classifieds',
    'author_name' => 'MB Themes',
    'author_url'  => 'https://osclasspoint.com',
    'support_uri' => 'https://forums.osclasspoint.com/starter-osclass-responsive-theme/',
    'locations'   => array('header', 'footer')
  );
}



// RTL LANGUAGE SUPPORT
function starter_is_rtl() {
  $current_lang = strtolower(osc_current_user_locale());
  $locale = osc_get_current_user_locale();
  
  if(isset($locale['b_rtl']) && $locale['b_rtl'] == 1) {
    return true;
  } else if(in_array(osc_current_user_locale(), starter_rtl_languages())) {
    return true;
  } else {
    return false;
  }
}

// GET DIRECTION STRING
function starter_language_dir() {
  return starter_is_rtl() ? 'rtl' : 'ltr';
}

// LIST ALL RTL LANGUAGES/LOCALES FOR OLDER OSCLASS VERSIONS
function starter_rtl_languages() {
  $langs = array('ar_LB','ar_DZ','ar_BH','ar_EG','ar_IQ','ar_JO','ar_KW','ar_LY','ar_MA','ar_OM','ar_SA','ar_SY','fa_IR','ar_TN','ar_AE','ar_YE','ar_TD','ar_CO','ar_DJ','ar_ER','ar_MR','ar_SD');
  return $langs;
}

// OSCLASS 4.1 COMPATIBILITY
if(!function_exists('osc_item_show_phone')) {
  function osc_item_show_phone() {
    return true;
  }
}

if(!function_exists('osc_get_current_user_locations_native')) {
  function osc_get_current_user_locations_native() {
    return false;
  }
}

if(!function_exists('osc_location_native_name_selector')) {
  function osc_location_native_name_selector($array, $column = 's_name') {
    return @$array[$column];
  }
}


// DISABLE ERROR404 ON SEARCH PAGE WHEN NO ITEMS FOUND
function starter_disable_404() {
  if(osc_is_search_page() && osc_count_items() <= 0) {
    if(http_response_code() == 404) {
      http_response_code(200);
    }
  }
}

osc_add_hook('header', 'starter_disable_404');


// GET TRANSACTION NAME
function starter_transaction_name($id = '') {
  $transaction = false;

  if ($id == '') {
    return false;
  } else if ($id == 1) {
    $transaction = __('Sell', 'starter');
  } else if ($id == 2) {
    $transaction = __('Buy', 'starter');
  } else if ($id == 3) {
    $transaction = __('Rent', 'starter');
  } else if ($id == 4) {
    $transaction = __('Exchange', 'starter');
  }

  return $transaction;
}

// GET CONDITION NAME
function starter_condition_name($id = '') {
  $condition = false;

  if ($id == '') {
    return false;
  } else if ($id == 1) {
    $condition = __('New', 'starter');
  } else if ($id == 2) {
    $condition = __('Used', 'starter');
  }

  return $condition;
}


// CHECK IF PRICE ENABLED ON CATEGORY
function starter_check_category_price($id) {
  if(!osc_price_enabled_at_items()) {
    return false;
  } else if(!isset($id) || $id == '' || $id <= 0) {
    return true;
  } else {
    $category = Category::newInstance()->findByPrimaryKey($id);
    return ($category['b_price_enabled'] == 1 ? true : false);
  }
}



// FLAT CATEGORIES CONTENT (Publish)
function starter_flat_categories() {
  return '<div id="flat-cat-fancy" style="display:none;overflow:hidden;">' . starter_category_loop() . '</div>';
}


// SMART DATE
function starter_smart_date( $time ) {
  $time_diff = round(abs(time() - strtotime( $time )) / 60);
  $time_diff_h = floor($time_diff/60);
  $time_diff_d = floor($time_diff/1440);
  $time_diff_w = floor($time_diff/10080);
  $time_diff_m = floor($time_diff/43200);
  $time_diff_y = floor($time_diff/518400);


  if($time_diff < 2) {
    $time_diff_name = __('minute ago', 'starter');
  } else if ($time_diff < 60) {
    $time_diff_name = sprintf(__('%d minutes ago', 'starter'), $time_diff);
  } else if ($time_diff < 120) {
    $time_diff_name = sprintf(__('%d hour ago', 'starter'), $time_diff_h);
  } else if ($time_diff < 1440) {
    $time_diff_name = sprintf(__('%d hours ago', 'starter'), $time_diff_h);
  } else if ($time_diff < 2880) {
    $time_diff_name = sprintf(__('%d day ago', 'starter'), $time_diff_d);
  } else if ($time_diff < 10080) {
    $time_diff_name = sprintf(__('%d days ago', 'starter'), $time_diff_d);
  } else if ($time_diff < 20160) {
    $time_diff_name = sprintf(__('%d week ago', 'starter'), $time_diff_w);
  } else if ($time_diff < 43200) {
    $time_diff_name = sprintf(__('%d weeks ago', 'starter'), $time_diff_w);
  } else if ($time_diff < 86400) {
    $time_diff_name = sprintf(__('%d month ago', 'starter'), $time_diff_m);
  } else if ($time_diff < 518400) {
    $time_diff_name = sprintf(__('%d months ago', 'starter'), $time_diff_m);
  } else if ($time_diff < 1036800) {
    $time_diff_name = sprintf(__('%d year ago', 'starter'), $time_diff_y);
  } else {
    $time_diff_name = sprintf(__('%d years ago', 'starter'), $time_diff_y);
  }

  return $time_diff_name;
}



// CHECK IF ITEM MARKED AS SOLD-UNSOLD
function starter_check_sold(){
  $conn = DBConnectionClass::newInstance();
  $data = $conn->getOsclassDb();
  $comm = new DBCommandClass($data);

  $status = Params::getParam('markSold');
  $id = Params::getParam('itemId');
  $secret = Params::getParam('secret');
  $item_type = Params::getParam('itemType');

  if($status <> '' && $id <> '' && $id > 0) {
    $item = Item::newInstance()->findByPrimaryKey($id);

    if( $secret == $item['s_secret'] ) {
      //Item::newInstance()->dao->update(DB_TABLE_PREFIX.'t_item_starter', array('i_sold' => $status), array('fk_i_item_id' => $item['pk_i_id']));
      $comm->update(DB_TABLE_PREFIX.'t_item_starter', array('i_sold' => $status), array('fk_i_item_id' => $item['pk_i_id']));
 
      if (osc_rewrite_enabled()) {
        $item_type_url = '?itemType=' . $item_type;
      } else {
        $item_type_url = '&itemType=' . $item_type;
      }

      header('Location: ' . osc_user_list_items_url() . $item_type_url);
    }
  }
}

osc_add_hook('header', 'starter_check_sold');



// HELP FUNCTION TO GET CATEGORIES
function starter_category_loop( $parent_id = NULL, $parent_color = NULL ) {
  $parent_color = isset($parent_color) ? $parent_color : NULL;

  if(Params::getParam('sCategory') <> '') {
    $id = Params::getParam('sCategory');
  } else if (starter_get_session('sCategory') <> '' && (osc_is_publish_page() || osc_is_edit_page())) {
    $id = starter_get_session('sCategory');
  } else if (osc_item_category_id() <> '') {
    $id = osc_item_category_id();
  } else {
    $id = '';
  }


  if($parent_id <> '' && $parent_id > 0) {
    $categories = Category::newInstance()->findSubcategoriesEnabled( $parent_id );
  } else {
    $parent_id = 0;
    $categories = Category::newInstance()->findRootCategoriesEnabled();
  }

  $html = '<div class="flat-wrap' . ($parent_id == 0 ? ' root' : '') . '" data-parent-id="' . $parent_id . '">';
  $html .= '<div class="single info">' . __('Select category', 'starter') . ' ' . ($parent_id <> 0 ? '<span class="back tr1 round2"><i class="fa fa-angle-left"></i> ' . __('Back', 'starter') . '</span>' : '') . '</div>';

  foreach( $categories as $c ) {
    if( $parent_id == 0) {
      $parent_color = starter_get_cat_color( $c['pk_i_id'] );
      $icon = '<div class="parent-icon" style="background:' . starter_get_cat_color( $c['pk_i_id'] ) . ';">' . starter_get_cat_icon( $c['pk_i_id'] ) . '</div>';
    } else {
      $icon = '<div class="parent-icon children" style="background: ' . $parent_color . '">' . starter_get_cat_icon( $c['pk_i_id'] ) . '</div>';
    }
    
    $html .= '<div class="single tr1' . ($c['pk_i_id'] == $id ? ' selected' : '') . '" data-id="' . $c['pk_i_id'] . '"><span>' . $icon . $c['s_name'] . '</span></div>';

    $subcategories = Category::newInstance()->findSubcategoriesEnabled( $c['pk_i_id'] );
    if(isset($subcategories[0])) {
      $html .= starter_category_loop( $c['pk_i_id'], $parent_color );
    }
  }
  
  $html .= '</div>';
  return $html;
}



// FLAT CATEGORIES SELECT (Publish)
function starter_flat_category_select(){  
  $root = Category::newInstance()->findRootCategoriesEnabled();

  $html = '<div class="category-box tr1">';
  foreach( $root as $c ) {
    $html .= '<div class="option tr1" style="background:' . starter_get_cat_color( $c['pk_i_id'] ) . ';" title="' . osc_esc_html($c['s_name']) . '">';
    $html .= '<span class="cat-icon">' . starter_get_cat_icon( $c['pk_i_id'] ) . '</span>';
    $html .= '<span class="cat-name">' . osc_esc_html($c['s_name']) . '</span>';
    $html .= '</div>';
  }
 
  $html .= '</div>';
  return $html;
}



// GET CITY, REGION, COUNTRY FOR AJAX LOADER
function starter_ajax_city() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sCity') <> '') {
    return Params::getParam('sCity');
  } else if (isset($item['fk_i_city_id']) && $item['fk_i_city_id'] <> '') {
    return $item['fk_i_city_id'];
  } else if (isset($user['fk_i_city_id']) && $user['fk_i_city_id'] <> '') {
    return $user['fk_i_city_id'];
  }
}


function starter_ajax_region() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sRegion') <> '') {
    return Params::getParam('sRegion');
  } else if (isset($item['fk_i_region_id']) && $item['fk_i_region_id'] <> '') {
    return $item['fk_i_region_id'];
  } else if (isset($user['fk_i_region_id']) && $user['fk_i_region_id'] <> '') {
    return $user['fk_i_region_id'];
  }
}


function starter_ajax_country() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sCountry') <> '') {
    return Params::getParam('sCountry');
  } else if (isset($item['fk_c_country_code']) && $item['fk_c_country_code'] <> '') {
    return $item['fk_c_country_code'];
  } else if (isset($user['fk_c_country_code']) && $user['fk_c_country_code'] <> '') {
    return $user['fk_c_country_code'];
  }
}



// GET PROPER PROFILE IMAGE
function starter_profile_picture($user_id = 0, $size = 'small', $id_set = false) {
  if(!$id_set) {
    $user_id = ($user_id > 0 ? $user_id : osc_item_user_id());
    $user_id = ($user_id > 0 ? $user_id : osc_premium_user_id());
  }

  if($size == 'small') {
    $dimension = 36;
  } else if ($size == 'medium') {
    $dimension = 128;
  } else {
    $dimension = 256;
  }

  $img = '';

  if($user_id > 0 && function_exists('profile_picture_show')) {
    try {
      $conn = DBConnectionClass::newInstance()->getOsclassDb();
      $comm = new DBCommandClass($conn);
      $rs = $comm->query(sprintf("SELECT user_id, pic_ext FROM %st_profile_picture WHERE user_id = %d", DB_TABLE_PREFIX, (int)$user_id));
      if($rs && $row = $rs->row()) {
        if(!empty($row['pic_ext'])) {
          $path = osc_plugins_path() . 'profile_picture/images/';
          if(file_exists($path . 'profile' . (int)$user_id . $row['pic_ext'])) {
            $img = osc_base_url() . 'oc-content/plugins/profile_picture/images/profile' . (int)$user_id . $row['pic_ext'];
          }
        }
      }
    } catch(Exception $e) {
      $img = '';
    }
  }

  if($img == '') {
    $img = osc_current_web_theme_url('images/profile-' . $dimension . '.png');
  }

  return $img;
}



// USER ACCOUNT - MENU ELEMENTS
function starter_user_menu() {
  $sections = array('items', 'profile', 'logout');

  $c_active = Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), 'active');
  $c_pending = Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), 'pending_validate');
  $c_expired = Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), 'expired');

  if (osc_rewrite_enabled()) {
    $s_active = '?itemType=active';
    $s_pending = '?itemType=pending_validate';
    $s_expired = '?itemType=expired';
  } else {
    $s_active = '&itemType=active';
    $s_pending = '&itemType=pending_validate';
    $s_expired = '&itemType=expired';
  }

  if(isset($_SERVER['HTTPS'])) {
    $protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
  } else {
    $protocol = 'http';
  }

  $current_url =  $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

  $yes_active = 0;
  $yes_pending = 0;
  $yes_expired = 0;

  if (strpos($current_url, 'itemType=active') !== false) {
    $yes_active = 1;
  } else if (strpos($current_url, 'itemType=pending_validate') !== false) {
    $yes_pending = 1;
  } else if (strpos($current_url, 'itemType=expired') !== false) {
    $yes_expired = 1;
  }


  $options = array();
  $options[] = array('name' => __('Active', 'starter'), 'url' => osc_user_list_items_url() . $s_active, 'class' => 'opt_active_items', 'icon' => 'fa-check-square-o', 'section' => 1, 'count' => $c_active, 'is_active' => $yes_active);
  $options[] = array('name' => __('Not Validated', 'starter'), 'url' => osc_user_list_items_url() . $s_pending, 'class' => 'opt_not_validated_items', 'icon' => 'fa-stack-overflow', 'section' => 1, 'count' => $c_pending, 'is_active' => $yes_pending);
  $options[] = array('name' => __('Expired', 'starter'), 'url' => osc_user_list_items_url() . $s_expired, 'class' => 'opt_expired_items', 'icon' => 'fa-times-circle', 'section' => 1, 'count' => $c_expired, 'is_active' => $yes_expired);
  //$options[] = array('name' => __('Dashboard', 'starter'), 'url' => osc_user_dashboard_url(), 'class' => 'opt_dashboard', 'icon' => 'fa-dashboard', 'section' => 2);
  $options[] = array('name' => __('Alerts', 'starter'), 'url' => osc_user_alerts_url(), 'class' => 'opt_alerts', 'icon' => 'fa-bullhorn', 'section' => 2);
  $options[] = array('name' => __('My profile', 'starter'), 'url' => osc_user_profile_url(), 'class' => 'opt_account', 'icon' => 'fa-file-text-o', 'section' => 2);
  $options[] = array('name' => __('Public Profile', 'starter'), 'url' => osc_user_public_profile_url(), 'class' => 'opt_publicprofile', 'icon' => 'fa-picture-o', 'section' => 2);
  $options[] = array('name' => __('Logout', 'starter'), 'url' => osc_user_logout_url(), 'class' => 'opt_logout', 'icon' => 'fa-sign-out', 'section' => 3);

  $options = osc_apply_filter('user_menu_filter', $options);

  // MOBILE CONTROLER
  echo '<a href="#" class="btn btn-secondary is767" id="show-user-menu">' . __('Menu', 'starter') . '</a>';


  // SECTION 0 - USER NAME
  echo '<div class="um s0">';
  echo '<ul class="user_menu">';
  echo '<li class="uname">';
  echo '<strong>' . osc_logged_user_name() . '</strong>';
  echo '<span>' . (osc_logged_user_phone() <> '' ? osc_logged_user_phone() : __('No phone', 'starter')) . '</span>';
  echo '</li>';
  echo '</ul>';
  echo '</div>';

  // SECTION 1 - LISTINGS
  echo '<div class="um s1">';
  //echo '<div class="user-menu-header"><i class="fa fa-list"></i> ' . __('My listings', 'starter') . '</div>';
  echo '<ul class="user_menu">';

  foreach($options as $o) {
    if($o['section'] == 1) {
      $o['icon'] = isset($o['icon']) ? ($o['icon'] <> '' ? $o['icon'] : 'fa-dot-circle-o') : 'fa-dot-circle-o';

      if( isset($o['is_active']) && $o['is_active'] == 1 || $current_url == $o['url'] ) {
        $active_class =  ' active';
      } else {
        $active_class = '';
      }

      echo '<li class="' . $o['class'] . $active_class . '" ><a href="' . $o['url'] . '" >' . ($o['icon'] <> '' ? '<i class="fa ' . $o['icon'] . '"></i>' : '') . $o['name'] . '</a>' . (isset($o['count']) ? ' <span class="count">' . $o['count'] . '</span>' : '') . '</li>';
    }
  }

  osc_run_hook('user_menu_items');

  echo '</ul>';
  echo '</div>';


  // SECTION 2 - PROFILE & USER
  echo '<div class="um s2">';
  //echo '<div class="user-menu-header"><i class="fa fa-user"></i> ' . __('My account', 'starter') . '</div>';
  echo '<ul class="user_menu">';

  foreach($options as $o) {
    if($o['section'] == 2) {
      $active_class = ($current_url == $o['url'] ? ' active' : '');
      $o['icon'] = isset($o['icon']) ? ($o['icon'] <> '' ? $o['icon'] : 'fa-dot-circle-o') : 'fa-dot-circle-o';
      echo '<li class="' . $o['class'] . $active_class . '" ><a href="' . $o['url'] . '" >' . ($o['icon'] <> '' ? '<i class="fa ' . $o['icon'] . '"></i>' : '') . $o['name'] . '</a></li>';
    }
  }

  echo '<div class="hook-options">';
    osc_run_hook('user_menu');
  echo '</div>';

  echo '</ul>';
  echo '</div>';

  

  // SECTION 3 - LOGOUT
  echo '<div class="um s3 logout">';
  echo '<ul class="user_menu">';

  foreach($options as $o) {
    if($o['section'] == 3) {
      $o['icon'] = isset($o['icon']) ? ($o['icon'] <> '' ? $o['icon'] : 'fa-dot-circle-o') : 'fa-dot-circle-o';
      echo '<li class="' . $o['class'] . '" ><a href="' . $o['url'] . '" >' . ($o['icon'] <> '' ? '<i class="fa ' . $o['icon'] . '"></i>' : '') . $o['name'] . '</a></li>';
    }
  }

  echo '</ul>';
  echo '</div>';
}



// GET TERM NAME BASED ON COUNTRY, REGION & CITY
function starter_get_term($term = '', $country = '', $region = '', $city = ''){
  if( $term == '') {
    if( $city <> '' && is_numeric($city) ) {
      $city_info = City::newInstance()->findByPrimaryKey( $city );
      return (osc_location_native_name_selector($city_info, 's_name') <> '' ? osc_location_native_name_selector($city_info, 's_name') : $city);
    }
 
    if( $region <> '' && is_numeric($region) ) {
      $region_info = Region::newInstance()->findByPrimaryKey( $region );
      return (osc_location_native_name_selector($region_info, 's_name') <> '' ? osc_location_native_name_selector($region_info, 's_name') : $region);
    }

    if( $country <> '' && strlen($country) == 2 ) {
      $country_info = Country::newInstance()->findByCode( $country );
      return (osc_location_native_name_selector($country_info, 's_name') <> '' ? osc_location_native_name_selector($country_info, 's_name') : $country);
    }

    $array = array_filter(array($city, $region, $country));
    return @$array[0]; // if all fail, return first non-empty

  } else {
    return $term;
  }
}


// GET LOCATION FULL NAME BASED ON COUNTRY, REGION & CITY
function starter_get_full_loc($country = '', $region = '', $city = ''){
  if( $city <> '' && is_numeric($city) ) {
    $city_info = City::newInstance()->findByPrimaryKey( $city );
    $region_info = Region::newInstance()->findByPrimaryKey( $city_info['fk_i_region_id'] );
    $country_info = Country::newInstance()->findByCode( $city_info['fk_c_country_code'] );
    return osc_location_native_name_selector($city_info, 's_name') . ', ' . osc_location_native_name_selector($region_info, 's_name') . ', ' . osc_location_native_name_selector($country_info, 's_name');
  }

  if( $region <> '' && is_numeric($region) ) {
    $region_info = Region::newInstance()->findByPrimaryKey( $region );
    $country_info = Country::newInstance()->findByCode( $region_info['fk_c_country_code'] );

    return osc_location_native_name_selector($region_info, 's_name') . ', ' . osc_location_native_name_selector($country_info, 's_name');
  }

  if( $country <> '' && strlen($country) == 2 ) {
    $country_info = Country::newInstance()->findByCode( $country );
    return osc_location_native_name_selector($country_info, 's_name');
  }

  return '';
}



// ADD TRANSACTION AND CONDITION TO OC-ADMIN EDIT ITEM
function starter_extra_add_admin( $catId = null, $item_id = null ){
  $current_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  $admin_url = osc_admin_base_url();

  if (strpos($current_url, $admin_url) !== false) {
    if($item_id > 0) {
      $item = Item::newInstance()->findByPrimaryKey( $item_id );
      $item_extra = starter_item_extra( $item_id );

      echo '<div class="control-group">';
      echo '<label class="control-label" for="sTransaction">' . __('Transaction', 'starter') . '</label>';
      echo '<div class="controls">' . starter_simple_transaction(true, $item_id <> '' ? $item_id : false) . '</div>';
      echo '</div>';

      echo '<div class="control-group">';
      echo '<label class="control-label" for="sCondition">' . __('Condition', 'starter') . '</label>';
      echo '<div class="controls">' . starter_simple_condition(true, $item_id <> '' ? $item_id : false) . '</div>';
      echo '</div>';

      echo '<div class="control-group">';
      echo '<label class="control-label" for="sPhone">' . __('Phone', 'starter') . '</label>';
      echo '<div class="controls"><input type="text" name="sPhone" id="sPhone" value="' . $item_extra['s_phone'] . '" /></div>';
      echo '</div>';

      echo '<div class="control-group">';
      echo '<label class="control-label" for="sSold">' . __('Item Sold', 'starter') . '</label>';
      echo '<div class="controls"><input type="checkbox" name="sSold" id="sSold" ' . ($item_extra['i_sold'] == 1 ? 'checked' : '') . ' /></div>';
      echo '</div>';
    }
  }
}

osc_add_hook('item_form', 'starter_extra_add_admin');
osc_add_hook('item_edit', 'starter_extra_add_admin');



function starter_extra_edit( $item ) {
  $item['pk_i_id'] = isset($item['pk_i_id']) ? $item['pk_i_id'] : 0;
  $detail = ModelAisItem::newInstance()->findByItemId( $item['pk_i_id'] );

  if( isset($detail['fk_i_item_id']) ) {
    ModelAisItem::newInstance()->updateItemMeta( $item['pk_i_id'], Params::getParam('ais_meta_title'), Params::getParam('ais_meta_description') );
  } else {
    ModelAisItem::newInstance()->insertItemMeta( $item['pk_i_id'], Params::getParam('ais_meta_title'), Params::getParam('ais_meta_description') );
  } 
}


// SIMPLE SEARCH SORT
function starter_simple_sort() {
  $type = Params::getParam('sOrder');           // date - price
  $order = Params::getParam('iOrderType');      // asc - desc

  $orders = osc_list_orders();


  //$html  = '<input type="hidden" name="sOrder" id="sOrder" val="' . $type . '"/>';
  //$html  = '<input type="hidden" name="iOrderType" id="iOrderType" val="' . $order . '"/>';

  $html  = '<select class="orderSelect" id="orderSelect" name="orderSelect">';
  
  foreach($orders as $label => $spec) {

    $selected = '';
    if( $spec['sOrder'] == $type && $spec['iOrderType'] == $order ) {
      $selected = ' selected="selected"';
    }
 
    $html .= '<option' . $selected . ' data-type="' . $spec['sOrder'] . '" data-order="' . $spec['iOrderType'] . '">' . $label . '</option>';
  }

  $html .= '</select>';

  return $html;
}


// SIMPLE CATEGORY SELECT
function starter_simple_category( $select = false ) {
  $categories = Category::newInstance()->toTree();
  $current = Params::getParam('sCategory');
  $c_category = Category::newInstance()->findByPrimaryKey( Params::getParam('sCategory') );
  $root = Category::newInstance()->toRootTree( $current );
  $root = isset($root[0]) ? $root[0] : array('pk_i_id' => Params::getParam('sCategory'), 's_name' => (isset($c_category['s_name']) ? $c_category['s_name'] : ''));


  if(!$select) {

    $html  = '<div class="simple-cat simple-select">';
    $html .= '<input type="hidden" name="sCategory" class="input-hidden sCategory" value="' . Params::getParam('sCategory') . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . ($root['s_name'] <> '' ? $root['s_name'] : __('All categories', 'starter')) . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose one category', 'starter') . '</div>';
    $html .= '<div class="option bold' . ($root['pk_i_id'] == "" ? ' selected' : '') . '" data-id="">' . __('All categories', 'starter') . '</div>';

    foreach($categories as $c) {
      $html .= '<div class="option' . ($root['pk_i_id'] == $c['pk_i_id'] ? ' selected' : '') . '" data-id="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</span></div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {
    $html  = '<select class="sCategory" id="sCategory" name="sCategory">';
    $html .= '<option value="" ' . ($root['pk_i_id'] == "" ? ' selected="selected"' : '') . '>' . __('All categories', 'starter') . '</option>';

    foreach($categories as $c) {
      $html .= '<option ' . ($root['pk_i_id'] == $c['pk_i_id'] ? ' selected="selected"' : '') . ' value="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</option>';
    }

    $html .= '</select>';

    return $html;

  }
}



// SIMPLE SELLER TYPE SELECT
function starter_simple_seller( $select = false ) {
  $id = Params::getParam('sCompany');

  if($id == "") {
    $name = __('All sellers', 'starter');
  } else if ($id == "0") {
    $name = __('Personal', 'starter');
  } else if ($id == "1") {
    $name = __('Company', 'starter');
  } else {
    $name = __('All sellers', 'starter');
  }


  if( !$select ) {
    $html  = '<div class="simple-seller simple-select">';
    $html .= '<input type="hidden" name="sCompany" class="input-hidden" value="' . Params::getParam('sCompany') . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . $name . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose seller type', 'starter') . '</div>';
    $html .= '<div class="option bold' . ($id == "" ? ' selected' : '') . '" data-id="">' . __('All sellers', 'starter') . '</div>';

    $html .= '<div class="option' . ($id == "0" ? ' selected' : '') . '" data-id="0">' . __('Personal', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "1" ? ' selected' : '') . '" data-id="1">' . __('Company', 'starter') . '</span></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {

    $html  = '<select class="sCompany" id="sCompany" name="sCompany">';
    $html .= '<option value="" ' . ($id == "" ? ' selected="selected"' : '') . '>' . __('All sellers', 'starter') . '</option>';
    $html .= '<option value="0" ' . ($id == "0" ? ' selected="selected"' : '') . '>' . __('Personal', 'starter') . '</option>';
    $html .= '<option value="1" ' . ($id == "1" ? ' selected="selected"' : '') . '>' . __('Company', 'starter') . '</option>';
    $html .= '</select>';

    return $html;

  }
}



// SIMPLE TRANSACTION TYPE SELECT
function starter_simple_transaction( $select = false, $item_id = false ) {
  if((osc_is_publish_page() || osc_is_edit_page()) && starter_get_session('sTransaction') <> '') {
    $id = starter_get_session('sTransaction');
  } else {
    $id = Params::getParam('sTransaction');
  }

  if( $item_id == '' ) {
    $item_id = osc_item_id();
  }

  if( $item_id > 0 ) {
    //$item = Item::newInstance()->findByPrimaryKey( $item_id );
    //$id = $item['i_transaction'];

    $id = starter_item_extra( $item_id );
    $id = $id['i_transaction'];
  }

  // $id = $id <> '' ? $id : osc_item_field('i_transaction');

  if($id == "") {
    $name = __('Any transaction', 'starter');
  } else if ($id == 1) {
    $name = __('Sell', 'starter');
  } else if ($id == 2) {
    $name = __('Buy', 'starter');
  } else if ($id == 3) {
    $name = __('Rent', 'starter');
  } else if ($id == 4) {
    $name = __('Exchange', 'starter');
  } else {
    $name = __('Other', 'starter');
  }

  if( !$select ) {
    $html  = '<div class="simple-transaction simple-select">';
    $html .= '<input type="hidden" name="sTransaction" class="input-hidden" value="' . $id . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . $name . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose transaction type', 'starter') . '</div>';
    $html .= '<div class="option bold' . ($id == "" ? ' selected' : '') . '" data-id="">' . __('Any transaction', 'starter') . '</div>';

    $html .= '<div class="option' . ($id == "1" ? ' selected' : '') . '" data-id="1">' . __('Sell', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "2" ? ' selected' : '') . '" data-id="2">' . __('Buy', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "3" ? ' selected' : '') . '" data-id="3">' . __('Rent', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "4" ? ' selected' : '') . '" data-id="4">' . __('Exchange', 'starter') . '</span></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {

    $html  = '<select class="sTransaction" id="sTransaction" name="sTransaction">';
    $html .= '<option value="" ' . ($id == "" ? ' selected="selected"' : '') . '>' . __('Any transaction', 'starter') . '</option>';
    $html .= '<option value="1" ' . ($id == "1" ? ' selected="selected"' : '') . '>' . __('Sell', 'starter') . '</option>';
    $html .= '<option value="2" ' . ($id == "2" ? ' selected="selected"' : '') . '>' . __('Buy', 'starter') . '</option>';
    $html .= '<option value="3" ' . ($id == "3" ? ' selected="selected"' : '') . '>' . __('Rent', 'starter') . '</option>';
    $html .= '<option value="4" ' . ($id == "4" ? ' selected="selected"' : '') . '>' . __('Exchange', 'starter') . '</option>';
    $html .= '</select>';

    return $html;

  }
}



// SIMPLE OFFER TYPE SELECT
function starter_simple_condition( $select = false, $item_id = false ) {
  if((osc_is_publish_page() || osc_is_edit_page()) && starter_get_session('sCondition') <> '') {
    $id = starter_get_session('sCondition');
  } else {
    $id = Params::getParam('sCondition');
  }

  if( $item_id == '' ) {
    $item_id = osc_item_id();
  }

  if( $item_id > 0 ) {
    //$item = Item::newInstance()->findByPrimaryKey( $item_id );
    //$id = $item['i_condition'];

    $id = starter_item_extra( $item_id );
    $id = $id['i_condition'];
  }

  //$id = $id <> '' ? $id : osc_item_field('i_condition');


  if($id == "") {
    $name = __('Any condition', 'starter');
  } else if ($id == 1) {
    $name = __('New', 'starter');
  } else if ($id == 2) {
    $name = __('Used', 'starter');
  } else {
    $name = __('Other', 'starter');
  }


  if( !$select ) {
    $html  = '<div class="simple-condition simple-select">';
    $html .= '<input type="hidden" name="sCondition" class="input-hidden" value="' . $id . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . $name . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose condition of item', 'starter') . '</div>';
    $html .= '<div class="option bold' . ($id == "" ? ' selected' : '') . '" data-id="">' . __('Any condition', 'starter') . '</div>';

    $html .= '<div class="option' . ($id == "1" ? ' selected' : '') . '" data-id="1">' . __('New', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "2" ? ' selected' : '') . '" data-id="2">' . __('Used', 'starter') . '</span></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {

    $html  = '<select class="sCondition" id="sCondition" name="sCondition">';
    $html .= '<option value="" ' . ($id == "" ? ' selected="selected"' : '') . '>' . __('Any condition', 'starter') . '</option>';
    $html .= '<option value="1" ' . ($id == "1" ? ' selected="selected"' : '') . '>' . __('New', 'starter') . '</option>';
    $html .= '<option value="2" ' . ($id == "2" ? ' selected="selected"' : '') . '>' . __('Used', 'starter') . '</option>';
    $html .= '</select>';

    return $html;

  }
}



// SIMPLE CURRENCY SELECT (publish)
function starter_simple_currency() {
  $currencies = osc_get_currencies();
  $item = osc_item(); 

  if((osc_is_publish_page() || osc_is_edit_page()) && starter_get_session('currency') <> '') {
    $id = starter_get_session('currency');
  } else {
    $id = Params::getParam('currency');
  }

  $currency = $id <> '' ? $id : osc_get_preference('currency');

  if( isset($item['fk_c_currency_code']) ) {
    $default_key = $item['fk_c_currency_code'];
  } elseif( isset( $currency ) && $currency <> '' ) {
    $default_key = $currency;
  } else {
    $default_key = $currencies[0]['pk_c_code'];
  }

  if($default_key <> '') {
    $default_currency = Currency::newInstance()->findByPrimaryKey($default_key);
  } else {
    $default_currency = array('pk_c_code' => '', 's_description' => '');
  }

  $html  = '<div class="simple-currency simple-select">';
  $html .= '<input type="hidden" name="currency" id="currency" class="input-hidden" value="' . $default_currency['pk_c_code'] . '"/>';
  //$html .= '<span class="text round3 tr1"><span>' . $default_currency['pk_c_code'] . ' (' . $default_currency['s_description'] . ')</span> <i class="fa fa-angle-down"></i></span>';
  $html .= '<span class="text round3 tr1"><span>' . $default_currency['s_description'] . '</span> <i class="fa fa-angle-down"></i></span>';
  $html .= '<div class="list">';
  $html .= '<div class="option info">' . __('Currency', 'starter') . '</div>';

  foreach($currencies as $c) {
    //$html .= '<div class="option' . ($c['pk_c_code'] == $default_key ? ' selected' : '') . '" data-id="' . $c['pk_c_code'] . '">' . $c['pk_c_code'] . ' (' . $c['s_description'] . ')</span></div>';
    $html .= '<div class="option' . ($c['pk_c_code'] == $default_key ? ' selected' : '') . '" data-id="' . $c['pk_c_code'] . '">' . $c['s_description'] . '</span></div>';
  }

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}



// SIMPLE PRICE TYPE SELECT (publish)
function starter_simple_price_type() {
  $item = osc_item(); 

  // Item edit
  if( isset($item['i_price']) ) {
    if( $item['i_price'] > 0 ) {
      $default_key = 0;
      $default_name = '<i class="fa fa-pencil help"></i> ' . __('Enter price', 'starter');
    } else if( $item['i_price'] == 0 ) {
      $default_key = 1;
      $default_name = '<i class="fa fa-cut help"></i> ' . __('Free', 'starter');
    } else if( $item['i_price'] == '' ) {
      $default_key = 2;
      $default_name = '<i class="fa fa-phone help"></i> ' . __('Check with seller', 'starter');
    } 
  
  // Item publish
  } else {
    $default_key = 0;
    $default_name = '<i class="fa fa-pencil help"></i> ' . __('Enter price', 'starter');
  }


  $html  = '<div class="simple-price-type simple-select">';
  $html .= '<span class="text round3 tr1"><span>' . $default_name . '</span> <i class="fa fa-angle-down"></i></span>';
  $html .= '<div class="list">';
  $html .= '<div class="option info">' . __('Choose price type', 'starter') . '</div>';

  $html .= '<div class="option' . ($default_key == 0 ? ' selected' : '') . '" data-id="0"><i class="fa fa-pencil help"></i> ' . __('Enter price', 'starter') . '</span></div>';
  $html .= '<div class="option' . ($default_key == 1 ? ' selected' : '') . '" data-id="1"><i class="fa fa-cut help"></i> ' . __('Free', 'starter') . '</span></div>';
  $html .= '<div class="option' . ($default_key == 2 ? ' selected' : '') . '" data-id="2"><i class="fa fa-phone help"></i> ' . __('Check with seller', 'starter') . '</span></div>';

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}


// SIMPLE PERIOD SELECT (search only)
function starter_simple_period( $select = false ) {
  $id = Params::getParam('sPeriod');

  if($id == "") {
    $name = __('Any age', 'starter');
  } else if ($id == 1) {
    $name = __('1 day old', 'starter');
  } else if ($id == 2) {
    $name = __('2 days old', 'starter');
  } else if ($id == 7) {
    $name = __('1 week old', 'starter');
  } else if ($id == 14) {
    $name = __('2 weeks old', 'starter');
  } else if ($id == 31) {
    $name = __('1 month old', 'starter');
  } else {
    $name = __('Other age', 'starter');
  }


  if( !$select ) {
    $html  = '<div class="simple-period simple-select">';
    $html .= '<input type="hidden" name="sPeriod" class="input-hidden" value="' . $id . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . $name . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose period', 'starter') . '</div>';
    $html .= '<div class="option bold' . ($id == "" ? ' selected' : '') . '" data-id="">' . __('Any age', 'starter') . '</div>';

    $html .= '<div class="option' . ($id == "1" ? ' selected' : '') . '" data-id="1">' . __('1 day old', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "2" ? ' selected' : '') . '" data-id="2">' . __('2 days old', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "7" ? ' selected' : '') . '" data-id="7">' . __('1 week old', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "14" ? ' selected' : '') . '" data-id="14">' . __('2 weeks old', 'starter') . '</span></div>';
    $html .= '<div class="option' . ($id == "31" ? ' selected' : '') . '" data-id="31">' . __('1 month old', 'starter') . '</span></div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {

    $html  = '<select class="sPeriod" id="sPeriod" name="sPeriod">';
    $html .= '<option value="" ' . ($id == "" ? ' selected="selected"' : '') . '>' . __('Any age', 'starter') . '</option>';
    $html .= '<option value="1" ' . ($id == "1" ? ' selected="selected"' : '') . '>' . __('1 day old', 'starter') . '</option>';
    $html .= '<option value="2" ' . ($id == "2" ? ' selected="selected"' : '') . '>' . __('2 days old', 'starter') . '</option>';
    $html .= '<option value="3" ' . ($id == "7" ? ' selected="selected"' : '') . '>' . __('1 week old', 'starter') . '</option>';
    $html .= '<option value="4" ' . ($id == "14" ? ' selected="selected"' : '') . '>' . __('2 weeks old', 'starter') . '</option>';
    $html .= '<option value="4" ' . ($id == "31" ? ' selected="selected"' : '') . '>' . __('1 month old', 'starter') . '</option>';
    $html .= '</select>';

    return $html;

  }
}


// Cookies work
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

// Ajax clear cookies
if(isset($_GET['clearCookieSearch']) && $_GET['clearCookieSearch'] == 'done') {
  mb_drop_cookie('starter-sCategory');
  //mb_drop_cookie('starter-sPattern');
  mb_drop_cookie('starter-sPriceMin');
  mb_drop_cookie('starter-sPriceMax');
}




// FIND ROOT CATEGORY OF SELECTED
function starter_category_root( $category_id ) {
  $category = Category::newInstance()->findRootCategory( $category_id );
  return $category;
}


// CHECK IF THEME IS DEMO
function starter_is_demo() {
  if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}

// CREATE ITEM (in loop)
function starter_draw_item($c = NULL, $view = 'gallery', $premium = false, $class = false) {
  $filename = 'loop-single';

  if($premium){
    $filename .='-premium';
  }

  require WebThemes::newInstance()->getCurrentThemePath() . $filename . '.php';
}



// RANDOM LATEST ITEMS ON HOME PAGE
function starter_random_items($numItems = 10, $category = array(), $withPicture = false) {
  $max_items = osc_get_preference('maxLatestItems@home', 'osclass');

  if($max_items == '' or $max_items == 0) {
    $max_items = 24;
  }

  $numItems = $max_items;

  $withPicture = osc_get_preference('latest_picture', 'starter_theme');
  $randomOrder = osc_get_preference('latest_random', 'starter_theme');
  $premiums = osc_get_preference('latest_premium', 'starter_theme');
  $category = osc_get_preference('latest_category', 'starter_theme');



  $randSearch = Search::newInstance();
  $randSearch->dao->select(DB_TABLE_PREFIX.'t_item.* ');
  $randSearch->dao->from( DB_TABLE_PREFIX.'t_item use index (PRIMARY)' );

  // where
  $whe  = DB_TABLE_PREFIX.'t_item.b_active = 1 AND ';
  $whe .= DB_TABLE_PREFIX.'t_item.b_enabled = 1 AND ';
  $whe .= DB_TABLE_PREFIX.'t_item.b_spam = 0 AND ';

  if($premiums == 1) {
    $whe .= DB_TABLE_PREFIX.'t_item.b_premium = 1 AND ';
  }

  $whe .= '('.DB_TABLE_PREFIX.'t_item.b_premium = 1 || '.DB_TABLE_PREFIX.'t_item.dt_expiration >= \''. date('Y-m-d H:i:s').'\') ';

  if( $category <> '' and $category > 0 ) {
    $subcat_list = Category::newInstance()->findSubcategories( $category );
    $subcat_id = array();
    $subcat_id[] = $category;

    foreach( $subcat_list as $s) {
      $subcat_id[] = $s['pk_i_id'];
    }

    $listCategories = implode(', ', $subcat_id);

    $whe .= ' AND '.DB_TABLE_PREFIX.'t_item.fk_i_category_id IN ('.$listCategories.') ';
  }



  if($withPicture) {
    $prem_where = ' AND ' . $whe;

    $randSearch->dao->from( '(' . sprintf("select %st_item.pk_i_id FROM %st_item, %st_item_resource WHERE %st_item_resource.s_content_type LIKE '%%image%%' AND %st_item.pk_i_id = %st_item_resource.fk_i_item_id %s GROUP BY %st_item.pk_i_id ORDER BY %st_item.dt_pub_date DESC LIMIT %s", DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, $prem_where, DB_TABLE_PREFIX, DB_TABLE_PREFIX, $numItems) . ') AS LIM' );
  } else {
    $prem_where = ' WHERE ' . $whe;

    $randSearch->dao->from( '(' . sprintf("select %st_item.pk_i_id FROM %st_item %s ORDER BY %st_item.dt_pub_date DESC LIMIT %s", DB_TABLE_PREFIX, DB_TABLE_PREFIX, $prem_where, DB_TABLE_PREFIX, $numItems) . ') AS LIM' );
  }

  $randSearch->dao->where(DB_TABLE_PREFIX.'t_item.pk_i_id = LIM.pk_i_id');
  

  // group by & order & limit
  $randSearch->dao->groupBy(DB_TABLE_PREFIX.'t_item.pk_i_id');

  if(!$randomOrder) {
    $randSearch->dao->orderBy(DB_TABLE_PREFIX.'t_item.dt_pub_date DESC');
  } else {
    $randSearch->dao->orderBy('RAND()');
  }

  $randSearch->dao->limit($numItems);

  $rs = $randSearch->dao->get();

  if($rs === false){
    return array();
  }
  if( $rs->numRows() == 0 ) {
    return array();
  }

  $items = $rs->result();
  return Item::newInstance()->extendData($items);
}


function starter_manage_cookies() { 
  if(Params::getParam('page') == 'search') { $reset = true; } else { $reset = false; }
  if($reset) {
    if(Params::getParam('sCountry') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') {
      mb_set_cookie('starter-sCountry', Params::getParam('sCountry')); 
      mb_set_cookie('starter-sRegion', ''); 
      mb_set_cookie('starter-sCity', ''); 
    }

    if(Params::getParam('sRegion') <> '' or Params::getParam('cookieAction') == 'done'or Params::getParam('cookieActionMobile') == 'done') {
      if(is_numeric(Params::getParam('sRegion'))) {
        $reg = Region::newInstance()->findByPrimaryKey(Params::getParam('sRegion'));
      
        mb_set_cookie('starter-sCountry', strtoupper($reg['fk_c_country_code'])); 
        mb_set_cookie('starter-sRegion', osc_location_native_name_selector($reg, 's_name')); 
        mb_set_cookie('starter-sCity', ''); 
      } else {
        mb_set_cookie('starter-sRegion', Params::getParam('sRegion')); 
        mb_set_cookie('starter-sCity', ''); 
      }
    }

    if(Params::getParam('sCity') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') {
      if(is_numeric(Params::getParam('sCity'))) {
        $city = City::newInstance()->findByPrimaryKey(Params::getParam('sCity'));
        $reg = Region::newInstance()->findByPrimaryKey($city['fk_i_region_id']);
        
        mb_set_cookie('starter-sCountry', strtoupper($city['fk_c_country_code'])); 
        mb_set_cookie('starter-sRegion', osc_location_native_name_selector($reg, 's_name')); 
        mb_set_cookie('starter-sCity', osc_location_native_name_selector($city, 's_name')); 
      } else {
        mb_set_cookie('starter-sCity', Params::getParam('sCity')); 
      }
    }


    if(Params::getParam('sCategory') <> '' and Params::getParam('sCategory') <> 0 or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sCategory', Params::getParam('sCategory')); }
    if(Params::getParam('sCategory') == 0 and osc_is_search_page()) { mb_set_cookie('starter-sCategory', ''); }
    //if(Params::getParam('sPattern') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sPattern', Params::getParam('sPattern')); }
    //if(Params::getParam('sPriceMin') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sPriceMin', Params::getParam('sPriceMin')); }
    //if(Params::getParam('sPriceMax') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sPriceMax', Params::getParam('sPriceMax')); }
    //if(Params::getParam('sCompany') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done' or isset($_GET['sCompany'])) { mb_set_cookie('starter-sCompany', Params::getParam('sCompany')); }
    if(Params::getParam('sShowAs') <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sShowAs', Params::getParam('sShowAs')); }
  }

  $cat = osc_search_category_id();
  $cat = isset($cat[0]) ? $cat[0] : '';

  $reg = osc_search_region();
  $cit = osc_search_city();

  if($cat <> '' and $cat <> 0 or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sCategory', $cat); }
  if($reg <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sRegion', $reg); }
  if($cit <> '' or Params::getParam('cookieAction') == 'done' or Params::getParam('cookieActionMobile') == 'done') { mb_set_cookie('starter-sCity', $cit); }

  Params::setParam('sCountry', mb_get_cookie('starter-sCountry'));
  Params::setParam('sRegion', mb_get_cookie('starter-sRegion'));
  Params::setParam('sCity', mb_get_cookie('starter-sCity'));
  Params::setParam('sCategory', mb_get_cookie('starter-sCategory'));
  //Params::setParam('sPattern', mb_get_cookie('starter-sPattern'));
  //Params::setParam('sPriceMin', mb_get_cookie('starter-sPriceMin'));
  //Params::setParam('sPriceMax', mb_get_cookie('starter-sPriceMax'));
  //Params::setParam('sCompany', mb_get_cookie('starter-sCompany'));
  Params::setParam('sShowAs', mb_get_cookie('starter-sShowAs'));
}



// LOCATION FORMATER - USED ON SEARCH LIST
function starter_location_format($country = null, $region = null, $city = null) { 
  if($country <> '') {
    if(strlen($country) == 2) {
      $country_full = Country::newInstance()->findByCode($country);
    } else {
      $country_full = Country::newInstance()->findByName($country);
    }

    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'starter') . ' ' . $region . (osc_location_native_name_selector($country_full, 's_name') <> '' ? ' (' . osc_location_native_name_selector($country_full, 's_name') . ')' : '');
      } else {
        return $region . ' (' . osc_location_native_name_selector($country_full, 's_name') . ')';
      }
    } else { 
      if($city <> '') {
        return $city . ' ' . __('in', 'starter') . ' ' . osc_location_native_name_selector($country_full, 's_name');
      } else {
        return osc_location_native_name_selector($country_full, 's_name');
      }
    }
  } else {
    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'starter') . ' ' . $region;
      } else {
        return $region;
      }
    } else { 
      if($city <> '') {
        return $city;
      } else {
        return __('Location not entered', 'starter');
      }
    }
  }
}



function mb_filter_extend() {
  // SEARCH - ALL - INDIVIDUAL - COMPANY TYPE
  Search::newInstance()->addJoinTable( DB_TABLE_PREFIX.'t_item_starter.fk_i_item_id', DB_TABLE_PREFIX.'t_item_starter', DB_TABLE_PREFIX.'t_item.pk_i_id = '.DB_TABLE_PREFIX.'t_item_starter.fk_i_item_id', 'LEFT OUTER' ) ; // Mod


  // SEARCH - TRANSACTION
  if(Params::getParam('sTransaction') <> '') {
    Search::newInstance()->addConditions(sprintf("%st_item_starter.i_transaction = %d", DB_TABLE_PREFIX, Params::getParam('sTransaction')));
  }


  // SEARCH - CONDITION
  if(Params::getParam('sCondition') <> '') {
    Search::newInstance()->addConditions(sprintf("%st_item_starter.i_condition = %d", DB_TABLE_PREFIX, Params::getParam('sCondition')));
  }


  // SEARCH - PERIOD
  if(Params::getParam('sPeriod') <> '') {
    $date_from = date('Y-m-d', strtotime(' -' . Params::getParam('sPeriod') . ' day', time()));
    Search::newInstance()->addConditions(sprintf('cast(%st_item.dt_pub_date as date) > "%s"', DB_TABLE_PREFIX, $date_from));
  }


  // SEARCH - COMPANY
  if(Params::getParam('sCompany') <> '' and Params::getParam('sCompany') <> null) {
    Search::newInstance()->addJoinTable( DB_TABLE_PREFIX.'t_user.pk_i_id', DB_TABLE_PREFIX.'t_user', DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id', 'LEFT OUTER' ) ; // Mod

    if(Params::getParam('sCompany') == 1) {
      Search::newInstance()->addConditions(sprintf("%st_user.b_company = 1", DB_TABLE_PREFIX));
    } else {
      Search::newInstance()->addConditions(sprintf("coalesce(%st_user.b_company, 0) <> 1", DB_TABLE_PREFIX));
    }
  }
}

osc_add_hook('search_conditions', 'mb_filter_extend');



// GET ALL SEARCH PARAMETERS
function starter_search_params() {
 return array(
   'sCategory' => Params::getParam('sCategory'),
   'sCountry' => Params::getParam('sCountry'),
   'sRegion' => Params::getParam('sRegion'),
   'sCity' => Params::getParam('sCity'),
   //'sPriceMin' => Params::getParam('sPriceMin'),
   //'sPriceMin' => Params::getParam('sPriceMax'),
   'sCompany' => Params::getParam('sCompany'),
   'sShowAs' => Params::getParam('sShowAs'),
   'sOrder' => Params::getParam('sOrder'),
   'iOrderType' => Params::getParam('iOrderType')
  );
}




// CHECK IF AJAX IMAGE UPLOAD ON PUBLISH-EDIT PAGE CAN BE USED (from osclass 3.3)
function starter_ajax_image_upload() {
  if(class_exists('Scripts')) {
    return Scripts::newInstance()->registered['jquery-fineuploader'] && method_exists('ItemForm', 'ajax_photos');
  }
}


// CLOSE BUTTON RETRO-COMPATIBILITY
if( !OC_ADMIN ) {
  if( !function_exists('add_close_button_action') ) {
    function add_close_button_action(){
      echo '<script type="text/javascript">';
      echo '$(".flashmessage .ico-close").click(function(){';
      echo '$(this).parent().hide();';
      echo '});';
      echo '</script>';
    }
    osc_add_hook('footer', 'add_close_button_action') ;
  }
}


if(!function_exists('message_ok')) {
  function message_ok( $text ) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


// RETRO COMPATIBILITY IF FUNCTION DOES NOT EXIST
if(!function_exists('osc_count_countries')) {
  function osc_count_countries() {
    if ( !View::newInstance()->_exists('contries') ) {
      View::newInstance()->_exportVariableToView('countries', Search::newInstance()->listCountries( ">=", "country_name ASC" ) );
    }
    return View::newInstance()->_count('countries');
  }
}


// GET CURRENT LANGUAGE OF USER
function mb_get_current_user_locale() {
  return OSCLocale::newInstance()->findByPrimaryKey(osc_current_user_locale());
}



// FIX PRICE FORMAT OF PREMIUM ITEMS
function starter_premium_formated_price($price = null) {
  if($price == '') {
    $price = osc_premium_price();
  }

  return (string) starter_premium_format_price($price);
}

function starter_premium_format_price($price, $symbol = null) {
  if ($price === null) return osc_apply_filter ('item_price_null', __('Check with seller', 'starter') );
  if ($price == 0) return osc_apply_filter ('item_price_zero', __('Free', 'starter') );

  if($symbol==null) { $symbol = osc_premium_currency_symbol(); }

  $price = $price/1000000;

  $currencyFormat = osc_locale_currency_format();
  $currencyFormat = str_replace('{NUMBER}', number_format($price, osc_locale_num_dec(), osc_locale_dec_point(), osc_locale_thousands_sep()), $currencyFormat);
  $currencyFormat = str_replace('{CURRENCY}', $symbol, $currencyFormat);
  return osc_apply_filter('premium_price', $currencyFormat );
}


function starter_ajax_item_format_price($price, $symbol_code) {
  if ($price === null) return __('Check with seller', 'starter');
  if ($price == 0) return __('Free', 'starter');
  return round($price/1000000, 2) . ' ' . $symbol_code;
}





// THEME FUNCTIONS
function theme_starter_actions_admin() {
  if( Params::getParam('file') == 'oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php' ) {
    if( Params::getParam('donation') == 'successful' ) {
      osc_set_preference('donation', '1', 'starter_theme');
      osc_reset_preferences();
    }
  }


  if( Params::getParam('starter_general') == 'done' ) {
    $cat_icons = Params::getParam('cat_icons');
    $footerLink  = Params::getParam('footer_link');
    $defaultLogo = Params::getParam('default_logo');
    $def_view = Params::getParam('def_view');
    $format_sep = Params::getParam('format_sep');
    $format_cur = Params::getParam('format_cur');
    $latest_picture = Params::getParam('latest_picture');
    $latest_random = Params::getParam('latest_random');
    $latest_premium = Params::getParam('latest_premium');
    $premium_home = Params::getParam('premium_home');
    $premium_search_list = Params::getParam('premium_search_list');
    $premium_search_gallery = Params::getParam('premium_search_gallery');
    $search_cookies = Params::getParam('search_cookies');
    $search_ajax = Params::getParam('search_ajax');
    $forms_ajax = Params::getParam('forms_ajax');
    $remove_footer_link = Params::getParam('remove_footer_link');

    osc_set_preference('link_remover_order_id', Params::getParam('link_remover_order_id'), 'starter_theme');
    osc_set_preference('remove_footer_link', ($remove_footer_link ? '1' : '0'), 'starter_theme');
    osc_set_preference('cat_icons', ($cat_icons ? '1' : '0'), 'starter_theme');
    osc_set_preference('footer_link', ($footerLink ? '1' : '0'), 'starter_theme');
    osc_set_preference('default_logo', ($defaultLogo ? '1' : '0'), 'starter_theme');
    osc_set_preference('latest_picture', ($latest_picture ? '1' : '0'), 'starter_theme');
    osc_set_preference('latest_random', ($latest_random ? '1' : '0'), 'starter_theme');
    osc_set_preference('latest_premium', ($latest_premium ? '1' : '0'), 'starter_theme');
    osc_set_preference('latest_category', Params::getParam('latest_category'), 'starter_theme');
    osc_set_preference('item_images', Params::getParam('item_images'), 'starter_theme');
    osc_set_preference('publish_category', Params::getParam('publish_category'), 'starter_theme');
    osc_set_preference('def_view', Params::getParam('def_view'), 'starter_theme');
    osc_set_preference('format_sep', Params::getParam('format_sep'), 'starter_theme');
    osc_set_preference('format_cur', Params::getParam('format_cur'), 'starter_theme');
    osc_set_preference('premium_home', ($premium_home ? '1' : '0'), 'starter_theme');
    osc_set_preference('premium_search_list', ($premium_search_list ? '1' : '0'), 'starter_theme');
    osc_set_preference('premium_search_gallery', ($premium_search_gallery ? '1' : '0'), 'starter_theme');
    osc_set_preference('premium_home_count', Params::getParam('premium_home_count'), 'starter_theme');
    osc_set_preference('premium_search_list_count', Params::getParam('premium_search_list_count'), 'starter_theme');
    osc_set_preference('premium_search_gallery_count', Params::getParam('premium_search_gallery_count'), 'starter_theme');
    osc_set_preference('search_cookies', ($search_cookies ? '1' : '0'), 'starter_theme');
    osc_set_preference('search_ajax', ($search_ajax ? '1' : '0'), 'starter_theme');
    osc_set_preference('forms_ajax', ($forms_ajax ? '1' : '0'), 'starter_theme');
    osc_set_preference('post_required', Params::getParam('post_required'), 'starter_theme');
    osc_set_preference('post_extra_exclude', Params::getParam('post_extra_exclude'), 'starter_theme');

    osc_add_flash_ok_message(__('Theme settings updated correctly', 'starter'), 'admin');
    header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php')); exit;
  }


  if( Params::getParam('starter_banner') == 'done' ) {
    $theme_adsense = Params::getParam('theme_adsense');

    osc_set_preference('theme_adsense', ($theme_adsense ? '1' : '0'), 'starter_theme');

    foreach(starter_banner_list() as $b) {
      osc_set_preference($b['id'], stripslashes(Params::getParam($b['id'], false, false)), 'starter_theme');
    }

    osc_add_flash_ok_message(__('Banner settings updated correctly', 'starter'), 'admin');
    header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php')); exit;
  }


  switch( Params::getParam('action_specific') ) {
    case('upload_logo'):
      $package = Params::getFiles('logo');
      if( $package['error'] == UPLOAD_ERR_OK ) {
        if( move_uploaded_file($package['tmp_name'], WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
          osc_add_flash_ok_message(__('The logo image has been uploaded correctly', 'starter'), 'admin');
        } else {
          osc_add_flash_error_message(__("An error has occurred, please try again", 'starter'), 'admin');
        }
      } else {
        osc_add_flash_error_message(__("An error has occurred, please try again", 'starter'), 'admin');
      }
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php')); exit;
      break;

    case('remove'):
      if(file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
        @unlink( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" );
        osc_add_flash_ok_message(__('The logo image has been removed', 'starter'), 'admin');
      } else {
        osc_add_flash_error_message(__("Image not found", 'starter'), 'admin');
      }
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php')); exit;
      break;
  }
}

osc_add_hook('init_admin', 'theme_starter_actions_admin');
//osc_admin_menu_appearance(__('Header logo', 'starter'), osc_admin_render_theme_url('oc-content/themes/starter/admin/header.php'), 'header_starter');
//osc_admin_menu_appearance(__('Theme settings', 'starter'), osc_admin_render_theme_url('oc-content/themes/starter/admin/settings.php'), 'settings_starter');
AdminMenu::newInstance()->add_menu(__('Theme Setting', 'starter'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php'), 'starter_menu');
AdminMenu::newInstance()->add_submenu_divider( 'starter_menu', __('Theme Settings', 'starter'), 'starter_submenu');
AdminMenu::newInstance()->add_submenu( 'starter_menu', __('Header logo', 'starter'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php'), 'header_starter', 'administrator');
AdminMenu::newInstance()->add_submenu( 'starter_menu', __('Theme settings', 'starter'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'), 'settings_starter');


if( !function_exists('logo_header') ) {
  function logo_header() {
    $html = '<img border="0" alt="' . osc_esc_html(osc_page_title()) . '" src="' . osc_current_web_theme_url('images/logo.jpg') . '" />';
    if( file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
      return $html;
    } else if( osc_get_preference('default_logo', 'starter_theme') && (file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/default-logo.jpg")) ) {
      return '<img border="0" alt="' . osc_esc_html(osc_page_title()) . '" src="' . osc_current_web_theme_url('images/default-logo.jpg') . '" />';
    } else {
      return osc_page_title();
    }
  }
}


// INSTALL & UPDATE OPTIONS
if( !function_exists('starter_theme_install') ) {
  $themeInfo = starter_theme_info();

  function starter_theme_install() {
    osc_set_preference('version', STARTER_THEME_VERSION, 'starter_theme');
    osc_set_preference('date_format', 'mm/dd', 'starter_theme');
    osc_set_preference('cat_icons', '1', 'starter_theme');
    osc_set_preference('footer_link', '1', 'starter_theme');
    osc_set_preference('donation', '0', 'starter_theme');
    osc_set_preference('default_logo', '1', 'starter_theme');
    osc_set_preference('theme_adsense', '1', 'starter_theme');
    osc_set_preference('def_view', '0', 'starter_theme');
    osc_set_preference('format_sep', '', 'starter_theme');
    osc_set_preference('format_cur', '0', 'starter_theme');
    osc_set_preference('latest_picture', '0', 'starter_theme');
    osc_set_preference('latest_random', '1', 'starter_theme');
    osc_set_preference('latest_premium', '0', 'starter_theme');
    osc_set_preference('latest_category', '', 'starter_theme');
    osc_set_preference('item_images', '2', 'starter_theme');
    osc_set_preference('publish_category', '1', 'starter_theme');
    osc_set_preference('premium_home', '1', 'starter_theme');
    osc_set_preference('premium_search_list', '0', 'starter_theme');
    osc_set_preference('premium_search_gallery', '0', 'starter_theme');
    osc_set_preference('premium_home_count', '5', 'starter_theme');
    osc_set_preference('premium_search_list_count', '5', 'starter_theme');
    osc_set_preference('premium_search_gallery_count', '5', 'starter_theme');
    osc_set_preference('search_cookies', '0', 'starter_theme');
    osc_set_preference('search_ajax', '1', 'starter_theme');
    osc_set_preference('forms_ajax', '1', 'starter_theme');
    osc_set_preference('post_required', 'country,region,name,phone', 'starter_theme');
    osc_set_preference('post_extra_exclude', '', 'starter_theme');


    /* Banners */
    if(function_exists('starter_banner_list')) {
      foreach(starter_banner_list() as $b) {
        osc_set_preference($b['id'], '', 'starter_theme');
      }
    }

    osc_reset_preferences();

    starter_add_item_fields();  // add s_phone column to database if does not exists
  }
}


if(!function_exists('check_install_starter_theme')) {
  function check_install_starter_theme() {
    $current_version = osc_get_preference('version', 'starter_theme');
    //check if current version is installed or need an update<
    if( !$current_version ) {
      starter_theme_install();
    }
  }
}

check_install_starter_theme();



// WHEN NEW LISTING IS CREATED, ADD IT TO STARTER EXTRA TABLE
function starter_new_item_extra($item) {
  $conn = DBConnectionClass::newInstance();
  $data = $conn->getOsclassDb();
  $comm = new DBCommandClass($data);
  $db_prefix = DB_TABLE_PREFIX;

  $query = "INSERT INTO {$db_prefix}t_item_starter (fk_i_item_id) VALUES ({$item['pk_i_id']})";
  $result = $comm->query($query);
}

osc_add_hook('posted_item', 'starter_new_item_extra');


// WHEN NEW CATEGORY IS CREATED, ADD IT TO STARTER EXTRA TABLE
function starter_new_category_extra() {

  $conn = DBConnectionClass::newInstance();
  $data = $conn->getOsclassDb();
  $comm = new DBCommandClass($data);
  $db_prefix = DB_TABLE_PREFIX;

  $query = "INSERT INTO {$db_prefix}t_category_starter (fk_i_category_id) 
            SELECT c.pk_i_id FROM {$db_prefix}t_category c WHERE c.pk_i_id NOT IN (SELECT d.fk_i_category_id FROM {$db_prefix}t_category_starter d)";
  $result = $comm->query($query);
}

osc_add_hook('footer', 'starter_new_category_extra');



// USER MENU FIX
function starter_user_menu_fix() {
  $user = User::newInstance()->findByPrimaryKey( osc_logged_user_id() );
  View::newInstance()->_exportVariableToView('user', $user);
}

osc_add_hook('header', 'starter_user_menu_fix');


// ADD COLOR COLUMN INTO CATEGORY TABLE
// NOT USED ANYMORE
//function starter_add_color_col() {
//  $conn = DBConnectionClass::newInstance();
//  $data = $conn->getOsclassDb();
//  $comm = new DBCommandClass($data);
//  $db_prefix = DB_TABLE_PREFIX;

//  $query = "ALTER TABLE {$db_prefix}t_category ADD s_color VARCHAR(50);";
//  $result = $comm->query($query);
//}


// ADD THEME COLUMNS INTO ITEM TABLE
function starter_add_item_fields() {
  $conn = DBConnectionClass::newInstance();
  $data = $conn->getOsclassDb();
  $comm = new DBCommandClass($data);
  $db_prefix = DB_TABLE_PREFIX;
  $struct = osc_base_path() . 'oc-content/themes/starter/model/struct.sql';
  $sql = file_get_contents($struct);


  //$query = "ALTER TABLE {$db_prefix}t_item ADD s_phone VARCHAR(100);";
  //$result = $comm->query($query);

  //$query = "ALTER TABLE {$db_prefix}t_item ADD i_condition VARCHAR(100);";
  //$result = $comm->query($query);

  //$query = "ALTER TABLE {$db_prefix}t_item ADD i_transaction VARCHAR(100);";
  //$result = $comm->query($query);

  //$query = "ALTER TABLE {$db_prefix}t_item ADD i_sold VARCHAR(100);";
  //$result = $comm->query($query);

  //$query = "ALTER TABLE {$db_prefix}t_item_stats ADD i_num_phone_clicks INT(10) DEFAULT 0;";
  //$result = $comm->query($query);


  // CREATE NEW TABLES IF DOES NOT EXISTS
  if(!$comm->importSQL($sql)){ 
    throw new Exception(__('Error creating tables for Starter theme. Check if these tables exists, if yes, drop them: t_item_starter, t_category_starter, t_item_stats_starter.', 'starter'));
  }

  // CREATE INDEXES MANUALLY, IF THERE IS PROBLEM
  /*
  $query = "ALTER TABLE {$db_prefix}t_item_starter ADD FOREIGN KEY (fk_i_item_id) REFERENCES {$db_prefix}t_item (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE";
  $result = $comm->query($query);

  $query = "ALTER TABLE {$db_prefix}t_item_stats_starter ADD FOREIGN KEY (fk_i_item_id) REFERENCES {$db_prefix}t_item (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE";
  $result = $comm->query($query);

  $query = "ALTER TABLE {$db_prefix}t_category_starter ADD FOREIGN KEY (fk_i_category_id) REFERENCES {$db_prefix}t_category (pk_i_id) ON DELETE CASCADE ON UPDATE CASCADE";
  $result = $comm->query($query);
  */
}



// UPDATE THEME COLS ON ITEM POST-EDIT
function starter_update_fields( $item ) {
  //$sql = sprintf("UPDATE %s SET s_phone = '%s'  WHERE pk_i_id = %d", DB_TABLE_PREFIX.'t_item', '1234', 1);
  //Item::newInstance()->dao->query($sql);

  if(Params::existParam('sSold')) {
    $fields = array(
      's_phone' => Params::getParam('sPhone'),
      'i_condition' => Params::getParam('sCondition'),
      'i_transaction' => Params::getParam('sTransaction'),
      'i_sold' => (Params::getParam('sSold') == 'on' ? 1 : Params::getParam('sSold'))
    );
  } else {
    $fields = array(
      's_phone' => Params::getParam('sPhone'),
      'i_condition' => Params::getParam('sCondition'),
      'i_transaction' => Params::getParam('sTransaction')
    );
  }

  //Item::newInstance()->dao->update(DB_TABLE_PREFIX.'t_item', $fields, array('pk_i_id' => $item['pk_i_id']));
  Item::newInstance()->dao->update(DB_TABLE_PREFIX.'t_item_starter', $fields, array('fk_i_item_id' => $item['pk_i_id']));
}

osc_add_hook('posted_item', 'starter_update_fields');
osc_add_hook('edited_item', 'starter_update_fields');


// GET STARTER ITEM EXTRA VALUES
function starter_item_extra($item_id) {
  if($item_id > 0) {
    $db_prefix = DB_TABLE_PREFIX;

    $query = "SELECT * FROM {$db_prefix}t_item_starter s WHERE fk_i_item_id = " . $item_id . ";";
    $result = Item::newInstance()->dao->query($query);
    
    if($result) { 
      $prepare = $result->row();
      
      if(isset($prepare['fk_i_item_id']) && $prepare['fk_i_item_id'] > 0) {
        return $prepare;
      }
    }
  }
  
  return array(
    'fk_i_item_id' => $item_id,
    's_phone' => '',
    'i_condition' => null,
    'i_transaction' => null,
    'i_sold' => null
  );    
}


// GET STARTER CATEGORY EXTRA VALUES
function starter_category_extra($category_id) {
  if($category_id > 0) {
    $db_prefix = DB_TABLE_PREFIX;

    $query = "SELECT * FROM {$db_prefix}t_category_starter s WHERE fk_i_category_id = " . $category_id . ";";
    $result = Category::newInstance()->dao->query( $query );
    if( !$result ) { 
      $prepare = array();
      return false;
    } else {
      $prepare = $result->row();
      return $prepare;
    }
  }
}



// KEEP VALUES OF INPUTS ON RELOAD
function starter_post_preserve() {
  Session::newInstance()->_setForm('sPhone', Params::getParam('sPhone'));
  Session::newInstance()->_setForm('term', Params::getParam('term'));
  Session::newInstance()->_setForm('zip', Params::getParam('zip'));
  Session::newInstance()->_setForm('sCondition', Params::getParam('sCondition'));
  Session::newInstance()->_setForm('sTransaction', Params::getParam('sTransaction'));

  Session::newInstance()->_keepForm('sPhone');
  Session::newInstance()->_keepForm('term');
  Session::newInstance()->_keepForm('zip');
  Session::newInstance()->_keepForm('sCondition');
  Session::newInstance()->_keepForm('sTransaction');
}

osc_add_hook('pre_item_post', 'starter_post_preserve');


// DROP VALUES OF INPUTS ON SUCCESSFUL PUBLISH
function starter_post_drop() {
  Session::newInstance()->_dropKeepForm('sPhone');
  Session::newInstance()->_dropKeepForm('term');
  Session::newInstance()->_dropKeepForm('zip');
  Session::newInstance()->_dropKeepForm('sCondition');
  Session::newInstance()->_dropKeepForm('sTransaction');

  Session::newInstance()->_clearVariables();
}

osc_add_hook('posted_item', 'starter_post_drop');



// GET VALUES FROM SESSION ON PUBLISH PAGE
function starter_get_session( $param ) {
  return Session::newInstance()->_getForm($param);
}


// COMPATIBILITY FUNCTIONS
if(!function_exists('osc_is_register_page')) {
  function osc_is_register_page() {
    return osc_is_current_page("register", "register");
  }
}

if(!function_exists('osc_is_edit_page')) {
  function osc_is_edit_page() {
    return osc_is_current_page('item', 'item_edit');
  }
}


// DEFAULT ICONS ARRAY
function starter_default_icons() {
  $icons = array(
    1 => 'fa-newspaper-o', 2 => 'fa-motorcycle', 3 => 'fa-graduation-cap', 4 => 'fa-home', 5 => 'fa-wrench', 6 => 'fa-users', 7 => 'fa-venus-mars', 8 => 'fa-briefcase', 9 => 'fa-paw', 
    10 => 'fa-paint-brush', 11 => 'fa-exchange', 12 => 'fa-newspaper-o', 13 => 'fa-camera', 14 => 'fa-tablet', 15 => 'fa-mobile', 16 => 'fa-shopping-bag', 
    17 => 'fa-laptop', 18 => 'fa-mobile', 19 => 'fa-lightbulb-o', 20 => 'fa-soccer-ball-o', 21 => 'fa-s15', 22 => 'fa-medkit', 23 => 'fa-home', 24 => 'fa-clock-o', 
    25 => 'fa-microphone', 26 => 'fa-bicycle', 27 => 'fa-ticket', 28 => 'fa-plane', 29 => 'fa-television', 30 => 'fa-ellipsis-h', 31 => 'fa-car', 32 => 'fa-gears', 
    33 => 'fa-motorcycle', 34 => 'fa-ship', 35 => 'fa-bus', 36 => 'fa-truck', 37 => 'fa-ellipsis-h', 38 => 'fa-laptop', 39 => 'fa-language', 40 => 'fa-microphone', 
    41 => 'fa-graduation-cap', 42 => 'fa-ellipsis-h', 43 => 'fa-building-o', 44 => 'fa-building', 45 => 'fa-refresh', 46 => 'fa-exchange', 47 => 'fa-plane', 48 => 'fa-car', 
    49 => 'fa-window-minimize', 50 => 'fa-suitcase', 51 => 'fa-shopping-basket', 52 => 'fa-child', 53 => 'fa-microphone', 54 => 'fa-laptop', 55 => 'fa-music', 
    56 => 'fa-stethoscope', 57 => 'fa-star', 58 => 'fa-home', 59 => 'fa-truck', 60 => 'fa-wrench', 61 => 'fa-pencil', 62 => 'fa-ellipsis-h', 63 => 'fa-refresh', 
    64 => 'fa-sun-o', 65 => 'fa-star', 66 => 'fa-music', 67 => 'fa-wheelchair', 68 => 'fa-key', 69 => 'fa-venus', 70 => 'fa-mars', 71 => 'fa-mars-double', 
    72 => 'fa-venus-double', 73 => 'fa-genderless', 74 => 'fa-phone', 75 => 'fa-money', 76 => 'fa-television', 77 => 'fa-paint-brush', 78 => 'fa-book', 79 => 'fa-headphones', 
    80 => 'fa-graduation-cap', 81 => 'fa-paper-plane-o', 82 => 'fa-medkit', 83 => 'fa-users', 84 => 'fa-internet-explorer', 85 => 'fa-gavel', 86 => 'fa-wrench', 
    87 => 'fa-industry', 88 => 'fa-newspaper-o', 89 => 'fa-wheelchair', 90 => 'fa-home', 91 => 'fa-spoon', 92 => 'fa-exchange', 93 => 'fa-gavel', 94 => 'fa-microchip', 
    95 => 'fa-ellipsis-h', 999 => 'fa-newspaper-o'
  );

  return $icons;
}


function starter_default_colors() {
  $colors = array(1 => '#F44336', 2 => '#00BCD4', 3 => '#009688', 4 => '#FDE74C', 5 => '#8BC34A', 6 => '#D32F2F', 7 => '#2196F3', 8 => '#777', 999 => '#F44336');
  return $colors;
}


function starter_get_cat_icon( $id, $string = false ) {
  $category = Category::newInstance()->findByPrimaryKey( $id );
  $category_extra = starter_category_extra($id);
  $default_icons = starter_default_icons();

  $icon_code = '';
  if ($category && isset($category['s_name']) && $category['s_name'] != '') {
    $cat_name = $category['s_name'];
    if (stripos($cat_name, 'Artifact') !== false) {
      $icon_code = 'fa-university';
    } elseif (stripos($cat_name, 'Decor') !== false || stripos($cat_name, 'Home') !== false) {
      $icon_code = 'fa-camera-retro';
    } elseif (stripos($cat_name, 'Book') !== false || stripos($cat_name, 'Manuscript') !== false) {
      $icon_code = 'fa-book';
    } elseif (stripos($cat_name, 'Collect') !== false) {
      $icon_code = 'fa-diamond';
    } elseif (stripos($cat_name, 'Furniture') !== false) {
      $icon_code = 'fa-bed';
    }
  }

  if ($icon_code == '') {
    if(isset($category_extra['s_icon']) && $category_extra['s_icon'] <> '') {
      $icon_code = $category_extra['s_icon'];
    } else {
      $cat_pk = isset($category['pk_i_id']) ? $category['pk_i_id'] : $id;
      if(isset($default_icons[$cat_pk]) && $default_icons[$cat_pk] <> '') {
        $icon_code = $default_icons[$cat_pk];
      } else {
        $icon_code = $default_icons[999];
      }
    }
  }

  if(osc_get_preference('cat_icons', 'starter_theme') == 1) { 
    if($string) {
      return $icon_code;
    } else {
      return '<i class="fa ' . $icon_code . '"></i>';
    }
  } else {
    $cat_pk = isset($category['pk_i_id']) ? $category['pk_i_id'] : $id;
    if($string) {
      return osc_current_web_theme_url() . 'images/small_cat/' . $cat_pk . '.png';
    } else {
      return '<img src="' . osc_current_web_theme_url() . 'images/small_cat/' . $cat_pk . '.png" />';
    }
  }
}


function starter_get_cat_color( $id ) {
  $category = Category::newInstance()->findByPrimaryKey( $id );
  $category_extra = starter_category_extra($id);
  $default_colors = starter_default_colors();

  if($category_extra['s_color'] <> '') {
    $color_code = $category_extra['s_color'];                        
  } else {
    if(isset($default_colors[$category['pk_i_id']]) && $default_colors[$category['pk_i_id']] <> '') {
      $color_code = $default_colors[$category['pk_i_id']];
    } else {
      $color_code = $default_colors[999];
    }
  }

  return $color_code;
}



// INCREASE PHONE CLICK VIEWS
function starter_increase_clicks($itemId, $itemUserId = NULL) {
  if($itemId > 0) {
    if($itemUserId == '' || $itemUserId == 0 || ($itemUserId <> '' && $itemUserId > 0 && $itemUserId <> osc_logged_user_id())) {
      $db_prefix = DB_TABLE_PREFIX;
      //$query = "INSERT INTO {$db_prefix}t_item_stats_starter (fk_i_item_id, dt_date, i_num_phone_clicks) VALUES ({$itemId}, \"{date('Y-m-d')}\", 1) ON DUPLICATE KEY UPDATE  i_num_phone_clicks = i_num_phone_clicks + 1";
      $query = 'INSERT INTO ' . $db_prefix . 't_item_stats_starter (fk_i_item_id, dt_date, i_num_phone_clicks) VALUES (' . $itemId . ', "' . date('Y-m-d') . '", 1) ON DUPLICATE KEY UPDATE  i_num_phone_clicks = i_num_phone_clicks + 1';
      return ItemStats::newInstance()->dao->query($query);
    }
  }
}


// FIX ADMIN MENU LIST WITH THEME OPTIONS
function starter_admin_menu_fix(){
  echo '<style>' . PHP_EOL;
  echo 'body.compact #starter_menu .ico-starter_menu {bottom:-6px!important;width:50px!important;height:50px!important;margin:0!important;background:#fff url(' . osc_base_url() . 'oc-content/themes/starter/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo 'body.compact #starter_menu .ico-starter_menu:hover {background-color:rgba(255,255,255,0.95)!important;}' . PHP_EOL;
  echo 'body.compact #menu_starter_menu > h3 {bottom:0!important;}' . PHP_EOL;
  echo 'body.compact #menu_starter_menu > ul {border-top-left-radius:0px!important;margin-top:1px!important;}' . PHP_EOL;
  echo 'body.compact #menu_starter_menu.current:after {content:"";display:block;width:6px;height:6px;border-radius:10px;box-shadow:1px 1px 3px rgba(0,0,0,0.1);position:absolute;left:3px;bottom:3px;background:#03a9f4}' . PHP_EOL;
  echo 'body:not(.compact) #starter_menu .ico-starter_menu {background:transparent url(' . osc_base_url() . 'oc-content/themes/starter/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo '</style>' . PHP_EOL;
}

osc_add_hook('admin_header', 'starter_admin_menu_fix');



// BACKWARD COMPATIBILITY FUNCTIONS
if(!function_exists('osc_is_current_page')){
  function osc_is_current_page($location, $section) {
    if( osc_get_osclass_location() === $location && osc_get_osclass_section() === $section ) {
      return true;
    }

    return false;
  }
}


// CREATE URL FOR THEME AJAX REQUESTS
function zara_ajax_url() {
  $url = osc_contact_url();

  if (osc_rewrite_enabled()) {
    $url .= '?ajaxRequest=1';
  } else {
    $url .= '&ajaxRequest=1';
  }

  return $url;
}


// COUNT PHONE CLICKS ON ITEM
function starter_phone_clicks( $item_id ) {
  if( $item_id <> '' ) {
    $db_prefix = DB_TABLE_PREFIX;

    $query = "SELECT sum(coalesce(i_num_phone_clicks, 0)) as phone_clicks FROM {$db_prefix}t_item_stats_starter s WHERE fk_i_item_id = " . $item_id . ";";
    $result = ItemStats::newInstance()->dao->query( $query );
    if( !$result ) { 
      $prepare = array();
      return '0';
    } else {
      $prepare = $result->row();

      if($prepare['phone_clicks'] <> '') {
        return $prepare['phone_clicks'];
      } else {
        return '0';
      }
    }
  }
}


// NO CAPTCHA RECAPTCHA CHECK
function starter_show_recaptcha( $section = '' ){
  if(function_exists('anr_get_option')) {
    if(anr_get_option('site_key') <> '') {
      osc_run_hook("anr_captcha_form_field");
    }
  } else {
    if(osc_recaptcha_public_key() <> '') {
      if( ((osc_is_publish_page() || osc_is_edit_page()) && osc_recaptcha_items_enabled()) || (!osc_is_publish_page() && !osc_is_edit_page()) ) {
        osc_show_recaptcha($section);
      }
    }
  }
}

if(!function_exists('osc_contact_attachment')) {
  function osc_contact_attachment() {
    return false;
  }
}

if(!function_exists('osc_user_dashboard_url')) {
  function osc_user_dashboard_url() {
    return osc_user_list_items_url();
  }
}

if(!function_exists('osc_get_osclass_section')) {
  function osc_get_osclass_section() {
    return isset($_GET['section']) ? $_GET['section'] : '';
  }
}

if(!function_exists('mb_get_current_user_locale')) {
  function mb_get_current_user_locale() {
    $code = osc_locale_code();
    return array('s_short_name' => strtoupper(substr($code, 0, 2)), 'pk_c_code' => $code);
  }
}


// SHOW BANNER
function starter_banner( $location ) {
  $html = '';

  if(osc_get_preference('theme_adsense', 'starter_theme') == 1) {
    if( starter_is_demo() ) {
      $class = ' is-demo';
    } else {
      $class = '';
    }

    if(osc_get_preference('banner_' . $location, 'starter_theme') == '') {
      $blank = ' blank';
    } else {
      $blank = '';
    }

    if( starter_is_demo() && osc_get_preference('banner_' . $location, 'starter_theme') == '' ) {
      $title = ' title="' . __('You can define your own banner code from theme settings', 'starter') . '"';
    } else {
      $title = '';
    }

    $html .= '<div class="banner-theme banner-' . $location . ' not767' . $class . $blank . '"' . $title . '><div>';
    $html .= osc_get_preference('banner_' . $location, 'starter_theme');

    if( starter_is_demo() && osc_get_preference('banner_' . $location, 'starter_theme') == '' ) {
      $html .= __('Banner space', 'starter') . ': <u>' . $location . '</u>';
    }

    $html .= '</div></div>';

    return $html;
  } else {
    return false;
  }
}


function starter_banner_list() {
  $list = array(
    array('id' => 'banner_home_top', 'position' => __('Top of home page', 'starter')),
    array('id' => 'banner_home_bottom', 'position' => __('Bottom of home page', 'starter')),
    array('id' => 'banner_search_sidebar', 'position' => __('Bottom of search sidebar', 'starter')),
    array('id' => 'banner_search_top', 'position' => __('Top of search page', 'starter')),
    array('id' => 'banner_search_bottom', 'position' => __('Bottom of search page', 'starter')),
    array('id' => 'banner_search_list', 'position' => __('On third position between search listings (list view)', 'starter')),
    array('id' => 'banner_item_top', 'position' => __('Top of item page', 'starter')),
    array('id' => 'banner_item_bottom', 'position' => __('Bottom of item page', 'starter')),
    array('id' => 'banner_item_sidebar', 'position' => __('Bottom of item sidebar', 'starter')),
    array('id' => 'banner_item_description', 'position' => __('Under item description', 'starter'))
  );

  return $list;
}


function starter_extra_fields_hide() {
  $list = trim(osc_get_preference('post_extra_exclude', 'starter_theme'));
  $array = explode(',', $list);
  $array = array_map('trim', $array);
  $array = array_filter($array);

  if(!empty($array) && count($array) > 0) {
    return $array;
  } else {
    return array();
  }
}


function starter_item_send_friend_url($item_id = -1) {
  if($item_id == -1) {
    $item_id = osc_item_id();
  }
 
  if (osc_rewrite_enabled()) {
    return osc_base_url() . osc_get_preference('rewrite_item_send_friend') . '/' . $item_id;
  } else {
    return osc_base_url(true).'?page=item&action=send_friend&id='.$item_id;
  }
}

function antique_format_price($price, $symbol = null) {
  if ($price === null) return __('Check with seller', 'starter');
  if ($price == 0) return __('Free', 'starter');
  if ($symbol === null) {
    $symbol = osc_item_currency_symbol();
    if (empty($symbol)) {
      $symbol = '$';
    }
  }
  $formatted = $price / 1000000;
  return $symbol . ' ' . number_format($formatted, 2, '.', ',');
}

function antique_premium_format_price($price, $symbol = null) {
  if ($price === null) return __('Check with seller', 'starter');
  if ($price == 0) return __('Free', 'starter');
  if ($symbol === null) {
    $symbol = osc_item_currency_symbol();
    if (empty($symbol)) {
      $symbol = '$';
    }
  }
  $formatted = $price / 1000000;
  return $symbol . ' ' . number_format($formatted, 2, '.', ',');
}

// Global Header Currency Switcher Widget
function starter_header_currency_selector() {
  $currencies = osc_get_currencies();
  if (empty($currencies)) {
    $currencies = array(
      array('pk_c_code' => 'USD', 's_description' => 'USD ($)'),
      array('pk_c_code' => 'EUR', 's_description' => 'EUR (€)'),
      array('pk_c_code' => 'GBP', 's_description' => 'GBP (£)'),
      array('pk_c_code' => 'LKR', 's_description' => 'LKR (Rs)'),
      array('pk_c_code' => 'AUD', 's_description' => 'AUD ($)'),
      array('pk_c_code' => 'CAD', 's_description' => 'CAD ($)'),
      array('pk_c_code' => 'AED', 's_description' => 'AED'),
    );
  }

  $selected = Params::getParam('currency');
  if (empty($selected) && function_exists('starter_get_session')) {
    $selected = starter_get_session('currency');
  }
  if (empty($selected)) {
    $selected = 'USD';
  }

  $html  = '<div class="header-currency-selector not767" style="position:relative;display:inline-block;margin-right:8px;">';
  $html .= '<button type="button" class="le-btn currency-toggle" onclick="toggleCurrencyDropdown(event)" style="display:flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;color:#0f172a;cursor:pointer;">';
  $html .= '<i class="fa fa-money" style="color:var(--gold-600);"></i> <span>' . osc_esc_html($selected) . '</span> <i class="fa fa-angle-down"></i>';
  $html .= '</button>';
  $html .= '<ul class="currency-dropdown-menu" id="currency-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;margin-top:6px;background:#ffffff;border:1px solid #cbd5e1;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.15);min-width:120px;z-index:999;padding:6px 0;list-style:none;">';

  foreach ($currencies as $c) {
    $code = is_array($c) ? $c['pk_c_code'] : $c;
    $label = is_array($c) && isset($c['s_description']) ? $c['s_description'] : $code;
    $url = osc_base_url(true) . '?' . http_build_query(array_merge($_GET, array('currency' => $code)));
    $html .= '<li style="margin:0;"><a href="' . osc_esc_html($url) . '" style="display:block;padding:8px 16px;font-size:13px;color:#334155;text-decoration:none;font-weight:' . ($code == $selected ? '700' : '500') . ';">' . osc_esc_html($label) . '</a></li>';
  }

  $html .= '</ul>';
  $html .= '</div>';
  $html .= '<script>function toggleCurrencyDropdown(e){e.stopPropagation();var m=document.getElementById("currency-dropdown-menu");if(m)m.style.display=(m.style.display==="none"||!m.style.display)?"block":"none";}window.addEventListener("click",function(){var m=document.getElementById("currency-dropdown-menu");if(m)m.style.display="none";});</script>';

  return $html;
}
?>