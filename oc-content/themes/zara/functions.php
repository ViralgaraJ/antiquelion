<?php
define('ZARA_THEME_VERSION', '1.8.0');
define('USER_MENU_ICONS', 0);
define('THEME_COMPATIBLE_WITH_OSCLASS_HOOKS', 820);     // Compatibility with new hooks up to version



function zara_theme_info() {
  return array(
    'name' => 'Zara Premium Osclass Theme',
    'version' => '1.8.0',
    'description' => 'Lightweight theme for osclass with top category navigation',
    'author_name' => 'MB Themes',
    'author_url' => 'https://osclasspoint.com',
    'support_uri' => 'https://forums.osclasspoint.com/zara-osclass-responsive-theme/',
    'locations' => array('header', 'footer')
  );
}



// RTL LANGUAGE SUPPORT
function zara_is_rtl() {
  $current_lang = strtolower(osc_current_user_locale());
  $locale = osc_get_current_user_locale();
  
  if(isset($locale['b_rtl']) && $locale['b_rtl'] == 1) {
    return true;
  } else if(in_array(osc_current_user_locale(), zara_rtl_languages())) {
    return true;
  } else {
    return false;
  }
}

// GET DIRECTION STRING
function zara_language_dir() {
  return zara_is_rtl() ? 'rtl' : 'ltr';
}

// LIST ALL RTL LANGUAGES/LOCALES FOR OLDER OSCLASS VERSIONS
function zara_rtl_languages() {
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
  mb_drop_cookie('zara-sCategory');
  //mb_drop_cookie('zara-sPattern');
  mb_drop_cookie('zara-sPriceMin');
  mb_drop_cookie('zara-sPriceMax');
}

if(Params::getParam('clearCookieLocation') == 'done') {
  mb_drop_cookie('zara-sCountry');
  mb_drop_cookie('zara-sRegion');
  mb_drop_cookie('zara-sCity');
  mb_drop_cookie('zara-sLocator');
}


// FIND ROOT CATEGORY OF SELECTED
function zara_category_root( $category_id ) {
  $category = Category::newInstance()->findRootCategory( $category_id );
  return $category;
}


// CHECK IF THEME IS DEMO
function zara_is_demo() {
  if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}

// CREATE ITEM (in loop)
function zara_draw_item($c = NULL, $view = 'gallery', $premium = false, $class = false) {
  $filename = 'loop-single';

  if($premium){
    $filename .='-premium';
  }

  require WebThemes::newInstance()->getCurrentThemePath() . $filename . '.php';
}



// RANDOM LATEST ITEMS ON HOME PAGE
function zara_random_items($numItems = 10, $category = array(), $withPicture = false) {
  $max_items = osc_get_preference('maxLatestItems@home', 'osclass');

  if($max_items == '' or $max_items == 0) {
    $max_items = 24;
  }

  $numItems = $max_items;

  $withPicture = osc_get_preference('latest_picture', 'zara_theme');
  $randomOrder = osc_get_preference('latest_random', 'zara_theme');
  $premiums = osc_get_preference('latest_premium', 'zara_theme');
  $category = osc_get_preference('latest_category', 'zara_theme');



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

  
  //if($withPicture) {
  //  $randSearch->dao->from(sprintf('%st_item_resource', DB_TABLE_PREFIX));
  //  $randSearch->dao->where(sprintf("%st_item_resource.s_content_type LIKE '%%image%%' AND %st_item.pk_i_id = %st_item_resource.fk_i_item_id", DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX));
  //}

 

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


function zara_manage_cookies() { 
  if(Params::getParam('page') == 'search') { $reset = true; } else { $reset = false; }
  if($reset) {
    if(Params::getParam('sCountry') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') {
      mb_set_cookie('zara-sCountry', Params::getParam('sCountry')); 
      mb_set_cookie('zara-sRegion', ''); 
      mb_set_cookie('zara-sCity', ''); 
    }

    if(Params::getParam('sRegion') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') {
      if(is_numeric(Params::getParam('sRegion'))) {
        $reg = Region::newInstance()->findByPrimaryKey(Params::getParam('sRegion'));
      
        mb_set_cookie('zara-sCountry', strtoupper($reg['fk_c_country_code'])); 
        mb_set_cookie('zara-sRegion', osc_location_native_name_selector($reg, 's_name')); 
        mb_set_cookie('zara-sCity', ''); 
      } else {
        mb_set_cookie('zara-sRegion', Params::getParam('sRegion')); 
        mb_set_cookie('zara-sCity', ''); 
      }
    }

    if(Params::getParam('sCity') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') {
      if(is_numeric(Params::getParam('sCity'))) {
        $city = City::newInstance()->findByPrimaryKey(Params::getParam('sCity'));
        $reg = Region::newInstance()->findByPrimaryKey($city['fk_i_region_id']);
        
        mb_set_cookie('zara-sCountry', strtoupper($city['fk_c_country_code'])); 
        mb_set_cookie('zara-sRegion', osc_location_native_name_selector($reg, 's_name')); 
        mb_set_cookie('zara-sCity', osc_location_native_name_selector($city, 's_name')); 
      } else {
        mb_set_cookie('zara-sCity', Params::getParam('sCity')); 
      }
    }


    if(Params::getParam('sCategory') <> '' and Params::getParam('sCategory') <> 0 or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sCategory', Params::getParam('sCategory')); }
    if(Params::getParam('sCategory') == 0 and osc_is_search_page()) { mb_set_cookie('zara-sCategory', ''); }
    //if(Params::getParam('sPattern') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sPattern', Params::getParam('sPattern')); }
    //if(Params::getParam('sPriceMin') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sPriceMin', Params::getParam('sPriceMin')); }
    //if(Params::getParam('sPriceMax') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sPriceMax', Params::getParam('sPriceMax')); }
    if(Params::getParam('sLocator') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sLocator', Params::getParam('sLocator')); }
    if(Params::getParam('sCompany') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done' or isset($_GET['sCompany'])) { mb_set_cookie('zara-sCompany', Params::getParam('sCompany')); }
    if(Params::getParam('sShowAs') <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sShowAs', Params::getParam('sShowAs')); }
  }

  $cat = osc_search_category_id();
  $cat = isset($cat[0]) ? $cat[0] : '';

  $reg = osc_search_region();
  $cit = osc_search_city();

  if($cat <> '' and $cat <> 0 or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sCategory', $cat); }
  if($reg <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sRegion', $reg); }
  if($cit <> '' or Params::getParam('cookie-action') == 'done' or Params::getParam('cookie-action-side') == 'done') { mb_set_cookie('zara-sCity', $cit); }

  Params::setParam('sCountry', mb_get_cookie('zara-sCountry'));
  Params::setParam('sRegion', mb_get_cookie('zara-sRegion'));
  Params::setParam('sCity', mb_get_cookie('zara-sCity'));
  Params::setParam('sCategory', mb_get_cookie('zara-sCategory'));
  //Params::setParam('sPattern', mb_get_cookie('zara-sPattern'));
  //Params::setParam('sPriceMin', mb_get_cookie('zara-sPriceMin'));
  //Params::setParam('sPriceMax', mb_get_cookie('zara-sPriceMax'));
  Params::setParam('sLocator', mb_get_cookie('zara-sLocator'));
  Params::setParam('sCompany', mb_get_cookie('zara-sCompany'));
  Params::setParam('sShowAs', mb_get_cookie('zara-sShowAs'));
}


// LOCATION FORMATER
function zara_location_format($country = null, $region = null, $city = null) { 
  if($country <> '') {
    if(strlen($country) == 2) {
      $country_full = Country::newInstance()->findByCode($country);
    } else {
      $country_full = Country::newInstance()->findByName($country);
    }

    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'zara') . ' ' . $region . (osc_location_native_name_selector($country_full, 's_name') <> '' ? ' (' . osc_location_native_name_selector($country_full, 's_name') . ')' : '');
      } else {
        return $region . ' (' . osc_location_native_name_selector($country_full, 's_name') . ')';
      }
    } else { 
      if($city <> '') {
        return $city . ' ' . __('in', 'zara') . ' ' . osc_location_native_name_selector($country_full, 's_name');
      } else {
        return osc_location_native_name_selector($country_full, 's_name');
      }
    }
  } else {
    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'zara') . ' ' . $region;
      } else {
        return $region;
      }
    } else { 
      if($city <> '') {
        return $city;
      } else {
        return __('Location not entered', 'zara');
      }
    }
  }
}

// Add All / Private /Company type to search page
function mb_filter_user_type() {
  if(Params::getParam('sCompany') <> '' and Params::getParam('sCompany') <> null) {
    Search::newInstance()->addJoinTable( 'pk_i_id', DB_TABLE_PREFIX.'t_user', DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id', 'LEFT OUTER' ) ; // Mod

    if(Params::getParam('sCompany') == 1) {
      Search::newInstance()->addConditions(sprintf("%st_user.b_company = 1", DB_TABLE_PREFIX));
    } else {
      Search::newInstance()->addConditions(sprintf("coalesce(%st_user.b_company, 0) <> 1", DB_TABLE_PREFIX, DB_TABLE_PREFIX));
    }
  }
}

osc_add_hook('search_conditions', 'mb_filter_user_type');


// Radius search compatibility
if(!function_exists('radius_installed')) {function radius_installed() {return '';}}


function zara_search_params() {
 return array(
   'sCategory' => Params::getParam('sCategory'),
   'sCountry' => Params::getParam('sCountry'),
   'sRegion' => Params::getParam('sRegion'),
   'sCity' => Params::getParam('sCity'),
   'sPriceMin' => Params::getParam('sPriceMin'),
   'sPriceMin' => Params::getParam('sPriceMax'),
   'sCompany' => Params::getParam('sCompany'),
   'sShowAs' => Params::getParam('sShowAs')
  );
}

function zara_max_price($cat_id = null, $country_code = null, $region_id = null, $city_id = null) {
  // Search by all parameters
  $allSearch = new Search();
  $allSearch->addCategory($cat_id);
  $allSearch->addCountry($country_code);
  $allSearch->addRegion($region_id);
  $allSearch->addCity($city_id);
  $allSearch->order('i_price', 'DESC');
  $allSearch->limit(0, 1);

  $result = $allSearch->doSearch();
  $result = @$result[0];

  $max_price = isset($result['i_price']) ? $result['i_price'] : 0;


  // FOLLOWING BLOCK LOOKS FOR MAX-PRICE IF IT IS 0
  // City is set, find max price by Region
  if($max_price <= 0 && isset($city_id) && $city_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->addCountry($country_code);
    $regSearch->addRegion($region_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = @$result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Region is set, find max price by Country
  if($max_price <= 0 && isset($region_id) && $region_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->addCountry($country_code);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = @$result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Country is set, find max price WorldWide
  if($max_price <= 0 && isset($country_code) && $country_code <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = @$result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Category is set, find max price in all Categories
  if($max_price <= 0 && isset($region_id) && $region_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = @$result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // If max_price is still 0, set it to 1 to avoid slider defect
  if($max_price <= 0) {
    $max_price = 1000000;
  }


  return array(
    'max_price' => $max_price/1000000,
    'max_currency' => osc_get_preference('def_cur', 'zara_theme')
  );
}


// ZARA CONFIGURATOR COMPATIBILITY
function zara_current( $name ) {
  if( function_exists('zc_current') ) {
    return zc_current( $name );
  } else {
    return 1;
  }
}


// Drag & drop image uploader
if(modern_is_fineuploader() and osc_get_osclass_section() == 'item_add' or osc_get_osclass_section() == 'item_edit') {
  if(!OC_ADMIN) {
    osc_enqueue_style('fine-uploader-css', osc_assets_url('js/fineuploader/fineuploader.css'));
  }
  osc_enqueue_script('jquery-fineuploader');
}

function modern_is_fineuploader() {
  if(class_exists('Scripts')) {
    return Scripts::newInstance()->registered['jquery-fineuploader'] && method_exists('ItemForm', 'ajax_photos');
  }
}

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


if(!function_exists('osc_count_countries')) {
  function osc_count_countries() {
    if ( !View::newInstance()->_exists('contries') ) {
      View::newInstance()->_exportVariableToView('countries', Search::newInstance()->listCountries( ">=", "country_name ASC" ) );
    }
    return View::newInstance()->_count('countries');
  }
}


function mb_subcat_list($categories) {
  foreach($categories as $c) {
    echo '<h3>';
      echo '<a href="#" class="single-subcat" id="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</a>';

      if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
        echo '<div class="icon-add-next"></div>';
        echo '<div class="sub" style="display:none">';
          mb_subcat_list($c['categories']);
        echo '</div>';
      }
    echo '</h3>';
  }     
}

/* ------------ New Category Select ----------------------*/
function mb_category_select($categories, $c_cat, $default_item = null, $name = "sCategory") {
  $is_parent = '';
  echo '<input type="hidden" id="sCategory" name="sCategory" value="' . $c_cat . '" />';
  echo '<div id="uniform-sCategory">';

  if($c_cat <> 0 and $c_cat <> '') {
    $def = Category::newInstance()->findByPrimaryKey($c_cat);
    $def = isset($def['s_name']) ? $def['s_name'] : $default_item;
  } else {
    $def = $default_item;
  }

  echo '<span>' . $def . '</span>';

  echo '<div id="inc-cat-box">';
  echo '<div class="current-cat"><i class="fa fa-hand-o-right"></i>&nbsp;' . __('Select category you would like to browse', 'zara') . '</div>';

  echo '<ul id="inc-cat-list">';
  echo '<li class="bold" rel="">' . __('All categories', 'zara') . '</li>';
  //if(isset($default_item)) {
  //  echo '<option value="">' . $default_item . '</option>';
  //}

  $found_parent = false;

  foreach($categories as $c) {
    echo '<li rel="' . $c['pk_i_id'] . '"' . ( ($c_cat == $c['pk_i_id']) ? 'class="active"' : '' ) . '>' . $c['s_name'] . '</li>';
    if(isset($c['categories']) && is_array($c['categories']) && !$found_parent) {
      $a = mb_subcategory_select($c['categories'], $c_cat, $default_item, 1, $is_parent, $c['pk_i_id']);

      // If found selected category, whole subcategory tree is added to select
      if($a[1] or $c_cat == $c['pk_i_id']) { echo $a[0]; }
    }    
  }

  echo '</ul>';
  echo '</div>';
  echo '</div>';
}

function mb_subcategory_select($categories, $c_cat = 0, $default_item = null, $deep = 0, $is_parent = 0, $parent = 0) {
  $help_text = "";
  $deep_string = "";
  for($var = 0;$var<$deep;$var++) {
    $deep_string .= '&nbsp;&nbsp;';
  }
  $deep_string = $deep_string . '-&nbsp;';
  $deep++;



  if($is_parent < 2) { // only show subcategories in next level, not more
    if($is_parent == 1) {$is_parent = 2;}
    $found_parent = false;
    foreach($categories as $c) {
      if($c_cat == $c['pk_i_id']) { 
        $is_parent = 1;
        $found_parent = true; 
      }

      $help_text .= '<li rel="' . $c['pk_i_id'] . '"' . ( ($c_cat == $c['pk_i_id']) ? 'class="active"' : '' ) . '>' . $deep_string . $c['s_name'] . '</li>';

      if(isset($c['categories']) && is_array($c['categories'])) {
        $a = mb_subcategory_select($c['categories'], $c_cat, $default_item, $deep, $is_parent, $c['pk_i_id']);
        $help_text .= $a[0];
        if($a[1]) {$found_parent = true; }
      }
    }

    if($found_parent or $parent == $c_cat) {} else {$help_text = '';}
  }

  return array($help_text, $found_parent);
}


function mb_categories_select($name = 'sCategory', $category = null, $default_str = null) {
  if($default_str == null) { $default_str = __('Select a category', 'zara'); }
  mb_category_select(Category::newInstance()->toTree(), $category, $default_str, $name);
}

/* ----------------- End New Category Select --------------------- */



function mb_get_current_user_locale() {
  return OSCLocale::newInstance()->findByPrimaryKey(osc_current_user_locale());
}

function theme_zara_actions_admin() {
  if( Params::getParam('file') == 'oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php' ) {
    if( Params::getParam('donation') == 'successful' ) {
      osc_set_preference('donation', '1', 'zara_theme');
      osc_reset_preferences();
    }
  }



if( Params::getParam('zara_general') == 'done' ) {
  $cat_icons = Params::getParam('cat_icons');
  $footerLink  = Params::getParam('footer_link');
  $defaultLogo = Params::getParam('default_logo');
  $image_upload = Params::getParam('image_upload');
  $drop_cat = Params::getParam('drop_cat');
  $def_cur = Params::getParam('def_cur');
  $def_view = Params::getParam('def_view');
  $format_sep = Params::getParam('format_sep');
  $format_cur = Params::getParam('format_cur');
  $latest_picture = Params::getParam('latest_picture');
  $latest_random = Params::getParam('latest_random');
  $latest_premium = Params::getParam('latest_premium');
  $item_pager = Params::getParam('item_pager');
  $remove_footer_link = Params::getParam('remove_footer_link');

  osc_set_preference('link_remover_order_id', Params::getParam('link_remover_order_id'), 'zara_theme');
  osc_set_preference('remove_footer_link', ($remove_footer_link ? '1' : '0'), 'zara_theme');
  osc_set_preference('phone', Params::getParam('phone'), 'zara_theme');
  osc_set_preference('date_format', Params::getParam('date_format'), 'zara_theme');
  osc_set_preference('cat_icons', ($cat_icons ? '1' : '0'), 'zara_theme');
  osc_set_preference('footer_link', ($footerLink ? '1' : '0'), 'zara_theme');
  osc_set_preference('default_logo', ($defaultLogo ? '1' : '0'), 'zara_theme');
  osc_set_preference('image_upload', ($image_upload ? '1' : '0'), 'zara_theme');
  osc_set_preference('latest_picture', ($latest_picture ? '1' : '0'), 'zara_theme');
  osc_set_preference('latest_random', ($latest_random ? '1' : '0'), 'zara_theme');
  osc_set_preference('latest_premium', ($latest_premium ? '1' : '0'), 'zara_theme');
  osc_set_preference('latest_category', Params::getParam('latest_category'), 'zara_theme');
  osc_set_preference('item_pager', ($item_pager ? '1' : '0'), 'zara_theme');
  osc_set_preference('drop_cat', ($drop_cat ? '1' : '0'), 'zara_theme');
  osc_set_preference('def_cur', Params::getParam('def_cur'), 'zara_theme');
  osc_set_preference('def_view', Params::getParam('def_view'), 'zara_theme');
  osc_set_preference('format_sep', Params::getParam('format_sep'), 'zara_theme');
  osc_set_preference('format_cur', Params::getParam('format_cur'), 'zara_theme');

  osc_set_preference('website_name', Params::getParam('website_name'), 'zara_theme');
  osc_set_preference('footer_email', Params::getParam('footer_email'), 'zara_theme');

  osc_add_flash_ok_message(__('Theme settings updated correctly', 'zara'), 'admin');
  header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php')); exit;
}



if( Params::getParam('zara_banner') == 'done' ) {
  $theme_adsense = Params::getParam('theme_adsense');

  osc_set_preference('theme_adsense', ($theme_adsense ? '1' : '0'), 'zara_theme');

  foreach(zara_banner_list() as $b) {
    osc_set_preference($b['id'], stripslashes(Params::getParam($b['id'], false, false)), 'zara_theme');
  }

  osc_add_flash_ok_message(__('Banner settings updated correctly', 'zara'), 'admin');
  header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php')); exit;
}


switch( Params::getParam('action_specific') ) {
  case('upload_logo'):
    $package = Params::getFiles('logo');
    if( $package['error'] == UPLOAD_ERR_OK ) {
      if( move_uploaded_file($package['tmp_name'], WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
        osc_add_flash_ok_message(__('The logo image has been uploaded correctly', 'zara'), 'admin');
      } else {
        osc_add_flash_error_message(__("An error has occurred, please try again", 'zara'), 'admin');
      }
    } else {
      osc_add_flash_error_message(__("An error has occurred, please try again", 'zara'), 'admin');
    }
    header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php')); exit;
    break;

  case('remove'):
    if(file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
      @unlink( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" );
      osc_add_flash_ok_message(__('The logo image has been removed', 'zara'), 'admin');
    } else {
      osc_add_flash_error_message(__("Image not found", 'zara'), 'admin');
    }
    header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php')); exit;
    break;
  }
}

osc_add_hook('init_admin', 'theme_zara_actions_admin');
//osc_admin_menu_appearance(__('Header logo', 'zara'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php'), 'header_zara');
//osc_admin_menu_appearance(__('Theme settings', 'zara'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'), 'settings_zara');
AdminMenu::newInstance()->add_menu(__('Theme Setting', 'zara'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php'), 'zara_menu', null, null, 1);
AdminMenu::newInstance()->add_submenu_divider( 'zara_menu', __('Theme Settings', 'zara'), 'zara_submenu');
AdminMenu::newInstance()->add_submenu( 'zara_menu', __('Header logo', 'zara'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/header.php'), 'header_zara', 'administrator');
AdminMenu::newInstance()->add_submenu( 'zara_menu', __('Theme settings', 'zara'), osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'), 'settings_zara');

if( !function_exists('logo_header') ) {
  function logo_header() {
    $html = '<img border="0" alt="' . osc_esc_html(osc_page_title()) . '" src="' . osc_current_web_theme_url('images/logo.jpg') . '" />';
    if( file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/logo.jpg" ) ) {
      return $html;
    } else if( osc_get_preference('default_logo', 'zara_theme') && (file_exists( WebThemes::newInstance()->getCurrentThemePath() . "images/default-logo.jpg")) ) {
      return '<img border="0" alt="' . osc_esc_html(osc_page_title()) . '" src="' . osc_current_web_theme_url('images/default-logo.jpg') . '" />';
    } else {
      return osc_page_title();
    }
  }
}


// FIX ADMIN MENU LIST WITH THEME OPTIONS
function zara_admin_menu_fix(){
  echo '<style>' . PHP_EOL;
  echo 'body.compact #zara_menu .ico-zara_menu {bottom:-6px!important;width:50px!important;height:50px!important;margin:0!important;background:#fff url(https://www.zara.mb-themes.com/oc-content/themes/zara/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo 'body.compact #zara_menu .ico-zara_menu:hover {background-color:rgba(255,255,255,0.95)!important;}' . PHP_EOL;
  echo 'body.compact #menu_zara_menu > h3 {bottom:0!important;}' . PHP_EOL;
  echo 'body.compact #menu_zara_menu > ul {border-top-left-radius:0px!important;margin-top:1px!important;}' . PHP_EOL;
  echo 'body.compact #menu_zara_menu.current:after {content:"";display:block;width:6px;height:6px;border-radius:10px;box-shadow:1px 1px 3px rgba(0,0,0,0.1);position:absolute;left:3px;bottom:3px;background:#03a9f4}' . PHP_EOL;
  echo 'body:not(.compact) #zara_menu .ico-zara_menu {background:transparent url(https://www.zara.mb-themes.com/oc-content/themes/zara/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo '</style>' . PHP_EOL;
}

osc_add_hook('admin_header', 'zara_admin_menu_fix');




function zara_location_selector() {
  //View::newInstance()->_exportVariableToView('list_regions', Search::newInstance()->listRegions('%%%%', '>=', 'region_name ASC') ) ;
  //View::newInstance()->_exportVariableToView('list_countries', Search::newInstance()->listCountries('%%%%', '>=', 'country_name ASC') ) ;

  $curr_country = '';
  $curr_reg = osc_search_region();
  $curr_city = osc_search_city();
  
  if(function_exists('osc_search_country')) { $curr_country = osc_search_country(); } 
  if($curr_country == '') { $curr_country = $_GET['sCountry']; }
  if($curr_country == '') { $curr_country = $_GET['country']; }
  if($curr_country == '') { $curr_country = Params::getParam('sCountry'); }
  if($curr_reg == '') { $curr_reg = $_GET['sRegion']; }
  if($curr_reg == '') { $curr_reg = Params::getParam('sRegion'); }
  if($curr_city == '') { $curr_city = $_GET['sCity']; }
  if($curr_city == '') { $curr_city = Params::getParam('sCity'); }

  // Detect user location, if was not set already or does not exist in installation, nothing happen
    mb_set_cookie('zara-userLocation', '0');

  if(mb_get_cookie('zara-userLocation') <> 1) {
    mb_set_cookie('zara-userLocation', '1');
    $user_loc = zara_user_location();

    if($curr_country == '' and $curr_reg == '' and $curr_city == '') {
      $country = Country::newInstance()->findByCode($user_loc['country_code']);
      $region = Region::newInstance()->findByName($user_loc['region']);
      $city = City::newInstance()->findByName($user_loc['city']);

      if($country['pk_c_code'] <> '') {
        $curr_country = $user_loc['country_code'];
        mb_set_cookie('zara-sCountry', $curr_country);
      }

      if($region['pk_i_id'] <> '') {
        $curr_reg = $user_loc['region'];
        mb_set_cookie('zara-sRegion', $curr_reg);
      }

      if($city['pk_i_id'] <> '') {
        $curr_city = $user_loc['city'];
        mb_set_cookie('zara-sCity', $curr_city);
      }
    }
  }

  if(osc_count_countries() > 1) {
    $del = '&nbsp;&nbsp;&nbsp;';
    $show_country = true;
  } else {
    $del = '';
    $show_country = false;
  }

  if(strlen($curr_country) > 2) { 
    $cc = Country::newInstance()->findByName($curr_country);
    $curr_country = $cc['pk_c_code'];
  }

  echo '<div id="uniform-Locator">';
  echo '<div class="cover"></div>';
  echo '<span>' . __('Location', 'zara') . '</span>';

  echo '<div id="loc-box">';

  // My current location
  echo '<div class="current-loc">' . __('Your current location is:', 'zara') . '</div>';
  echo '<div class="h-my-loc">';
  echo '<div class="font">';

  if(Params::getParam('sCountry') == '' and Params::getParam('sRegion') == '' and Params::getParam('sCity') == '') {
    _e('Location not saved', 'zara');
  } else {
    $loc = array_filter(array(Params::getParam('sCountry'), Params::getParam('sRegion'), Params::getParam('sCity')));
    $loc = trim(implode(', ', $loc));
    echo $loc;
    echo '<i class="fa fa-close clear-cookie-location" title="' . osc_esc_html(__('Clear location', 'zara')) . '"></i>';
  }

  echo '</div>';
  echo '</div>';
  // End my location block

  echo '<div class="choose"><i class="fa fa-hand-o-right"></i>' . __('Select location', 'zara') . '</div>';


  echo '<ul id="loc-list" name="Locator" data-placeholder="' . __('Location', 'zara') . '"  id="Locator">';

  if($show_country) {
    while(mb_has_list_countries()) {
      if($show_country) {
        echo '<li rel="' . osc_list_country_name() . '" class="country-level' . ( (osc_list_country_code() == $curr_country or osc_list_country_name() == $curr_country) ? ' active' : '' ) . '">' . osc_list_country_name() . '</li>';
      }

      if(osc_list_country_code() == $curr_country) { 
        while(mb_has_list_regions($curr_country) ) { 
          echo '<li rel="//' . osc_list_region_name() . '" class="region-level' . ( (osc_list_region_name() == $curr_reg ) ? ' active' : '' ) . '">' . $del . osc_list_region_name() . '</li>';

          if(osc_list_region_name() == $curr_reg) { 
            $myreg_id = '';
            if( $curr_reg != '' ) {
              $v_reg_id  = Region::newInstance()->findByName($curr_reg);
              if(isset($v_reg_id['pk_i_id'])) {
                $myreg_id = $v_reg_id['pk_i_id'];
              }
            }

            while(mb_has_list_cities($myreg_id)) { 
              echo '<li rel="--' . osc_list_city_name() . '" class="city-level' . ( (osc_list_city_name() == $curr_city ) ? ' active' : '' ) . '">&nbsp;&nbsp;&nbsp;' . $del . '- ' . osc_list_city_name() . '</li>';
            }
          } 
        } // End region loop
      }
    } // End country loop 

  } else {

    while(mb_has_list_regions() ) { 
      echo '<li rel="//' . osc_list_region_name() . '" class="region-level' . ( (osc_list_region_name() == $curr_reg ) ? ' active' : '' ) . '">' . $del . osc_list_region_name() . '</li>';

      if(osc_list_region_name() == $curr_reg) { 
        $myreg_id = '';
        if( $curr_reg != '' ) {
          $v_reg_id  = Region::newInstance()->findByName($curr_reg);
          if(isset($v_reg_id['pk_i_id'])) {
            $myreg_id = $v_reg_id['pk_i_id'];
          }
        }
 
        while(mb_has_list_cities($myreg_id)) { 
          echo '<li rel="--' . osc_list_city_name() . '" class="city-level' . ( (osc_list_city_name() == $curr_city ) ? ' active' : '' ) . '">&nbsp;&nbsp;&nbsp;' . $del . '- ' . osc_list_city_name() . '</li>';
        }
      }
    }
  }

  echo '</ul>';
  echo '</div>';
  echo '</div>';

  View::newInstance()->_erase('cities');
  View::newInstance()->_erase('regions');
  View::newInstance()->_erase('countries');
}

// install update options
if( !function_exists('zara_theme_install') ) {
  $themeInfo = zara_theme_info();

  function zara_theme_install() {
    osc_set_preference('version', ZARA_THEME_VERSION, 'zara_theme');
    osc_set_preference('phone', __('+1 (800) 228-5651', 'zara'), 'zara_theme');
    osc_set_preference('date_format', 'mm/dd', 'zara_theme');
    osc_set_preference('cat_icons', '1', 'zara_theme');
    osc_set_preference('footer_link', '1', 'zara_theme');
    osc_set_preference('donation', '0', 'zara_theme');
    osc_set_preference('default_logo', '1', 'zara_theme');
    osc_set_preference('image_upload', '1', 'zara_theme');
    osc_set_preference('theme_adsense', '1', 'zara_theme');
    osc_set_preference('def_cur', '', 'zara_theme');
    osc_set_preference('def_view', '0', 'zara_theme');
    osc_set_preference('format_sep', '', 'zara_theme');
    osc_set_preference('format_cur', '0', 'zara_theme');
    osc_set_preference('footer_email', '', 'zara_theme');
    osc_set_preference('drop_cat', '1', 'zara_theme');
    osc_set_preference('banner_home', '', 'zara_theme');
    osc_set_preference('banner_search', '', 'zara_theme');
    osc_set_preference('banner_item', '', 'zara_theme');
    osc_set_preference('website_name', 'myWebsite.com', 'zara_theme');
    osc_set_preference('latest_picture', '0', 'zara_theme');
    osc_set_preference('latest_random', '1', 'zara_theme');
    osc_set_preference('latest_premium', '0', 'zara_theme');
    osc_set_preference('latest_category', '', 'zara_theme');
    osc_set_preference('item_pager', '0', 'zara_theme');

    /* Banners */
    if(function_exists('zara_banner_list')) {
      foreach(zara_banner_list() as $b) {
        osc_set_preference($b['id'], '', 'zara_theme');
      }
    }

    osc_reset_preferences();

    zara_add_color_col();  // add s_color column to database if does not exists
  }
}

if(!function_exists('check_install_zara_theme')) {
  function check_install_zara_theme() {
    $current_version = osc_get_preference('version', 'zara_theme');
    //check if current version is installed or need an update<
    if( !$current_version ) {
      zara_theme_install();
    }
  }
}

check_install_zara_theme();

// New function to fix premium price format
function zara_premium_formated_price($price = null) {
  if($price == '') {
    $price = osc_premium_price();
  }

  return (string) zara_premium_format_price($price);
}

function zara_premium_format_price($price, $symbol = null) {
  if ($price === null) return osc_apply_filter ('item_price_null', __('Check with seller', 'zara') );
  if ($price == 0) return osc_apply_filter ('item_price_zero', __('Free', 'zara') );

  if($symbol==null) { $symbol = osc_premium_currency_symbol(); }

  $price = $price/1000000;

  $currencyFormat = osc_locale_currency_format();
  $currencyFormat = str_replace('{NUMBER}', number_format($price, osc_locale_num_dec(), osc_locale_dec_point(), osc_locale_thousands_sep()), $currencyFormat);
  $currencyFormat = str_replace('{CURRENCY}', $symbol, $currencyFormat);
  return osc_apply_filter('premium_price', $currencyFormat );
}


// USER MENU FIX
function zara_user_menu_fix() {
  $user = User::newInstance()->findByPrimaryKey( osc_logged_user_id() );
  View::newInstance()->_exportVariableToView('user', $user);
}

osc_add_hook('header', 'zara_user_menu_fix');


function zara_banner_list() {
  $list = array(
    array('id' => 'banner_home_top', 'position' => __('Top of home page', 'zara')),
    array('id' => 'banner_home_bottom', 'position' => __('Bottom of home page', 'zara')),
    array('id' => 'banner_search_sidebar', 'position' => __('Bottom of search sidebar', 'zara')),
    array('id' => 'banner_search_top', 'position' => __('Top of search page', 'zara')),
    array('id' => 'banner_search_bottom', 'position' => __('Bottom of search page', 'zara')),
    array('id' => 'banner_search_list', 'position' => __('On third position between search listings (list view)', 'zara')),
    array('id' => 'banner_item_top', 'position' => __('Top of item page', 'zara')),
    array('id' => 'banner_item_bottom', 'position' => __('Bottom of item page', 'zara')),
    array('id' => 'banner_item_sidebar', 'position' => __('Bottom of item sidebar', 'zara')),
    array('id' => 'banner_item_description', 'position' => __('Under item description', 'zara'))
  );

  return $list;
}


// SHOW BANNER
// SHOW BANNER
function zara_banner( $location ) {
  $html = '';

  if(osc_get_preference('theme_adsense', 'zara_theme') == 1) {
    if( zara_is_demo() ) {
      $class = ' is-demo';
    } else {
      $class = '';
    }

    if(osc_get_preference('banner_' . $location, 'zara_theme') == '') {
      $blank = ' blank';
    } else {
      $blank = '';
    }

    if( zara_is_demo() && osc_get_preference('banner_' . $location, 'zara_theme') == '' ) {
      $title = ' title="' . __('You can define your own banner code from theme settings', 'zara') . '"';
    } else {
      $title = '';
    }

    $html .= '<div class="banner-theme banner-' . $location . ' not767' . $class . $blank . '"' . $title . '><div>';
    $html .= osc_get_preference('banner_' . $location, 'zara_theme');

    if( zara_is_demo() && osc_get_preference('banner_' . $location, 'zara_theme') == '' ) {
      $html .= __('Banner space', 'zara') . ': <u>' . $location . '</u>';
    }

    $html .= '</div></div>';

    return $html;
  } else {
    return false;
  }
}


// ADD COLOR COLUMN INTO CATEGORY TABLE
function zara_add_color_col() {
  $conn = DBConnectionClass::newInstance();
  $data = $conn->getOsclassDb();
  $comm = new DBCommandClass($data);
  $db_prefix = DB_TABLE_PREFIX;

  $query = "ALTER TABLE {$db_prefix}t_category ADD s_color VARCHAR(50);";
  $result = $comm->query($query);
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
?>